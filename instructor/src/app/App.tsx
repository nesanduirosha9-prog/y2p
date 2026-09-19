import { useState, useRef } from "react";
import {
  Calendar, BarChart2, Users, MessageSquare, Bell, Settings,
  LogOut, X, ChevronDown, Eye, EyeOff, Send, Search,
  Plus, CheckCircle, ChevronRight, MapPin,
  Clock, BookOpen, Fingerprint, Check, AlertTriangle, Info,
  Paperclip, Upload, CalendarDays, ClipboardList,
  ChevronLeft, Star, ArrowRightLeft, UserPlus, Layers,
  UserCheck, ListChecks, ChevronUp
} from "lucide-react";

// ── Types ──────────────────────────────────────────────────────────────────
type Page = "login" | "signup" | "forgot" | "dashboard";
type UserRole = "instructor" | "lecturer";

const DEMO_ACCOUNTS: Record<string, { pass: string; role: UserRole; name: string; dept: string }> = {
  "instructor@university.edu": { pass: "Instructor@123", role: "instructor", name: "Ms. Thilini Fernando", dept: "Computer Science" },
  "lecturer@university.edu":   { pass: "Lecturer@123",  role: "lecturer",   name: "Dr. Nimal Perera",    dept: "Computer Science" },
};
type DashTab =
  | "timetable"
  | "workload"
  | "requests"
  | "leave"
  | "messages"
  | "settings";
type SessionType = "lab" | "practical" | "lecture" | "assignment";

interface Session {
  id: number;
  code: string;
  name: string;
  venue: string;
  batch: string;
  day: number; // 0=Mon … 4=Fri
  start: number; // hour (8–17)
  end: number;
  type: SessionType;
  date: string;
}

interface SelectedSlot { day: number; hour: number; }
interface StaffRequest {
  id: number; courseCode: string; courseName: string; sessionType: string;
  slots: string; membersNeeded: number; supportType: string; notes: string;
  status: "pending" | "approved"; assignedMembers: string[]; submittedAt: string;
}
interface TimetableGroup {
  id: number; name: string; avatar: string;
  courseCode: string; members: string[];
  preview: string; time: string;
}

// ── Week date helpers ──────────────────────────────────────────────────────
const BASE_MONDAY = new Date(2025, 6, 14); // Mon 14 Jul 2025
const MONTH_S = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
const DAY_S   = ["Mon","Tue","Wed","Thu","Fri"];

function getWeekDates(offset: number): Date[] {
  return [0,1,2,3,4].map(i => {
    const d = new Date(BASE_MONDAY);
    d.setDate(d.getDate() + offset * 7 + i);
    return d;
  });
}
function fmtWeekRange(offset: number): string {
  const dates = getWeekDates(offset);
  const a = dates[0], b = dates[4];
  return `${a.getDate()} – ${b.getDate()} ${MONTH_S[b.getMonth()]} ${b.getFullYear()}`;
}

// Members per course code (for group creation)
const COURSE_MEMBERS: Record<string, string[]> = {
  CS3401: ["Ms. T. Fernando (You)", "Dr. N. Perera", "Mr. K. Bandara"],
  CS3402: ["Ms. T. Fernando (You)", "Dr. N. Perera"],
  CS2201: ["Ms. T. Fernando (You)", "Prof. A. Silva"],
  IT2301: ["Ms. T. Fernando (You)", "Prof. A. Silva", "Mr. J. Peris"],
  IT3201: ["Ms. T. Fernando (You)", "Prof. A. Silva"],
};

// ── Data ───────────────────────────────────────────────────────────────────
const SESSION_STYLE: Record<SessionType, { bg: string; border: string; text: string; label: string }> = {
  lab:        { bg: "#ede9fe", border: "#a684ff", text: "#4d179a", label: "LAB" },
  practical:  { bg: "#d0fae5", border: "#00d492", text: "#004f3b", label: "PRACTICAL" },
  lecture:    { bg: "#dbeafe", border: "#51a2ff", text: "#1c398e", label: "LECTURE" },
  assignment: { bg: "#fff7ed", border: "#f97316", text: "#9a3412", label: "ASSIGNMENT" },
};

const SESSIONS: Session[] = [
  { id:1, code:"CS3401", name:"Fundamentals of Computing Lab",    venue:"Lab A-201", batch:"Y1 CS", day:0, start:8,  end:10, type:"lab",       date:"Mon, 14 Jul 2025" },
  { id:2, code:"IT2301", name:"Web Technologies Practical",       venue:"Lab B-104", batch:"Y2 IT", day:0, start:14, end:16, type:"practical",  date:"Mon, 14 Jul 2025" },
  { id:3, code:"CS3402", name:"Data Structures Lab",              venue:"Lab C-201", batch:"Y2 CS", day:1, start:9,  end:11, type:"lab",       date:"Tue, 15 Jul 2025" },
  { id:4, code:"CS2201", name:"Database Systems Lab",             venue:"Lab A-301", batch:"Y3 CS", day:2, start:13, end:15, type:"lab",       date:"Wed, 16 Jul 2025" },
  { id:5, code:"CS3401", name:"Fundamentals of Computing Lab",    venue:"Lab A-201", batch:"Y1 CS", day:3, start:10, end:12, type:"lab",       date:"Thu, 17 Jul 2025" },
  { id:6, code:"IT3201", name:"Network Administration Practical", venue:"Lab D-102", batch:"Y3 IT", day:4, start:14, end:16, type:"practical",  date:"Fri, 18 Jul 2025" },
];

// Pre-created instructor assignment (shown on timetable by default)
const INSTRUCTOR_PRESET_ASSIGNMENT: Session = {
  id: 100, code: "CS3401", name: "Mid-Sem Lab Assessment", venue: "Lab A-201",
  batch: "Y1 CS", day: 2, start: 13, end: 15, type: "assignment", date: "Wed, 16 Jul 2025",
};

// Lecturer base sessions
const LECTURER_SESSIONS: Session[] = [
  { id:10, code:"CS3401", name:"Fundamentals of Computing",  venue:"LT-201", batch:"Y1 CS", day:0, start:10, end:12, type:"lecture",    date:"Mon, 14 Jul 2025" },
  { id:11, code:"CS3402", name:"Data Structures",            venue:"LT-301", batch:"Y2 CS", day:1, start:8,  end:10, type:"lecture",    date:"Tue, 15 Jul 2025" },
  { id:12, code:"CS2201", name:"Database Systems",           venue:"LT-401", batch:"Y3 CS", day:2, start:10, end:12, type:"lecture",    date:"Wed, 16 Jul 2025" },
  { id:13, code:"IT2301", name:"Web Technologies",           venue:"LT-102", batch:"Y2 IT", day:3, start:8,  end:10, type:"lecture",    date:"Thu, 17 Jul 2025" },
  { id:14, code:"IT3201", name:"Network Administration",     venue:"LT-203", batch:"Y3 IT", day:4, start:10, end:12, type:"lecture",    date:"Fri, 18 Jul 2025" },
  { id:15, code:"CS3402", name:"Data Structures Mid-Term",   venue:"LT-301", batch:"Y2 CS", day:3, start:13, end:15, type:"assignment", date:"Thu, 17 Jul 2025" },
];

type Degree = "CS" | "IS";
type Year = "Y1" | "Y2" | "Y3" | "Y4";

interface StudentSession {
  id: number; code: string; name: string; venue: string;
  degree: Degree; year: Year;
  day: number; start: number; end: number; type: SessionType;
}

const STUDENT_SESSIONS: StudentSession[] = [
  // ── CS Y1 ──
  { id:101, code:"CS1101", name:"Intro to Programming",      venue:"LT-201",    degree:"CS", year:"Y1", day:0, start:8,  end:10, type:"lecture"   },
  { id:102, code:"CS1102", name:"Discrete Mathematics",      venue:"LT-202",    degree:"CS", year:"Y1", day:0, start:10, end:12, type:"lecture"   },
  { id:103, code:"CS1101", name:"Programming Lab",           venue:"Lab A-201", degree:"CS", year:"Y1", day:1, start:13, end:15, type:"lab"       },
  { id:104, code:"CS1103", name:"Digital Logic",             venue:"LT-301",    degree:"CS", year:"Y1", day:2, start:9,  end:11, type:"lecture"   },
  { id:105, code:"CS1103", name:"Digital Logic Practical",   venue:"Lab B-101", degree:"CS", year:"Y1", day:2, start:14, end:16, type:"practical" },
  { id:106, code:"CS1104", name:"Calculus I",                venue:"LT-101",    degree:"CS", year:"Y1", day:3, start:8,  end:10, type:"lecture"   },
  { id:107, code:"CS1102", name:"Discrete Math Tutorial",    venue:"LT-202",    degree:"CS", year:"Y1", day:4, start:10, end:12, type:"lecture"   },

  // ── CS Y2 ──
  { id:201, code:"CS2101", name:"Data Structures",           venue:"LT-301",    degree:"CS", year:"Y2", day:0, start:8,  end:10, type:"lecture"   },
  { id:202, code:"CS2101", name:"Data Structures Lab",       venue:"Lab C-201", degree:"CS", year:"Y2", day:0, start:14, end:16, type:"lab"       },
  { id:203, code:"CS2102", name:"Computer Architecture",     venue:"LT-201",    degree:"CS", year:"Y2", day:1, start:9,  end:11, type:"lecture"   },
  { id:204, code:"CS2103", name:"Database Systems",          venue:"LT-202",    degree:"CS", year:"Y2", day:2, start:8,  end:10, type:"lecture"   },
  { id:205, code:"CS2103", name:"Database Lab",              venue:"Lab A-301", degree:"CS", year:"Y2", day:2, start:13, end:15, type:"lab"       },
  { id:206, code:"CS2104", name:"OOP with Java",             venue:"LT-301",    degree:"CS", year:"Y2", day:3, start:10, end:12, type:"lecture"   },
  { id:207, code:"CS2102", name:"Computer Arch. Lab",        venue:"Lab B-201", degree:"CS", year:"Y2", day:4, start:9,  end:11, type:"practical" },

  // ── CS Y3 ──
  { id:301, code:"CS3301", name:"Operating Systems",         venue:"LT-401",    degree:"CS", year:"Y3", day:0, start:9,  end:11, type:"lecture"   },
  { id:302, code:"CS3302", name:"Software Engineering",      venue:"LT-301",    degree:"CS", year:"Y3", day:1, start:8,  end:10, type:"lecture"   },
  { id:303, code:"CS3301", name:"OS Lab",                    venue:"Lab D-101", degree:"CS", year:"Y3", day:1, start:14, end:16, type:"lab"       },
  { id:304, code:"CS3303", name:"Computer Networks",         venue:"LT-402",    degree:"CS", year:"Y3", day:2, start:10, end:12, type:"lecture"   },
  { id:305, code:"CS3304", name:"Algorithm Design",          venue:"LT-301",    degree:"CS", year:"Y3", day:3, start:8,  end:10, type:"lecture"   },
  { id:306, code:"CS3302", name:"SE Lab",                    venue:"Lab C-301", degree:"CS", year:"Y3", day:3, start:13, end:15, type:"practical" },
  { id:307, code:"CS3303", name:"Networks Lab",              venue:"Lab D-201", degree:"CS", year:"Y3", day:4, start:10, end:12, type:"lab"       },

  // ── CS Y4 ──
  { id:401, code:"CS4101", name:"Machine Learning",          venue:"LT-501",    degree:"CS", year:"Y4", day:0, start:10, end:12, type:"lecture"   },
  { id:402, code:"CS4102", name:"Cloud Computing",           venue:"LT-401",    degree:"CS", year:"Y4", day:1, start:9,  end:11, type:"lecture"   },
  { id:403, code:"CS4103", name:"Research Methods",          venue:"LT-402",    degree:"CS", year:"Y4", day:2, start:9,  end:11, type:"lecture"   },
  { id:404, code:"CS4101", name:"ML Lab",                    venue:"Lab E-101", degree:"CS", year:"Y4", day:3, start:10, end:12, type:"lab"       },
  { id:405, code:"CS4104", name:"Cybersecurity",             venue:"LT-501",    degree:"CS", year:"Y4", day:4, start:9,  end:11, type:"lecture"   },

  // ── IS Y1 ──
  { id:501, code:"IS1101", name:"Intro to Info. Systems",    venue:"LT-103",    degree:"IS", year:"Y1", day:0, start:8,  end:10, type:"lecture"   },
  { id:502, code:"IS1101", name:"IS Lab",                    venue:"Lab F-101", degree:"IS", year:"Y1", day:0, start:13, end:15, type:"practical" },
  { id:503, code:"IS1102", name:"Business Computing",        venue:"LT-104",    degree:"IS", year:"Y1", day:1, start:10, end:12, type:"lecture"   },
  { id:504, code:"IS1103", name:"Math for IS",               venue:"LT-102",    degree:"IS", year:"Y1", day:2, start:9,  end:11, type:"lecture"   },
  { id:505, code:"IS1104", name:"Communication Skills",      venue:"LT-103",    degree:"IS", year:"Y1", day:3, start:8,  end:10, type:"lecture"   },
  { id:506, code:"IS1102", name:"Computing Lab",             venue:"Lab F-201", degree:"IS", year:"Y1", day:4, start:10, end:12, type:"lab"       },

  // ── IS Y2 ──
  { id:601, code:"IS2101", name:"System Analysis & Design",  venue:"LT-204",    degree:"IS", year:"Y2", day:0, start:9,  end:11, type:"lecture"   },
  { id:602, code:"IS2102", name:"Database Management",       venue:"LT-203",    degree:"IS", year:"Y2", day:1, start:8,  end:10, type:"lecture"   },
  { id:603, code:"IS2102", name:"Database Lab",              venue:"Lab G-101", degree:"IS", year:"Y2", day:1, start:14, end:16, type:"lab"       },
  { id:604, code:"IS2103", name:"Web Development",           venue:"LT-104",    degree:"IS", year:"Y2", day:2, start:10, end:12, type:"lecture"   },
  { id:605, code:"IS2103", name:"Web Dev Lab",               venue:"Lab F-301", degree:"IS", year:"Y2", day:2, start:13, end:15, type:"practical" },
  { id:606, code:"IS2104", name:"Business Intelligence",     venue:"LT-204",    degree:"IS", year:"Y2", day:3, start:9,  end:11, type:"lecture"   },
  { id:607, code:"IS2101", name:"SAD Lab",                   venue:"Lab G-201", degree:"IS", year:"Y2", day:4, start:8,  end:10, type:"practical" },

  // ── IS Y3 ──
  { id:701, code:"IS3101", name:"IT Project Management",     venue:"LT-304",    degree:"IS", year:"Y3", day:0, start:10, end:12, type:"lecture"   },
  { id:702, code:"IS3102", name:"Enterprise Systems",        venue:"LT-303",    degree:"IS", year:"Y3", day:1, start:9,  end:11, type:"lecture"   },
  { id:703, code:"IS3102", name:"Enterprise Systems Lab",    venue:"Lab H-101", degree:"IS", year:"Y3", day:1, start:14, end:16, type:"practical" },
  { id:704, code:"IS3103", name:"E-Commerce Systems",        venue:"LT-304",    degree:"IS", year:"Y3", day:2, start:8,  end:10, type:"lecture"   },
  { id:705, code:"IS3104", name:"Decision Support Systems",  venue:"LT-303",    degree:"IS", year:"Y3", day:3, start:10, end:12, type:"lecture"   },
  { id:706, code:"IS3103", name:"E-Commerce Practical",      venue:"Lab H-201", degree:"IS", year:"Y3", day:4, start:9,  end:11, type:"practical" },

  // ── IS Y4 ──
  { id:801, code:"IS4101", name:"Strategic IS Management",   venue:"LT-404",    degree:"IS", year:"Y4", day:0, start:9,  end:11, type:"lecture"   },
  { id:802, code:"IS4102", name:"Digital Transformation",    venue:"LT-403",    degree:"IS", year:"Y4", day:1, start:10, end:12, type:"lecture"   },
  { id:803, code:"IS4103", name:"IS Research Methods",       venue:"LT-404",    degree:"IS", year:"Y4", day:2, start:9,  end:11, type:"lecture"   },
  { id:804, code:"IS4101", name:"Capstone Project",          venue:"Lab I-101",  degree:"IS", year:"Y4", day:3, start:9,  end:11, type:"practical" },
  { id:805, code:"IS4104", name:"Professional Ethics in IS", venue:"LT-403",    degree:"IS", year:"Y4", day:4, start:10, end:12, type:"lecture"   },
];

const HOURS = [8,9,10,11,12,13,14,15,16,17];
const DAYS  = ["Monday","Tuesday","Wednesday","Thursday","Friday"];
const HOUR_H = 72;

function fmtHour(h: number) {
  return h < 12 ? `${h} AM` : h === 12 ? "12 PM" : `${h-12} PM`;
}

// ── OTP Box Component ──────────────────────────────────────────────────────
function OtpInput({ value, onChange }: { value: string[]; onChange: (v: string[]) => void }) {
  const refs = useRef<(HTMLInputElement | null)[]>([]);
  return (
    <div className="flex gap-2 justify-center">
      {value.map((ch, i) => (
        <input
          key={i}
          ref={el => { refs.current[i] = el; }}
          type="text"
          inputMode="numeric"
          maxLength={1}
          value={ch}
          onChange={e => {
            const v = e.target.value.replace(/\D/g, "").slice(-1);
            const next = [...value];
            next[i] = v;
            onChange(next);
            if (v && i < 5) refs.current[i + 1]?.focus();
          }}
          onKeyDown={e => {
            if (e.key === "Backspace" && !ch && i > 0) refs.current[i - 1]?.focus();
          }}
          className="w-11 h-12 text-center text-lg font-semibold font-mono rounded-lg border border-[#dde3ee] bg-white focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all text-[#0f1c2e]"
        />
      ))}
    </div>
  );
}

// ── University Logo Mark ───────────────────────────────────────────────────
function LogoMark({ size = 34, dark = false }: { size?: number; dark?: boolean }) {
  return (
    <div
      className="rounded-xl flex items-center justify-center shrink-0"
      style={{ width: size, height: size, background: dark ? "#2563eb" : "#1a3a6b" }}
    >
      <svg width={size * 0.55} height={size * 0.55} viewBox="0 0 20 20" fill="none">
        <path d="M3 5.5h14M7 2v3M13 2v3M3 9.5h14M3 13.5h8" stroke="white" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"/>
        <circle cx="15" cy="15" r="3" stroke="white" strokeWidth="1.5"/>
        <path d="M14.2 15l.8.8 1.3-1.3" stroke="white" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
      </svg>
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════════════
// AUTH PAGES
// ══════════════════════════════════════════════════════════════════════════

// ── Login ──────────────────────────────────────────────────────────────────
function LoginPage({ nav, setRole }: { nav: (p: Page) => void; setRole: (r: UserRole) => void }) {
  const [email, setEmail] = useState("");
  const [pass, setPass]   = useState("");
  const [show, setShow]   = useState(false);
  const [err, setErr]     = useState("");

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !pass) { setErr("Please fill in all fields."); return; }
    const acct = DEMO_ACCOUNTS[email.toLowerCase().trim()];
    if (acct && acct.pass === pass) {
      setRole(acct.role);
      nav("dashboard");
    } else if (acct) {
      setErr("Incorrect password for this account.");
    } else {
      setErr("Account not found. Use a demo account below.");
    }
  };

  const fillDemo = (email: string) => {
    const acct = DEMO_ACCOUNTS[email];
    setEmail(email);
    setPass(acct.pass);
    setErr("");
  };

  return (
    <div className="min-h-screen bg-[#f4f6f9] flex items-center justify-center p-4">
      <div className="w-full max-w-sm">
        {/* Logo */}
        <div className="flex flex-col items-center mb-8">
          <div className="flex items-center gap-3 mb-6">
            <LogoMark size={44} />
            <div>
              <p className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-xl text-[#0f1c2e] leading-tight">StaffSync</p>
              <p className="text-xs text-[#6b7c96] font-medium">University Staff Portal</p>
            </div>
          </div>
          <h1 className="text-2xl font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-center">Welcome back</h1>
          <p className="text-sm text-[#6b7c96] mt-1 text-center">Sign in to your staff account</p>
        </div>

        <div className="bg-white rounded-2xl border border-[#dde3ee] p-6 shadow-sm">
          {err && (
            <div className="flex items-center gap-2 text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-4 text-sm">
              <AlertTriangle size={15} /> {err}
            </div>
          )}
          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <div>
              <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Staff Email</label>
              <input
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                placeholder="you@university.edu"
                className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Password</label>
              <div className="relative">
                <input
                  type={show ? "text" : "password"}
                  value={pass}
                  onChange={e => setPass(e.target.value)}
                  placeholder="••••••••"
                  className="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all"
                />
                <button type="button" onClick={() => setShow(!show)} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af] hover:text-[#6b7c96]">
                  {show ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
            </div>
            <div className="flex justify-end">
              <button type="button" onClick={() => nav("forgot")} className="text-xs text-[#2563eb] font-medium hover:underline">Forgot Password?</button>
            </div>
            <button type="submit" className="w-full bg-[#1a3a6b] hover:bg-[#0f2a55] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
              Sign In
            </button>
            <button type="button" className="w-full flex items-center justify-center gap-2 border border-[#dde3ee] bg-white hover:bg-[#f4f6f9] text-[#0f1c2e] font-semibold py-2.5 rounded-lg transition-colors text-sm">
              <Fingerprint size={16} className="text-[#2563eb]" />
              Sign in with Passkey
            </button>
          </form>
        </div>

        {/* Demo accounts */}
        <div className="mt-5">
          <p className="text-center text-[11px] font-semibold uppercase tracking-wider text-[#9ca3af] mb-3">Demo Accounts — click to fill</p>
          <div className="flex flex-col gap-2">
            {(Object.entries(DEMO_ACCOUNTS) as [string, typeof DEMO_ACCOUNTS[string]][]).map(([em, acct]) => (
              <button key={em} onClick={() => fillDemo(em)}
                className="w-full text-left bg-white border border-[#dde3ee] hover:border-[#2563eb] hover:bg-[#eff6ff] rounded-xl px-4 py-3 transition-all group">
                <div className="flex items-center gap-3">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 ${acct.role === "lecturer" ? "bg-[#1a3a6b]" : "bg-[#4d179a]"}`}>
                    {acct.name.split(" ").map(w => w[0]).filter((_,i) => i < 2).join("")}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-semibold text-[#0f1c2e] truncate">{acct.name}</p>
                    <p className="text-[10.5px] text-[#6b7c96] truncate">{em}</p>
                  </div>
                  <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${acct.role === "lecturer" ? "bg-[#dbeafe] text-[#1c398e]" : "bg-[#ede9fe] text-[#4d179a]"}`}>
                    {acct.role.toUpperCase()}
                  </span>
                </div>
                <p className="text-[10px] text-[#9ca3af] mt-1.5 pl-11">Password: <span className="font-mono font-semibold text-[#6b7c96]">{acct.pass}</span></p>
              </button>
            ))}
          </div>
        </div>

        <p className="text-center text-sm text-[#6b7c96] mt-4">
          {"Don't have an account? "}
          <button onClick={() => nav("signup")} className="text-[#2563eb] font-semibold hover:underline">Sign Up</button>
        </p>
      </div>
    </div>
  );
}

// ── Sign Up ────────────────────────────────────────────────────────────────
function SignUpPage({ nav }: { nav: (p: Page) => void }) {
  const [step, setStep]       = useState<1|2|3>(1);
  const [email, setEmail]     = useState("");
  const [otp, setOtp]         = useState(["","","","","",""]);
  const [name, setName]       = useState("");
  const [dept, setDept]       = useState("");
  const [pass, setPass]       = useState("");
  const [conf, setConf]       = useState("");
  const [passkey, setPasskey] = useState(false);
  const [show, setShow]       = useState(false);
  const [showC, setShowC]     = useState(false);

  const depts = ["Computer Science","Information Systems","Mathematics","Physics","Statistics"];

  return (
    <div className="min-h-screen bg-[#f4f6f9] flex items-center justify-center p-4">
      <div className="w-full max-w-sm">
        <div className="flex flex-col items-center mb-6">
          <div className="flex items-center gap-3 mb-5">
            <LogoMark size={40} />
            <div>
              <p className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-lg text-[#0f1c2e]">StaffSync</p>
              <p className="text-xs text-[#6b7c96] font-medium">University Staff Portal</p>
            </div>
          </div>
          <h1 className="text-2xl font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e]">
            {step === 1 ? "Create Account" : step === 2 ? "Verify Email" : "Complete Profile"}
          </h1>
          <p className="text-sm text-[#6b7c96] mt-1">
            {step === 1 ? "Register as Instructor" : step === 2 ? "Enter the 6-digit code sent to your email" : "Set up your account"}
          </p>
          {/* Step dots */}
          <div className="flex gap-2 mt-4">
            {[1,2,3].map(s => (
              <div key={s} className={`h-1.5 rounded-full transition-all ${s === step ? "w-8 bg-[#2563eb]" : s < step ? "w-4 bg-[#1a3a6b]" : "w-4 bg-[#dde3ee]"}`} />
            ))}
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-[#dde3ee] p-6 shadow-sm">
          {step === 1 && (
            <div className="flex flex-col gap-4">
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Staff Email Address</label>
                <input type="email" value={email} onChange={e => setEmail(e.target.value)} placeholder="you@university.edu"
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <div className="flex items-center gap-2 bg-[#eff6ff] border border-[#bfdbfe] rounded-lg px-3 py-2 text-xs text-[#1e40af]">
                <Info size={13} />
                <span>Instructor accounts are provisioned separately from Lecturer accounts.</span>
              </div>
              <button onClick={() => setStep(2)} disabled={!email}
                className="w-full bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                Send OTP
              </button>
            </div>
          )}

          {step === 2 && (
            <div className="flex flex-col gap-5">
              <p className="text-xs text-[#6b7c96] text-center">Code sent to <span className="font-semibold text-[#0f1c2e]">{email}</span></p>
              <OtpInput value={otp} onChange={setOtp} />
              <button onClick={() => setStep(3)} disabled={otp.some(c => !c)}
                className="w-full bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                Verify OTP
              </button>
              <button onClick={() => {}} className="text-xs text-[#2563eb] font-medium text-center hover:underline">Resend code</button>
            </div>
          )}

          {step === 3 && (
            <div className="flex flex-col gap-4">
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Full Name</label>
                <input type="text" value={name} onChange={e => setName(e.target.value)} placeholder="Dr. Jane Smith"
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Department</label>
                <div className="relative">
                  <select value={dept} onChange={e => setDept(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all appearance-none">
                    <option value="">Select department</option>
                    {depts.map(d => <option key={d}>{d}</option>)}
                  </select>
                  <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none" />
                </div>
              </div>
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1">Designation</label>
                <div className="px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#eef1f6] text-sm text-[#6b7c96] font-medium">Instructor</div>
              </div>
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Create Password</label>
                <div className="relative">
                  <input type={show ? "text" : "password"} value={pass} onChange={e => setPass(e.target.value)} placeholder="Min. 8 characters"
                    className="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
                  <button type="button" onClick={() => setShow(!show)} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af]">
                    {show ? <EyeOff size={15}/> : <Eye size={15}/>}
                  </button>
                </div>
              </div>
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Confirm Password</label>
                <div className="relative">
                  <input type={showC ? "text" : "password"} value={conf} onChange={e => setConf(e.target.value)} placeholder="Repeat password"
                    className="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
                  <button type="button" onClick={() => setShowC(!showC)} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af]">
                    {showC ? <EyeOff size={15}/> : <Eye size={15}/>}
                  </button>
                </div>
              </div>
              <label className="flex items-center gap-2.5 cursor-pointer">
                <div onClick={() => setPasskey(!passkey)}
                  className={`w-4 h-4 rounded border flex items-center justify-center transition-colors ${passkey ? "bg-[#1a3a6b] border-[#1a3a6b]" : "border-[#c8d0de] bg-white"}`}>
                  {passkey && <Check size={10} strokeWidth={3} className="text-white" />}
                </div>
                <span className="text-sm text-[#0f1c2e]">Register Passkey / Enable Passkey Login</span>
              </label>
              <button onClick={() => nav("login")} disabled={!name || !dept || !pass || !conf}
                className="w-full bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                Complete Registration
              </button>
            </div>
          )}
        </div>

        <p className="text-center text-sm text-[#6b7c96] mt-4">
          Already have an account?{" "}
          <button onClick={() => nav("login")} className="text-[#2563eb] font-semibold hover:underline">Sign In</button>
        </p>
      </div>
    </div>
  );
}

// ── Forgot / Reset Password ────────────────────────────────────────────────
function ForgotPage({ nav }: { nav: (p: Page) => void }) {
  const [step, setStep]   = useState<1|2|3>(1);
  const [email, setEmail] = useState("");
  const [otp, setOtp]     = useState(["","","","","",""]);
  const [pass, setPass]   = useState("");
  const [conf, setConf]   = useState("");
  const [show, setShow]   = useState(false);
  const [passkey, setPasskey] = useState(false);

  return (
    <div className="min-h-screen bg-[#f4f6f9] flex items-center justify-center p-4">
      <div className="w-full max-w-sm">
        <div className="flex flex-col items-center mb-6">
          <div className="flex items-center gap-3 mb-5">
            <LogoMark size={40} />
            <div>
              <p className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-lg text-[#0f1c2e]">StaffSync</p>
              <p className="text-xs text-[#6b7c96] font-medium">University Staff Portal</p>
            </div>
          </div>
          <h1 className="text-2xl font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e]">
            {step === 1 ? "Reset Password" : step === 2 ? "Verify Identity" : "New Password"}
          </h1>
          <p className="text-sm text-[#6b7c96] mt-1">
            {step === 1 ? "We'll send a verification code to your email" : step === 2 ? "Enter the 6-digit code" : "Create a new secure password"}
          </p>
          <div className="flex gap-2 mt-4">
            {[1,2,3].map(s => (
              <div key={s} className={`h-1.5 rounded-full transition-all ${s === step ? "w-8 bg-[#2563eb]" : s < step ? "w-4 bg-[#1a3a6b]" : "w-4 bg-[#dde3ee]"}`} />
            ))}
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-[#dde3ee] p-6 shadow-sm">
          {step === 1 && (
            <div className="flex flex-col gap-4">
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Staff Email</label>
                <input type="email" value={email} onChange={e => setEmail(e.target.value)} placeholder="you@university.edu"
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <button onClick={() => setStep(2)} disabled={!email}
                className="w-full bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                Send OTP
              </button>
            </div>
          )}

          {step === 2 && (
            <div className="flex flex-col gap-5">
              <p className="text-xs text-[#6b7c96] text-center">Code sent to <span className="font-semibold text-[#0f1c2e]">{email}</span></p>
              <OtpInput value={otp} onChange={setOtp} />
              <button onClick={() => setStep(3)} disabled={otp.some(c => !c)}
                className="w-full bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                Verify OTP
              </button>
              <button className="text-xs text-[#2563eb] font-medium text-center hover:underline">Resend code</button>
            </div>
          )}

          {step === 3 && (
            <div className="flex flex-col gap-4">
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">New Password</label>
                <div className="relative">
                  <input type={show ? "text" : "password"} value={pass} onChange={e => setPass(e.target.value)} placeholder="Min. 8 characters"
                    className="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
                  <button type="button" onClick={() => setShow(!show)} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af]">
                    {show ? <EyeOff size={15}/> : <Eye size={15}/>}
                  </button>
                </div>
              </div>
              <div>
                <label className="block text-sm font-semibold text-[#0f1c2e] mb-1.5">Confirm Password</label>
                <input type="password" value={conf} onChange={e => setConf(e.target.value)} placeholder="Repeat password"
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <label className="flex items-center gap-2.5 cursor-pointer">
                <div onClick={() => setPasskey(!passkey)}
                  className={`w-4 h-4 rounded border flex items-center justify-center transition-colors ${passkey ? "bg-[#1a3a6b] border-[#1a3a6b]" : "border-[#c8d0de] bg-white"}`}>
                  {passkey && <Check size={10} strokeWidth={3} className="text-white" />}
                </div>
                <span className="text-sm text-[#0f1c2e]">Update Passkey</span>
              </label>
              <button onClick={() => nav("login")} disabled={!pass || !conf}
                className="w-full bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                Reset Password
              </button>
            </div>
          )}
        </div>

        <p className="text-center text-sm text-[#6b7c96] mt-4">
          Remember it?{" "}
          <button onClick={() => nav("login")} className="text-[#2563eb] font-semibold hover:underline">Back to Login</button>
        </p>
      </div>
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════════════
// DASHBOARD SCREENS
// ══════════════════════════════════════════════════════════════════════════

// ── Hash Icon (custom, course-code label) ─────────────────────────────────
function Hash({ size }: { size: number }) {
  return (
    <svg width={size} height={size} viewBox="0 0 16 16" fill="none">
      <path d="M2 6h12M2 10h12M6 2l-2 12M10 2l-2 12" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
    </svg>
  );
}

// ── Segmented control ──────────────────────────────────────────────────────
function SegControl<T extends string>({ options, labels, value, onChange }: {
  options: T[]; labels?: Partial<Record<T, string>>; value: T; onChange: (v: T) => void;
}) {
  return (
    <div className="bg-[#eef1f6] rounded-[11.5px] p-[3.75px] flex gap-[3.75px] shrink-0">
      {options.map(o => (
        <button key={o} onClick={() => onChange(o)}
          className={`rounded-[7.5px] text-[13px] font-semibold transition-all px-4 py-1.5 whitespace-nowrap ${value === o
            ? "bg-white text-[#1a3a6b] shadow-sm"
            : "text-[#6b7c96] hover:text-[#0f1c2e]"}`}>
          {(labels as Record<string, string> | undefined)?.[o] ?? o}
        </button>
      ))}
    </div>
  );
}

// ── Calendar grid (shared between My and Student views) ────────────────────
type GridSession = { id: number; day: number; start: number; end: number; type: SessionType; code: string; name: string; venue: string; };

function CalendarGrid({ sessions, selectedId, onSelect, weekDates, selectMode, selectedSlots, onSlotClick, highlightCodes }: {
  sessions: GridSession[];
  selectedId: number | null;
  onSelect: (s: GridSession) => void;
  weekDates: Date[];
  selectMode?: boolean;
  selectedSlots?: Set<string>;
  onSlotClick?: (day: number, hour: number) => void;
  highlightCodes?: Set<string>;
}) {
  const forDay = (d: number) => sessions.filter(s => s.day === d);
  const occupiedAt = (day: number, hour: number) =>
    sessions.some(s => s.day === day && hour >= s.start && hour < s.end);

  return (
    <div className="min-w-[760px]">
      {/* Day headers with dates */}
      <div className="flex border-b border-[#dde3ee] bg-white sticky top-0 z-10">
        <div className="w-[64px] shrink-0 border-r border-[#dde3ee]" />
        {DAYS.map((_, di) => {
          const d = weekDates[di];
          return (
            <div key={di} className="flex-1 py-2 text-center border-r border-[#dde3ee] last:border-r-0">
              <p className="text-[10px] font-semibold tracking-widest uppercase text-[#9ca3af]">{DAY_S[di]}</p>
              <p className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-base leading-tight">{d.getDate()}</p>
              <p className="text-[10px] text-[#9ca3af]">{MONTH_S[d.getMonth()]}</p>
            </div>
          );
        })}
      </div>

      {/* Grid body */}
      <div className="flex relative">
        {/* Single full-width lunch stripe */}
        <div className="absolute right-0 pointer-events-none flex items-center justify-center bg-[#f0f3f8] border-t border-b border-[#c8d0de] z-10"
          style={{ top: (12 - 8) * HOUR_H, height: HOUR_H, left: 64 }}>
          <p className="text-[10px] font-semibold tracking-widest uppercase text-[#6b7c96]">Lunch Break</p>
        </div>

        {/* Time column */}
        <div className="w-[64px] shrink-0 border-r border-[#c8d0de]">
          {HOURS.map(h => (
            <div key={h} style={{ height: h < 17 ? HOUR_H : 20 }}
              className="flex items-start justify-end pr-2.5 pt-1.5 border-t border-[#dde3ee] first:border-t-0">
              <span className={`font-['JetBrains_Mono',monospace] font-bold text-[10.5px] ${h === 12 ? "text-[#fe9a00]" : "text-[#6b7c96]"}`}>
                {fmtHour(h)}
              </span>
            </div>
          ))}
        </div>

        {/* Day columns */}
        {DAYS.map((_, di) => (
          <div key={di} className="flex-1 border-r border-[#c8d0de] last:border-r-0 relative"
            style={{ height: (HOURS.length - 1) * HOUR_H + 20 }}>
            {/* Hour rule lines */}
            {HOURS.map((h, hi) => (
              <div key={h} className={`absolute left-0 right-0 border-t ${h === 12 ? "border-[#c8d0de]" : "border-[#dde3ee]"}`}
                style={{ top: hi * HOUR_H }} />
            ))}

            {/* Selectable empty hour cells (only in selectMode) */}
            {selectMode && HOURS.slice(0, -1).map((h, hi) => {
              const key = `${di}-${h}`;
              const taken = occupiedAt(di, h);
              const sel = selectedSlots?.has(key);
              if (h === 12) return null; // skip lunch
              return (
                <div key={h}
                  onClick={() => !taken && onSlotClick?.(di, h)}
                  className={`absolute left-0 right-0 transition-colors ${taken ? "cursor-not-allowed" : "cursor-pointer"}`}
                  style={{ top: hi * HOUR_H, height: HOUR_H }}>
                  {!taken && (
                    <div className={`absolute inset-[3px] rounded-lg border-2 border-dashed transition-all ${
                      sel
                        ? "bg-[#dbeafe] border-[#2563eb]"
                        : "border-transparent hover:bg-[#eff6ff] hover:border-[#93c5fd]"
                    }`}>
                      {sel && (
                        <div className="absolute top-1 right-1.5 w-4 h-4 bg-[#2563eb] rounded-full flex items-center justify-center">
                          <Check size={9} strokeWidth={3} className="text-white" />
                        </div>
                      )}
                    </div>
                  )}
                </div>
              );
            })}

            {/* Session blocks */}
            {forDay(di).map(s => {
              const st = SESSION_STYLE[s.type];
              const isHighlighted = highlightCodes?.has(s.code);
              return (
                <button key={s.id}
                  onClick={() => onSelect(s)}
                  className="absolute rounded-lg overflow-hidden flex flex-col justify-center text-left transition-all hover:scale-[1.02] hover:z-20"
                  style={{
                    top: (s.start - 8) * HOUR_H + 2,
                    height: (s.end - s.start) * HOUR_H - 4,
                    left: 5, right: 5,
                    background: st.bg,
                    borderLeft: `4px solid ${st.border}`,
                    boxShadow: selectedId === s.id
                      ? `0 0 0 2px ${st.border}`
                      : isHighlighted
                      ? `0 0 0 2px #f59e0b, inset 0 0 0 1px #fcd34d`
                      : undefined,
                  }}>
                  {isHighlighted && (
                    <div className="absolute top-1 right-1.5">
                      <Star size={10} className="text-[#f59e0b] fill-[#f59e0b]" />
                    </div>
                  )}
                  <div className="px-2.5 py-1.5">
                    <p className="font-['JetBrains_Mono',monospace] font-bold text-[10px] leading-tight" style={{ color: st.text }}>{s.code}</p>
                    <p className="font-semibold text-[10px] leading-tight mt-0.5 truncate" style={{ color: st.text }}>{s.name}</p>
                    <p className="text-[9.5px] mt-0.5 opacity-75 truncate" style={{ color: st.text }}>{s.venue}</p>
                  </div>
                </button>
              );
            })}
          </div>
        ))}
      </div>
    </div>
  );
}

// ── Timetable ─────────────────────────────────────────────────────────────
function TimetableScreen({ onCreateGroup, role }: { onCreateGroup: (g: TimetableGroup) => void; role: UserRole }) {
  const baseSessions = role === "lecturer" ? LECTURER_SESSIONS : SESSIONS;

  // ── sessions (extra = created assignments); instructor starts with one pre-created ──
  const [extraSessions, setExtraSessions] = useState<Session[]>(
    role === "instructor" ? [INSTRUCTOR_PRESET_ASSIGNMENT] : []
  );
  const allSessions = [...baseSessions, ...extraSessions];

  // ── staff requests ──
  const [staffRequests, setStaffRequests] = useState<StaffRequest[]>([
    { id:0, courseCode:"CS3401", courseName:"Fundamentals of Computing Lab", sessionType:"LAB", slots:"Mon 08:00", membersNeeded:1, supportType:"Lab Assistant", notes:"Need someone to assist with equipment.", status:"pending", assignedMembers:[], submittedAt:"2 days ago" },
  ]);

  // ── view / week ──
  const [view, setView]         = useState<"mine" | "student">("mine");
  const [weekOffset, setWeekOff] = useState(0);
  const weekDates = getWeekDates(weekOffset);

  // ── student filters ──
  const [degree, setDegree] = useState<Degree>("CS");
  const [year, setYear]     = useState<Year>("Y1");

  // ── mode & slot selection ──
  const [mode, setMode]           = useState<"view" | "select">("view");
  const [selectedSlots, setSelSlots] = useState<Set<string>>(new Set());

  // ── session detail ──
  const [detailSession, setDetailSession]   = useState<Session | null>(null);
  const [detailStudent, setDetailStudent]   = useState<GridSession | null>(null);

  // ── active panel ──
  type Panel = "assignment" | "staffReq" | "timeChange" | "myRequests" | null;
  const [panel, setPanel] = useState<Panel>(null);

  // ── assignment form ──
  const [aCode, setACode]           = useState("");
  const [aName, setAName]           = useState("");
  const [aType, setAType]           = useState("");
  const [aNotes, setANotes]         = useState("");
  const [aMembersNeeded, setAMembersNeeded] = useState("1");

  // ── staff request form ──
  const [srType, setSrType]   = useState("");
  const [srNotes, setSrNotes] = useState("");

  // ── time change form ──
  const [tcSlots, setTcSlots]   = useState<Set<string>>(new Set());
  const [tcManDay, setTcManDay] = useState("");
  const [tcManStart, setTcManStart] = useState("");
  const [tcManEnd, setTcManEnd] = useState("");
  const [tcReason, setTcReason] = useState("");
  const [tcDone, setTcDone]     = useState(false);
  const [tcPickMode, setTcPickMode] = useState(false);

  const tcSlotLabel = (key: string) => {
    const [d, h] = key.split("-").map(Number);
    return `${DAY_S[d]} ${fmtHour(h)}–${fmtHour(h + 1)}`;
  };
  const toggleTcSlot = (key: string) => setTcSlots(prev => {
    const n = new Set(prev); n.has(key) ? n.delete(key) : n.add(key); return n;
  });
  const addManualSlots = () => {
    if (!tcManDay || !tcManStart || !tcManEnd) return;
    const di = DAYS.indexOf(tcManDay);
    const s = Number(tcManStart), e = Number(tcManEnd);
    for (let h = s; h < e; h++) toggleTcSlot(`${di}-${h}`);
    setTcManDay(""); setTcManStart(""); setTcManEnd("");
  };

  // ── success toast ──
  const [toast, setToast]     = useState("");

  const showToast = (msg: string) => { setToast(msg); setTimeout(() => setToast(""), 3500); };

  // ── derived ──
  const studentSessions: GridSession[] = STUDENT_SESSIONS.filter(s => s.degree === degree && s.year === year);
  const MY_CODES = new Set(baseSessions.map(s => s.code));

  const freeInfo = DAYS.map((_, di) => {
    const busy = studentSessions.filter(s => s.day === di).reduce((a, s) => a + (s.end - s.start), 0);
    return Math.max(0, 10 - busy - 1);
  });

  const slotCount = selectedSlots.size;
  const slotLabel = [...selectedSlots].map(k => {
    const [d, h] = k.split("-").map(Number);
    return `${DAY_S[d]} ${fmtHour(h)}`;
  }).join(", ");

  const MY_COURSES = [...new Map(baseSessions.map(s => [s.code, { code: s.code, name: s.name }])).values()];

  const toggleSlot = (day: number, hour: number) => {
    setSelSlots(prev => {
      const k = `${day}-${hour}`, n = new Set(prev);
      n.has(k) ? n.delete(k) : n.add(k);
      return n;
    });
  };

  const clearSelection = () => { setSelSlots(new Set()); setMode("view"); setPanel(null); };

  const submitAssignment = () => {
    // Create one session per day from selected slots
    const byDay = new Map<number, number[]>();
    for (const k of selectedSlots) {
      const [d, h] = k.split("-").map(Number);
      if (!byDay.has(d)) byDay.set(d, []);
      byDay.get(d)!.push(h);
    }
    const newSessions: Session[] = [];
    byDay.forEach((hours, day) => {
      const start = Math.min(...hours);
      const end   = Math.max(...hours) + 1;
      const wd    = weekDates[day];
      newSessions.push({
        id: Date.now() + day,
        code: aCode, name: aName, venue: "TBD", batch: "TBD",
        day, start, end, type: "assignment",
        date: `${DAY_S[day]}, ${wd.getDate()} ${MONTH_S[wd.getMonth()]} ${wd.getFullYear()}`,
      });
    });
    setExtraSessions(prev => [...prev, ...newSessions]);

    // Auto-create staff request if members needed > 0
    const needed = parseInt(aMembersNeeded) || 0;
    if (needed > 0) {
      setStaffRequests(prev => [{
        id: Date.now(),
        courseCode: aCode,
        courseName: MY_COURSES.find(c => c.code === aCode)?.name ?? aName,
        sessionType: aType,
        slots: slotLabel,
        membersNeeded: needed,
        supportType: "General Support",
        notes: aNotes || "Requested with assignment creation.",
        status: "pending",
        assignedMembers: [],
        submittedAt: "Just now",
      }, ...prev]);
    }

    // Create group chat — no navigation, just toast
    const members  = COURSE_MEMBERS[aCode] ?? ["Ms. T. Fernando (You)"];
    const groupName = `${aCode} – ${aName}`;
    onCreateGroup({ id: Date.now() + 99, name: groupName, avatar: aCode.slice(0,2), courseCode: aCode, members, preview: `Group created for ${aType}`, time: "Just now" });
    showToast(`Assignment created · Group "${groupName}" added in Messages`);
    clearSelection();
    setACode(""); setAName(""); setAType(""); setANotes(""); setAMembersNeeded("1");
  };

  const approveRequest = (id: number) => {
    setStaffRequests(prev => prev.map(r => r.id !== id ? r : {
      ...r,
      status: "approved",
      assignedMembers: ["Mr. A. Karunaratne", "Ms. B. Jayasuriya"].slice(0, r.membersNeeded),
    }));
    showToast("Request approved — assigned members added to the session group.");
  };

  const submitStaffReq = () => {
    if (!detailSession) return;
    setStaffRequests(prev => [{
      id: Date.now(),
      courseCode: detailSession.code,
      courseName: detailSession.name,
      sessionType: SESSION_STYLE[detailSession.type].label,
      slots: `${DAY_S[detailSession.day]} ${fmtHour(detailSession.start)}–${fmtHour(detailSession.end)}`,
      membersNeeded: 1,
      supportType: srType,
      notes: srNotes,
      status: "pending",
      assignedMembers: [],
      submittedAt: "Just now",
    }, ...prev]);
    showToast("Support staff request submitted.");
    setPanel(null); setSrType(""); setSrNotes("");
  };

  const submitTimeChange = () => {
    showToast("Time change request sent to coordinator.");
    setTcDone(true);
    setPanel(null); setDetailSession(null); setTcPickMode(false);
    setTimeout(() => setTcDone(false), 100);
    setTcSlots(new Set()); setTcReason("");
    setTcManDay(""); setTcManStart(""); setTcManEnd("");
  };

  const supportTypes = ["Lab Assistant","Technical Support","Equipment Setup","IT Support"];
  const sessionTypes = ["Lab Session","Tutorial Session","Practical Session","Assessment Session"];

  // ── right-side panel content ──
  const PanelAssignment = () => (
    <div className="flex flex-col h-full">
      <div className="flex items-center justify-between px-5 py-4 border-b border-[#dde3ee] shrink-0">
        <div>
          <h3 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-sm">Create Assignment</h3>
          <p className="text-[10.5px] text-[#9ca3af] mt-0.5">{slotCount} slot{slotCount !== 1 ? "s" : ""} selected</p>
        </div>
        <button onClick={() => setPanel(null)} className="text-[#9ca3af] hover:text-[#0f1c2e]"><X size={15}/></button>
      </div>
      <div className="flex-1 overflow-y-auto p-5 flex flex-col gap-4">
        {/* Selected slots summary */}
        <div className="bg-[#eff6ff] border border-[#bfdbfe] rounded-lg p-3">
          <p className="text-[10px] font-semibold uppercase tracking-wide text-[#1e40af] mb-1">Selected Slots</p>
          <p className="text-xs text-[#1e40af] font-medium">{slotLabel}</p>
        </div>
        {/* Course */}
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Course</label>
          <div className="relative">
            <select value={aCode} onChange={e => setACode(e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] appearance-none">
              <option value="">Select course</option>
              {MY_COURSES.map(c => <option key={c.code} value={c.code}>{c.code} – {c.name}</option>)}
            </select>
            <ChevronDown size={13} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
          </div>
        </div>
        {/* Assignment name */}
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Assignment Name</label>
          <input value={aName} onChange={e => setAName(e.target.value)} placeholder="e.g. Mid-Sem Lab Assessment"
            className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb]" />
        </div>
        {/* Type */}
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Session Type</label>
          <div className="relative">
            <select value={aType} onChange={e => setAType(e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] appearance-none">
              <option value="">Select type</option>
              {sessionTypes.map(t => <option key={t}>{t}</option>)}
            </select>
            <ChevronDown size={13} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
          </div>
        </div>
        {/* Notes */}
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Notes</label>
          <textarea value={aNotes} onChange={e => setANotes(e.target.value)} rows={3} placeholder="Additional instructions…"
            className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] resize-none" />
        </div>
        {/* Auto members */}
        {aCode && COURSE_MEMBERS[aCode] && (
          <div className="bg-[#f4f6f9] rounded-lg border border-[#dde3ee] p-3">
            <p className="text-[10px] font-semibold uppercase tracking-wide text-[#6b7c96] mb-2 flex items-center gap-1.5">
              <UserPlus size={11}/> Group Members
            </p>
            {COURSE_MEMBERS[aCode].map(m => (
              <p key={m} className="text-xs text-[#0f1c2e] py-0.5">{m}</p>
            ))}
            <p className="text-[10px] text-[#9ca3af] mt-1.5">More members added when coordinator assigns staff.</p>
          </div>
        )}
        {/* Members Needed */}
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Additional Staff Needed</label>
          <div className="flex items-center gap-3">
            <input type="number" min="0" max="10" value={aMembersNeeded} onChange={e => setAMembersNeeded(e.target.value)}
              className="w-20 px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] text-center font-semibold" />
            <p className="text-xs text-[#6b7c96]">A staff request will be auto-submitted for this count.</p>
          </div>
        </div>
      </div>
      <div className="shrink-0 border-t border-[#dde3ee] px-5 py-3 flex gap-2">
        <button onClick={() => setPanel(null)} className="flex-1 py-2 text-sm font-semibold text-[#6b7c96] border border-[#dde3ee] rounded-lg hover:bg-[#f4f6f9]">Cancel</button>
        <button onClick={submitAssignment} disabled={!aCode || !aName || !aType || slotCount === 0}
          className="flex-1 py-2 text-sm font-semibold bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white rounded-lg transition-colors">
          Create + Group
        </button>
      </div>
    </div>
  );

  const PanelStaffReq = () => (
    <div className="flex flex-col h-full">
      <div className="flex items-center justify-between px-5 py-4 border-b border-[#dde3ee] shrink-0">
        <div>
          <h3 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-sm">Request Support Staff</h3>
          <p className="text-[10.5px] text-[#9ca3af] mt-0.5">For selected time slots</p>
        </div>
        <button onClick={() => setPanel(null)} className="text-[#9ca3af] hover:text-[#0f1c2e]"><X size={15}/></button>
      </div>
      <div className="flex-1 overflow-y-auto p-5 flex flex-col gap-4">
        <div className="bg-[#eff6ff] border border-[#bfdbfe] rounded-lg p-3">
          <p className="text-[10px] font-semibold uppercase tracking-wide text-[#1e40af] mb-1">Slots</p>
          <p className="text-xs text-[#1e40af] font-medium">{slotLabel || (detailSession ? `${fmtHour(detailSession.start)} – ${fmtHour(detailSession.end)}, ${detailSession.date}` : "")}</p>
        </div>
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Course</label>
          <div className="px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#eef1f6] text-sm text-[#6b7c96]">
            {detailSession ? `${detailSession.code} – ${detailSession.name}` : "Select from calendar"}
          </div>
        </div>
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Type of Support</label>
          <div className="relative">
            <select value={srType} onChange={e => setSrType(e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] appearance-none">
              <option value="">Select type</option>
              {supportTypes.map(t => <option key={t}>{t}</option>)}
            </select>
            <ChevronDown size={13} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
          </div>
        </div>
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Notes</label>
          <textarea value={srNotes} onChange={e => setSrNotes(e.target.value)} rows={3} placeholder="Describe what support is needed…"
            className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] resize-none" />
        </div>
      </div>
      <div className="shrink-0 border-t border-[#dde3ee] px-5 py-3 flex gap-2">
        <button onClick={() => setPanel(null)} className="flex-1 py-2 text-sm font-semibold text-[#6b7c96] border border-[#dde3ee] rounded-lg hover:bg-[#f4f6f9]">Cancel</button>
        <button onClick={submitStaffReq} disabled={!srType}
          className="flex-1 py-2 text-sm font-semibold bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white rounded-lg transition-colors">Submit</button>
      </div>
    </div>
  );

  const PanelTimeChange = () => (
    <div className="flex flex-col h-full">
      <div className="flex items-center justify-between px-5 py-4 border-b border-[#dde3ee] shrink-0">
        <div>
          <h3 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-sm">Request Time Change</h3>
          <p className="text-[10.5px] text-[#9ca3af] mt-0.5">{detailSession?.code} · {detailSession?.name}</p>
        </div>
        <button onClick={() => setPanel(null)} className="text-[#9ca3af] hover:text-[#0f1c2e]"><X size={15}/></button>
      </div>
      <div className="flex-1 overflow-y-auto p-5 flex flex-col gap-4">
        {/* Current slot */}
        <div className="bg-[#fef3c7] border border-[#fcd34d] rounded-lg p-3 text-xs text-[#92400e]">
          <p className="font-semibold">Current slot</p>
          <p className="mt-0.5">{DAYS[detailSession?.day ?? 0]} · {fmtHour(detailSession?.start ?? 8)} – {fmtHour(detailSession?.end ?? 9)} · {detailSession?.venue}</p>
        </div>

        {/* Preferred slots */}
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-2 uppercase tracking-wide">Preferred Slots</label>

          {/* Selected slot chips */}
          {tcSlots.size > 0 && (
            <div className="flex flex-wrap gap-1.5 mb-2">
              {[...tcSlots].map(k => (
                <span key={k} className="flex items-center gap-1 bg-[#eff6ff] border border-[#bfdbfe] text-[#1e40af] text-[10.5px] font-semibold px-2 py-0.5 rounded-full">
                  {tcSlotLabel(k)}
                  <button onClick={() => toggleTcSlot(k)} className="text-[#93c5fd] hover:text-[#1e40af]"><X size={9}/></button>
                </span>
              ))}
              <button onClick={() => setTcSlots(new Set())} className="text-[10px] text-[#9ca3af] hover:text-[#ef4444] font-medium self-center">Clear all</button>
            </div>
          )}

          {/* Pick from calendar button */}
          <button
            onClick={() => setTcPickMode(v => !v)}
            className={`w-full flex items-center justify-center gap-2 py-2 text-sm font-semibold rounded-lg border transition-colors ${
              tcPickMode
                ? "bg-[#1a3a6b] text-white border-[#1a3a6b]"
                : "bg-[#eff6ff] text-[#1a3a6b] border-[#bfdbfe] hover:bg-[#dbeafe]"}`}>
            <CalendarDays size={14}/>
            {tcPickMode ? `Picking… (${tcSlots.size} selected) — click Done` : "Select slots from calendar"}
          </button>
          {tcPickMode && (
            <button onClick={() => setTcPickMode(false)}
              className="mt-1.5 w-full py-1.5 text-xs font-semibold text-white bg-[#2563eb] hover:bg-[#1d4ed8] rounded-lg transition-colors">
              Done selecting
            </button>
          )}
        </div>

        {/* Or add manually */}
        <div>
          <p className="text-[10px] font-semibold text-[#9ca3af] uppercase tracking-wide mb-2.5">Or add manually</p>
          <div className="flex flex-col gap-2">
            <div className="relative">
              <select value={tcManDay} onChange={e => setTcManDay(e.target.value)}
                className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] appearance-none">
                <option value="">Select day</option>
                {DAYS.map(d => <option key={d}>{d}</option>)}
              </select>
              <ChevronDown size={13} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
            </div>
            <div className="grid grid-cols-2 gap-2">
              <div className="relative">
                <select value={tcManStart} onChange={e => setTcManStart(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] appearance-none">
                  <option value="">From</option>
                  {HOURS.slice(0,-1).map(h => <option key={h} value={String(h)}>{fmtHour(h)}</option>)}
                </select>
                <ChevronDown size={13} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
              </div>
              <div className="relative">
                <select value={tcManEnd} onChange={e => setTcManEnd(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] appearance-none">
                  <option value="">To</option>
                  {HOURS.slice(1).map(h => <option key={h} value={String(h)}>{fmtHour(h)}</option>)}
                </select>
                <ChevronDown size={13} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
              </div>
            </div>
            <button onClick={addManualSlots} disabled={!tcManDay || !tcManStart || !tcManEnd}
              className="w-full py-1.5 text-xs font-semibold bg-[#f4f6f9] hover:bg-[#eef1f6] disabled:opacity-40 text-[#1a3a6b] border border-[#dde3ee] rounded-lg transition-colors">
              + Add slot
            </button>
          </div>
        </div>

        {/* Reason */}
        <div>
          <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Reason</label>
          <textarea value={tcReason} onChange={e => setTcReason(e.target.value)} rows={3} placeholder="Explain why you need the change…"
            className="w-full px-3 py-2 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] resize-none" />
        </div>
      </div>
      <div className="shrink-0 border-t border-[#dde3ee] px-5 py-3 flex gap-2">
        <button onClick={() => setPanel(null)} className="flex-1 py-2 text-sm font-semibold text-[#6b7c96] border border-[#dde3ee] rounded-lg hover:bg-[#f4f6f9]">Cancel</button>
        <button onClick={submitTimeChange} disabled={tcSlots.size === 0 || !tcReason}
          className="flex-1 py-2 text-sm font-semibold bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white rounded-lg transition-colors">Send Request</button>
      </div>
    </div>
  );

  const PanelMyRequests = () => (
    <div className="flex flex-col h-full">
      <div className="flex items-center justify-between px-5 py-4 border-b border-[#dde3ee] shrink-0">
        <div>
          <h3 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-sm">My Staff Requests</h3>
          <p className="text-[10.5px] text-[#9ca3af] mt-0.5">{staffRequests.length} total · {staffRequests.filter(r => r.status === "pending").length} pending</p>
        </div>
        <button onClick={() => setPanel(null)} className="text-[#9ca3af] hover:text-[#0f1c2e]"><X size={15}/></button>
      </div>
      <div className="flex-1 overflow-y-auto p-4 flex flex-col gap-3">
        {staffRequests.length === 0 && (
          <div className="text-center py-10 text-[#9ca3af] text-sm">No requests submitted yet.</div>
        )}
        {staffRequests.map(r => (
          <div key={r.id} className="bg-white border border-[#dde3ee] rounded-xl overflow-hidden">
            <div className="flex items-start justify-between gap-2 px-4 pt-4 pb-2">
              <div className="min-w-0">
                <p className="font-semibold text-sm text-[#0f1c2e] truncate">{r.courseCode}</p>
                <p className="text-[10.5px] text-[#6b7c96] truncate">{r.courseName}</p>
                <p className="text-[10px] text-[#9ca3af] mt-0.5">{r.slots} · {r.supportType}</p>
              </div>
              <span className={`shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold flex items-center gap-1 ${r.status === "approved" ? "bg-[#d0fae5] text-[#004f3b]" : "bg-[#fef3c7] text-[#92400e]"}`}>
                {r.status === "approved" ? <><UserCheck size={9}/> APPROVED</> : <><Clock size={9}/> PENDING</>}
              </span>
            </div>
            <div className="px-4 pb-2">
              <p className="text-xs text-[#6b7c96]">{r.membersNeeded} staff needed · {r.sessionType}</p>
              {r.notes && <p className="text-[10.5px] text-[#9ca3af] mt-0.5 italic">"{r.notes}"</p>}
            </div>
            {r.status === "approved" && r.assignedMembers.length > 0 && (
              <div className="mx-4 mb-3 bg-[#d0fae5] rounded-lg p-2.5">
                <p className="text-[10px] font-semibold text-[#004f3b] mb-1 flex items-center gap-1"><UserCheck size={10}/> Assigned Staff</p>
                {r.assignedMembers.map(m => <p key={m} className="text-xs text-[#004f3b] font-medium">{m}</p>)}
              </div>
            )}
            {r.status === "pending" && (
              <div className="px-4 pb-3">
                <button onClick={() => approveRequest(r.id)}
                  className="text-[10px] text-[#9ca3af] hover:text-[#2563eb] font-medium underline underline-offset-2">
                  Simulate coordinator approval →
                </button>
              </div>
            )}
            <div className="px-4 pb-2.5 border-t border-[#f4f6f9] pt-2 mt-1">
              <p className="text-[10px] text-[#9ca3af]">Submitted {r.submittedAt}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  // ── Session detail drawer (My Timetable) ──
  const MyDetailDrawer = () => detailSession ? (
    <div className="w-72 shrink-0 border-l border-[#dde3ee] bg-white flex flex-col overflow-y-auto">
      <div className="flex items-center justify-between px-5 py-4 border-b border-[#dde3ee] shrink-0">
        <h3 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-sm">Session Details</h3>
        <button onClick={() => { setDetailSession(null); setPanel(null); }} className="text-[#9ca3af] hover:text-[#0f1c2e]"><X size={15}/></button>
      </div>
      <div className="flex-1 overflow-y-auto p-5 flex flex-col gap-3">
        <span className="self-start px-2.5 py-0.5 rounded-full text-[10.5px] font-bold uppercase"
          style={{ background: SESSION_STYLE[detailSession.type].bg, color: SESSION_STYLE[detailSession.type].text }}>
          {SESSION_STYLE[detailSession.type].label}
        </span>
        {[
          { label:"Course Code", value: detailSession.code,  icon:<Hash size={12}/> },
          { label:"Course Name", value: detailSession.name,  icon:<BookOpen size={12}/> },
          { label:"Venue",       value: detailSession.venue, icon:<MapPin size={12}/> },
          { label:"Batch",       value: detailSession.batch, icon:<Users size={12}/> },
          { label:"Date",        value: detailSession.date,  icon:<CalendarDays size={12}/> },
          { label:"Time",        value: `${fmtHour(detailSession.start)} – ${fmtHour(detailSession.end)}`, icon:<Clock size={12}/> },
        ].map(r => (
          <div key={r.label}>
            <p className="text-[10px] font-semibold uppercase tracking-wider text-[#9ca3af] mb-0.5">{r.label}</p>
            <div className="flex items-center gap-1.5 text-[#0f1c2e] text-sm font-medium">
              <span className="text-[#6b7c96]">{r.icon}</span>{r.value}
            </div>
          </div>
        ))}
        {/* Action buttons */}
        <div className="mt-2 flex flex-col gap-2">
          <button onClick={() => setPanel("staffReq")}
            className="w-full flex items-center justify-center gap-2 py-2 text-sm font-semibold bg-[#eff6ff] hover:bg-[#dbeafe] text-[#1a3a6b] rounded-lg transition-colors border border-[#bfdbfe]">
            <ClipboardList size={14}/> Request Support Staff
          </button>
          {/* Instructor: only assignments they created. Lecturer: all own sessions. */}
          {(role === "lecturer" || detailSession?.type === "assignment") ? (
            <button onClick={() => setPanel("timeChange")}
              className="w-full flex items-center justify-center gap-2 py-2 text-sm font-semibold bg-[#fef3c7] hover:bg-[#fde68a] text-[#92400e] rounded-lg transition-colors border border-[#fcd34d]">
              <ArrowRightLeft size={14}/> Request Time Change
            </button>
          ) : (
            <div className="w-full flex items-center justify-center gap-2 py-2 text-xs text-[#9ca3af] bg-[#f4f6f9] rounded-lg border border-[#dde3ee] cursor-not-allowed">
              <ArrowRightLeft size={13}/> Time change not available for {detailSession?.type} sessions
            </div>
          )}
        </div>
      </div>
    </div>
  ) : null;

  // ── Student detail drawer ──
  const StudentDetailDrawer = () => detailStudent ? (
    <div className="w-72 shrink-0 border-l border-[#dde3ee] bg-white flex flex-col overflow-y-auto">
      <div className="flex items-center justify-between px-5 py-4 border-b border-[#dde3ee] shrink-0">
        <h3 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-sm">Student Session</h3>
        <button onClick={() => setDetailStudent(null)} className="text-[#9ca3af] hover:text-[#0f1c2e]"><X size={15}/></button>
      </div>
      <div className="p-5 flex flex-col gap-3">
        <div className="flex items-center gap-2 flex-wrap">
          <span className="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold uppercase"
            style={{ background: SESSION_STYLE[detailStudent.type].bg, color: SESSION_STYLE[detailStudent.type].text }}>
            {SESSION_STYLE[detailStudent.type].label}
          </span>
          <span className="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-[#eef1f6] text-[#1a3a6b]">{degree} · {year}</span>
          {MY_CODES.has(detailStudent.code) && (
            <span className="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-[#fef3c7] text-[#92400e] flex items-center gap-1">
              <Star size={9} className="fill-[#f59e0b] text-[#f59e0b]"/> Your course
            </span>
          )}
        </div>
        {[
          { label:"Course Code", value: detailStudent.code,  icon:<Hash size={12}/> },
          { label:"Course Name", value: detailStudent.name,  icon:<BookOpen size={12}/> },
          { label:"Venue",       value: detailStudent.venue, icon:<MapPin size={12}/> },
          { label:"Time",        value: `${fmtHour(detailStudent.start)} – ${fmtHour(detailStudent.end)}`, icon:<Clock size={12}/> },
        ].map(r => (
          <div key={r.label}>
            <p className="text-[10px] font-semibold uppercase tracking-wider text-[#9ca3af] mb-0.5">{r.label}</p>
            <div className="flex items-center gap-1.5 text-[#0f1c2e] text-sm font-medium">
              <span className="text-[#6b7c96]">{r.icon}</span>{r.value}
            </div>
          </div>
        ))}
        <div className="mt-1 bg-[#fef3c7] border border-[#fcd34d] rounded-lg px-3 py-2.5 text-xs text-[#92400e] flex items-start gap-2">
          <Info size={12} className="shrink-0 mt-0.5"/>
          <span>Students are <strong>busy</strong> here. Free slots shown as empty cells.</span>
        </div>
      </div>
    </div>
  ) : null;

  const rightPanel = panel !== null;

  return (
    <div className="flex flex-col h-full relative">
      {/* ── Toast ── */}
      {toast && (
        <div className="absolute top-4 right-4 z-50 bg-[#0f1c2e] text-white text-xs font-semibold px-4 py-2.5 rounded-xl shadow-xl flex items-center gap-2">
          <CheckCircle size={13} className="text-[#22c55e]"/> {toast}
        </div>
      )}

      {/* ── Header ── */}
      <div className="bg-white border-b border-[#dde3ee] px-5 py-3 flex items-center gap-3 shrink-0 flex-wrap">
        {/* View toggle */}
        <SegControl
          options={["mine", "student"] as const}
          labels={{ mine: "My Timetable", student: "Student Timetable" }}
          value={view}
          onChange={v => { setView(v); setDetailSession(null); setDetailStudent(null); setPanel(null); clearSelection(); }}
        />

        {/* Week nav (My view only) */}
        {view === "mine" && (
          <div className="flex items-center gap-2 bg-[#f4f6f9] rounded-xl border border-[#dde3ee] px-2 py-1.5">
            <button onClick={() => setWeekOff(p => p - 1)}
              className="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-[#dde3ee] text-[#6b7c96] transition-colors">
              <ChevronLeft size={14}/>
            </button>
            <span className="text-[12px] font-semibold text-[#0f1c2e] whitespace-nowrap min-w-[148px] text-center">
              {fmtWeekRange(weekOffset)}
            </span>
            <button onClick={() => setWeekOff(p => p + 1)}
              className="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-[#dde3ee] text-[#6b7c96] transition-colors">
              <ChevronRight size={14}/>
            </button>
            {weekOffset !== 0 && (
              <button onClick={() => setWeekOff(0)}
                className="text-[10.5px] text-[#2563eb] font-semibold hover:underline whitespace-nowrap">Today</button>
            )}
          </div>
        )}

        {/* Student filters */}
        {view === "student" && (
          <div className="flex items-center gap-2">
            <SegControl options={["CS","IS"] as const} value={degree} onChange={d => { setDegree(d); setDetailStudent(null); }}/>
            <SegControl options={["Y1","Y2","Y3","Y4"] as const} value={year} onChange={y => { setYear(y); setDetailStudent(null); }}/>
          </div>
        )}

        {/* Select-slots mode toggle (My view only) */}
        {view === "mine" && (
          <>
            <button onClick={() => { setMode(m => m === "select" ? "view" : "select"); setPanel(null); setDetailSession(null); }}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[12px] font-semibold border transition-colors ${
                mode === "select"
                  ? "bg-[#1a3a6b] text-white border-[#1a3a6b]"
                  : "bg-white text-[#1a3a6b] border-[#dde3ee] hover:bg-[#eef1f6]"}`}>
              <Layers size={13}/> {mode === "select" ? "Cancel Selection" : "Select Slots"}
            </button>
            <button onClick={() => { setPanel(p => p === "myRequests" ? null : "myRequests"); setDetailSession(null); }}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[12px] font-semibold border transition-colors relative ${
                panel === "myRequests" ? "bg-[#fff7ed] text-[#9a3412] border-[#f97316]" : "bg-white text-[#6b7c96] border-[#dde3ee] hover:bg-[#f4f6f9]"}`}>
              <ListChecks size={13}/> My Requests
              {staffRequests.filter(r => r.status === "pending").length > 0 && (
                <span className="w-4 h-4 bg-[#f97316] text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                  {staffRequests.filter(r => r.status === "pending").length}
                </span>
              )}
            </button>
          </>
        )}

        {/* Legend */}
        <div className="ml-auto flex items-center gap-3 text-[11px] font-medium shrink-0">
          {view === "student" && (
            <span className="flex items-center gap-1 text-[#92400e]">
              <Star size={9} className="fill-[#f59e0b] text-[#f59e0b]"/> Your courses
            </span>
          )}
          {(Object.entries(SESSION_STYLE) as [SessionType, typeof SESSION_STYLE[SessionType]][]).map(([t, s]) => (
            <span key={t} className="flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-sm inline-block" style={{ background: s.border }}/>
              <span className="text-[#6b7c96]">{s.label}</span>
            </span>
          ))}
        </div>
      </div>

      {/* ── Free-slot banner (student view) ── */}
      {view === "student" && (
        <div className="bg-[#eff6ff] border-b border-[#bfdbfe] px-5 py-2 flex items-center gap-5 shrink-0 overflow-x-auto">
          <p className="text-[11px] font-semibold text-[#1e40af] whitespace-nowrap shrink-0">Free time · {degree} {year}</p>
          <div className="flex gap-3">
            {DAYS.map((d, i) => (
              <div key={d} className="flex items-center gap-1 whitespace-nowrap">
                <span className="text-[11px] font-medium text-[#6b7c96]">{d.slice(0,3)}</span>
                <span className={`text-[11px] font-bold ${freeInfo[i] >= 4 ? "text-[#22c55e]" : freeInfo[i] >= 2 ? "text-[#f59e0b]" : "text-[#ef4444]"}`}>{freeInfo[i]}h</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ── TC pick-mode banner ── */}
      {tcPickMode && (
        <div className="bg-[#1a3a6b] px-5 py-2.5 flex items-center gap-3 shrink-0">
          <CalendarDays size={14} className="text-[#bedbff] shrink-0"/>
          <p className="text-white text-sm font-semibold flex-1">
            Click free slots to select · click again to deselect
            {tcSlots.size > 0 && <span className="text-[#bedbff] font-normal"> · {tcSlots.size} selected</span>}
          </p>
          <button onClick={() => setTcPickMode(false)}
            className="bg-white text-[#1a3a6b] text-xs font-bold px-3 py-1.5 rounded-lg hover:bg-[#eef1f6] transition-colors">
            Done
          </button>
        </div>
      )}

      {/* ── Selection action bar ── */}
      {mode === "select" && slotCount > 0 && (
        <div className="bg-[#1a3a6b] px-5 py-2.5 flex items-center gap-3 shrink-0">
          <p className="text-white text-sm font-semibold">{slotCount} slot{slotCount !== 1 ? "s" : ""} selected</p>
          <p className="text-[#bedbff] text-xs flex-1 truncate">{slotLabel}</p>
          <button onClick={() => { setPanel("assignment"); setDetailSession(null); }}
            className="flex items-center gap-1.5 bg-white text-[#1a3a6b] text-xs font-bold px-3 py-1.5 rounded-lg hover:bg-[#eef1f6] transition-colors">
            <Layers size={12}/> Create Assignment
          </button>
          <button onClick={clearSelection} className="text-[#bedbff] hover:text-white text-xs font-semibold">Clear</button>
        </div>
      )}

      {/* ── Body ── */}
      <div className="flex flex-1 min-h-0">
        {/* Calendar */}
        <div className="flex-1 overflow-auto">
          {view === "mine" ? (
            <CalendarGrid
              sessions={allSessions as GridSession[]}
              selectedId={detailSession?.id ?? null}
              onSelect={s => { if (mode === "view" && !tcPickMode) { setDetailSession(s as Session); setPanel(null); } }}
              weekDates={weekDates}
              selectMode={tcPickMode || mode === "select"}
              selectedSlots={tcPickMode ? tcSlots : selectedSlots}
              onSlotClick={tcPickMode ? (day, hour) => toggleTcSlot(`${day}-${hour}`) : toggleSlot}
            />
          ) : (
            <CalendarGrid
              sessions={studentSessions}
              selectedId={detailStudent?.id ?? null}
              onSelect={s => { setDetailStudent(s); }}
              weekDates={weekDates}
              highlightCodes={MY_CODES}
            />
          )}
        </div>

        {/* Right panels – only one visible at a time */}
        {view === "mine" && !rightPanel && <MyDetailDrawer />}
        {view === "student" && !rightPanel && <StudentDetailDrawer />}
        {rightPanel && (
          <div className="w-80 shrink-0 border-l border-[#dde3ee] bg-white flex flex-col overflow-hidden">
            {panel === "assignment"  && <PanelAssignment />}
            {panel === "staffReq"   && <PanelStaffReq />}
            {panel === "timeChange" && <PanelTimeChange />}
            {panel === "myRequests" && <PanelMyRequests />}
          </div>
        )}
      </div>

      {/* Footer hint */}
      <div className="text-center text-xs text-[#9ca3af] py-1.5 bg-white border-t border-[#dde3ee] shrink-0">
        {view === "mine"
          ? mode === "select"
            ? "Click empty cells to select time slots · click again to deselect"
            : "Click a session for details · use Select Slots to create assignments or request staff"
          : `${degree} ${year} schedule · ★ = your courses · empty cells = students are free`}
      </div>
    </div>
  );
}

// ── Workload ───────────────────────────────────────────────────────────────
type AssignmentStatus = "accepted" | "declined" | "pending" | "completed";
interface Assignment {
  id: number; code: string; name: string; type: string;
  batch: string; hours: number; status: AssignmentStatus;
  assignedBy: string; assignedDate: string;
}

const INIT_ASSIGNMENTS: Assignment[] = [
  { id:1, code:"CS3401", name:"Fundamentals of Computing Lab",    type:"Lab Supervisor",       batch:"Y1 CS", hours:4, status:"accepted",  assignedBy:"Dr. Perera",  assignedDate:"2 Jul 2025" },
  { id:2, code:"IT2301", name:"Web Technologies Practical",       type:"Practical Supervisor", batch:"Y2 IT", hours:3, status:"accepted",  assignedBy:"Prof. Silva", assignedDate:"2 Jul 2025" },
  { id:3, code:"CS2201", name:"Database Systems Lab",             type:"Lab Supervisor",       batch:"Y3 CS", hours:4, status:"pending",   assignedBy:"Dr. Perera",  assignedDate:"8 Jul 2025" },
  { id:4, code:"IT3201", name:"Network Administration Practical", type:"Practical Supervisor", batch:"Y3 IT", hours:2, status:"pending",   assignedBy:"Prof. Silva", assignedDate:"9 Jul 2025" },
  { id:5, code:"CS3402", name:"Data Structures Lab",              type:"Lab Supervisor",       batch:"Y2 CS", hours:4, status:"completed", assignedBy:"Dr. Perera",  assignedDate:"10 Jun 2025" },
];

function WorkloadScreen() {
  const [assignments] = useState<Assignment[]>(INIT_ASSIGNMENTS);
  const [coverRequests, setCoverRequests] = useState([
    { id: 101, staffOnLeave: "Dr. N. Perera",  task: "Database Systems Lecture – CS2201", date: "2025-07-21", hours: 2, responded: false, accepted: false },
    { id: 102, staffOnLeave: "Prof. A. Silva", task: "Network Admin Practical – IT3201",   date: "2025-07-23", hours: 3, responded: false, accepted: false },
  ]);
  const respondCover = (id: number, accepted: boolean) =>
    setCoverRequests(prev => prev.map(r => r.id === id ? { ...r, responded: true, accepted } : r));

  // Filter state
  const [fSemester, setFSemester] = useState("");
  const [fMonth,    setFMonth]    = useState("");
  const [fWeek,     setFWeek]     = useState("");
  // History filter state
  const [hSemester, setHSemester] = useState("");
  const [hMonth,    setHMonth]    = useState("");
  const [hWeek,     setHWeek]     = useState("");
  const [hType,     setHType]     = useState("");

  const SEMESTER_OPTIONS = ["Semester 1 - 2026", "Semester 2 - 2025", "Semester 1 - 2025"];
  const MONTH_OPTIONS    = ["March 2026", "February 2026", "January 2026", "December 2025", "November 2025"];
  const WEEK_OPTIONS     = ["Week 5 (Mar 3–7)", "Week 4 (Feb 24–28)", "Week 3 (Feb 17–21)", "Week 2 (Feb 10–14)", "Week 1 (Feb 3–7)"];
  const TYPE_OPTIONS     = ["Lab Supervision", "Lecture", "Practical", "Review"];

  // Static workload data
  const SEMESTER_DATA = [
    { period: "Semester 1 - 2026", assigned: 320, completed: 248 },
    { period: "Semester 2 - 2025", assigned: 280, completed: 280 },
    { period: "Semester 1 - 2025", assigned: 300, completed: 290 },
  ];
  const MONTHLY_DATA = [
    { period: "March 2026",    assigned: 42, completed: 39 },
    { period: "February 2026", assigned: 38, completed: 35 },
    { period: "January 2026",  assigned: 36, completed: 33 },
  ];
  const WEEKLY_DATA = [
    { period: "Week 5", assigned: 12, completed: 10 },
    { period: "Week 4", assigned: 11, completed: 11 },
    { period: "Week 3", assigned: 10, completed:  9 },
  ];
  const WORK_HISTORY = [
    { date: "2026-03-10", task: "Database Lab",       code: "CS2201", hours: 3, type: "Lab Supervision", sem: "Semester 1 - 2026", month: "March 2026",    week: "Week 5" },
    { date: "2026-03-07", task: "Lecture Session",    code: "CS3401", hours: 2, type: "Lecture",         sem: "Semester 1 - 2026", month: "March 2026",    week: "Week 5" },
    { date: "2026-03-02", task: "Project Review",     code: "CS3402", hours: 4, type: "Review",          sem: "Semester 1 - 2026", month: "March 2026",    week: "Week 4" },
    { date: "2026-02-28", task: "Lab Supervision",    code: "IT2301", hours: 3, type: "Lab Supervision", sem: "Semester 1 - 2026", month: "February 2026", week: "Week 4" },
    { date: "2026-02-21", task: "Practical Session",  code: "IT3201", hours: 2, type: "Practical",       sem: "Semester 1 - 2026", month: "February 2026", week: "Week 3" },
    { date: "2026-01-15", task: "Data Structures Lab",code: "CS3402", hours: 4, type: "Lab Supervision", sem: "Semester 1 - 2026", month: "January 2026",  week: "Week 2" },
  ];

  // Derive current-period values for the overview cards
  const curSem   = SEMESTER_DATA[0];
  const curMonth = MONTHLY_DATA[0];
  const curWeek  = WEEKLY_DATA[0];

  // Filter overview data
  const filtSemesters = fSemester ? SEMESTER_DATA.filter(r => r.period === fSemester) : SEMESTER_DATA;
  const filtMonths    = fMonth    ? MONTHLY_DATA.filter(r => r.period === fMonth)     : MONTHLY_DATA;
  const filtWeeks     = fWeek     ? WEEKLY_DATA.filter(r => r.period === fWeek)       : WEEKLY_DATA;

  // Filter history
  const filtHistory = WORK_HISTORY.filter(h => {
    if (hSemester && h.sem   !== hSemester) return false;
    if (hMonth    && h.month !== hMonth)    return false;
    if (hWeek     && h.week  !== hWeek)     return false;
    if (hType     && h.type  !== hType)     return false;
    return true;
  });

  const resetFilters = () => { setFSemester(""); setFMonth(""); setFWeek(""); };

  const pct = (c: number, a: number) => Math.round((c / a) * 100);

  const assignedWork = assignments.filter(a => a.status !== "declined" && a.status !== "completed");
  const totalAssignedHrs = assignedWork.reduce((s, a) => s + a.hours, 0);

  // Tab + expand state
  const [wTab,        setWTab]        = useState<"overview" | "assigned" | "history">("overview");
  const [expandSem,   setExpandSem]   = useState(true);
  const [expandMonth, setExpandMonth] = useState(true);
  const [expandWeek,  setExpandWeek]  = useState(true);

  // Shared select style
  const selCls = "pl-3 pr-7 py-1.5 rounded-lg border border-[#dde3ee] bg-white text-xs text-[#0f1c2e] focus:outline-none focus:border-[#1a3a6b] focus:ring-2 focus:ring-[#1a3a6b]/10 transition-all appearance-none cursor-pointer";

  return (
    <div className="flex flex-col h-full overflow-y-auto">
      {/* ── Page header ──────────────────────────────────────────────────── */}
      

      {/* ── Tab bar ─────────────────────────────────────────────────────── */}
      <div className="bg-white border-b border-[#dde3ee] px-6 shrink-0 flex items-end gap-0">
        {([
          { key: "overview", label: "Overview",      icon: <BarChart2 size={14}/> },
          { key: "assigned", label: "Assigned Work", icon: <Layers size={14}/> },
          { key: "history",  label: "History",       icon: <ListChecks size={14}/> },
        ] as const).map(t => (
          <button key={t.key} onClick={() => setWTab(t.key)}
            className={`flex items-center gap-1.5 px-5 py-3 text-xs font-bold border-b-2 transition-all ${
              wTab === t.key
                ? "border-[#1a3a6b] text-[#1a3a6b]"
                : "border-transparent text-[#9ca3af] hover:text-[#6b7c96] hover:border-[#dde3ee]"
            }`}>
            {t.icon}{t.label}
            {t.key === "assigned" && coverRequests.some(r => !r.responded) && (
              <span className="w-2 h-2 rounded-full bg-[#f59e0b] inline-block ml-0.5"/>
            )}
          </button>
        ))}
      </div>

      <div className="p-6 flex flex-col gap-5">

        {/* ══════════════════ OVERVIEW TAB ══════════════════ */}
        {wTab === "overview" && (<>

        {/* ── Filter bar ───────────────────────────────────────────────────── */}
        <div className="bg-white rounded-xl border border-[#dde3ee] px-5 py-3 flex items-center gap-3 flex-wrap">
          <div className="flex items-center gap-2 shrink-0">
            <BarChart2 size={14} className="text-[#1a3a6b]"/>
            <span className="text-xs font-bold text-[#0f1c2e]">Filter Workload</span>
          </div>
          <div className="w-px h-5 bg-[#dde3ee] shrink-0"/>
          <button onClick={resetFilters}
            className="flex items-center gap-1 text-[10.5px] font-bold text-[#6b7c96] hover:text-[#0f1c2e] border border-[#dde3ee] hover:border-[#c5d0e6] bg-[#f4f6f9] hover:bg-[#eef1f6] px-2.5 py-1.5 rounded-lg transition-all shrink-0">
            <X size={10}/> Reset
          </button>
          <div className="w-px h-5 bg-[#dde3ee] shrink-0"/>
          {/* Semester filter */}
          <div className="flex items-center gap-2">
            <span className="text-[10.5px] font-bold uppercase tracking-wide text-[#9ca3af] shrink-0">Semester</span>
            <div className="relative">
              <select value={fSemester} onChange={e => setFSemester(e.target.value)} className={selCls}>
                <option value="">Select…</option>
                {SEMESTER_OPTIONS.map(o => <option key={o}>{o}</option>)}
              </select>
              <ChevronDown size={11} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
            </div>
          </div>
          {/* Month filter */}
          <div className="flex items-center gap-2">
            <span className="text-[10.5px] font-bold uppercase tracking-wide text-[#9ca3af] shrink-0">Month</span>
            <div className="relative">
              <select value={fMonth} onChange={e => setFMonth(e.target.value)} className={selCls}>
                <option value="">Select…</option>
                {MONTH_OPTIONS.map(o => <option key={o}>{o}</option>)}
              </select>
              <ChevronDown size={11} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
            </div>
          </div>
          {/* Week filter */}
          <div className="flex items-center gap-2">
            <span className="text-[10.5px] font-bold uppercase tracking-wide text-[#9ca3af] shrink-0">Week</span>
            <div className="relative">
              <select value={fWeek} onChange={e => setFWeek(e.target.value)} className={selCls}>
                <option value="">Select…</option>
                {WEEK_OPTIONS.map(o => <option key={o}>{o}</option>)}
              </select>
              <ChevronDown size={11} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
            </div>
          </div>
          {(fSemester || fMonth || fWeek) && (
            <span className="ml-auto text-[10.5px] text-[#1a3a6b] font-semibold bg-[#e8edf5] px-2 py-0.5 rounded-full">Filtered</span>
          )}
        </div>

        {/* ── Overview cards — 3 in a row ──────────────────────────────────── */}
        <div className="grid grid-cols-3 gap-4">
          {/* Semester Workload */}
          <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
            <div className="px-5 py-3 border-b border-[#dde3ee] bg-[#f4f7fc] flex items-center gap-2">
              <div className="w-7 h-7 rounded-lg bg-[#e8edf5] flex items-center justify-center">
                <BookOpen size={14} className="text-[#1a3a6b]"/>
              </div>
              <div>
                <p className="text-[10.5px] font-bold uppercase tracking-wide text-[#6b7c96]">Semester Workload</p>
                <p className="text-[10px] text-[#9ca3af]">{fSemester || curSem.period}</p>
              </div>
            </div>
            <div className="px-5 py-4 flex gap-4">
              <div className="flex-1 text-center">
                <p className="text-[10px] font-bold uppercase tracking-wide text-[#9ca3af] mb-1">Assigned</p>
                <p className="font-['JetBrains_Mono',monospace] font-bold text-2xl text-[#1a3a6b]">{fSemester ? (filtSemesters[0]?.assigned ?? curSem.assigned) : curSem.assigned}</p>
                <p className="text-[10px] text-[#9ca3af] mt-0.5">hrs</p>
              </div>
              <div className="w-px bg-[#dde3ee] self-stretch shrink-0"/>
              <div className="flex-1 text-center">
                <p className="text-[10px] font-bold uppercase tracking-wide text-[#9ca3af] mb-1">Completed</p>
                <p className="font-['JetBrains_Mono',monospace] font-bold text-2xl text-[#0f766e]">{fSemester ? (filtSemesters[0]?.completed ?? curSem.completed) : curSem.completed}</p>
                <p className="text-[10px] text-[#9ca3af] mt-0.5">hrs</p>
              </div>
            </div>
            <div className="px-5 pb-4">
              <div className="h-1.5 rounded-full bg-[#eef1f6] overflow-hidden">
                <div className="h-full rounded-full bg-[#1a3a6b]" style={{ width: `${pct(fSemester ? (filtSemesters[0]?.completed ?? curSem.completed) : curSem.completed, fSemester ? (filtSemesters[0]?.assigned ?? curSem.assigned) : curSem.assigned)}%` }}/>
              </div>
              <p className="text-[10px] text-[#9ca3af] mt-1">{pct(fSemester ? (filtSemesters[0]?.completed ?? curSem.completed) : curSem.completed, fSemester ? (filtSemesters[0]?.assigned ?? curSem.assigned) : curSem.assigned)}% completed</p>
            </div>
          </div>

          {/* Monthly Workload */}
          <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
            <div className="px-5 py-3 border-b border-[#dde3ee] bg-[#f7f4fd] flex items-center gap-2">
              <div className="w-7 h-7 rounded-lg bg-[#ede9fe] flex items-center justify-center">
                <CalendarDays size={14} className="text-[#7c3aed]"/>
              </div>
              <div>
                <p className="text-[10.5px] font-bold uppercase tracking-wide text-[#6b7c96]">Monthly Workload</p>
                <p className="text-[10px] text-[#9ca3af]">{fMonth || curMonth.period}</p>
              </div>
            </div>
            <div className="px-5 py-4 flex gap-4">
              <div className="flex-1 text-center">
                <p className="text-[10px] font-bold uppercase tracking-wide text-[#9ca3af] mb-1">Assigned</p>
                <p className="font-['JetBrains_Mono',monospace] font-bold text-2xl text-[#7c3aed]">{fMonth ? (filtMonths[0]?.assigned ?? curMonth.assigned) : curMonth.assigned}</p>
                <p className="text-[10px] text-[#9ca3af] mt-0.5">hrs</p>
              </div>
              <div className="w-px bg-[#dde3ee] self-stretch shrink-0"/>
              <div className="flex-1 text-center">
                <p className="text-[10px] font-bold uppercase tracking-wide text-[#9ca3af] mb-1">Completed</p>
                <p className="font-['JetBrains_Mono',monospace] font-bold text-2xl text-[#0f766e]">{fMonth ? (filtMonths[0]?.completed ?? curMonth.completed) : curMonth.completed}</p>
                <p className="text-[10px] text-[#9ca3af] mt-0.5">hrs</p>
              </div>
            </div>
            <div className="px-5 pb-4">
              <div className="h-1.5 rounded-full bg-[#eef1f6] overflow-hidden">
                <div className="h-full rounded-full bg-[#7c3aed]" style={{ width: `${pct(fMonth ? (filtMonths[0]?.completed ?? curMonth.completed) : curMonth.completed, fMonth ? (filtMonths[0]?.assigned ?? curMonth.assigned) : curMonth.assigned)}%` }}/>
              </div>
              <p className="text-[10px] text-[#9ca3af] mt-1">{pct(fMonth ? (filtMonths[0]?.completed ?? curMonth.completed) : curMonth.completed, fMonth ? (filtMonths[0]?.assigned ?? curMonth.assigned) : curMonth.assigned)}% completed</p>
            </div>
          </div>

          {/* Weekly Workload */}
          <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
            <div className="px-5 py-3 border-b border-[#dde3ee] bg-[#fffbf0] flex items-center gap-2">
              <div className="w-7 h-7 rounded-lg bg-[#fef3c7] flex items-center justify-center">
                <Clock size={14} className="text-[#b45309]"/>
              </div>
              <div>
                <p className="text-[10.5px] font-bold uppercase tracking-wide text-[#6b7c96]">Weekly Workload</p>
                <p className="text-[10px] text-[#9ca3af]">{fWeek || curWeek.period}</p>
              </div>
            </div>
            <div className="px-5 py-4 flex gap-4">
              <div className="flex-1 text-center">
                <p className="text-[10px] font-bold uppercase tracking-wide text-[#9ca3af] mb-1">Assigned</p>
                <p className="font-['JetBrains_Mono',monospace] font-bold text-2xl text-[#b45309]">{fWeek ? (filtWeeks[0]?.assigned ?? curWeek.assigned) : curWeek.assigned}</p>
                <p className="text-[10px] text-[#9ca3af] mt-0.5">hrs</p>
              </div>
              <div className="w-px bg-[#dde3ee] self-stretch shrink-0"/>
              <div className="flex-1 text-center">
                <p className="text-[10px] font-bold uppercase tracking-wide text-[#9ca3af] mb-1">Completed</p>
                <p className="font-['JetBrains_Mono',monospace] font-bold text-2xl text-[#0f766e]">{fWeek ? (filtWeeks[0]?.completed ?? curWeek.completed) : curWeek.completed}</p>
                <p className="text-[10px] text-[#9ca3af] mt-0.5">hrs</p>
              </div>
            </div>
            <div className="px-5 pb-4">
              <div className="h-1.5 rounded-full bg-[#eef1f6] overflow-hidden">
                <div className="h-full rounded-full bg-[#b45309]" style={{ width: `${pct(fWeek ? (filtWeeks[0]?.completed ?? curWeek.completed) : curWeek.completed, fWeek ? (filtWeeks[0]?.assigned ?? curWeek.assigned) : curWeek.assigned)}%` }}/>
              </div>
              <p className="text-[10px] text-[#9ca3af] mt-1">{pct(fWeek ? (filtWeeks[0]?.completed ?? curWeek.completed) : curWeek.completed, fWeek ? (filtWeeks[0]?.assigned ?? curWeek.assigned) : curWeek.assigned)}% completed</p>
            </div>
          </div>
        </div>

        {/* ── Expandable breakdown sections ────────────────────────────────── */}
        {([
          { title: "Semester Breakdown", icon: <BookOpen size={13}/>,     color: "#1a3a6b", hdr: "Semester", data: filtSemesters, open: expandSem,   setOpen: setExpandSem   },
          { title: "Monthly Breakdown",  icon: <CalendarDays size={13}/>, color: "#7c3aed", hdr: "Month",    data: filtMonths,    open: expandMonth, setOpen: setExpandMonth },
          { title: "Weekly Breakdown",   icon: <Clock size={13}/>,        color: "#b45309", hdr: "Week",     data: filtWeeks,     open: expandWeek,  setOpen: setExpandWeek  },
        ] as const).map(tbl => (
          <div key={tbl.title} className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
            <button onClick={() => tbl.setOpen(!tbl.open)}
              className="w-full px-5 py-3 flex items-center gap-2 hover:bg-[#f9fafb] transition-colors text-left">
              <span style={{ color: tbl.color }}>{tbl.icon}</span>
              <p className="text-sm font-bold text-[#0f1c2e]">{tbl.title}</p>
              <span className="ml-auto text-[10.5px] text-[#9ca3af] mr-2">{tbl.data.length} period{tbl.data.length !== 1 ? "s" : ""}</span>
              {tbl.open ? <ChevronUp size={14} className="text-[#9ca3af] shrink-0"/> : <ChevronDown size={14} className="text-[#9ca3af] shrink-0"/>}
            </button>
            {tbl.open && (
              <div className="border-t border-[#dde3ee]">
                <table className="w-full">
                  <thead>
                    <tr className="text-[10.5px] font-bold uppercase tracking-wider text-[#9ca3af] border-b border-[#dde3ee] bg-[#f9fafb]">
                      <th className="text-left px-5 py-2.5">{tbl.hdr}</th>
                      <th className="text-right px-5 py-2.5">Assigned</th>
                      <th className="text-right px-5 py-2.5">Completed</th>
                      <th className="text-right px-5 py-2.5 w-32">Progress</th>
                    </tr>
                  </thead>
                  <tbody>
                    {tbl.data.map((row, i) => {
                      const p = pct(row.completed, row.assigned);
                      return (
                        <tr key={row.period} className={`border-b border-[#dde3ee] last:border-0 hover:bg-[#f4f6f9] transition-colors ${i % 2 === 1 ? "bg-[#fafbfd]" : ""}`}>
                          <td className="px-5 py-3 text-xs font-semibold text-[#0f1c2e]">{row.period}</td>
                          <td className="px-5 py-3 text-right">
                            <span className="font-['JetBrains_Mono',monospace] text-xs font-bold text-[#1a3a6b]">{row.assigned} hrs</span>
                          </td>
                          <td className="px-5 py-3 text-right">
                            <span className="font-['JetBrains_Mono',monospace] text-xs font-bold text-[#0f766e]">{row.completed} hrs</span>
                          </td>
                          <td className="px-5 py-3">
                            <div className="flex items-center gap-2 justify-end">
                              <div className="w-20 h-1.5 rounded-full bg-[#eef1f6] overflow-hidden">
                                <div className="h-full rounded-full bg-[#0f766e]" style={{ width: `${p}%` }}/>
                              </div>
                              <span className="text-[10px] font-bold text-[#6b7c96] w-8 text-right">{p}%</span>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                  <tfoot>
                    <tr className="bg-[#f4f6f9] border-t-2 border-[#dde3ee]">
                      <td className="px-5 py-2.5 text-[10.5px] font-bold text-[#6b7c96] uppercase tracking-wide">Total</td>
                      <td className="px-5 py-2.5 text-right font-['JetBrains_Mono',monospace] text-xs font-bold text-[#1a3a6b]">{tbl.data.reduce((s,r)=>s+r.assigned,0)} hrs</td>
                      <td className="px-5 py-2.5 text-right font-['JetBrains_Mono',monospace] text-xs font-bold text-[#0f766e]">{tbl.data.reduce((s,r)=>s+r.completed,0)} hrs</td>
                      <td className="px-5 py-2.5 text-right text-[10px] font-bold text-[#6b7c96]">
                        {tbl.data.length ? pct(tbl.data.reduce((s,r)=>s+r.completed,0), tbl.data.reduce((s,r)=>s+r.assigned,0)) : 0}% avg
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            )}
          </div>
        ))}

        </>)}
        {/* ══════════════════ END OVERVIEW TAB ══════════════════ */}

        {/* ══════════════════ ASSIGNED WORK TAB ══════════════════ */}
        {wTab === "assigned" && (<>

        {/* Cover Staff Requests */}
        {coverRequests.some(r => !r.responded) && (
          <div className="bg-white rounded-xl border border-[#f59e0b]/40 overflow-hidden shadow-sm">
            <div className="px-5 py-3 border-b border-[#fde68a] bg-[#fffbeb] flex items-center gap-2">
              <div className="w-2 h-2 rounded-full bg-[#f59e0b] animate-pulse"/>
              <p className="text-sm font-bold text-[#0f1c2e]">Cover Staff Requests</p>
              <span className="ml-1 px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-[#fef3c7] text-[#92400e]">
                {coverRequests.filter(r => !r.responded).length} pending
              </span>
              <p className="ml-auto text-[10.5px] text-[#9ca3af]">A colleague is on leave — please respond</p>
            </div>
            <div className="divide-y divide-[#fde68a]/60">
              {coverRequests.filter(r => !r.responded).map(r => (
                <div key={r.id} className="px-5 py-4 flex items-center gap-4 bg-[#fffdf5]">
                  <div className="w-10 h-10 rounded-full bg-[#fef3c7] flex items-center justify-center shrink-0 border border-[#fde68a]">
                    <UserCheck size={17} className="text-[#b45309]"/>
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-bold text-[#0f1c2e]">{r.task}</p>
                    <div className="flex items-center gap-3 mt-1 flex-wrap">
                      <span className="text-[10.5px] text-[#6b7c96]">Staff on leave: <span className="font-semibold text-[#92400e]">{r.staffOnLeave}</span></span>
                      <span className="text-[#dde3ee]">·</span>
                      <span className="text-[10.5px] text-[#6b7c96]">Date: <span className="font-semibold text-[#0f1c2e]">{r.date}</span></span>
                      <span className="text-[#dde3ee]">·</span>
                      <span className="text-[10.5px] text-[#6b7c96]">Duration: <span className="font-semibold text-[#0f1c2e]">{r.hours} hrs</span></span>
                    </div>
                  </div>
                  <div className="flex gap-2 shrink-0">
                    <button onClick={() => respondCover(r.id, true)}
                      className="px-4 py-1.5 bg-[#d0fae5] hover:bg-[#a7f3d0] text-[#004f3b] text-[10.5px] font-bold rounded-lg transition-colors flex items-center gap-1.5 border border-[#6ee7b7]">
                      <Check size={11}/> Accept
                    </button>
                    <button onClick={() => respondCover(r.id, false)}
                      className="px-4 py-1.5 bg-[#fee2e2] hover:bg-[#fecaca] text-[#991b1b] text-[10.5px] font-bold rounded-lg transition-colors flex items-center gap-1.5 border border-[#fca5a5]">
                      <X size={11}/> Reject
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
        {coverRequests.some(r => r.responded) && (
          <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden divide-y divide-[#dde3ee]">
            {coverRequests.filter(r => r.responded).map(r => (
              <div key={r.id} className="px-5 py-3 flex items-center gap-3">
                <div className={`w-2 h-2 rounded-full shrink-0 ${r.accepted ? "bg-[#22c55e]" : "bg-[#ef4444]"}`}/>
                <p className="text-xs text-[#6b7c96] flex-1">{r.task} — {r.date}</p>
                <span className={`px-2 py-0.5 rounded-full text-[10.5px] font-bold ${r.accepted ? "bg-[#d0fae5] text-[#004f3b]" : "bg-[#fee2e2] text-[#991b1b]"}`}>
                  {r.accepted ? "Cover Accepted" : "Cover Rejected"}
                </span>
              </div>
            ))}
          </div>
        )}

        {/* Regular Assigned Work */}
        <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
          <div className="px-5 py-3 border-b border-[#dde3ee] flex items-center gap-3">
            <Layers size={15} className="text-[#1a3a6b]"/>
            <p className="text-sm font-bold text-[#0f1c2e]">Assigned Work</p>
            <span className="text-[10.5px] text-[#9ca3af]">All auto-accepted · included in workload total</span>
            <div className="ml-auto flex items-center gap-1.5">
              <span className="text-[10.5px] text-[#6b7c96]">This week:</span>
              <span className="font-['JetBrains_Mono',monospace] font-bold text-sm text-[#1a3a6b]">{totalAssignedHrs} hrs</span>
            </div>
          </div>
          <div className="p-4 flex flex-col gap-2.5">
            {assignedWork.map(a => (
              <div key={a.id} className="flex items-center gap-4 px-4 py-3.5 rounded-xl bg-[#f4f6f9] border border-[#dde3ee] hover:border-[#c5d0e6] hover:bg-[#eef1f6] transition-all">
                <div className="w-9 h-9 rounded-lg bg-[#e8edf5] flex items-center justify-center shrink-0">
                  <BookOpen size={15} className="text-[#1a3a6b]"/>
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-0.5">
                    <span className="font-['JetBrains_Mono',monospace] font-bold text-[10.5px] text-[#1a3a6b]">{a.code}</span>
                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#dbeafe] text-[#1c398e]">ASSIGNED</span>
                  </div>
                  <p className="text-xs font-semibold text-[#0f1c2e] truncate">{a.name}</p>
                  <p className="text-[10.5px] text-[#9ca3af] mt-0.5">{a.type} · {a.batch} · {a.assignedBy}</p>
                </div>
                <div className="text-right shrink-0">
                  <p className="font-['JetBrains_Mono',monospace] font-bold text-xl text-[#1a3a6b]">{a.hours}<span className="text-xs font-medium text-[#9ca3af] ml-0.5">h/wk</span></p>
                  <p className="text-[10px] text-[#9ca3af] mt-0.5">{a.assignedDate}</p>
                </div>
              </div>
            ))}
          </div>
          <div className="px-5 py-2.5 border-t border-[#dde3ee] bg-[#f9fafb] flex items-center gap-2">
            <Info size={12} className="text-[#9ca3af]"/>
            <p className="text-[10.5px] text-[#9ca3af]">All regular assignments are automatically accepted and count toward your semester workload total.</p>
          </div>
        </div>

        </>)}
        {/* ══════════════════ END ASSIGNED WORK TAB ══════════════════ */}

        {/* ══════════════════ HISTORY TAB ══════════════════ */}
        {wTab === "history" && (<>

        <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
          {/* History filter bar */}
          <div className="px-5 py-3 border-b border-[#dde3ee] flex items-center gap-3 flex-wrap">
            <ListChecks size={15} className="text-[#1a3a6b]"/>
            <p className="text-sm font-bold text-[#0f1c2e]">Work History</p>
            <div className="ml-auto flex items-center gap-2 flex-wrap">
              <div className="relative">
                <select value={hSemester} onChange={e => setHSemester(e.target.value)} className={selCls}>
                  <option value="">Semester</option>
                  {SEMESTER_OPTIONS.map(o => <option key={o}>{o}</option>)}
                </select>
                <ChevronDown size={11} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
              </div>
              <div className="relative">
                <select value={hMonth} onChange={e => setHMonth(e.target.value)} className={selCls}>
                  <option value="">Month</option>
                  {MONTH_OPTIONS.map(o => <option key={o}>{o}</option>)}
                </select>
                <ChevronDown size={11} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
              </div>
              <div className="relative">
                <select value={hWeek} onChange={e => setHWeek(e.target.value)} className={selCls}>
                  <option value="">Week</option>
                  {WEEK_OPTIONS.map(o => <option key={o}>{o}</option>)}
                </select>
                <ChevronDown size={11} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
              </div>
              <div className="relative">
                <select value={hType} onChange={e => setHType(e.target.value)} className={selCls}>
                  <option value="">Work Type</option>
                  {TYPE_OPTIONS.map(o => <option key={o}>{o}</option>)}
                </select>
                <ChevronDown size={11} className="absolute right-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
              </div>
              {(hSemester || hMonth || hWeek || hType) && (
                <button onClick={() => { setHSemester(""); setHMonth(""); setHWeek(""); setHType(""); }}
                  className="text-[10.5px] font-bold text-[#6b7c96] hover:text-[#dc2626] flex items-center gap-0.5 border border-[#dde3ee] px-2 py-1.5 rounded-lg hover:bg-[#fff5f5] hover:border-[#fecaca] transition-colors">
                  <X size={10}/> Clear
                </button>
              )}
            </div>
          </div>
          {/* History table */}
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="text-[10.5px] font-bold uppercase tracking-wider text-[#9ca3af] border-b border-[#dde3ee] bg-[#f9fafb]">
                  {["Date","Task","Course","Type","Hours","Status"].map(h => (
                    <th key={h} className="text-left px-5 py-2.5 font-bold">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filtHistory.length === 0 ? (
                  <tr><td colSpan={6} className="px-5 py-10 text-center text-xs text-[#9ca3af]">No records match the selected filters</td></tr>
                ) : filtHistory.map((h, i) => (
                  <tr key={i} className={`border-b border-[#dde3ee] last:border-0 hover:bg-[#f4f6f9] transition-colors ${i % 2 === 1 ? "bg-[#fafbfd]" : ""}`}>
                    <td className="px-5 py-3 text-[10.5px] font-semibold text-[#6b7c96] whitespace-nowrap">{h.date}</td>
                    <td className="px-5 py-3 text-xs font-semibold text-[#0f1c2e]">{h.task}</td>
                    <td className="px-5 py-3">
                      <span className="font-['JetBrains_Mono',monospace] text-[10.5px] font-bold text-[#1a3a6b]">{h.code}</span>
                    </td>
                    <td className="px-5 py-3 text-[10.5px] text-[#6b7c96]">{h.type}</td>
                    <td className="px-5 py-3">
                      <span className="font-['JetBrains_Mono',monospace] font-bold text-xs text-[#0f1c2e]">{h.hours}</span>
                      <span className="text-[10px] text-[#9ca3af] ml-0.5">hrs</span>
                    </td>
                    <td className="px-5 py-3">
                      <span className="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-[#dbeafe] text-[#1c398e]">Completed</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        </>)}
        {/* ══════════════════ END HISTORY TAB ══════════════════ */}

      </div>
    </div>
  );
}

// ── Supportive Staff Requests ──────────────────────────────────────────────
interface StaffRequest {
  id: number; course: string; type: string; preferred: string;
  notes: string; status: string; date: string;
}

const INIT_REQUESTS: StaffRequest[] = [
  { id:1, course:"CS3401 – Fundamentals of Computing Lab", type:"Lab Assistant",     preferred:"Mr. Kamal", notes:"Needs to set up equipment before session", status:"Approved",  date:"1 Jul 2025" },
  { id:2, course:"IT2301 – Web Technologies Practical",    type:"Technical Support", preferred:"",          notes:"Network configuration required",          status:"Pending",   date:"8 Jul 2025" },
  { id:3, course:"CS2201 – Database Systems Lab",          type:"Lab Assistant",     preferred:"",          notes:"",                                        status:"Rejected",  date:"5 Jun 2025" },
];

function RequestsScreen() {
  const [requests, setRequests] = useState<StaffRequest[]>(INIT_REQUESTS);
  const [showForm, setShowForm] = useState(false);
  const [course, setCourse]     = useState("");
  const [type, setType]         = useState("");
  const [preferred, setPref]    = useState("");
  const [notes, setNotes]       = useState("");

  const submit = () => {
    setRequests(prev => [{
      id: Date.now(), course, type, preferred, notes, status: "Pending", date: "9 Jul 2025"
    }, ...prev]);
    setShowForm(false); setCourse(""); setType(""); setPref(""); setNotes("");
  };

  const supportTypes = ["Lab Assistant","Technical Support","Equipment Setup","IT Support","Administrative Support"];
  const statusStyle: Record<string, { bg: string; text: string }> = {
    Approved: { bg: "#d0fae5", text: "#004f3b" },
    Pending:  { bg: "#fef3c7", text: "#92400e" },
    Rejected: { bg: "#fee2e2", text: "#991b1b" },
  };

  return (
    <div className="flex flex-col h-full overflow-y-auto">
      <div className="bg-white border-b border-[#dde3ee] px-6 py-4 flex items-center justify-between shrink-0">
        <div>
          <h2 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-lg">Supportive Staff Requests</h2>
          <p className="text-xs text-[#9ca3af] mt-0.5">Request additional support for your sessions</p>
        </div>
        <button onClick={() => setShowForm(!showForm)}
          className="flex items-center gap-1.5 bg-[#1a3a6b] hover:bg-[#0f2a55] text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
          <Plus size={15} /> New Request
        </button>
      </div>

      <div className="p-6 flex flex-col gap-5">
        {/* New request form */}
        {showForm && (
          <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
            <div className="px-5 py-3 border-b border-[#dde3ee] bg-[#f4f6f9] flex items-center justify-between">
              <p className="text-sm font-semibold text-[#0f1c2e]">New Support Request</p>
              <button onClick={() => setShowForm(false)} className="text-[#9ca3af] hover:text-[#0f1c2e]"><X size={15}/></button>
            </div>
            <div className="p-5 grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Course / Module</label>
                <input value={course} onChange={e => setCourse(e.target.value)} placeholder="e.g. CS3401 – Fundamentals of Computing Lab"
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Type of Support</label>
                <div className="relative">
                  <select value={type} onChange={e => setType(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all appearance-none">
                    <option value="">Select type</option>
                    {supportTypes.map(t => <option key={t}>{t}</option>)}
                  </select>
                  <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none" />
                </div>
              </div>
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Preferred Staff <span className="normal-case font-normal">(optional)</span></label>
                <input value={preferred} onChange={e => setPref(e.target.value)} placeholder="Staff name if known"
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <div className="col-span-2">
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Notes</label>
                <textarea value={notes} onChange={e => setNotes(e.target.value)} rows={3} placeholder="Describe what support is needed..."
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all resize-none" />
              </div>
              <div className="col-span-2 flex justify-end gap-3">
                <button onClick={() => setShowForm(false)} className="px-4 py-2 text-sm font-semibold text-[#6b7c96] border border-[#dde3ee] rounded-lg hover:bg-[#f4f6f9] transition-colors">Cancel</button>
                <button onClick={submit} disabled={!course || !type}
                  className="px-4 py-2 text-sm font-semibold bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white rounded-lg transition-colors">Submit Request</button>
              </div>
            </div>
          </div>
        )}

        {/* Requests table */}
        <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
          <div className="px-5 py-3 border-b border-[#dde3ee]">
            <p className="text-sm font-semibold text-[#0f1c2e]">My Requests</p>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="text-[10.5px] font-semibold uppercase tracking-wider text-[#6b7c96] border-b border-[#dde3ee]">
                  {["Course / Module","Support Type","Preferred Staff","Notes","Submitted","Status"].map(h => (
                    <th key={h} className="text-left px-4 py-2.5 font-semibold">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {requests.map(r => {
                  const ss = statusStyle[r.status] || { bg: "#eef1f6", text: "#6b7c96" };
                  return (
                    <tr key={r.id} className="border-b border-[#dde3ee] last:border-0 hover:bg-[#f4f6f9] transition-colors">
                      <td className="px-4 py-3 text-sm text-[#0f1c2e] font-medium max-w-[200px]"><span className="line-clamp-2">{r.course}</span></td>
                      <td className="px-4 py-3 text-xs text-[#6b7c96]">{r.type}</td>
                      <td className="px-4 py-3 text-xs text-[#6b7c96]">{r.preferred || "—"}</td>
                      <td className="px-4 py-3 text-xs text-[#6b7c96] max-w-[180px]"><span className="line-clamp-2">{r.notes || "—"}</span></td>
                      <td className="px-4 py-3 text-xs text-[#6b7c96] whitespace-nowrap">{r.date}</td>
                      <td className="px-4 py-3">
                        <span className="px-2 py-0.5 rounded-full text-[10.5px] font-semibold" style={ss}>{r.status}</span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}

// ── Leave ──────────────────────────────────────────────────────────────────
interface LeaveRecord {
  id: number; type: string; dates: string[]; reason: string; cover: string; cancelled: boolean;
}

const INIT_LEAVES: LeaveRecord[] = [
  { id:1, type:"Annual Leave",    dates:["2025-06-20","2025-06-21","2025-06-22","2025-06-23","2025-06-24"], reason:"Family holiday",        cover:"Dr. N. Perera",  cancelled:false },
  { id:2, type:"Medical Leave",   dates:["2025-07-03","2025-07-04"],                                         reason:"Medical appointment",   cover:"",               cancelled:false },
  { id:3, type:"Conference Leave",dates:["2025-08-11","2025-08-12","2025-08-13","2025-08-14"],               reason:"ICCS 2025 Conference",  cover:"Ms. Kumari",     cancelled:false },
];

const LEAVE_COVER_STAFF = ["Dr. N. Perera","Mr. K. Bandara","Prof. A. Silva","Ms. Kumari","Mr. J. Peris","Mr. Perera","Dr. R. Jayawardena","Ms. S. Wijesinghe"];
const LEAVE_TYPES = ["Annual Leave","Medical Leave","Conference Leave","Study Leave","Emergency Leave","Maternity / Paternity Leave"];
const CAL_MONTH_NAMES = ["January","February","March","April","May","June","July","August","September","October","November","December"];
const CAL_DAY_LABELS = ["Mo","Tu","We","Th","Fr","Sa","Su"];

function MiniCalendar({ selectedDates, onToggle, year, month, onPrev, onNext }: {
  selectedDates: string[]; onToggle: (d: string) => void;
  year: number; month: number; onPrev: () => void; onNext: () => void;
}) {
  const TODAY_STR = "2025-07-14";
  const firstDow = new Date(year, month, 1).getDay(); // 0=Sun
  const startOffset = firstDow === 0 ? 6 : firstDow - 1; // Mon-based offset
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const cells: (number | null)[] = [];
  for (let i = 0; i < startOffset; i++) cells.push(null);
  for (let i = 1; i <= daysInMonth; i++) cells.push(i);
  const fmtDate = (d: number) => `${year}-${String(month + 1).padStart(2,"0")}-${String(d).padStart(2,"0")}`;
  return (
    <div className="select-none">
      <div className="flex items-center justify-between mb-3">
        <button onClick={onPrev} className="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-[#eef1f6] transition-colors text-[#6b7c96]">
          <ChevronLeft size={14}/>
        </button>
        <span className="text-xs font-bold text-[#0f1c2e]">{CAL_MONTH_NAMES[month]} {year}</span>
        <button onClick={onNext} className="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-[#eef1f6] transition-colors text-[#6b7c96]">
          <ChevronRight size={14}/>
        </button>
      </div>
      <div className="grid grid-cols-7 gap-0.5 mb-1">
        {CAL_DAY_LABELS.map(d => (
          <div key={d} className="text-center text-[10px] font-bold text-[#9ca3af] py-1">{d}</div>
        ))}
      </div>
      <div className="grid grid-cols-7 gap-0.5">
        {cells.map((day, i) => {
          if (!day) return <div key={i}/>;
          const ds = fmtDate(day);
          const selected = selectedDates.includes(ds);
          const isToday = ds === TODAY_STR;
          return (
            <button key={i} onClick={() => onToggle(ds)}
              className={`w-full aspect-square rounded-lg text-[11px] font-semibold flex flex-col items-center justify-center gap-px transition-all relative
                ${selected ? "bg-[#1a3a6b] text-white shadow-sm" : isToday ? "ring-2 ring-[#1a3a6b] ring-inset bg-[#dbeafe] text-[#1a3a6b]" : "hover:bg-[#eef1f6] text-[#0f1c2e]"}`}>
              {day}
              {isToday && (
                <span className={`w-1 h-1 rounded-full shrink-0 ${selected ? "bg-white/80" : "bg-[#1a3a6b]"}`}/>
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
}

function LeaveScreen() {
  const TODAY = "2025-07-14";
  const [leaves, setLeaves] = useState<LeaveRecord[]>(INIT_LEAVES);
  const [showModal, setShowModal] = useState(false);
  const [lType, setLType] = useState("");
  const [selectedDates, setSelectedDates] = useState<string[]>([]);
  const [reason, setReason] = useState("");
  const [cover, setCover] = useState("");
  const [calYear, setCalYear] = useState(2025);
  const [calMonth, setCalMonth] = useState(6); // July
  const [manualDate, setManualDate] = useState("");
  const [filterFrom, setFilterFrom] = useState("");
  const [filterTo, setFilterTo] = useState("");

  const isUpcoming = (l: LeaveRecord) => !l.cancelled && l.dates.some(d => d >= TODAY);
  const isHistory  = (l: LeaveRecord) => l.cancelled  || l.dates.every(d => d < TODAY);

  const upcomingLeaves = leaves.filter(isUpcoming);
  const historyLeaves  = leaves.filter(isHistory);

  const totalDaysUsed  = historyLeaves.filter(l => !l.cancelled).reduce((s, l) => s + l.dates.length, 0);
  const upcomingDays   = upcomingLeaves.reduce((s, l) => s + l.dates.length, 0);
  const annualBalance  = 21 - totalDaysUsed;

  const cancelLeave = (id: number) => setLeaves(prev => prev.map(l => l.id === id ? { ...l, cancelled: true } : l));

  const toggleDate = (ds: string) =>
    setSelectedDates(prev => prev.includes(ds) ? prev.filter(d => d !== ds) : [...prev, ds]);

  const addManualDate = () => {
    if (manualDate && !selectedDates.includes(manualDate)) {
      setSelectedDates(prev => [...prev, manualDate].sort());
    }
    setManualDate("");
  };

  const prevMonth = () => {
    if (calMonth === 0) { setCalMonth(11); setCalYear(y => y - 1); }
    else setCalMonth(m => m - 1);
  };
  const nextMonth = () => {
    if (calMonth === 11) { setCalMonth(0); setCalYear(y => y + 1); }
    else setCalMonth(m => m + 1);
  };

  const submit = () => {
    if (!lType || selectedDates.length === 0 || !reason) return;
    setLeaves(prev => [{ id: Date.now(), type: lType, dates: [...selectedDates].sort(), reason, cover, cancelled: false }, ...prev]);
    setShowModal(false); setLType(""); setSelectedDates([]); setReason(""); setCover(""); setManualDate("");
  };

  const closeModal = () => {
    setShowModal(false); setLType(""); setSelectedDates([]); setReason(""); setCover(""); setManualDate("");
    setCalYear(2025); setCalMonth(6);
  };

  const fmtDisplayDates = (dates: string[]) => {
    if (!dates.length) return "—";
    const s = [...dates].sort();
    if (s.length === 1) return s[0];
    return `${s[0]} – ${s[s.length - 1]}`;
  };

  const filteredHistory = historyLeaves.filter(l => {
    const sorted = [...l.dates].sort();
    const first = sorted[0], last = sorted[sorted.length - 1];
    if (filterFrom && last < filterFrom) return false;
    if (filterTo && first > filterTo) return false;
    return true;
  });

  const STAT_CARDS = [
    { label: "Annual Balance", value: annualBalance, unit: "days", color: "#1a3a6b", bg: "#e8edf5" },
    { label: "Days Used",       value: totalDaysUsed, unit: "days", color: "#0f766e", bg: "#d0fae5" },
    { label: "Upcoming Days",   value: upcomingDays,  unit: "days", color: "#b45309", bg: "#fef3c7" },
    { label: "Total Requests",  value: leaves.filter(l => !l.cancelled).length, unit: "", color: "#6b7c96", bg: "#f4f6f9" },
  ];

  return (
    <div className="flex flex-col h-full overflow-y-auto">
      {/* Header */}
      <div className="bg-white border-b border-[#dde3ee] px-6 py-4 flex items-center justify-between shrink-0">
        <div>
          <h2 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-lg">Leave</h2>
          <p className="text-xs text-[#9ca3af] mt-0.5">Manage your leave requests</p>
        </div>
        <button onClick={() => setShowModal(true)}
          className="flex items-center gap-1.5 bg-[#1a3a6b] hover:bg-[#0f2a55] text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
          <Plus size={15}/> Request Leave
        </button>
      </div>

      <div className="p-6 flex flex-col gap-5">
        {/* Stat cards */}
        <div className="grid grid-cols-4 gap-4">
          {STAT_CARDS.map(c => (
            <div key={c.label} className="bg-white rounded-xl border border-[#dde3ee] px-5 py-4">
              <div className="flex items-center gap-2 mb-2">
                <div className="w-7 h-7 rounded-lg flex items-center justify-center" style={{ background: c.bg }}>
                  <CalendarDays size={14} style={{ color: c.color }}/>
                </div>
                <span className="text-[10.5px] font-semibold uppercase tracking-wide text-[#9ca3af]">{c.label}</span>
              </div>
              <div className="text-2xl font-bold" style={{ color: c.color }}>{c.value}<span className="text-sm font-medium text-[#9ca3af] ml-1">{c.unit}</span></div>
            </div>
          ))}
        </div>

        {/* Upcoming leaves */}
        {upcomingLeaves.length > 0 && (
          <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
            <div className="px-5 py-3 border-b border-[#dde3ee] bg-[#fffbeb] flex items-center gap-2">
              <Clock size={14} className="text-[#b45309]"/>
              <p className="text-sm font-semibold text-[#0f1c2e]">Upcoming Leaves</p>
              <span className="ml-auto text-[10.5px] text-[#9ca3af]">You can cancel before the leave date</span>
            </div>
            <div className="divide-y divide-[#dde3ee]">
              {upcomingLeaves.map(l => {
                const sorted = [...l.dates].sort();
                const canCancel = sorted[0] >= TODAY;
                return (
                  <div key={l.id} className="px-5 py-3.5 flex items-center gap-4 hover:bg-[#fafbfc] transition-colors">
                    <div className="w-2 h-2 rounded-full bg-[#f59e0b] shrink-0"/>
                    <div className="flex-1 min-w-0">
                      <p className="text-xs font-semibold text-[#0f1c2e]">{l.type}</p>
                      <p className="text-[10.5px] text-[#6b7c96] mt-0.5">{fmtDisplayDates(sorted)} · {l.dates.length} day{l.dates.length !== 1 ? "s" : ""} · {l.reason}</p>
                    </div>
                    {l.cover && <span className="text-[10.5px] text-[#6b7c96] shrink-0">Cover: {l.cover}</span>}
                    {canCancel && (
                      <button onClick={() => cancelLeave(l.id)}
                        className="text-[10.5px] font-semibold text-[#dc2626] border border-[#fecaca] bg-[#fff5f5] hover:bg-[#fee2e2] px-2.5 py-1 rounded-lg transition-colors shrink-0">
                        Cancel Leave
                      </button>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        )}

        {/* Leave history */}
        <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
          <div className="px-5 py-3 border-b border-[#dde3ee] flex items-center gap-3 flex-wrap">
            <p className="text-sm font-semibold text-[#0f1c2e] mr-auto">Leave History</p>
            <div className="flex items-center gap-2 text-xs text-[#6b7c96]">
              <span className="text-[10.5px] font-semibold uppercase tracking-wide text-[#6b7c96]">Filter by date:</span>
              <label className="flex items-center gap-1 relative group cursor-pointer">
                <span className="text-[10.5px] text-[#9ca3af]">From</span>
                <div className="relative">
                  <input type="date" value={filterFrom} onChange={e => setFilterFrom(e.target.value)}
                    placeholder="YYYY-MM-DD"
                    className="pl-7 pr-2.5 py-1.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-xs text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all w-[135px]"/>
                  <CalendarDays size={11} className="absolute left-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
                </div>
              </label>
              <span className="text-[#9ca3af]">—</span>
              <label className="flex items-center gap-1 relative group cursor-pointer">
                <span className="text-[10.5px] text-[#9ca3af]">To</span>
                <div className="relative">
                  <input type="date" value={filterTo} onChange={e => setFilterTo(e.target.value)}
                    placeholder="YYYY-MM-DD"
                    className="pl-7 pr-2.5 py-1.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-xs text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all w-[135px]"/>
                  <CalendarDays size={11} className="absolute left-2 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
                </div>
              </label>
              {(filterFrom || filterTo) && (
                <button onClick={() => { setFilterFrom(""); setFilterTo(""); }}
                  className="text-[10.5px] text-[#6b7c96] hover:text-[#dc2626] flex items-center gap-0.5 transition-colors border border-[#dde3ee] px-2 py-1.5 rounded-lg hover:bg-[#fff5f5] hover:border-[#fecaca]">
                  <X size={11}/> Clear
                </button>
              )}
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="text-[10.5px] font-semibold uppercase tracking-wider text-[#6b7c96] border-b border-[#dde3ee] bg-[#f9fafb]">
                  {["Leave Type","Dates","Days","Reason","Cover Staff","Note"].map(h => (
                    <th key={h} className="text-left px-4 py-2.5 font-semibold whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filteredHistory.length === 0 ? (
                  <tr><td colSpan={6} className="px-4 py-8 text-center text-xs text-[#9ca3af]">No leave history found</td></tr>
                ) : filteredHistory.map(l => {
                  const sorted = [...l.dates].sort();
                  return (
                    <tr key={l.id} className="border-b border-[#dde3ee] last:border-0 hover:bg-[#f4f6f9] transition-colors">
                      <td className="px-4 py-3 text-xs font-semibold text-[#0f1c2e] whitespace-nowrap">{l.type}</td>
                      <td className="px-4 py-3 text-xs text-[#6b7c96] whitespace-nowrap">{fmtDisplayDates(sorted)}</td>
                      <td className="px-4 py-3 text-xs font-semibold text-[#0f1c2e]">{l.dates.length}</td>
                      <td className="px-4 py-3 text-xs text-[#6b7c96] max-w-[160px]"><span className="line-clamp-2">{l.reason}</span></td>
                      <td className="px-4 py-3 text-xs text-[#6b7c96]">{l.cover || "—"}</td>
                      <td className="px-4 py-3">
                        {l.cancelled
                          ? <span className="px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-[#fee2e2] text-[#991b1b]">Cancelled</span>
                          : <span className="px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-[#d0fae5] text-[#004f3b]">Completed</span>}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Request Leave Modal */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(15,28,46,0.55)" }}>
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            {/* Modal header */}
            <div className="px-6 py-4 border-b border-[#dde3ee] flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
              <div>
                <p className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-base">Request Leave</p>
                <p className="text-[10.5px] text-[#9ca3af] mt-0.5">Select dates and fill in the details below</p>
              </div>
              <button onClick={closeModal} className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#f4f6f9] text-[#9ca3af] hover:text-[#0f1c2e] transition-colors"><X size={16}/></button>
            </div>

            <div className="p-6 flex flex-col gap-5">
              {/* Leave type */}
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Leave Type</label>
                <div className="relative">
                  <select value={lType} onChange={e => setLType(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all appearance-none">
                    <option value="">Select leave type…</option>
                    {LEAVE_TYPES.map(t => <option key={t}>{t}</option>)}
                  </select>
                  <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
                </div>
              </div>

              {/* Date selection */}
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-2 uppercase tracking-wide">
                  Select Dates <span className="normal-case font-normal text-[#9ca3af]">— click dates on the calendar or type manually</span>
                </label>
                <div className="border border-[#dde3ee] rounded-xl overflow-hidden">
                  {/* Mini calendar */}
                  <div className="p-4 bg-[#f9fafb]">
                    <MiniCalendar
                      selectedDates={selectedDates}
                      onToggle={toggleDate}
                      year={calYear} month={calMonth}
                      onPrev={prevMonth} onNext={nextMonth}
                    />
                  </div>
                  {/* Manual date input */}
                  <div className="border-t border-[#dde3ee] px-4 py-3 bg-white flex items-center gap-2">
                    <span className="text-[10.5px] font-semibold text-[#6b7c96] whitespace-nowrap">Or type date:</span>
                    <input type="date" value={manualDate} onChange={e => setManualDate(e.target.value)}
                      className="flex-1 px-2.5 py-1.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-xs text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] transition-all"/>
                    <button onClick={addManualDate} disabled={!manualDate}
                      className="px-3 py-1.5 rounded-lg bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white text-xs font-semibold transition-colors">
                      Add
                    </button>
                  </div>
                  {/* Selected dates chips */}
                  {selectedDates.length > 0 && (
                    <div className="border-t border-[#dde3ee] px-4 py-3 bg-white flex flex-wrap gap-1.5 items-center">
                      <span className="text-[10.5px] font-semibold text-[#6b7c96]">{selectedDates.length} date{selectedDates.length !== 1 ? "s" : ""} selected:</span>
                      {[...selectedDates].sort().map(d => (
                        <span key={d} className="flex items-center gap-1 bg-[#e8edf5] text-[#1a3a6b] text-[10.5px] font-semibold px-2 py-0.5 rounded-full">
                          {d}
                          <button onClick={() => toggleDate(d)} className="hover:text-[#dc2626] transition-colors"><X size={10}/></button>
                        </span>
                      ))}
                      <button onClick={() => setSelectedDates([])} className="text-[10.5px] text-[#9ca3af] hover:text-[#dc2626] transition-colors ml-1">Clear all</button>
                    </div>
                  )}
                </div>
              </div>

              {/* Reason */}
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Reason</label>
                <textarea value={reason} onChange={e => setReason(e.target.value)} rows={2} placeholder="Brief reason for leave…"
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all resize-none"/>
              </div>

              {/* Cover staff */}
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Cover Staff <span className="normal-case font-normal">(optional)</span></label>
                <div className="relative">
                  <select value={cover} onChange={e => setCover(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all appearance-none">
                    <option value="">Select cover staff…</option>
                    {LEAVE_COVER_STAFF.map(s => <option key={s}>{s}</option>)}
                  </select>
                  <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af] pointer-events-none"/>
                </div>
              </div>

              {/* Footer */}
              <div className="flex justify-end gap-3 pt-1">
                <button onClick={closeModal} className="px-4 py-2 text-sm font-semibold text-[#6b7c96] border border-[#dde3ee] rounded-lg hover:bg-[#f4f6f9] transition-colors">Cancel</button>
                <button onClick={submit} disabled={!lType || selectedDates.length === 0 || !reason}
                  className="px-5 py-2 text-sm font-semibold bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white rounded-lg transition-colors flex items-center gap-1.5">
                  <Check size={14}/> Submit Request
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// ── Messages ───────────────────────────────────────────────────────────────
interface Conversation {
  id: number; name: string; avatar: string; preview: string;
  time: string; unread: number; isGroup: boolean; members?: string[];
}

interface Message {
  id: number; text: string; time: string; mine: boolean;
}

const CONVERSATIONS: Conversation[] = [
  { id:1, name:"CS3401 Lab Group",         avatar:"CS", preview:"Dr. Perera: Lab report template has been updated.", time:"9:41 AM", unread:3, isGroup:true,  members:["Ms. T. Fernando (You)","Dr. N. Perera","Mr. K. Bandara"] },
  { id:2, name:"IT2301 Practical Group",   avatar:"IT", preview:"Don't forget the network config submission today",  time:"9:12 AM", unread:1, isGroup:true,  members:["Ms. T. Fernando (You)","Prof. A. Silva","Mr. J. Peris"] },
  { id:3, name:"Dr. Nimal Perera",         avatar:"NP", preview:"Please send the attendance sheet for last week",    time:"Yesterday",unread:0, isGroup:false },
  { id:4, name:"Prof. Anoma Silva",        avatar:"AS", preview:"Thank you for the update.",                         time:"Monday",  unread:0, isGroup:false },
  { id:5, name:"Dept. Instructors Channel",avatar:"DI", preview:"Reminder: Staff meeting at 3 PM tomorrow",          time:"Monday",  unread:2, isGroup:true,  members:["Ms. T. Fernando (You)","Dr. N. Perera","Prof. A. Silva","Mr. K. Bandara","Mr. J. Peris"] },
];

const MESSAGES: Record<number, Message[]> = {
  1: [
    { id:1, text:"Good morning everyone. Today we'll cover memory allocation in C.", time:"8:02 AM", mine:false },
    { id:2, text:"Dr. Perera, the lab report template has been updated — please check the shared drive.", time:"8:45 AM", mine:false },
    { id:3, text:"Got it, thanks. I'll distribute it at the start of class.", time:"9:10 AM", mine:true },
    { id:4, text:"Also, can someone confirm the projector in Lab A-201 is working?", time:"9:35 AM", mine:true },
    { id:5, text:"Lab report template has been updated — please use the new version.", time:"9:41 AM", mine:false },
  ],
  2: [
    { id:1, text:"Hi, just a reminder — network config submissions close at 5 PM today.", time:"9:00 AM", mine:false },
    { id:2, text:"Noted. I'll remind students during the practical session.", time:"9:12 AM", mine:true },
  ],
  3: [
    { id:1, text:"Good afternoon. Could you send me the attendance sheet for last week's lab sessions?", time:"Yesterday 2:15 PM", mine:false },
    { id:2, text:"Of course, Dr. Perera. I'll have it ready by end of day.", time:"Yesterday 2:30 PM", mine:true },
  ],
  4: [
    { id:1, text:"Thank you for the update on the lab schedule.", time:"Monday 3:45 PM", mine:false },
    { id:2, text:"You're welcome, Prof. Silva.", time:"Monday 4:00 PM", mine:true },
  ],
  5: [
    { id:1, text:"Reminder: Staff meeting in the Faculty Boardroom tomorrow at 3 PM.", time:"Monday 11:00 AM", mine:false },
    { id:2, text:"Attendance is mandatory for all instructors.", time:"Monday 11:01 AM", mine:false },
    { id:3, text:"I'll be there.", time:"Monday 11:30 AM", mine:true },
  ],
};

function MessagesScreen({ conversations, addConversation }: { conversations: Conversation[]; addConversation: (c: Conversation) => void }) {
  const [active, setActive]       = useState<number>(1);
  const [msgMap, setMsgMap]       = useState(MESSAGES);
  const [draft, setDraft]         = useState("");
  const [showGroupInfo, setShowGroupInfo] = useState(false);
  const conv = conversations.find(c => c.id === active) ?? conversations[0];
  const msgs = msgMap[active] || [];

  const send = () => {
    if (!draft.trim()) return;
    setMsgMap(prev => ({ ...prev, [active]: [...(prev[active] || []), { id: Date.now(), text: draft, time: "Just now", mine: true }] }));
    setDraft("");
  };

  const avatarBg: Record<string, string> = {
    CS:"#4d179a", IT:"#004f3b", NP:"#1a3a6b", AS:"#92400e", DI:"#1c398e"
  };

  return (
    <div className="flex h-full overflow-hidden">
      {/* Conversation list */}
      <div className="w-72 shrink-0 border-r border-[#dde3ee] bg-white flex flex-col">
        <div className="px-4 py-3 border-b border-[#dde3ee]">
          <h2 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-base">Messages</h2>
          <div className="relative mt-2">
            <Search size={13} className="absolute left-3 top-1/2 -translate-y-1/2 text-[#9ca3af]" />
            <input placeholder="Search conversations…" className="w-full pl-8 pr-3 py-1.5 bg-[#f4f6f9] rounded-lg border border-[#dde3ee] text-xs text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] transition-all" />
          </div>
        </div>
        <div className="flex-1 overflow-y-auto">
          {conversations.map(c => (
            <button key={c.id} onClick={() => setActive(c.id)}
              className={`w-full flex items-center gap-3 px-4 py-3 border-b border-[#dde3ee] hover:bg-[#f4f6f9] transition-colors text-left ${active === c.id ? "bg-[#eff6ff]" : ""}`}>
              <div className="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-white text-xs font-bold"
                style={{ background: avatarBg[c.avatar] || "#1a3a6b" }}>
                {c.avatar}
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between">
                  <p className="text-xs font-semibold text-[#0f1c2e] truncate">{c.name}</p>
                  <span className="text-[10px] text-[#9ca3af] shrink-0 ml-1">{c.time}</span>
                </div>
                <p className="text-[11px] text-[#6b7c96] truncate mt-0.5">{c.preview}</p>
              </div>
              {c.unread > 0 && (
                <span className="w-4.5 h-4.5 bg-[#2563eb] text-white text-[10px] font-bold rounded-full flex items-center justify-center shrink-0">
                  {c.unread}
                </span>
              )}
            </button>
          ))}
        </div>
      </div>

      {/* Thread */}
      <div className="flex-1 flex overflow-hidden">
        <div className="flex-1 flex flex-col bg-[#f4f6f9] overflow-hidden">
          {/* Thread header */}
          <div className="bg-white border-b border-[#dde3ee] px-5 py-3 flex items-center gap-3 shrink-0">
            <div className="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
              style={{ background: avatarBg[conv.avatar] || "#1a3a6b" }}>
              {conv.avatar}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-[#0f1c2e] truncate">{conv.name}</p>
              <p className="text-[10px] text-[#9ca3af]">
                {conv.isGroup ? `Group · ${conv.members?.length ?? 0} members` : "Direct message"}
              </p>
            </div>
            {conv.isGroup && (
              <button onClick={() => setShowGroupInfo(v => !v)}
                className={`flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors border ${showGroupInfo ? "bg-[#eff6ff] border-[#bfdbfe] text-[#1e40af]" : "border-[#dde3ee] text-[#6b7c96] hover:bg-[#f4f6f9]"}`}>
                <Users size={13}/> Members {showGroupInfo ? <ChevronUp size={11}/> : <ChevronDown size={11}/>}
              </button>
            )}
          </div>

          {/* Messages */}
          <div className="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-3">
            {msgs.map(m => (
              <div key={m.id} className={`flex ${m.mine ? "justify-end" : "justify-start"}`}>
                <div className={`max-w-[70%] rounded-2xl px-4 py-2.5 text-sm ${m.mine ? "bg-[#1a3a6b] text-white rounded-br-sm" : "bg-white border border-[#dde3ee] text-[#0f1c2e] rounded-bl-sm"}`}>
                  <p>{m.text}</p>
                  <p className={`text-[10px] mt-1 ${m.mine ? "text-[#bedbff]" : "text-[#9ca3af]"} text-right`}>{m.time}</p>
                </div>
              </div>
            ))}
          </div>

          {/* Composer */}
          <div className="bg-white border-t border-[#dde3ee] px-4 py-3 shrink-0">
            <div className="flex items-center gap-2 bg-[#f4f6f9] rounded-xl border border-[#dde3ee] px-3 py-2">
              <button className="text-[#9ca3af] hover:text-[#6b7c96]"><Paperclip size={15}/></button>
              <input
                value={draft}
                onChange={e => setDraft(e.target.value)}
                onKeyDown={e => e.key === "Enter" && !e.shiftKey && (e.preventDefault(), send())}
                placeholder={`Message ${conv.name}…`}
                className="flex-1 bg-transparent text-sm text-[#0f1c2e] placeholder-[#9ca3af] focus:outline-none"
              />
              <button onClick={send} disabled={!draft.trim()}
                className="bg-[#1a3a6b] disabled:opacity-40 hover:bg-[#0f2a55] text-white rounded-lg p-1.5 transition-colors">
                <Send size={13}/>
              </button>
            </div>
          </div>
        </div>

        {/* Group info panel */}
        {showGroupInfo && conv.isGroup && (
          <div className="w-60 shrink-0 border-l border-[#dde3ee] bg-white flex flex-col overflow-hidden">
            <div className="px-4 py-3 border-b border-[#dde3ee] flex items-center justify-between shrink-0">
              <p className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-sm">Group Info</p>
              <button onClick={() => setShowGroupInfo(false)} className="text-[#9ca3af] hover:text-[#0f1c2e]"><X size={14}/></button>
            </div>
            {/* Group avatar + name */}
            <div className="flex flex-col items-center py-5 border-b border-[#dde3ee] gap-2 px-4">
              <div className="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-lg"
                style={{ background: avatarBg[conv.avatar] || "#1a3a6b" }}>
                {conv.avatar}
              </div>
              <p className="font-semibold text-sm text-[#0f1c2e] text-center">{conv.name}</p>
              <p className="text-[10.5px] text-[#9ca3af]">Group · {conv.members?.length ?? 0} members</p>
            </div>
            {/* Members list */}
            <div className="flex-1 overflow-y-auto">
              <p className="text-[10px] font-semibold uppercase tracking-wider text-[#9ca3af] px-4 pt-3 pb-1.5">Members</p>
              {(conv.members ?? []).map((m, i) => {
                const isMe = m.includes("(You)");
                const initials = m.replace(/\(You\)/, "").trim().split(" ").map(w => w[0]).filter(Boolean).slice(0,2).join("");
                return (
                  <div key={i} className="flex items-center gap-3 px-4 py-2.5 hover:bg-[#f4f6f9] transition-colors">
                    <div className="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0 bg-[#1a3a6b]">
                      {initials}
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-xs font-semibold text-[#0f1c2e] truncate">{m.replace(" (You)", "")}</p>
                      {isMe && <p className="text-[9.5px] text-[#2563eb]">You</p>}
                    </div>
                  </div>
                );
              })}
              {(!conv.members || conv.members.length === 0) && (
                <p className="text-xs text-[#9ca3af] px-4 py-3">No member list available.</p>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// ── Settings ───────────────────────────────────────────────────────────────
function SettingsScreen() {
  const [name, setName]       = useState("Ms. Thilini Fernando");
  const [bio, setBio]         = useState("Instructor in the Department of Computer Science. Specialising in lab-based practical education for undergraduate students.");
  const [office, setOffice]   = useState("Room 204, Science Block B");
  const [ext, setExt]         = useState("4512");
  const [passkeys, setPasskeys] = useState([{ id:1, name:"MacBook Pro Touch ID", added:"3 Jan 2025" }]);
  const [notifs, setNotifs] = useState({
    workload: true, leave: true, messages: true, timetable: true, requests: false, digest: true,
  });
  const [saved, setSaved] = useState(false);

  const save = () => { setSaved(true); setTimeout(() => setSaved(false), 2500); };

  const Toggle = ({ on, onChange }: { on: boolean; onChange: () => void }) => (
    <button onClick={onChange} className={`w-10 h-5.5 rounded-full transition-colors relative ${on ? "bg-[#1a3a6b]" : "bg-[#c8d0de]"}`}>
      <div className={`w-4 h-4 bg-white rounded-full absolute top-0.5 transition-all ${on ? "left-5" : "left-0.5"}`} />
    </button>
  );

  return (
    <div className="flex flex-col h-full overflow-y-auto">
      <div className="bg-white border-b border-[#dde3ee] px-6 py-4 flex items-center justify-between shrink-0">
        <div>
          <h2 className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-lg">Settings</h2>
          <p className="text-xs text-[#9ca3af] mt-0.5">Manage your profile and preferences</p>
        </div>
        {saved && (
          <div className="flex items-center gap-1.5 text-[#004f3b] bg-[#d0fae5] border border-[#00d492] rounded-lg px-3 py-1.5 text-xs font-semibold">
            <CheckCircle size={13}/> Saved
          </div>
        )}
      </div>

      <div className="p-6 flex flex-col gap-5 max-w-2xl">
        {/* Profile section */}
        <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
          <div className="px-5 py-3 border-b border-[#dde3ee] bg-[#f4f6f9]">
            <p className="text-sm font-semibold text-[#0f1c2e]">Profile</p>
          </div>
          <div className="p-5 flex gap-5 items-start">
            {/* Photo */}
            <div className="shrink-0 flex flex-col items-center gap-2">
              <div className="w-20 h-20 rounded-2xl bg-[#1a3a6b] flex items-center justify-center text-white font-bold text-2xl font-['Plus_Jakarta_Sans',sans-serif]">TF</div>
              <button className="flex items-center gap-1 text-xs text-[#2563eb] font-medium hover:underline"><Upload size={11}/> Upload</button>
            </div>
            <div className="flex-1 grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Full Name</label>
                <input value={name} onChange={e => setName(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <div className="col-span-2">
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Bio</label>
                <textarea value={bio} onChange={e => setBio(e.target.value)} rows={3}
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all resize-none" />
              </div>
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Office Location</label>
                <input value={office} onChange={e => setOffice(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Phone Extension</label>
                <input value={ext} onChange={e => setExt(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#f4f6f9] text-sm text-[#0f1c2e] focus:outline-none focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20 transition-all" />
              </div>
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Email</label>
                <div className="px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#eef1f6] text-sm text-[#6b7c96]">t.fernando@university.edu</div>
              </div>
              <div>
                <label className="block text-xs font-semibold text-[#6b7c96] mb-1.5 uppercase tracking-wide">Designation</label>
                <div className="px-3.5 py-2.5 rounded-lg border border-[#dde3ee] bg-[#eef1f6] text-sm text-[#6b7c96]">Instructor</div>
              </div>
            </div>
          </div>
          <div className="px-5 py-3 border-t border-[#dde3ee] flex justify-end">
            <button onClick={save} className="px-4 py-2 bg-[#1a3a6b] hover:bg-[#0f2a55] text-white text-sm font-semibold rounded-lg transition-colors">Save Changes</button>
          </div>
        </div>

        {/* Passkey management */}
        <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
          <div className="px-5 py-3 border-b border-[#dde3ee] bg-[#f4f6f9] flex items-center justify-between">
            <p className="text-sm font-semibold text-[#0f1c2e]">Passkeys</p>
            <button className="flex items-center gap-1.5 text-xs text-[#2563eb] font-semibold hover:underline"><Plus size={12}/> Add Passkey</button>
          </div>
          <div className="p-5 flex flex-col gap-3">
            {passkeys.map(pk => (
              <div key={pk.id} className="flex items-center justify-between p-3 bg-[#f4f6f9] rounded-lg border border-[#dde3ee]">
                <div className="flex items-center gap-3">
                  <div className="w-9 h-9 rounded-lg bg-[#1a3a6b] flex items-center justify-center">
                    <Fingerprint size={17} className="text-white"/>
                  </div>
                  <div>
                    <p className="text-sm font-semibold text-[#0f1c2e]">{pk.name}</p>
                    <p className="text-xs text-[#9ca3af]">Added {pk.added}</p>
                  </div>
                </div>
                <button onClick={() => setPasskeys([])} className="text-[#ef4444] hover:text-red-700 text-xs font-semibold hover:underline">Remove</button>
              </div>
            ))}
            {passkeys.length === 0 && (
              <p className="text-sm text-[#9ca3af] text-center py-4">No passkeys registered. Add one for faster sign-in.</p>
            )}
          </div>
        </div>

        {/* Notification preferences */}
        <div className="bg-white rounded-xl border border-[#dde3ee] overflow-hidden">
          <div className="px-5 py-3 border-b border-[#dde3ee] bg-[#f4f6f9]">
            <p className="text-sm font-semibold text-[#0f1c2e]">Notification Preferences</p>
          </div>
          <div className="p-5 flex flex-col gap-4">
            {([
              ["workload", "Workload Assignments", "Notify when new assignments are sent for acceptance"],
              ["leave",    "Leave Approvals",       "Notify when your leave requests are approved or rejected"],
              ["messages", "New Messages",          "Notify on direct messages and group mentions"],
              ["timetable","Timetable Updates",     "Notify when your timetable is changed or finalized"],
              ["requests", "Staff Request Updates", "Notify on status changes to your support requests"],
              ["digest",   "Weekly Summary Digest", "Receive a weekly summary of your workload and schedule"],
            ] as [keyof typeof notifs, string, string][]).map(([key, label, desc]) => (
              <div key={key} className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-semibold text-[#0f1c2e]">{label}</p>
                  <p className="text-xs text-[#6b7c96]">{desc}</p>
                </div>
                <Toggle on={notifs[key]} onChange={() => setNotifs(prev => ({ ...prev, [key]: !prev[key] }))} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════════════
// DASHBOARD SHELL
// ══════════════════════════════════════════════════════════════════════════

const NAV_ITEMS: { key: DashTab; label: string; icon: React.ReactNode; badge?: number }[] = [
  { key:"timetable",     label:"My Timetable",           icon:<Calendar size={17}/> },
  { key:"workload",      label:"My Workload",             icon:<BarChart2 size={17}/> },
  { key:"requests",      label:"Supportive Staff Requests", icon:<ClipboardList size={17}/> },
  { key:"leave",         label:"Leave",                   icon:<CalendarDays size={17}/> },
  { key:"messages",      label:"Messages",                icon:<MessageSquare size={17}/>, badge:4 },
  { key:"settings",      label:"Settings",                icon:<Settings size={17}/> },
];

function Dashboard({ navToPage, role }: { navToPage: (p: Page) => void; role: UserRole }) {
  const [activeTab, setActiveTab] = useState<DashTab>("timetable");
  const [showProfile, setShowProfile] = useState(false);

  const [conversations, setConversations] = useState<Conversation[]>(CONVERSATIONS);
  const addConversation = (g: TimetableGroup) => {
    const newConv: Conversation = {
      id: g.id,
      name: g.name,
      avatar: g.avatar,
      preview: `Group created · ${g.members.length} members`,
      time: "Just now",
      unread: 0,
      isGroup: true,
    };
    setConversations(prev => [newConv, ...prev]);
  };

  return (
    <div className="flex h-screen w-screen overflow-hidden bg-[#f4f6f9]">
      {/* Sidebar */}
      <aside className="flex flex-col h-full shrink-0" style={{ width: 225, background: "#0f1c2e" }}>
        {/* Logo */}
        <div className="px-[18.75px] py-[18.75px] border-b border-white/10">
          <div className="flex items-center gap-[11.25px]">
            <div className="w-[33.75px] h-[33.75px] rounded-xl bg-[#2563eb] flex items-center justify-center shrink-0 shadow-lg">
              <svg width="19" height="19" viewBox="0 0 20 20" fill="none">
                <path d="M3 5.5h14M7 2v3M13 2v3M3 9.5h14M3 13.5h8" stroke="white" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"/>
                <circle cx="15" cy="15" r="3" stroke="white" strokeWidth="1.5"/>
                <path d="M14.2 15l.8.8 1.3-1.3" stroke="white" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            </div>
            <div>
              <p className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-white text-[13.125px] leading-tight">StaffSync</p>
              <p className="text-[#51a2ff] text-[11.25px] font-normal">Staff Portal</p>
            </div>
          </div>
        </div>

        {/* Nav label */}
        <div className="pt-[11.25px] pb-[7.5px] px-[11.25px]">
          <p className="text-[#51a2ff]/60 text-[11.25px] font-semibold tracking-[0.5625px] uppercase">Navigation</p>
        </div>

        {/* Nav items */}
        <nav className="flex-1 px-[11.25px] flex flex-col gap-[1.875px] overflow-y-auto">
          {NAV_ITEMS.map(item => {
            const isActive = activeTab === item.key;
            return (
              <button key={item.key} onClick={() => setActiveTab(item.key)}
                className={`flex items-center gap-[11.25px] px-[11.25px] py-[9.375px] rounded-[11.5px] w-full text-left transition-all ${isActive ? "bg-[#2563eb] shadow-[0px_10px_7.5px_rgba(28,57,142,0.3),0px_4px_3px_rgba(28,57,142,0.3)]" : "hover:bg-white/7"}`}>
                <span className={isActive ? "text-white" : "text-[#bedbff]"}>{item.icon}</span>
                <span className={`text-[13.125px] font-medium flex-1 ${isActive ? "text-white" : "text-[#bedbff]"}`}>{item.label}</span>
                {item.badge && (
                  <span className="w-[15px] h-[15px] bg-[#fb2c36] rounded-full flex items-center justify-center text-white text-[11.25px] font-bold shrink-0">
                    {item.badge}
                  </span>
                )}
              </button>
            );
          })}
        </nav>

        {/* Sign Out */}
        <div className="border-t border-white/10 px-[11.25px] pt-[8.5px] pb-[15px]">
          <button onClick={() => navToPage("login")}
            className="flex items-center gap-[11.25px] px-[11.25px] py-[9.375px] rounded-[11.5px] w-full text-left hover:bg-white/7 transition-all group">
            <LogOut size={17} className="text-[#ff6467]" />
            <span className="text-[#ff6467] text-[13.125px] font-medium">Sign Out</span>
          </button>
        </div>
      </aside>

      {/* Main content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Top bar */}
        <header className="bg-white border-b border-[#dde3ee] flex items-center justify-between px-6 py-3 shrink-0">
          <div>
            <p className="font-['Plus_Jakarta_Sans',sans-serif] font-bold text-[#0f1c2e] text-[16.875px] leading-tight">
              {NAV_ITEMS.find(n => n.key === activeTab)?.label}
            </p>
            <p className="text-[#9ca3af] text-[11.25px]">University of Colombo · Faculty of Physical &amp; Computational Sciences</p>
          </div>
          <div className="flex items-center gap-2">
            {/* Theme toggle placeholder */}
            <button className="w-[33.75px] h-[33.75px] bg-[#f4f6f9] rounded-[11.5px] flex items-center justify-center text-[#6b7c96] hover:bg-[#eef1f6] transition-colors">
              <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M7.5 1v1.5M7.5 12.5V14M1 7.5h1.5M12.5 7.5H14M3 3l1.1 1.1M10.9 10.9L12 12M3 12l1.1-1.1M10.9 4.1L12 3M7.5 10.5a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round"/></svg>
            </button>
            {/* Profile button */}
            <div className="relative">
              <button onClick={() => setShowProfile(!showProfile)}
                className="flex items-center gap-[9.375px] pl-[3.75px] pr-[11.25px] py-[3.75px] rounded-[11.5px] hover:bg-[#f4f6f9] transition-colors">
                {(() => {
                  const u = role === "lecturer" ? DEMO_ACCOUNTS["lecturer@university.edu"] : DEMO_ACCOUNTS["instructor@university.edu"];
                  const initials = u.name.split(" ").map(w=>w[0]).filter((_,i)=>i<2).join("");
                  return (
                    <div className="w-[26.25px] h-[26.25px] rounded-[7.5px] flex items-center justify-center text-white text-[11.25px] font-bold"
                      style={{ background: role === "lecturer" ? "#1a3a6b" : "#4d179a" }}>
                      {initials}
                    </div>
                  );
                })()}
                <div className="text-left">
                  <p className="text-[11.25px] font-semibold text-[#0f1c2e] leading-tight">
                    {role === "lecturer" ? "Dr. N. Perera" : "Ms. T. Fernando"}
                  </p>
                  <p className="text-[11.25px] text-[#9ca3af] font-medium">
                    {role === "lecturer" ? "lecturer@university.edu" : "instructor@university.edu"}
                  </p>
                </div>
                <ChevronDown size={13} className="text-[#9ca3af]"/>
              </button>
              {showProfile && (
                <div className="absolute right-0 top-full mt-2 w-52 bg-white border border-[#dde3ee] rounded-xl shadow-lg z-50 overflow-hidden">
                  <div className="px-4 py-3 border-b border-[#dde3ee]">
                    <p className="text-sm font-semibold text-[#0f1c2e]">
                      {role === "lecturer" ? "Dr. Nimal Perera" : "Ms. Thilini Fernando"}
                    </p>
                    <span className={`inline-block mt-1 px-2 py-0.5 text-[10px] font-bold rounded-full ${role === "lecturer" ? "bg-[#dbeafe] text-[#1c398e]" : "bg-[#ede9fe] text-[#4d179a]"}`}>
                      {role.toUpperCase()}
                    </span>
                  </div>
                  <div className="py-1">
                    <button onClick={() => { setActiveTab("settings"); setShowProfile(false); }}
                      className="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-[#0f1c2e] hover:bg-[#f4f6f9] transition-colors">
                      <Settings size={14} className="text-[#6b7c96]"/> Settings
                    </button>
                    <button onClick={() => navToPage("login")}
                      className="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-[#ef4444] hover:bg-[#fff5f5] transition-colors">
                      <LogOut size={14}/> Sign Out
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </header>

        {/* Screen content */}
        <main className="flex-1 overflow-hidden">
          {activeTab === "timetable"     && <TimetableScreen onCreateGroup={addConversation} role={role} />}
          {activeTab === "workload"      && <WorkloadScreen />}
          {activeTab === "requests"      && <RequestsScreen />}
          {activeTab === "leave"         && <LeaveScreen />}
          {activeTab === "messages"      && <MessagesScreen conversations={conversations} addConversation={c => setConversations(prev => [c, ...prev])} />}
          {activeTab === "settings"      && <SettingsScreen />}
        </main>
      </div>
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════════════
// ROOT
// ══════════════════════════════════════════════════════════════════════════
export default function App() {
  const [page, setPage] = useState<Page>("login");
  const [role, setRole] = useState<UserRole>("instructor");
  return (
    <div className="w-full h-full">
      {page === "login"     && <LoginPage   nav={setPage} setRole={setRole} />}
      {page === "signup"    && <SignUpPage   nav={setPage} />}
      {page === "forgot"    && <ForgotPage   nav={setPage} />}
      {page === "dashboard" && <Dashboard    navToPage={setPage} role={role} />}
    </div>
  );
}
