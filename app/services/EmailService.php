<?php

namespace app\services;

// EmailService: sends OTP emails over a raw SMTP connection to Gmail's relay
// (App Password auth) — no Composer, no external API, per this project's
// constraint. Talks SMTP directly via stream_socket_client()/fwrite()/fgets().
//
// Uses port 465 (implicit TLS): the socket is already encrypted the moment
// it connects, so unlike port 587 there's no STARTTLS command / re-EHLO
// dance to get wrong.
class EmailService
{
    private const HOST = 'smtp.gmail.com';
    private const PORT = 465;

    /**
     * Send a 6-digit OTP email. $purpose only picks the subject line — it
     * has no effect on delivery. Returns false on ANY failure (connect,
     * auth, a rejected recipient, ...) so the caller never stores/surfaces a
     * code that was never actually delivered.
     */
    public static function sendOtpEmail(string $toEmail, string $otp, string $purpose): bool
    {
        $subject = $purpose === 'password_reset' ? 'StaffSync Password Reset Code' : 'StaffSync Verification Code';
        $body = '<p>Your code is: <b>' . htmlspecialchars($otp) . '</b> (expires in 5 minutes)</p>'
            . '<p>If you didn\'t request this, ignore this email.</p>';

        return self::send($toEmail, $subject, $body);
    }

    /** Generic HTML-email transport — sendOtpEmail() and the role-handover flow both go through this. */
    public static function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        // This value is interpolated directly into raw SMTP commands and
        // headers below — a stray \r\n would let an attacker inject extra
        // headers/commands, so reject anything that isn't one clean address.
        if (preg_match('/[\r\n]/', $toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $socket = @stream_socket_client('ssl://' . self::HOST . ':' . self::PORT, $errno, $errstr, 15);
        if (!$socket) {
            return false;
        }
        stream_set_timeout($socket, 10);

        try {
            if (!self::expect($socket, '220')) return false; // greeting

            self::write($socket, 'EHLO staffsync.local');
            if (!self::expect($socket, '250')) return false;

            self::write($socket, 'AUTH LOGIN');
            if (!self::expect($socket, '334')) return false;

            self::write($socket, base64_encode(SMTP_USERNAME));
            if (!self::expect($socket, '334')) return false;

            // Gmail's UI shows App Passwords with spaces (xxxx xxxx xxxx xxxx)
            // that people commonly copy-paste as-is — strip them before use.
            self::write($socket, base64_encode(str_replace(' ', '', SMTP_APP_PASSWORD)));
            if (!self::expect($socket, '235')) return false;

            self::write($socket, 'MAIL FROM:<' . SMTP_USERNAME . '>');
            if (!self::expect($socket, '250')) return false;

            self::write($socket, 'RCPT TO:<' . $toEmail . '>');
            if (!self::expect($socket, '250', '251')) return false;

            self::write($socket, 'DATA');
            if (!self::expect($socket, '354')) return false;

            $message = "From: StaffSync <" . SMTP_USERNAME . ">\r\n"
                . "To: $toEmail\r\n"
                . "Subject: $subject\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . $htmlBody;
            // Lines starting with "." must be escaped per RFC 5321, or the
            // DATA transaction terminates early.
            $message = preg_replace('/^\./m', '..$0', $message);

            self::write($socket, $message . "\r\n.");
            if (!self::expect($socket, '250')) return false;

            self::write($socket, 'QUIT');
            return true;
        } finally {
            fclose($socket);
        }
    }

    // Reads one full (possibly multi-line) SMTP response. Gmail's replies
    // can span several lines (each non-final line has '-' at byte offset 3,
    // e.g. "250-..."; the final line has a space there) — a single fgets()
    // would leave the extra lines buffered and misread as the response to
    // the NEXT command, so this drains until the final line.
    private static function readResponse($socket): string
    {
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                return '';
            }
        } while (isset($line[3]) && $line[3] === '-');
        return $line;
    }

    private static function write($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private static function expect($socket, string ...$codes): bool
    {
        $line = self::readResponse($socket);
        return in_array(substr($line, 0, 3), $codes, true);
    }
}
