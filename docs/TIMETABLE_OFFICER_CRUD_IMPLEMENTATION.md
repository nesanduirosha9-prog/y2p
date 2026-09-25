# Timetable Officer CRUD — Implementation Log

> **Date:** 2026-09-25 · **Branch:** `timetableofficer-CRUD` (on top of `7ccc859`) · **Not committed.**
> **Plan this implements:** [`TIMETABLE_OFFICER_CRUD_ANALYSIS.md`](TIMETABLE_OFFICER_CRUD_ANALYSIS.md)
>
> This file records **every step, every file, and every block of code added or
> removed**, in the order it was done. It is meant to be enough to:
> 1. **redo** the work by hand (follow the steps top to bottom),
> 2. **undo** any part of it (see §5), and
> 3. **change** behaviour later (see §6 — every rule lives in one place).

---

## 0. Summary

| Module | Before | After | New endpoints |
|---|---|---|---|
| **Lecture Halls** (`rooms`) | R, U | **C R U D** | `POST /lecture-halls`, `DELETE /lecture-halls/{code}` |
| **Courses** (`courses` + `course_staff`) | R (C/U/D were DOM-only) | **C R U D** | `POST /courses`, `PUT /courses/{code}`, `DELETE /courses/{code}` |
| **Timetable Sessions** (`timetable_sessions`) | R (C/U/D were DOM-only) | **C R U D** + clash check | `POST /timetable/sessions`, `PUT` / `DELETE /timetable/sessions/{room}/{day}/{hour}` |

**Files: 11 modified, 3 created. Database: 2 migrations applied (FK change only — no new tables or columns, no data changed).**

| # | File | Status | Phase |
|---|---|---|---|
| 1 | `app/models/RoomModel.php` | modified | A |
| 2 | `app/controllers/timetable_officer/LectureHallsController.php` | modified | A |
| 3 | `app/views/timetable_officer/lecture_halls.php` | modified | A |
| 4 | `app/public/js/lecture_halls.js` | **rewritten** | A |
| 5 | `app/models/CourseModel.php` | modified | B |
| 6 | `app/controllers/timetable_officer/CoursesController.php` | **rewritten** (index() unchanged) | B |
| 7 | `app/views/timetable_officer/course_details.php` | modified | B |
| 8 | `app/public/js/courses.js` | modified | B |
| 9 | `database/migrations/018_alter_assignments_session_fk.sql` | **new** | C |
| 10 | `database/migrations/019_alter_reschedule_session_fk.sql` | **new** | C |
| 11 | `app/models/TimetableSessionModel.php` | modified | C |
| 12 | `app/controllers/timetable_officer/TimetableSessionsController.php` | **new** | C |
| 13 | `app/public/js/timetable.js` | modified | C |
| 14 | `app/public/index.php` | modified | A, B, C |

### ⚠️ Work that was already in the tree and is NOT part of this change
`git status` showed ~49 files already modified before this work started (CSS,
workload, settings, handover, …). None of them were touched, **except** that
two files I edited already contained someone else's uncommitted edits:

- `app/views/timetable_officer/lecture_halls.php` — the `<div class="page-head">…</div>` block had
  already been removed. (That's why the **Add Hall** button went into the search row.)
- `app/public/index.php` — a new `GET /settings/handover/candidates/{position}` route had already been added.
- `app/models/RoomModel.php` — **you** had already added `create()` and the two stubs (Step A1 below).
- `app/views/timetable_officer/lecturers.php` shows as modified too — not by this work.

So **`git diff` / `git checkout` on those files will include or throw away that
other work as well.** §5 explains how to undo safely.

### Design rules used everywhere
1. **Bottom-up per operation:** Model (SQL) → Controller (guard + validate + JSON) → Route → JS.
2. **Every write is guarded:** `guardJson($response, 'role', 'timetable_officer')` → any other role gets `401`.
3. **The browser always sends JSON** (`Content-Type: application/json`) — `Request::getBody()` only parses PUT/DELETE bodies that are JSON, and HTML-mangles form-encoded POST values.
4. **The DOM changes only after the server says `success: true`.** A reload always shows the truth.
5. **Status codes:** `400` bad input · `401` not officer · `404` not found · `409` conflict (duplicate / in use / clash) · `500` DB failure. Every error body is `{success:false, message:"…"}` and the UI shows `message` verbatim.
6. **Primary-key codes are never editable** (room code, course code) — five/one tables reference them without `ON UPDATE CASCADE`.
7. **Deletes that would cascade are refused** (room or course still on the timetable → `409`).

---

## 1. Phase A — Lecture Halls (added Create + Delete)

### A1. `app/models/RoomModel.php`
**Header comment** (above `class RoomModel`) — replaced:
```php
// BEFORE
// RoomModel: reads/updates the room/lecture-hall catalog for the
// "Lecture Halls" screen. `code` is the room's primary key, so editing a
// room updates its capacity/type in place — the code itself isn't editable
// here (it's also the FK every timetable_session references).

// AFTER
// RoomModel: full CRUD over the room/lecture-hall catalog for the
// "Lecture Halls" screen. `code` is the room's primary key, so editing a
// room updates its capacity/type in place — the code itself isn't editable
// here (it's also the FK every timetable_session references). Deleting is
// only safe when sessionCount() is 0: timetable_sessions.room_code is
// ON DELETE CASCADE, so the database would otherwise wipe those sessions.
```
**`create()`** — already written by you; kept as-is.
**`sessionCount()` and `delete()`** — your stubs (`// your turn: …`) replaced with:
```php
    /** How many timetable sessions use this room — deleting it would cascade to all of them. */
    public function sessionCount(string $code): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable_sessions WHERE room_code = :code");
        $stmt->execute(['code' => $code]);
        return (int) $stmt->fetchColumn();
    }

    /** Delete a room. Returns false if no row matched. */
    public function delete(string $code): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE code = :code");
        return $stmt->execute(['code' => $code]) && $stmt->rowCount() > 0;
    }
```
Also removed the trailing whitespace on the blank lines around them and the empty line before the closing `}`.

### A2. `app/controllers/timetable_officer/LectureHallsController.php`
**Header comment** — replaced the 5-line `view + edit` comment with:
```php
// LectureHallsController (timetable officer): full CRUD over the room catalog.
// 1. index()   — GET, lists every room (guarded to timetable officers).
// 2. store()   — POST /lecture-halls, adds a room via RoomModel::create().
// 3. update()  — PUT /lecture-halls/{code}, validates type/capacity from the
//    JSON body (note: only JSON bodies are parsed for non-GET/POST verbs —
//    see Request::getBody()) and persists via RoomModel::update().
// 4. destroy() — DELETE /lecture-halls/{code}, refused while the room has
//    timetable sessions (see RoomModel::sessionCount()).
```
**Added after `update()`** (before the class's closing `}`):
```php
    /** POST /lecture-halls — body: { code, type, capacity }. Adds a room. */
    public function store(Request $request, Response $response)
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $body = $request->getBody();
        $code = strtoupper(trim((string)($body['code'] ?? '')));
        $type = $body['type'] ?? '';
        $capacity = filter_var($body['capacity'] ?? null, FILTER_VALIDATE_INT);

        // rooms.code is VARCHAR(20); keep it to the shape the seeds use (LT-301, LAB-A201).
        if (!preg_match('/^[A-Z0-9][A-Z0-9-]{1,19}$/', $code)) {
            $response->json(['success' => false, 'message' => 'Hall code must be 2–20 letters, digits or dashes.'], 400);
            return;
        }
        if (!in_array($type, self::VALID_TYPES, true) || $capacity === false || $capacity < 1) {
            $response->json(['success' => false, 'message' => 'Invalid hall type or capacity.'], 400);
            return;
        }

        $rooms = new RoomModel();
        if ($rooms->exists($code)) {
            $response->json(['success' => false, 'message' => "A hall called {$code} already exists."], 409);
            return;
        }
        if (!$rooms->create($code, $type, $capacity)) {
            $response->json(['success' => false, 'message' => 'Could not save the hall.'], 500);
            return;
        }

        $response->json(['success' => true, 'code' => $code]);
    }

    /**
     * DELETE /lecture-halls/{code}. Refused (409) while any timetable session
     * is booked in the room — the FK is ON DELETE CASCADE, so deleting it
     * anyway would silently wipe those sessions from the timetable.
     */
    public function destroy(Request $request, Response $response, array $params = [])
    {
        if (!$this->guardJson($response, 'role', 'timetable_officer')) {
            return;
        }

        $code = $params['code'] ?? '';
        $rooms = new RoomModel();
        if (!$rooms->exists($code)) {
            $response->json(['success' => false, 'message' => 'Hall not found.'], 404);
            return;
        }

        $booked = $rooms->sessionCount($code);
        if ($booked > 0) {
            $response->json(['success' => false, 'message' => "{$code} has {$booked} timetable session(s). Move or delete them first."], 409);
            return;
        }

        if (!$rooms->delete($code)) {
            $response->json(['success' => false, 'message' => 'Could not delete the hall.'], 500);
            return;
        }

        $response->json(['success' => true]);
    }
```
Nothing removed. (`use app\core\Response;` was already imported.)

### A3. `app/public/index.php` — Lecture Halls routes
In the `// --- Lecture halls ---` section. **Comment** changed from
`// Already role-free before this refactor; timetable officer only.` to:
```php
// Already role-free before this refactor; timetable officer only. Full CRUD:
// the writes answer JSON and are called by js/lecture_halls.js.
```
**Added** `POST` before the existing `PUT`, and `DELETE` after it:
```php
$router->post('/lecture-halls', function (Request $request, Response $response) {
    return (new LectureHallsController())->store($request, $response);
});
// (existing $router->put('/lecture-halls/{code}', ...) stays here, unchanged)
$router->delete('/lecture-halls/{code}', function (Request $request, Response $response, array $params) {
    return (new LectureHallsController())->destroy($request, $response, $params);
});
```

### A4. `app/views/timetable_officer/lecture_halls.php`
Five edits:

1. **Header comment** → now says Add / Edit / Delete persist through `LectureHallsController` and that one drawer serves Add and Edit.
2. **Add Hall button** — added inside `<div class="dir-controls">`, after the search box:
   ```php
        <button type="button" class="btn-primary" id="addHallBtn" style="margin-left: auto;">
            <i class="fa-solid fa-plus"></i> Add Hall
        </button>
   ```
   (Inline `margin-left:auto` right-aligns it; the matching class `.staff-add-btn` lives in `coordinator/staff.css`, which this page doesn't load.)
3. **Delete button per row** — the Actions cell:
   ```php
   <!-- BEFORE -->
   <td>
       <button type="button" class="link-action" data-edit-hall>Edit</button>
   </td>
   <!-- AFTER -->
   <td>
       <div class="tag-row">
           <button type="button" class="link-action" data-edit-hall>Edit</button>
           <button type="button" class="icon-action danger" data-delete-hall title="Delete hall"><i class="fa-regular fa-trash-can"></i></button>
       </div>
   </td>
   ```
4. **Drawer comment + subtitle id** — the `<!-- Edit Lecture Hall Side Drawer … -->` comment now reads `Add / Edit Lecture Hall Side Drawer — lecture_halls.js switches the title, subtitle, button text and whether the code field is editable.`, and the subtitle got an id:
   ```php
   <p class="side-drawer-subtitle" id="hallModalSubtitle">Update capacity and venue type configuration</p>
   ```
5. **Code input** — `<input type="text" id="hallFieldName" disabled>` →
   ```php
   <input type="text" id="hallFieldName" maxlength="20" placeholder="LT-501" disabled>
   ```
   It stays `disabled` in the HTML; the JS enables it in Add mode only.

### A5. `app/public/js/lecture_halls.js` — rewritten (whole file)
**Removed:** the old file (116 lines: search, `openModalFor(row)`, and a submit handler that only did `PUT`).
**Now:**
- `sendJson(method, url, body)` — fetch wrapper that sends JSON and rejects with the server's `message`.
- `applySearch()` — unchanged logic; empty-state text now keys off the query.
- `openModal(row)` — one function for both modes: `row === null` → **Add** (code field enabled, blank, type defaults to *Lecture Hall*); otherwise **Edit** (code field disabled, prefilled).
- `#addHallBtn` → `openModal(null)`.
- Row clicks: `[data-edit-hall]` → `openModal(row)`; `[data-delete-hall]` → `deleteHall(row)` = `confirm()` → `DELETE` → remove row on success, `alert(message)` on failure.
- Submit: Add → `POST /lecture-halls` then `window.location.reload()` (server renders the new row in sorted order); Edit → `PUT` then patches the row in place (same as before).

Full file: see the current `app/public/js/lecture_halls.js` (≈150 lines, commented). The old version: `git show HEAD:app/public/js/lecture_halls.js`.

---

## 2. Phase B — Courses (added Create, Update, Delete)

### B1. `app/models/CourseModel.php`
1. **Header comment** → lists all methods, including that `create()/update()` write `course_staff` in one transaction and `delete()` is only safe when `sessionCount()` is 0.
2. **`listing()` now returns the semester** (needed to prefill the Edit form):
   ```php
   // SQL: added `semester`
   "SELECT code, title, credits, year_of_study, semester, department FROM courses ORDER BY code"
   // output row: added
   'semester' => (int) $r['semester'],
   ```
   and its docblock shape now includes `'semester'`.
3. **Added after `listing()`** (before `linkCodes()`):
```php
    public function create(array $c, array $lecturers, array $instructors): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO courses (code, title, credits, department, year_of_study, semester)
                 VALUES (:code, :title, :credits, :department, :year_of_study, :semester)"
            )->execute($c);
            $this->writeStaff($pdo, $c['code'], $lecturers, $instructors);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function update(array $c, array $lecturers, array $instructors): bool
    {
        // same shape: UPDATE courses SET title, credits, department, year_of_study, semester
        //             WHERE code = :code  → writeStaff() → commit / rollBack
    }

    public function delete(string $code): bool
    {
        // DELETE FROM courses WHERE code = :code; return execute && rowCount() > 0
    }

    public function sessionCount(string $code): int
    {
        // SELECT COUNT(*) FROM timetable_sessions WHERE course_code = :code
    }

    private function writeStaff(PDO $pdo, string $code, array $lecturers, array $instructors): void
    {
        $pdo->prepare("DELETE FROM course_staff WHERE course_code = :code")->execute(['code' => $code]);
        $insert = $pdo->prepare(
            "INSERT INTO course_staff (course_code, staff_code, assignment_role) VALUES (:course, :staff, :role)"
        );
        foreach ($lecturers as $staffCode)   { $insert->execute(['course' => $code, 'staff' => $staffCode, 'role' => 'lecturer']); }
        foreach ($instructors as $staffCode) { $insert->execute(['course' => $code, 'staff' => $staffCode, 'role' => 'instructor']); }
    }
```
(`update/delete/sessionCount` are written out in full in the file; the comments above are exactly what they do.)
**Important:** `$c` must contain **exactly** `code, title, credits, department, year_of_study, semester` — PDO throws on extra or missing named parameters. The controller builds it that way.

### B2. `app/controllers/timetable_officer/CoursesController.php` — rewritten
- **Imports added:** `use app\core\Response;` and `use app\models\StaffModel;`
- **Header comment** → numbered list of `index / store / update / destroy`.
- **`index()`** — unchanged.
- **Added `store()`** — guard → `readCourse()` (400) → exists? (409) → `CourseModel::create()` (500) → `{success:true}`.
- **Added `update()`** — guard → `readCourse()` (400) → **forces `$course['code'] = $params['code']`** (URL wins; code not editable) → not found (404) → `CourseModel::update()` (500).
- **Added `destroy()`** — guard → not found (404) → `sessionCount() > 0` (409 "…has N timetable session(s). Remove them from the timetable first.") → `delete()`.
- **Added `private readCourse(Request)`** → returns `[course, lecturers, instructors, errorOrNull]`. The rules:

| Field (JSON key) | Rule | Error |
|---|---|---|
| `code` | uppercased; `/^[A-Z0-9]{3,20}$/` | Course code must be 3–20 letters or digits. |
| `title` | non-empty, ≤150 chars | Course name is required (max 150 characters). |
| `credits` | int 1–12 | Credits must be between 1 and 12. |
| `program` → `department` | `CS`/`IS` lowercased to `cs`/`is` | Choose a program (CS or IS). |
| `year` → `year_of_study` | 1–4 | Choose an academic year. |
| `semester` | 1–2 | Choose a semester. |
| `lecturers[]`, `instructors[]` | de-duplicated; no overlap between the two lists (PK is `(course_code, staff_code)`) | One person cannot be both lecturer and instructor on the same course. |
| each staff code | exists, `role='academic_staff'`, `status='active'` | Unknown staff member: XYZ. |

### B3. `app/public/index.php` — Courses routes
Added **directly after** the existing `$router->get('/courses', …)` block:
```php
// Officer-only writes behind the Course Details drawer (JSON, js/courses.js).
// Different verbs from the GET above, so no dispatch is needed — the
// controller's guardJson() rejects every other role.
$router->post('/courses', function (Request $request, Response $response) {
    return (new OfficerCoursesController())->store($request, $response);
});
$router->put('/courses/{code}', function (Request $request, Response $response, array $params) {
    return (new OfficerCoursesController())->update($request, $response, $params);
});
$router->delete('/courses/{code}', function (Request $request, Response $response, array $params) {
    return (new OfficerCoursesController())->destroy($request, $response, $params);
});
```

### B4. `app/views/timetable_officer/course_details.php`
1. **Header comment** — `… add/edit/delete actions are all handled client-side in courses.js and do not persist across a reload.` → `… Add / Edit / Delete persist through POST /courses, PUT and DELETE /courses/{code}.`
2. **Row attribute added** after `data-year`:
   ```php
   data-semester="<?= (int)$c['semester'] ?>"
   ```
3. **Code input:** added `maxlength="20"` to `#fieldCode`.
4. **Semester field added** after the Year/Program `field-grid`:
   ```php
            <!-- courses.semester is NOT NULL with no default, so the form must supply it. -->
            <div class="form-row">
                <label for="fieldSemester">Semester</label>
                <select id="fieldSemester" required>
                    <option value="">Select&hellip;</option>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </select>
            </div>
   ```

### B5. `app/public/js/courses.js`
1. **Header comment** → says add/edit/delete persist and the row is redrawn only after the server confirms.
2. **Added `sendJson()`** right after the `esc` helper (same function as in `lecture_halls.js`).
3. **Added** `const fieldSemester = document.getElementById('fieldSemester');` after `fieldProgram`.
4. **`openModal()`** — Edit branch added:
   ```js
   fieldSemester.value = row.dataset.semester || '';
   fieldCode.disabled = true; // the code is the primary key — not editable
   ```
   Add branch added: `fieldCode.disabled = false;`
5. **`refreshSubmitState()`** — `ready` now also requires `fieldSemester.value`; `fieldSemester` added to the list of fields that re-check on input/change.
6. **Delete** — replaced:
   ```js
   // BEFORE (DOM-only)
   if (confirm('Delete ' + row.dataset.code + '? This cannot be undone.')) {
       row.remove();
       applyFilters();
   }
   // AFTER
   const code = row.dataset.code;
   if (!confirm('Delete ' + code + '? This cannot be undone.')) return;
   sendJson('DELETE', '/courses/' + encodeURIComponent(code))
       .then(function () { row.remove(); applyFilters(); })
       .catch(function (err) { alert(err.message); });
   ```
7. **Save** — the whole `// ---- Save (add or update a row, DOM-only) ----` block was replaced.
   **Removed:** the submit handler that built the row immediately, including the workaround *"adding a code that already exists → treat it as an edit"* (the server now answers 409 instead).
   **Added:**
   - `renderRow(row, c)` — the same row HTML as before, now built from the saved payload (also sets `data-semester`; program text is escaped).
   - A submit handler that builds `payload = {code, title, credits, year, semester, program, lecturers[], instructors[]}` → `POST /courses` (add) or `PUT /courses/{code}` (edit) → on success `renderRow()` + close drawer + `applyFilters()`; on failure `alert(message)`; `.finally(refreshSubmitState)` re-enables the button.
   - `tagRow()` is unchanged.

---

## 3. Phase C — Timetable Sessions (added Create, Update, Delete)

### C1. Migrations (new files) — **applied to your local DB**
`database/migrations/018_alter_assignments_session_fk.sql`
```sql
ALTER TABLE assignments
    DROP FOREIGN KEY fk_assignments_session,
    ADD CONSTRAINT fk_assignments_session_cascade
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE ON UPDATE CASCADE;
```
`database/migrations/019_alter_reschedule_session_fk.sql` — the same for `reschedule_requests`
(`fk_reschedule_session` → `fk_reschedule_session_cascade`).

**Why:** a session's primary key is `(room_code, day_of_week, start_hour)`. Editing
the room/day/time *changes the PK*; without `ON UPDATE CASCADE` the UPDATE fails
for any session that has assignments or reschedule requests. New constraint
names because drop + re-add under one name in a single `ALTER` is unreliable.

**Applied with:** `php database/migrate.php` → `applying 018 … ok`, `applying 019 … ok`.
**Verified:** both FKs now report `UPDATE_RULE = CASCADE, DELETE_RULE = CASCADE`.
**Teammates:** must run `php database/migrate.php` after pulling.

### C2. `app/models/TimetableSessionModel.php`
1. **Header comment** — added: the PK only catches two sessions *starting* together; overlaps and cohort clashes are caught by `findClash()`.
2. **`create()` docblock** — added `Called by TimetableSessionsController::store(), after findClash() has passed.` (the method body is unchanged — it already existed but had no caller).
3. **Added after `create()`:**
   - `exists(string $room, string $day, int $hour): bool`
   - `findClash(array $d, ?array $ignore = null): ?array`:
     ```sql
     SELECT course_code, room_code, start_hour FROM timetable_sessions
     WHERE day_of_week = :day
       AND start_hour < :end_hour                       -- existing starts before new ends
       AND start_hour + duration_hours > :start_hour    -- existing ends after new starts
       AND (room_code = :room                           -- same room, or
            OR (department = :dept AND semester = :sem AND year_of_study = :year))  -- same cohort
       [AND NOT (room_code = :i_room AND day_of_week = :i_day AND start_hour = :i_hour)]  -- on edit: skip itself
     LIMIT 1
     ```
   - `update(array $key, array $d): bool` — `UPDATE … SET room_code, day_of_week, start_hour, course_code, duration_hours, session_type, managed_by_code WHERE` *(original key)*. Department/semester/year are **not** updated (a session belongs to the cohort page it's on).
   - `delete(string $room, string $day, int $hour): bool` — `DELETE … WHERE` key; `rowCount() > 0`.

### C3. `app/controllers/timetable_officer/TimetableSessionsController.php` — **new file**
A separate controller because `app\controllers\TimetableController` is shared with academic staff and only renders the grid.

| Method | Route | Flow |
|---|---|---|
| `store()` | `POST /timetable/sessions` | guard → `readSession()` (400) → `findClash()` (409) → `create()` (500) |
| `update()` | `PUT /timetable/sessions/{room}/{day}/{hour}` | guard → `keyFrom()` + `exists()` (404) → `readSession()` (400) → `findClash($data, $key)` (409) → `update($key, $data)` |
| `destroy()` | `DELETE /timetable/sessions/{room}/{day}/{hour}` | guard → `keyFrom()` → `delete()` (404 if nothing deleted) |

Constants (the rules — change them here, see §6):
```php
private const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri'];
private const TYPES = ['lecture', 'tutorial', 'lab', 'practical'];
private const DAY_START = 8;   // first grid row
private const DAY_END = 17;    // a session must finish by 5 PM (last row is 4 PM)
private const LUNCH = 12;      // the 12–1 PM row is the lunch break
```
`readSession()` validation, in order:

| Check | Error (400) |
|---|---|
| `day_of_week` in DAYS | Invalid day. |
| `session_type` in TYPES | Invalid session type. |
| `department` cs/is, `semester` 1–2, `year_of_study` 1–4 | Invalid department, semester or year. |
| `start_hour`, `duration_hours` ints, duration 1–3 | Invalid start time or duration. |
| `start ≥ 8` and `start + duration ≤ 17` | Sessions must run between 8 AM and 5 PM. |
| not (`start ≤ 12` and `end > 12`) | Sessions cannot run through the 12–1 PM lunch break. |
| room exists | Choose a valid hall or lab. |
| course exists **and** matches the page's department + year | That course does not belong to this department and year. |

`managed_by_code` is always set to `$_SESSION['staff_code']` (EER *Manages*).
Clash message: `Clashes with CS1101 in LT-301 at 2 PM.` (uses `ViewHelpers::hourLabel()`).

### C4. `app/public/index.php` — Timetable routes
**Import added** after `use …\LectureHallsController;`:
```php
use app\controllers\timetable_officer\TimetableSessionsController;
```
**Added directly after** `$router->get('/timetable', …)`:
```php
// Officer-only writes behind the grid (JSON, js/timetable.js). The URL carries
// a session's ORIGINAL key (room/day/hour), since an edit may move it. Five
// segments, so nothing else can shadow these in the {param} loop.
$router->post('/timetable/sessions', function (Request $request, Response $response) {
    return (new TimetableSessionsController())->store($request, $response);
});
$router->put('/timetable/sessions/{room}/{day}/{hour}', function (Request $request, Response $response, array $params) {
    return (new TimetableSessionsController())->update($request, $response, $params);
});
$router->delete('/timetable/sessions/{room}/{day}/{hour}', function (Request $request, Response $response, array $params) {
    return (new TimetableSessionsController())->destroy($request, $response, $params);
});
```

### C5. `app/public/js/timetable.js`
**Design:** after every successful write the page **reloads**. The grid (which
cells are free vs covered by a multi-hour block) is laid out by PHP; the old
DOM-patching code got this wrong (e.g. Edit never freed the old cells).

1. **Header comment** — added 3 lines: writes persist through `/timetable/sessions` then reload; Publish is still `localStorage`; the archive is hardcoded.
2. **Added after `showToast()`:**
   ```js
   function sessionUrl(block)   // '/timetable/sessions/' + room + '/' + dayKey + '/' + startHour  (the ORIGINAL key, from data-*)
   function sessionPayload(courseCode, roomCode, dayKey, startHour, duration, type)
                                // → {course_code, room_code, day_of_week, start_hour, duration_hours,
                                //    session_type, department: dept, semester: sem, year_of_study: year}
   function saveSession(method, url, payload, successMessage)
                                // fetch JSON → success: green toast + reload after 800 ms
                                //              failure: amber toast with the server's message, no reload
   function esc(s)              // HTML-escape (course titles are user-entered now)
   ```
3. **XSS hardening:** `field()` now outputs `${esc(value)}`; both `courseOptions` loops (edit form and quick-schedule form) now escape `code` and `c.title`.
4. **Edit → Save** (`#saveEditBtn`): **removed** ~35 lines that rewrote the block's `data-*`, grid position and `innerHTML`, then `setDraftStatus()` + toast + reopen panel. **Now:**
   ```js
   const payload = sessionPayload(editCourseCode, editVenue, editDay, editStartHour, editDuration, editSessionType);
   saveSession('PUT', sessionUrl(block), payload, `Session ${payload.course_code} updated.`);
   ```
5. **Delete → confirm** (`#tspConfirmDelBtn`): **removed** ~30 lines that removed the block and re-created empty cells. **Now:**
   ```js
   saveSession('DELETE', sessionUrl(block), null, `Session ${d.code} deleted.`);
   ```
6. **Click empty slot → Save** (`#saveNewBtn`): **removed** the block-building code and the `|| 'TBA'` venue fallback (not a real room). **Now:** requires course **and** venue, then `saveSession('POST', '/timetable/sessions', payload, …)`.
7. **Schedule Course → Add to Timetable** (`#addToTimetableBtn`): **removed** the block-building code and the `'TBA'` fallback. **Now:** requires course and venue, **rejects non-consecutive slot selections** (`max − min + 1 !== count` → "Please select consecutive time slots."), then POSTs with `duration = selectedCells.length`.

`setDraftStatus()` is still defined but no longer called (the "Unpublished Changes" badge was DOM-only anyway). Safe to delete later.

---

## 4. How it was tested

All on the PHP dev server (`php -S 127.0.0.1:8099 -t app/public`) signed in as `tmo@`, using `curl` with JSON bodies. **Every test passed.** All test rows were deleted afterwards — the DB is back to seed counts (rooms 9, courses 15, course_staff 33, timetable_sessions 10, assignments 5).

| Area | Tests (expected → got) |
|---|---|
| Halls | create `demo-101` → 200 (stored as `DEMO-101`) · duplicate → 409 · code `x` → 400 · type `castle` → 400 · PUT capacity 80 → 200 · DELETE `LT-301` (in use) → 409 · DELETE `DEMO-101` → 200 · again → 404 |
| Courses | create with DSC lecturer + MKA instructor → 200, `course_staff` rows correct · duplicate → 409 · no semester → 400 · same person both roles → 400 · unknown staff → 400 · PUT (title, credits, dept, year, sem, staff) → 200, DB matches exactly · PUT unknown → 404 · DELETE `CS1101` (scheduled) → 409 · DELETE → 200, `course_staff` rows gone · again → 404 · page renders `data-semester` on all 15 rows |
| Sessions | create Fri 2–4 PM → 200 · cohort clash (other room) → 409 · room clash (other cohort) → 409 · across lunch → 400 · past 5 PM → 400 · IS course on CS page → 400 · bad room → 400 · edit in place to 3 h (no self-clash) → 200 · move to LT-302 Fri 9 → 200 (`managed_by_code=TMO`) · PUT old key → 404 · DELETE → 200 · again → 404 |
| FK cascade | moved seeded session `LAB-A201 mon 8` (3 assignments) to Fri → assignments followed it → moved back → assignments back on Mon |
| Security | as Coordinator (`mka@`): POST hall / DELETE course / DELETE session → all **401 Unauthorized** · logged out → **401 Not authenticated** |
| Syntax | `php -l` on every PHP file, `node --check` on every JS file → clean |

**Not tested automatically: clicking through the UI in a browser.** Please do it once:

- [ ] **Halls:** Add Hall → reload → still there · Edit capacity → reload · 🗑 on `LT-301` → alert "has N sessions" · 🗑 on your new hall → reload → gone
- [ ] **Courses:** Add Course (button stays disabled until Semester is chosen) → reload · Edit: Code field greyed out, Semester prefilled → change → reload · 🗑 `CS1101` → alert · 🗑 your course → reload → gone
- [ ] **Timetable** (CS · Sem 1 · Y1): click a free Friday cell → Save → page reloads with the block · click it → Edit → change time/room → reload shows it moved · click Tue 9 AM, 2 hours → amber "Clashes with CS1102…" toast · Schedule Course → select two consecutive cells → Add → 2-hour block · Delete → gone
- [ ] Check the browser console (F12) shows no errors on all three pages

---

## 5. How to undo

### Undo everything (code)
The three **new** files can simply be deleted:
```bash
rm app/controllers/timetable_officer/TimetableSessionsController.php
rm database/migrations/018_alter_assignments_session_fk.sql database/migrations/019_alter_reschedule_session_fk.sql
```
Files that contained **only** this work can be reset from git:
```bash
git checkout -- app/models/CourseModel.php app/models/TimetableSessionModel.php \
  app/controllers/timetable_officer/CoursesController.php \
  app/controllers/timetable_officer/LectureHallsController.php \
  app/views/timetable_officer/course_details.php \
  app/public/js/lecture_halls.js app/public/js/courses.js app/public/js/timetable.js
```
⚠️ **Do NOT `git checkout` these three** — they also hold other uncommitted work (see §0). Revert them by hand using §1–§3:
- `app/public/index.php` → remove the `TimetableSessionsController` import, the 3 `/timetable/sessions` routes, the 3 `/courses` write routes, and the `POST`/`DELETE` `/lecture-halls` routes (+ restore the two comments). Keep the `/settings/handover/candidates/{position}` route — that isn't mine.
- `app/views/timetable_officer/lecture_halls.php` → remove the Add Hall button, the delete button + `tag-row` wrapper, the `id="hallModalSubtitle"`, the `maxlength`/`placeholder`, and restore the two comments. Leave the removed `page-head` removed — that isn't mine.
- `app/models/RoomModel.php` → your `create()` and stubs were there before; to go back to *your* version, put the two `// your turn` stubs back in `sessionCount()` / `delete()` and restore the header comment.

### Undo the database change (migrations 018/019)
Run in phpMyAdmin or `mysql -u root staffsync_db`, **then** delete the two migration files:
```sql
ALTER TABLE assignments
    DROP FOREIGN KEY fk_assignments_session_cascade,
    ADD CONSTRAINT fk_assignments_session
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE;

ALTER TABLE reschedule_requests
    DROP FOREIGN KEY fk_reschedule_session_cascade,
    ADD CONSTRAINT fk_reschedule_session
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE;

DELETE FROM schema_migrations
 WHERE filename IN ('018_alter_assignments_session_fk.sql', '019_alter_reschedule_session_fk.sql');
```
(Or, if you don't mind losing local data: delete the two files and run `php database/migrate.php --fresh --seed`.)
If you undo the migrations but **keep** the timetable code, editing a session's room/day/time will fail with a 500 for sessions that have assignments or reschedule requests — creating, deleting and in-place edits keep working.

### Undo one phase only
- **Phase A:** revert A1–A5 (the hall routes in `index.php`, RoomModel methods, controller `store/destroy`, view edits, `git checkout` `lecture_halls.js`).
- **Phase B:** `git checkout` `CourseModel.php`, `CoursesController.php`, `course_details.php`, `courses.js`, and remove the 3 `/courses` write routes from `index.php`.
- **Phase C:** delete `TimetableSessionsController.php`, `git checkout` `TimetableSessionModel.php` and `timetable.js`, remove the import + 3 routes from `index.php`, and optionally undo the migrations as above.

---

## 6. How to change things later

| I want to… | Change |
|---|---|
| Allow deleting a hall that has sessions (cascade them) | `LectureHallsController::destroy()` — remove the `sessionCount()` / 409 block |
| Allow deleting a scheduled course | `CoursesController::destroy()` — remove the `sessionCount()` / 409 block (sessions, assignments etc. will cascade) |
| Change the hall-code format | regex in `LectureHallsController::store()` (DB limit: 20 chars) + `maxlength` in `lecture_halls.php` |
| Change the course-code format | regex in `CoursesController::readCourse()` + `maxlength` in `course_details.php` |
| Allow a person to be lecturer **and** instructor on one course | not possible without a schema change — `course_staff` PK is `(course_code, staff_code)` |
| Change teaching hours / lunch / max duration | constants at the top of `TimetableSessionsController` (and the grid's `$hours` in `timetable.php` + `HOURS` in `timetable.js`, which must agree) |
| Allow sessions through lunch | delete the LUNCH check in `TimetableSessionsController::readSession()` |
| Only block room clashes, not cohort clashes | `TimetableSessionModel::findClash()` — drop the `OR (department … )` part |
| Also block a **lecturer** teaching two classes at once | extend `findClash()`: join `course_staff` on the new course's lecturers and look for overlapping sessions of any course they teach |
| Update the page without reloading after a timetable save | replace `window.location.reload()` in `saveSession()` with DOM updates (the old code is in `git show HEAD:app/public/js/timetable.js` — note it didn't free/occupy cells correctly) |
| Let the course pickers show staff who don't teach anything yet | `CoursesController::index()` — build `lecturers` / `instructors` from `StaffModel::activeStaff('senior'/'junior')` instead of `LecturerModel::all()` / `InstructorModel::all()` |

---

## 7. What's still not persisted (unchanged by this work)
- **Publish Timetable** → `localStorage` only; the "Published / Unpublished" badge is cosmetic.
- **Past academic years archive** → hardcoded in `timetable.js`.
- Messages, Staff Details (read-only by design), and everything on the academic-staff side.

## 8. Follow-ups (not done)
- Add 016–019 to the table in `database/README.md`.
- Update `PROJECT_CONTEXT.md` feature table (all three officer CRUDs are now ✅) and the status tables in `TIMETABLE_OFFICER_CRUD_ANALYSIS.md`.
- Commit per phase once you've done the browser checklist in §4.
