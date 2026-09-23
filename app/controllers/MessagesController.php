<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Request;
use app\models\NotificationModel;

// MessagesController: the chat screen at /messages, shared by academic staff
// and the timetable officer.
//
// The two roles get the same view and the same JS; they differ only in the
// seeded conversation list and in whether group chats exist at all:
//
//   academic staff    course/department groups + direct messages
//   timetable officer direct messages only, and only with the Coordinator
//                     and the In-Charge — the two people the officer actually
//                     negotiates the schedule with. There is deliberately no
//                     "new conversation" affordance for either role, so the
//                     officer cannot open a thread with anyone else.
//
// NOTE (pre-existing, unchanged): read-only render. The
// `chat_rooms`/`chat_participants`/`messages` tables (migrations 011-013)
// exist but nothing here queries them yet — both datasets below are demo
// fixtures and nothing a user sends survives a reload.
class MessagesController extends Controller
{
    // Page copy keyed by $_SESSION['role'], same shape as TimetableController.
    private const COPY = [
        'timetable_officer' => [
            'title'       => 'Messages',
            'pageTitle'   => 'Messages',
            'allowGroups' => false,
        ],
        'academic_staff' => [
            'title'       => 'Messages',
            'pageTitle'   => 'Messages',
            'allowGroups' => true,
        ],
    ];

    public function __construct()
    {
        $this->setLayout('dashboard');
    }

    public function index(Request $request)
    {
        // Guard lives on Controller now — see app/core/Controller.php.
        $denied = $this->requireRole('academic_staff', 'timetable_officer');
        if ($denied !== null) {
            return $denied;
        }

        // The guard above already limited this to the two keys in self::COPY.
        $role = $_SESSION['role'];
        $copy = self::COPY[$role];

        $conversations = $role === 'timetable_officer'
            ? self::officerConversations()
            : self::staffConversations();

        return $this->render('messages', [
            'title' => $copy['title'],
            'css_file' => ['/css/messages.css'],
            'active' => 'messages',
            'pageTitle' => $copy['pageTitle'],
            'notificationCount' => (new NotificationModel())->unreadCount($_SESSION['staff_code']),
            'conversationsData' => $conversations,
            'allowGroups' => $copy['allowGroups'],
        ]);
    }

    // Timetable officer: direct messages only, pre-created with the
    // Coordinator (MKA) and the In-Charge (DSC) from database/seeds/001_staff.sql.
    // No groups, and no third party — this list is the whole address book.
    private static function officerConversations(): array
    {
        return [
            [
                'id' => 1, 'name' => 'Mr. Kwame Addo', 'subtitle' => 'Coordinator · Computer Science',
                'avatar' => 'KA', 'color' => '#1a3a6b', 'time' => '9:20 AM',
                'preview' => 'Can we move the CS3401 lab to Thursday afternoon?',
                'unread' => 2, 'isGroup' => false,
                'members' => ['T. M. Officer (You)', 'Mr. Kwame Addo'],
                'messages' => [
                    ['text' => 'Good morning. The published Year 1 grid has CS3401 clashing with the IS2201 practical.', 'time' => '9:02 AM', 'mine' => false],
                    ['text' => 'Checking now — Lab A-201 is double-booked for that slot.', 'time' => '9:11 AM', 'mine' => true],
                    ['text' => 'Can we move the CS3401 lab to Thursday afternoon?', 'time' => '9:20 AM', 'mine' => false],
                ],
            ],
            [
                'id' => 2, 'name' => 'Dr. Sarah Chen', 'subtitle' => 'In-Charge · Computer Science',
                'avatar' => 'SC', 'color' => '#4d179a', 'time' => 'Yesterday',
                'preview' => 'Approved — please publish the revised timetable.',
                'unread' => 0, 'isGroup' => false,
                'members' => ['T. M. Officer (You)', 'Dr. Sarah Chen'],
                'messages' => [
                    ['text' => 'The Semester 1 draft is ready for your review.', 'time' => 'Yesterday · 2:40 PM', 'mine' => true],
                    ['text' => 'Looks good. One note: keep Wednesday 12–1 clear for the staff meeting.', 'time' => 'Yesterday · 3:05 PM', 'mine' => false],
                    ['text' => 'Noted — lunch break is already blocked on every day.', 'time' => 'Yesterday · 3:12 PM', 'mine' => true],
                    ['text' => 'Approved — please publish the revised timetable.', 'time' => 'Yesterday · 3:15 PM', 'mine' => false],
                ],
            ],
        ];
    }

    // Academic staff: unchanged fixture, lifted verbatim out of the view.
    private static function staffConversations(): array
    {
        return [
            [
                'id' => 1, 'name' => 'CS3401 Lab Group', 'subtitle' => 'CS3401 – Fundamentals of Computing Lab',
                'avatar' => 'CS', 'color' => '#4d179a', 'time' => '9:41 AM',
                'preview' => 'Lab report template has been updated — please use the new version.',
                'unread' => 3, 'isGroup' => true,
                'members' => ['Ms. T. Fernando (You)', 'Dr. N. Perera', 'Mr. K. Bandara'],
                'messages' => [
                    ['text' => 'Good morning everyone. Today we\'ll cover memory allocation in C.', 'time' => '8:02 AM', 'mine' => false],
                    ['text' => 'Dr. Perera, the lab report template has been updated — please check the shared drive.', 'time' => '8:45 AM', 'mine' => false],
                    ['text' => 'Got it, thanks. I\'ll distribute it at the start of class.', 'time' => '9:10 AM', 'mine' => true],
                    ['text' => 'Also, can someone confirm the projector in Lab A-201 is working?', 'time' => '9:35 AM', 'mine' => true],
                    ['text' => 'Lab report template has been updated — please use the new version.', 'time' => '9:41 AM', 'mine' => false],
                ],
            ],
            [
                'id' => 2, 'name' => 'IT2301 Practical Group', 'subtitle' => 'IT2301 – Web Technologies Practical',
                'avatar' => 'IT', 'color' => '#0f766e', 'time' => '9:12 AM',
                'preview' => "Don't forget the network config lab.",
                'unread' => 1, 'isGroup' => true,
                'members' => ['Ms. T. Fernando (You)', 'Prof. A. Silva'],
                'messages' => [
                    ['text' => 'Reminder: bring your laptops fully charged for today\'s practical.', 'time' => '8:50 AM', 'mine' => true],
                    ['text' => "Don't forget the network config lab.", 'time' => '9:12 AM', 'mine' => false],
                ],
            ],
            [
                'id' => 3, 'name' => 'Dr. Nimal Perera', 'subtitle' => 'Dr. Nimal Perera',
                'avatar' => 'NP', 'color' => '#1a3a6b', 'time' => 'Yesterday',
                'preview' => 'Please send the attendance sheet for las…',
                'unread' => 0, 'isGroup' => false,
                'members' => ['Ms. T. Fernando (You)', 'Dr. N. Perera'],
                'messages' => [
                    ['text' => 'Please send the attendance sheet for last week\'s lab.', 'time' => 'Yesterday · 4:12 PM', 'mine' => false],
                    ['text' => 'Sure, sending it over now.', 'time' => 'Yesterday · 4:20 PM', 'mine' => true],
                ],
            ],
            [
                'id' => 4, 'name' => 'Prof. Anoma Silva', 'subtitle' => 'Prof. Anoma Silva',
                'avatar' => 'AS', 'color' => '#9a3412', 'time' => 'Monday',
                'preview' => 'Thank you for the update.',
                'unread' => 0, 'isGroup' => false,
                'members' => ['Ms. T. Fernando (You)', 'Prof. A. Silva'],
                'messages' => [
                    ['text' => 'Could you cover my practical session next Tuesday?', 'time' => 'Monday · 11:00 AM', 'mine' => false],
                    ['text' => 'Yes, happy to help — I\'ll confirm the room.', 'time' => 'Monday · 11:20 AM', 'mine' => true],
                    ['text' => 'Thank you for the update.', 'time' => 'Monday · 11:25 AM', 'mine' => false],
                ],
            ],
            [
                'id' => 5, 'name' => 'Dept. Instructors Channel', 'subtitle' => 'Department of Computer Science',
                'avatar' => 'DI', 'color' => '#4338ca', 'time' => 'Monday',
                'preview' => 'Reminder: staff meeting at 3 PM tomorrow.',
                'unread' => 2, 'isGroup' => true,
                'members' => ['Ms. T. Fernando (You)', 'Dr. N. Perera', 'Prof. A. Silva', 'Mr. K. Bandara'],
                'messages' => [
                    ['text' => 'Reminder: staff meeting at 3 PM tomorrow in the conference room.', 'time' => 'Monday · 2:00 PM', 'mine' => false],
                ],
            ],
        ];
    }
}
