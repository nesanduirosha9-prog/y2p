# Timetable Officer — CRUD Analysis & Demo Plan

> **Date:** 2026-09-24 · **Branch:** `feature-workload-ui` @ `7ccc859`
> **Method:** Read-only analysis. Checked against the code (routes, controllers,
> models, views, JS) and the live `staffsync_db` (MariaDB 10.4.28, all 17
> migrations applied). Nothing in the codebase was changed.
>
> ⚠️ `PROJECT_CONTEXT.md` is **out of date** (it still describes the old `users` /
> `lecturers` tables and the old route map). Use this report instead.

---

## 1. Short answers

| Question | Answer |
|---|---|
| **How many CRUDs does the Timetable Officer need?** | **3 full CRUD modules**: **Timetable Sessions**, **Courses** (including course ↔ staff assignments) and **Lecture Halls**. That is **12 operations** (3 × C/R/U/D). The officer's other 4 screens are partial on purpose (see §2). |
| **How many already work?** | **0 of 3 full CRUDs.** At the operation level, **4 of 12** operations save to the database: Read ×3 plus Lecture Hall Update. Another **6 have a UI but are DOM-only**: they change the page, and a reload undoes them. **2 do not exist at all** (Hall Create and Hall Delete). |
| **Do we have to change database tables?** | **No new tables and no new columns are required.** **2 FK alterations are recommended** (2 migration files) so that a timetable session can be moved to another room/day/time. **0 seed or data rows** need changing. See §5. |
| **How many entries have to change?** | About **43 code entries across 14 files**: 8 routes, 8 controller actions, 11 model methods, 8 JS handlers, 6 view edits and 2 migrations. See §6. |
| **Which full CRUDs work in the system *today* (demo-ready)?** | **2, and neither is a Timetable Officer feature:** ① **Staff Accounts** (Coordinator / In-Charge “Staff Details”), which does real INSERT/SELECT/UPDATE/DELETE, and ② **Coordinator Seats** (In-Charge → Settings → Handover), where all four actions save but are UPDATEs at the SQL level. See §7. |
| **Recommended 4 CRUDs for the demo** | **Lecture Halls**, **Courses** and **Timetable Sessions** (all Officer, all need work) plus **Staff Accounts** (already works). See §8. |

---

## 2. Timetable Officer surface map

The officer's sidebar ([dashboard.php:28-34](../app/views/layouts/dashboard.php#L28-L34)) plus the shared items:

| # | Screen | URL | Entity → table(s) | CRUD scope | Needs C/R/U/D? |
|---|---|---|---|---|---|
| 1 | Timetable | `/timetable` | Timetable Session → `timetable_sessions` | **Full CRUD** | ✅ Yes |
| 2 | Course Details | `/courses` | Course → `courses` + `course_staff` | **Full CRUD** | ✅ Yes |
| 3 | Lecture Halls | `/lecture-halls` | Room → `rooms` | **Full CRUD** | ✅ Yes |
| 4 | Staff Details | `/staff` | Staff → `staff` | Read-only **by design** (the Coordinator/In-Charge manage staff) | ❌ No |
| 5 | Notifications (bell) | inline + `/notifications/*` | `notification_recipients` | Read + Update (mark read) | ❌ No |
| 6 | Settings | `/settings` | own `staff` row | Read + Update (own profile) | ❌ No |
| 7 | Messages | `/messages` | `chat_rooms`, `messages` (tables exist, unused) | Read from hardcoded fixtures only | ❌ No |

Not CRUD, but worth knowing before a demo:
- **Publish Timetable** saves to the browser's `localStorage`, not the database ([timetable.js:747-751](../app/public/js/timetable.js#L747-L751)).
- **Past academic years archive** is hardcoded JS data ([timetable.js:768](../app/public/js/timetable.js#L768)).

---

## 3. Operation matrix (Timetable Officer)

Legend: ✅ saves to DB · 🟡 UI exists but is **DOM-only** (lost on reload) · ❌ missing (no UI, no backend)

| Module | Create | Read | Update | Delete | Working |
|---|---|---|---|---|---|
| **Timetable Sessions** | 🟡 2 UI paths | ✅ | 🟡 | 🟡 | 1 / 4 |
| **Courses** (+ staff assignment) | 🟡 | ✅ | 🟡 | 🟡 | 1 / 4 |
| **Lecture Halls** | ❌ | ✅ | ✅ `PUT /lecture-halls/{code}` | ❌ | 2 / 4 |
| **Total** | 0 / 3 | 3 / 3 | 1 / 3 | 0 / 3 | **4 / 12** |

---

## 4. Module-by-module detail

### 4.1 Lecture Halls (`rooms`): closest to done

**What exists**
- R: `GET /lecture-halls` → [LectureHallsController::index](../app/controllers/timetable_officer/LectureHallsController.php#L25) → `RoomModel::all()`
- U: `PUT /lecture-halls/{code}` ([index.php:176](../app/public/index.php#L176)) → [LectureHallsController::update](../app/controllers/timetable_officer/LectureHallsController.php#L44) → [RoomModel::update](../app/models/RoomModel.php#L35). The JS uses `fetch` with `PUT` and JSON ([lecture_halls.js:89](../app/public/js/lecture_halls.js#L89)). **Use this as the reference pattern for the rest of the work.**

**What's missing**
- **C** and **D** have no backend and no UI (no "Add Hall" button, no Delete button).

**Pitfalls**
- `timetable_sessions.room_code` is `ON DELETE CASCADE` ([005:30](../database/migrations/005_create_timetable_sessions.sql#L30)), so deleting a room **silently deletes every session in it** (and those sessions' `assignments` rows).
- **All 9 rooms in the live DB are used by sessions.** Recommendation: in `destroy()`, refuse with HTTP 409 when the room has sessions. This is an app-level check, so no schema change is needed.
- The code is the PK and is read-only on edit. Keep it that way.

**Guide snippet** (route + model shape; follow the existing `update()` style):
```php
// app/public/index.php — next to the existing PUT route
$router->post('/lecture-halls', fn($req, $res) => (new LectureHallsController())->store($req, $res));
$router->delete('/lecture-halls/{code}', fn($req, $res, $p) => (new LectureHallsController())->destroy($req, $res, $p));

// app/models/RoomModel.php
public function create(string $code, string $type, int $capacity): bool   // INSERT; false on duplicate code
public function sessionCount(string $code): int                          // SELECT COUNT(*) FROM timetable_sessions WHERE room_code = :code
public function delete(string $code): bool                               // DELETE ... ; return rowCount() > 0
```

---

### 4.2 Courses (`courses` + `course_staff`)

**What exists**
- R: `GET /courses` → [OfficerCoursesController::index](../app/controllers/timetable_officer/CoursesController.php#L23) → `CourseModel::listing()`
- Full Add/Edit/Delete UI (side drawer, lecturer/instructor multi-select) in [course_details.php](../app/views/timetable_officer/course_details.php) + [courses.js](../app/public/js/courses.js). **All of it is DOM-only.** The file itself says so at [courses.js:3](../app/public/js/courses.js#L3). Delete is at [courses.js:209-213](../app/public/js/courses.js#L209-L213) and save at [courses.js:222-272](../app/public/js/courses.js#L222-L272).

**What's missing**
- The C/U/D routes, controller actions and model methods.

**Pitfalls, including one blocker**
1. 🔴 **The form has no Semester field**, but `courses.semester` is `TINYINT NOT NULL` with no default ([003:13](../database/migrations/003_create_courses.sql#L13)). An INSERT fails in strict mode, or stores `0` otherwise. Add a Semester `<select>` and have `listing()` return `semester` so the Edit form can prefill it. **This is a code fix, not a DB change.**
2. **Don't let Edit change the course code.** `code` is the PK and is referenced by `course_staff`, `timetable_sessions`, `workload_tasks`, `support_requests` and `chat_rooms`, **none of which use `ON UPDATE CASCADE`**. Renaming a course that is in use throws an FK error. Disable the Code input in edit mode, the same way Lecture Halls does.
3. **Staff assignment on save.** Replace that course's `course_staff` rows inside one transaction: `DELETE WHERE course_code = ?`, then INSERT each lecturer (`assignment_role='lecturer'`) and each instructor (`'instructor'`). The PK is `(course_code, staff_code)`, so **one person cannot be both lecturer and instructor on the same course**. Validate against that.
4. Map Program `CS`/`IS` to the lowercase `department` enum `cs`/`is`.
5. **Delete cascades** to that course's `timetable_sessions`, `course_staff`, `assignments` and `support_requests` (and sets `workload_tasks` / `chat_rooms` to NULL). For the demo, create a new course and delete *that one*.
6. (Minor) The lecturer/instructor pickers only list staff who **already** teach at least one course ([LecturerModel.php:19-27](../app/models/LecturerModel.php#L19-L27)). A brand-new staff member can't be picked. This is fine for the demo; sourcing the pickers from `StaffModel::activeStaff()` would fix it later.

**Guide snippet**
```php
// Routes — /courses GET already dispatches by role; these are officer-only JSON endpoints
$router->post('/courses', ...store);
$router->put('/courses/{code}', ...update);
$router->delete('/courses/{code}', ...destroy);

// CourseModel
public function create(array $c): bool
public function update(string $code, array $c): bool           // title, credits, department, year_of_study, semester
public function delete(string $code): bool
public function replaceStaff(string $code, array $lecturers, array $instructors): void  // in a transaction
```

---

### 4.3 Timetable Sessions (`timetable_sessions`): most work

**What exists**
- R: `GET /timetable` → [TimetableController::index](../app/controllers/TimetableController.php#L52) → `TimetableSessionModel::forDeptSemYear()`
- **`TimetableSessionModel::create()` is already written** ([TimetableSessionModel.php:59-82](../app/models/TimetableSessionModel.php#L59-L82)), but **nothing calls it** (no route).
- A full UI in [timetable.js](../app/public/js/timetable.js), **all of it DOM-only**:
  - Create path A, "click empty slot → Save": [timetable.js:462-511](../app/public/js/timetable.js#L462-L511)
  - Create path B, "Schedule Course → select slots → Add to Timetable": [timetable.js:666-714](../app/public/js/timetable.js#L666-L714)
  - Update, "Edit Session → Save": [timetable.js:288-326](../app/public/js/timetable.js#L288-L326)
  - Delete, "Delete → confirm": [timetable.js:350-385](../app/public/js/timetable.js#L350-L385)

**What's missing**
- C/U/D routes, controller actions, and `update()` / `delete()` / clash-check model methods.

**Pitfalls**
1. **Composite primary key** `(room_code, day_of_week, start_hour)` ([005:29](../database/migrations/005_create_timetable_sessions.sql#L29)). The Edit form lets you change room, day and start time, which **changes the PK**. `assignments` and `reschedule_requests` reference that key with **no `ON UPDATE CASCADE`**, so moving a session that has children fails with an FK error. **This is the only reason a DB change is recommended** (see §5).
2. **The PK does not stop overlaps.** A 2-hour session at 08:00 and a 1-hour session at 09:00 in the same room are both accepted. It also doesn't stop one cohort (dept/sem/year) having two classes at once in different rooms. Add an app-level clash check:
   ```sql
   SELECT 1 FROM timetable_sessions
   WHERE day_of_week = :day
     AND start_hour < :end AND start_hour + duration_hours > :start          -- time ranges overlap
     AND (room_code = :room
          OR (department = :dept AND semester = :sem AND year_of_study = :year))
     -- on UPDATE also exclude the row being edited (its original room/day/hour)
   LIMIT 1
   ```
3. **Identify a session by its original key.** The blocks already carry `data-location` (room code), `data-day-key` and `data-start-hour`. Send the **original** values in the PUT/DELETE URL and the **new** values in the JSON body.
4. Path B's venue falls back to `'TBA'` ([timetable.js:676](../app/public/js/timetable.js#L676)), which is not a valid `rooms.code`. Make the room required.
5. **XSS:** these handlers build `innerHTML` from values without escaping ([timetable.js:317-321](../app/public/js/timetable.js#L317-L321), [500-504](../app/public/js/timetable.js#L500-L504), [700-703](../app/public/js/timetable.js#L700-L703)). Once the values come from the DB, reuse the `esc()` helper from `courses.js`.
6. Set `managed_by_code = $_SESSION['staff_code']` on create/update. The column exists for this purpose (EER *Manages*).
7. Validate server-side: `day` in `mon..fri`; `start_hour` in the grid's hours and not 12 (lunch); `duration` 1–3; `session_type` enum; room exists; the course exists and matches the page's dept/year.

**Guide snippet**
```php
// Routes (5 segments — no collision with anything else)
$router->post('/timetable/sessions', ...store);
$router->put('/timetable/sessions/{room}/{day}/{hour}', ...update);
$router->delete('/timetable/sessions/{room}/{day}/{hour}', ...destroy);

// TimetableSessionModel — create() already exists
public function update(string $room, string $day, int $hour, array $data): bool
public function delete(string $room, string $day, int $hour): bool
public function findClash(array $data, ?array $ignoreKey = null): ?array
```

---

## 5. Database changes

### Required: **none**
All three officer CRUDs fit the current schema. No new tables, no new columns, no seed changes. Current data is fine as it is: 9 rooms, 15 courses, 33 course-staff links and 10 sessions.

### Recommended: **2 migrations (2 FK constraints on 2 tables)**
These let a timetable session be *moved* (room/day/time edited) without an FK error. `migrate.php` runs each file as one statement and the repo rule is one change per file, so that means **2 files**:

```sql
-- database/migrations/018_alter_assignments_session_fk.sql
ALTER TABLE assignments
    DROP FOREIGN KEY fk_assignments_session,
    ADD CONSTRAINT fk_assignments_session_cascade
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE ON UPDATE CASCADE;

-- database/migrations/019_alter_reschedule_session_fk.sql
ALTER TABLE reschedule_requests
    DROP FOREIGN KEY fk_reschedule_session,
    ADD CONSTRAINT fk_reschedule_session_cascade
        FOREIGN KEY (room_code, day_of_week, start_hour)
        REFERENCES timetable_sessions(room_code, day_of_week, start_hour)
        ON DELETE CASCADE ON UPDATE CASCADE;
```
*(The constraints get new names because dropping and re-adding an FK with the same name in one `ALTER` is unreliable across MySQL/MariaDB versions.)*

**Alternatives if you want zero migrations:**
- (a) Only allow editing course/type/duration, not room/day/start. To move a session you delete it and create it again.
- (b) Do the move in a transaction: INSERT the new key → UPDATE the children to the new key → DELETE the old key. This works, but it is more code.

### Optional (not needed for the CRUD demo)
| Change | Why |
|---|---|
| `rooms.name` column | The Lecture Halls page says "Name", but only a code exists |
| `timetable_publications` table | To make **Publish** real instead of `localStorage` |
| `academic_year` on `timetable_sessions` (and in the PK) | To make the past-years archive real |
| `notifications.created_by_code` column | Only if you choose "Officer Announcements" as an alternative 4th CRUD (§8) |

---

## 6. Change inventory: how many entries

| Layer | Lecture Halls (C, D) | Courses (C, U, D) | Timetable Sessions (C, U, D) | **Total** |
|---|---|---|---|---|
| Routes (`index.php`) | 2 | 3 | 3 | **8** |
| Controller actions | 2 (`store`, `destroy`) | 3 (`store`, `update`, `destroy`) | 3 (`store`, `update`, `destroy`) | **8** |
| Model methods | 3 (`create`, `delete`, `sessionCount`) | 5 (`create`, `update`, `delete`, `replaceStaff` + modify `listing` to return semester) | 3 (`update`, `delete`, `findClash`; `create` exists) | **11** |
| JS handlers | 2 (create submit, delete click) | 2 (form submit → POST/PUT, delete → DELETE) | 4 (quick-schedule save, multi-slot add, edit save, delete confirm) | **8** |
| View edits | 3 (Add button, Delete button, drawer create mode) | 3 (Semester select, lock Code on edit, `data-semester` on rows) | 0 | **6** |
| Migrations | 0 | 0 | 2 (recommended) | **2** |
| **Entries** | **12** | **16** | **15** | **≈ 43** |

**Files touched: 14.** These are `index.php`; `LectureHallsController`, `RoomModel`, `lecture_halls.php`, `lecture_halls.js`; the officer `CoursesController`, `CourseModel`, `course_details.php`, `courses.js`; `TimetableController`, `TimetableSessionModel`, `timetable.js`; and 2 migrations.

**Cross-cutting rules for every new endpoint**
- Guard with `guardJson($response, 'role', 'timetable_officer')`, as `LectureHallsController::update` does.
- **Always send JSON** (`Content-Type: application/json`). [Request::getBody()](../app/core/Request.php#L38-L63) only parses a PUT/DELETE body when it is JSON, and it HTML-mangles form-encoded POST values (`O'Neil` → `O&#039;Neil`).
- Update the DOM **after** the server replies `success: true`, never before. That is what lets a reload prove the change was saved.

---

## 7. Full CRUDs in the whole system *today*

Every write path in the app was checked (all `fetch()` calls in `public/js/**` and all non-GET routes).

| Feature | Who uses it | Table | C | R | U | D | Full CRUD today? |
|---|---|---|---|---|---|---|---|
| **Staff Accounts** | Coordinator / In-Charge → `/staff` | `staff` | ✅ Add staff `POST /staff/create`; ✅ `/signup` | ✅ Active + Pending tabs | ✅ Approve (assign role) `POST /staff/{code}/approve`; ✅ profile via `/settings` | ✅ Reject `POST /staff/{code}/reject`, **pending rows only** | ✅ **Yes**, with one caveat ↓ |
| **Coordinator Seats** | In-Charge → `/settings#handover` | `staff.position` | ✅ Add coordinator | ✅ Role-holder table | ✅ Change holder (OTP verify) | ✅ Revoke `POST /settings/handover/revoke` | ✅ **Yes** at the feature level; ⚠️ every op is an SQL `UPDATE` |
| Lecture Halls | Officer | `rooms` | ❌ | ✅ | ✅ | ❌ | ❌ |
| Courses | Officer | `courses`, `course_staff` | 🟡 | ✅ | 🟡 | 🟡 | ❌ |
| Timetable Sessions | Officer | `timetable_sessions` | 🟡 | ✅ | 🟡 | 🟡 | ❌ |
| Notifications | All | `notification_recipients` | — | ✅ | ✅ mark read | — | ❌ |
| Profile Settings | All | `staff` | — | ✅ | ✅ | — | ❌ |
| Password reset | Public | `otp_codes`, `staff` | ✅ OTP | — | ✅ password | — | ❌ |
| Leave, Support Requests, Workload, Evaluations, Duty Scheduler, Messages, Audit Log | Academic staff / Coordinator | hardcoded fixtures (`*PrototypeData`, inline arrays) | 🟡 | fixtures | 🟡 | 🟡 | ❌ |

**Staff Accounts caveat:** the trash-can and Deactivate buttons on the **Active** staff tab are DOM-only ([coordinator/staff.js:291-342](../app/public/js/coordinator/staff.js#L291-L342)). Only **pending** registrations can really be deleted. For a clean demo, either:
- run the script in §8 as written (create → read → update one record, delete a *different* pending record), or
- make Active-row Delete save to the DB. That takes 4 entries: `DELETE /staff/{code}` route, `StaffController::destroy`, `StaffModel::delete` and a `fetch` in `staff.js`. Block deleting yourself and the last Coordinator/In-Charge.

**Coordinator Seats caveat:** examiners who check the SQL will see UPDATEs rather than INSERT/DELETE, so treat it only as a **backup** 4th CRUD. It also can't revoke the last coordinator (409), so add one first.

---

## 8. Recommended 4 CRUDs for the demo

| # | CRUD | Role | Status | Work left | Effort |
|---|---|---|---|---|---|
| 1 | **Lecture Halls** | Timetable Officer | R, U work | C, D (12 entries) | 🟢 Small, **build first** |
| 2 | **Courses + staff assignment** | Timetable Officer | R works; UI done | C, U, D backend + Semester field (16 entries) | 🟡 Medium |
| 3 | **Timetable Sessions** | Timetable Officer | R works; UI done; `create()` exists | C, U, D + clash check + 2 migrations (15 entries) | 🔴 Largest |
| 4 | **Staff Accounts** | Coordinator / In-Charge | **Already full CRUD** | None (optional 4-entry Active-delete fix) | ✅ Done |

**Build order:** Halls → Courses → Sessions. Each one uses the same pattern (route → guarded JSON controller → prepared-statement model → `fetch` + update the DOM on success), so each step makes the next one easier.

**If all 4 must be Timetable Officer features:** the officer has only 3 entities that naturally need full CRUD. The most defensible 4th is **Officer Announcements** (create/edit/delete a notification broadcast to staff about timetable changes). It uses the existing `notifications` + `notification_recipients` tables plus **1 new column** (`notifications.created_by_code`) so the officer can list and edit only their own announcements. Expect about 14 more entries, including a new UI.

### Demo scripts
In every script, **reload the page after each write**. DOM-only fakes look exactly like real saves until you reload. You can also show the row in phpMyAdmin (`localhost/phpmyadmin` → `staffsync_db`).

**Before the demo:** `php database/migrate.php --seed` for clean data. Sign in as `tmo@ucsc.cmb.ac.lk` (Officer) or `mka@` / `dsc@` (Coordinator / In-Charge). The password for all of them is `Password123!`.

1. **Lecture Halls:** Add `DEMO-101` (lecture hall, 60) → it appears in the table → Edit its capacity to 80 → Delete `DEMO-101`. Delete a *new* hall only; all 9 seeded rooms have sessions.
2. **Courses:** Add `CS9999` (Year 1, Sem 1, CS, pick one lecturer and one instructor) → search for it → Edit the title and lecturers → Delete `CS9999`. Don't delete a seeded course, because that cascades to its sessions.
3. **Timetable Sessions:** Open CS · Sem 1 · Year 1 (4 seeded sessions) → click an empty slot → schedule `CS9999`-style or any listed course in a free room → Edit it to another time/room → try to create a clash (same room and time) to show the rejection → Delete it.
4. **Staff Accounts:** Sign up two accounts at `/signup` (they must be `@ucsc.cmb.ac.lk`) → sign in as `mka@` → **Pending** tab shows both (R) → **Approve** one as Junior Staff (U: it moves to the Active tab and can now log in) → **Reject** the other (D: the row is deleted). Or use **Add Staff** for C. `DEMO_AUTH=true` in `config.php`, so no emails are sent.

---

## 9. Demo-day risks

| Risk | Where | Mitigation |
|---|---|---|
| Deleting a seeded room/course wipes its timetable sessions (cascade) | FKs in `005` | Delete only records created during the demo; add the 409 "in use" check for rooms |
| Publish and past-years archive look real but aren't in the DB | [timetable.js:747](../app/public/js/timetable.js#L747), [:768](../app/public/js/timetable.js#L768) | Don't present them as CRUD |
| Active-staff Delete/Deactivate look real but are DOM-only | [coordinator/staff.js:291-342](../app/public/js/coordinator/staff.js#L291-L342) | Use Pending → Reject for D, or make the 4-entry fix |
| Course insert without a semester | [003:13](../database/migrations/003_create_courses.sql#L13) | Add the Semester field before the demo |
| Editing a session's room/day/time fails if it has assignments or reschedule requests | [006](../database/migrations/006_create_assignments.sql), [014](../database/migrations/014_create_reschedule_requests.sql) | Apply migrations 018/019, or use a freshly created session |
| No CSRF tokens; `display_errors = 1` | global | Known issues; not a CRUD blocker, but mention them if asked about security |

---

## Appendix: live DB snapshot (2026-09-24)

| Table | Rows | | Table | Rows |
|---|---|---|---|---|
| `staff` | 24 | | `notifications` | 5 |
| `rooms` | 9 (all in use) | | `notification_recipients` | 8 |
| `courses` | 15 | | `leave_requests` | 2 |
| `course_staff` | 33 | | `workload_tasks` | 2 |
| `timetable_sessions` | 10 (CS Y1S1: 4, CS Y2S1: 4, IS Y1S1: 2) | | `reschedule_requests` | 1 |
| `assignments` | 5 | | `support_requests` | 2 |

Position holders: **DSC** = In-Charge, **MKA** = the only Coordinator (so the Handover "Revoke" demo needs a second coordinator added first).
