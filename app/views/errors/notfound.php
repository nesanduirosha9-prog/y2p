<!-- errors/notfound.php — standalone 404 page.
     Rendered via renderPartial() by HomeController::notFound(), which
     Router::resolve() falls back to when no route matches. Complete HTML
     document on its own, not injected into a layout's $content. -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found | StaffSync</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg: #f4f7fb;
            --bg-strong: #eaf0f7;
            --card: rgba(255, 255, 255, 0.9);
            --card-border: rgba(148, 163, 184, 0.18);
            --primary: #1f4f9d;
            --primary-strong: #173c78;
            --primary-soft: #e9f0ff;
            --text: #101828;
            --muted: #667085;
            --line: #dbe3ef;
            --shadow: 0 22px 60px rgba(19, 39, 77, 0.12);
            --success: #0f766e;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at top left, rgba(31, 79, 157, 0.14), transparent 30%),
                linear-gradient(135deg, var(--bg) 0%, #edf3fb 50%, var(--bg-strong) 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text);
        }

        .page-shell {
            width: min(100%, 900px);
            padding: 32px 20px;
        }

        .error-card {
            position: relative;
            background: var(--card);
            backdrop-filter: blur(8px);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            padding: 52px 42px;
            text-align: center;
            overflow: hidden;
        }

        .error-card::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 180px;
            height: 180px;
            background: linear-gradient(135deg, rgba(31, 79, 157, 0.12), rgba(31, 79, 157, 0));
            border-radius: 50%;
            transform: translate(-35%, -35%);
        }

        .error-card::after {
            content: "";
            position: absolute;
            right: -60px;
            bottom: -70px;
            width: 220px;
            height: 220px;
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.08), rgba(31, 79, 157, 0));
            border-radius: 50%;
        }

        .error-badge {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            border-radius: 999px;
            background: var(--primary-soft);
            border: 1px solid rgba(31, 79, 157, 0.12);
            color: var(--primary-strong);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .error-code {
            position: relative;
            z-index: 1;
            margin: 28px 0 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(4.5rem, 10vw, 9rem);
            line-height: 0.9;
            letter-spacing: -0.06em;
            color: rgba(31, 79, 157, 0.12);
        }

        h1 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.15;
            letter-spacing: -0.04em;
            color: var(--text);
        }

        p {
            position: relative;
            z-index: 1;
            max-width: 620px;
            margin: 18px auto 0;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .actions {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 32px;
        }

        .primary-btn,
        .secondary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
            padding: 14px 22px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .primary-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-strong));
            color: #fff;
            box-shadow: 0 14px 28px rgba(31, 79, 157, 0.22);
        }

        .secondary-btn {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--line);
        }

        .primary-btn:hover,
        .secondary-btn:hover {
            transform: translateY(-1px);
        }

        .meta {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 22px;
            margin-top: 30px;
            color: var(--muted);
            font-size: 0.85rem;
        }

        .meta span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .meta i {
            color: var(--success);
        }

        @media (max-width: 640px) {
            .error-card {
                padding: 40px 22px;
            }

            .actions {
                flex-direction: column;
            }

            .primary-btn,
            .secondary-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <div class="error-card">
            <div class="error-badge">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Error 404
            </div>

            <div class="error-code">404</div>
            <h1>Page Not Found</h1>
            <p>
                The page you are looking for may have been moved, deleted, or never existed.
                Please return to the StaffSync portal and continue from the main dashboard.
            </p>

            <div class="actions">
                <a class="primary-btn" href="/">
                    <i class="fa-solid fa-house" style="margin-right: 10px;"></i>Back to Home
                </a>
                <a class="secondary-btn" href="/timetable">
                    <i class="fa-solid fa-arrow-right-to-bracket" style="margin-right: 10px;"></i>Open Dashboard
                </a>
            </div>

            <div class="meta">
                <span><i class="fa-solid fa-shield-heart"></i> StaffSync Portal</span>
                <span><i class="fa-solid fa-headset"></i> Need support? Contact the system administrator</span>
            </div>
        </div>
    </div>
</body>
</html>
