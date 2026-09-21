# Design Patterns: Current State & Roadmap

This document answers two questions raised while working on the instructor timetable feature:
1. Which design patterns does StaffSync already use?
2. Where would introducing more patterns keep future changes simple instead of increasingly tangled?

**Constraint:** StaffSync intentionally uses no external libraries or frameworks — plain PHP, HTML, CSS, JS, SQL, running on XAMPP. Every item below is implementable with plain PHP classes/functions; nothing here requires Composer, an ORM, or a template engine.

This is a **reference/review document only** — nothing in it has been implemented. Pick an item and ask Claude to implement it when ready.

---

## 1. Patterns already in use

| Pattern | Where | Notes |
|---|---|---|
| **Singleton** | `app/core/Database.php` | `Database::getConnection()` lazily creates and reuses one PDO instance |
| **Front Controller** | `app/public/index.php` | Single entry point builds `Application` + `Router`, dispatches every request |
| **MVC separation** | `app/controllers/`, `app/models/`, `app/views/` | Controllers never run raw SQL; all DB access goes through Models |
| **Template Method (layout decorator)** | `app/core/Controller.php::render()` | Captures view output via `ob_start()`, then wraps it in the selected layout |
| **Registry** *(present but unused)* | `Application::$app` in `app/core/Application.php` | Static instance + array container exists, but no controller currently reads from it |
| **Partial / Component** | `app/views/components/notifications.php` | Shared include used by both dashboard layouts |
| **Convention-based routing** | `app/core/Router.php` | Explicit `get/post/put/delete` maps with `{param}` regex matching |
| **Migration / schema versioning** | `database/migrate.php` + `schema_migrations` table | Hand-rolled but functions like a real migration tool (`--status`, `--seed`, `--fresh`) |

Patterns notably **absent**: Dependency Injection, Repository abstraction, Strategy (role logic is `if/else` string comparisons), Observer/events, and any middleware pipeline for cross-cutting concerns like auth.

---

## 2. Roadmap — prioritized opportunities

Ordered by leverage vs. risk: the earliest items remove the most duplication for the smallest structural change, and later items build on the earlier ones.

### 1. Role-Guard via Template Method
- **Problem:** The exact same auth check is copy-pasted in 10 controllers:
  ```php
  if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'instructor') {
      $this->redirect('/login');
      return;
  }
  ```
- **Affected files:** `instructor/{LeaveController,MessagesController,RequestsController,SettingsController,TimetableController,WorkloadController}.php`, `timetable_officer/{CoursesController,LecturersController,SettingsController,TimetableController}.php`
- **Solution:** Add `protected function requireRole(string $role): bool` to `app/core/Controller.php`; each action calls it as its first line instead of repeating the `if` block.
- **Why it matters:** The recent role-based-auth rollout (`436daa0`) had to touch 10 files. With this in place, adding or changing a role check becomes a one-file change.
- **Effort/Risk:** Low effort, low risk — purely additive, one method per controller call site.

### 2. Base Model class
- **Problem:** Every model (`UserModel`, `CourseModel`, `LecturerModel`, `InstructorModel`, `NotificationModel`, `TimetableSessionModel`) repeats the same `$pdo = Database::getConnection();` → prepare → execute → fetch boilerplate (~15+ occurrences).
- **Solution:** Add an abstract `app/models/Model.php` with protected `query()`, `fetchOne()`, `fetchAll()`, `execute()` helpers wrapping `Database::getConnection()`. Existing models extend it and drop their repeated boilerplate.
- **Effort/Risk:** Low-medium effort (mechanical refactor per model); low risk since prepared-statement behavior stays identical.

### 3. Value Object for timetable filters
- **Problem:** `instructor/TimetableController.php` and `timetable_officer/TimetableController.php` both duplicate the same dept/semester/year whitelist validation:
  ```php
  $dept = in_array($dept, ['cs', 'is'], true) ? $dept : 'cs';
  $sem  = in_array($sem, [1, 2], true) ? $sem : 1;
  $year = in_array($year, [1, 2, 3, 4], true) ? $year : 1;
  ```
- **Solution:** Extract a small `TimetableFilterParams` class that validates/normalizes these three fields once; both controllers construct one from request input.
- **Effort/Risk:** Low effort, low risk.

### 4. Shared dropdown/select-option helper
- **Problem:** Dropdown and filter options are built ad hoc per view — `timetable.php` (lecturer/room filters), `courses.php` (program/year segmented buttons), `workload.php` (semester/month arrays) — which is exactly the class of bug the earlier "ensure dropdown consistency" fix (`7b90429`) had to patch manually.
- **Solution:** A small plain-PHP helper (e.g. a `SelectBuilder` class or a `options()` function in a shared view-helpers file) that every view calls instead of writing its own `foreach` + `<option>` loop.
- **Effort/Risk:** Low-medium effort; touches several view files but each change is small and visually verifiable.

### 5. Unify near-duplicate Timetable controllers/views
- **Problem:** `instructor/TimetableController` and `timetable_officer/TimetableController` are near-identical (same validation, same model calls, different layout/view path). The `instructor_dashboard.php` and `timetable_officer_dashboard.php` layouts are ~90% duplicated HTML, differing only in nav links and user-chip styling.
- **Solution:** Once items 1–3 land, fold the two Timetable controllers into one role-parameterized controller (or a shared abstract base), and parameterize the dashboard layout by role instead of maintaining two files.
- **Effort/Risk:** Medium effort, medium risk — do this *after* the smaller items above, since it's a bigger structural change touching routing and views together.

### 6. Fold error pages into the layout system
- **Problem:** `app/views/errors/forbidden.php` and `notfound.php` are each full standalone HTML documents (~200 lines, inline `<style>`), duplicating page chrome instead of using the existing layout mechanism. `forbidden.php` is also currently dead code — failed auth checks redirect to `/login` rather than rendering it.
- **Solution:** Route both through the `main`/`auth` layout system like every other view; decide whether failed role checks should render `forbidden.php` instead of redirecting.
- **Effort/Risk:** Low effort, low risk, mostly cosmetic/cleanup — good candidate to pair with item 1.

### 7. Minimal DI/service registry
- **Problem:** `new NotificationModel()`, `new CourseModel()`, etc. are instantiated inline throughout controllers and views (~10+ call sites), making them hard to swap or test.
- **Solution:** Wire up the already-present but unused `Application::$app` container in `app/core/Application.php` to hand out shared Model instances, instead of adding a new framework concept.
- **Effort/Risk:** Medium effort — touches many call sites; low risk since it's a mechanical substitution.

### 8. Repository-style Models for stub data
- **Problem:** `workload.php`, `leave.php`, `requests.php`, `messages.php` embed hardcoded PHP arrays as fake data directly in the view, unlike Timetable/Courses/Lecturers which are Model-backed. This is an architectural fork, not just missing data.
- **Solution:** Give these features real Models now (even against minimal/stub tables) so all views follow the same Controller → Model → View path before real persistence is added.
- **Effort/Risk:** Medium effort (new models + migrations); do before these features go live with real data entry.

### 9. Observer/event mini-dispatcher *(design-ahead)*
- **Problem:** No notification/email dispatch exists yet. When it's added, it would be easy to hardwire notification-creation calls directly into controller business logic.
- **Solution:** A tiny in-process event dispatcher (plain PHP — an array of callables keyed by event name) so controllers fire an event (e.g. `"LeaveRequested"`) and listeners handle notification/email side effects separately.
- **Effort/Risk:** Low effort to add the dispatcher itself; only relevant once a first real event is needed — don't build ahead of that need.

### 10. FileUploadService placeholder *(design-ahead)*
- **Problem:** No file upload handling exists yet (no `$_FILES`/`move_uploaded_file` usage found), but instructor "requests"/"leave" features will likely need attachments once they persist real data.
- **Solution:** When that need arrives, add a single `FileUploadService` class so upload handling (validation, storage path, naming) is consistent across features rather than ad hoc per controller.
- **Effort/Risk:** Not needed yet — noted so it's built once, consistently, rather than copy-pasted per feature later.

---

## Suggested order of execution
1 → 2 → 3 → 6 (all low-risk, mechanical, independently shippable)
→ 4 → 7 (medium effort, still isolated)
→ 5 → 8 (bigger structural changes, do once the above have stabilized)
→ 9, 10 as-needed when their triggering feature is actually built.
