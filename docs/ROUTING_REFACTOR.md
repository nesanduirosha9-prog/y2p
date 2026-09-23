# Routing refactor — role names out of URLs

**Branch:** `refactor/resource-routes`
**Status:** Phases 0–3 complete. The Courses half of 3d was assessed and deliberately
not done (see below).

## Why

Routes encoded the user's role in the path (`/instructor/…`, `/coordinator/…`,
`/in-charge/…`) while the timetable-officer routes were unprefixed (`/timetable`,
`/lecturers`, `/settings`). The role already lives in `$_SESSION`, so the prefix
duplicated state the server already had, forced one route per role×resource pair,
and produced duplicated controller/view stacks — two byte-identical
`SettingsController`s, three `EvaluationsController`s, two `WorkloadController`s.

It was also applied inconsistently. Two symptoms:

- the **In-Charge sidebar linked into `/coordinator/staff`**, a path named for a
  different role;
- `components/staff_directory.php` fell back to **`/in-charge/staff`**, which was
  never a registered route — a dead link to a 404.

**Goal: no role name appears in any URL. One canonical URL per resource. Role
decides authorization and which view renders — never which URL you visit.**

---

## Phase 0 — what was there before

### The route table as it stood (`app/public/index.php`, 201 lines)

| Method | Path | Handler | Guard |
|---|---|---|---|
| GET | `/timetable` | `timetable_officer\TimetableController::index` | inline role=`timetable_officer` → **/login** |
| GET | `/course-details` | academic_staff → 302 `/instructor/my-courses`; else `timetable_officer\CoursesController::index` | inline role → **/login** |
| GET | `/lecturers` | `timetable_officer\LecturersController::index` | inline role → **/login** |
| GET | `/lecture-halls` | `timetable_officer\LectureHallsController::index` | inline role → **/login** |
| PUT | `/lecture-halls/{code}` | `…LectureHallsController::update` | inline role → **JSON 401** |
| GET/POST | `/settings` | `timetable_officer\SettingsController::index/update` | inline role → **/login** / **JSON 401** |
| GET | `/instructor/timetable` | `instructor\TimetableController::index` | inline role=`academic_staff` → **/login** |
| GET | `/instructor/workload` | `instructor\WorkloadController::index` | inline → **/login** |
| GET | `/instructor/my-courses` | `instructor\CoursesController::index` | `checkAccess()` → **403** |
| GET | `/instructor/evaluations` | `instructor\EvaluationsController::index` → **302 `/instructor/my-courses`** | `checkAccess()` → **403** |
| GET | `/instructor/requests` | `instructor\RequestsController::index` | inline → **/login** |
| GET | `/instructor/leave` | `instructor\LeaveController::index` | inline → **/login** |
| GET | `/instructor/messages` | `instructor\MessagesController::index` | inline → **/login** |
| GET/POST | `/instructor/settings` | `instructor\SettingsController::index/update` | inline → **/login** / **JSON 401** |
| GET | `/coordinator/workload/distribution` | `coordinator\WorkloadController::distribution` | `checkAccess()` pos=coordinator → **403** |
| GET | `/coordinator/workload/scheduler` | `coordinator\WorkloadController::scheduler` | same → **403** |
| GET | `/coordinator/evaluations` | `coordinator\EvaluationsController::index` | `checkAccess()` pos=coordinator → **403** |
| GET | `/coordinator/staff` | `coordinator\StaffController::index` | `guard()` pos∈(coordinator,in_charge) → **/login** |
| POST | `/coordinator/staff/{code}/approve` \| `/reject` | `…StaffController::approve/reject` | `guard()` → **JSON 401** |
| GET | `/in-charge/workload/distribution` | `in_charge\WorkloadController::distribution` | `checkAccess()` pos=in_charge → **403** |
| GET | `/in-charge/evaluations` | `in_charge\EvaluationsController::index` | same → **403** |
| GET | `/in-charge/accounts` | **302 `/instructor/settings#handover`** — never reached `AccountsController::index` | none |
| GET | `/in-charge/accounts/change/{position}/{code}` | `AccountsController::change` | `guard()` pos=in_charge → **/login** |
| GET | `/in-charge/accounts/select/{position}/{code}` | `AccountsController::selectView` | same |
| POST | `/in-charge/accounts/select` | `AccountsController::selectSubmit` | same |
| GET/POST | `/in-charge/accounts/verify` | `AccountsController::verifyView/verifySubmit` | `guard()` + `$_SESSION['handover']` |
| GET | `/in-charge/accounts/updated` | `AccountsController::updatedView` | `guard()` |
| — | `/`, `/about`, `/login`, `/signup`, `/forgot-password*`, `/logout`, `/dashboard`, `/oldabout` | auth/home | role-free — **untouched by this refactor** |

### Every URL literal in the repo

Produced with:

```sh
grep -rn -oE "['\"\`]/(instructor|coordinator|in-charge|timetable|lecturers|lecture-halls|course-details|settings)[a-zA-Z0-9/_{}-]*" \
  --include="*.php" --include="*.js" app/
```

~60 hits. `app/views/layouts/dashboard.php` alone holds **19** of them (lines
24-27, 30-32, 40-43, 45-47, 50, 53, 56, 57, 61, 62) — the single highest-value
file. Line 46 was the In-Charge nav item pointing at `/coordinator/staff`.

| File:line | Literal | Disposition |
|---|---|---|
| `views/layouts/dashboard.php` ×19 | every role-prefixed nav `href` | rewritten in Phase 2 |
| `views/components/settings_form.php:192` | `/in-charge/accounts/change/{pos}/{code}` | → `/settings/handover/change/…` |
| `views/components/settings_form.php:7` | doc comment only — `$formAction` has **no `??` default**; line 67 uses it bare | comment only |
| `views/components/staff_directory.php:9` | `/in-charge/staff` ‖ `/coordinator/staff` | `/in-charge/staff` was a **dead 404**; whole fallback → `'/staff'` |
| `views/coordinator/staff.php:7` | `$basePath = '/coordinator/staff'` | → `'/staff'` |
| `views/coordinator/workload_distribution.php:19` | `/coordinator/workload/scheduler` | → `/workload/scheduler` |
| `views/instructor/evaluations.php:19` | `/instructor/my-courses` | → `/courses` (view is **unreachable** — the controller 302s before rendering it) |
| `views/in_charge/accounts.php:39`, `accounts_change.php:29,30`, `accounts_select.php:58`, `accounts_updated.php:40`, `accounts_verify.php:31` | `/in-charge/accounts…` | → `/settings/handover…` |
| `views/errors/notfound.php:229`, `views/timetable_officer/timetable.php:14` | `/timetable` | already canonical — unchanged |
| `controllers/AuthController.php:264` | `'/instructor/timetable' : '/timetable'` | → `return '/timetable';` |
| `controllers/instructor/EvaluationsController.php:35` | `/instructor/my-courses` | → `/courses` |
| `controllers/instructor/SettingsController.php:47`, `controllers/timetable_officer/SettingsController.php:35` | `$formAction` | → `/settings` |
| `controllers/in_charge/AccountsController.php:86,112,191,242` | `/in-charge/accounts…` | → `/settings/handover…`; **:112 is the JSON `redirect` field `accounts.js` follows** |
| `public/js/in_charge/accounts.js:47,60,95,103` | 4 hardcoded fetch/redirect URLs | → `/settings/handover/…` |
| `public/js/coordinator/staff.js:7` | `view.dataset.basePath \|\| '/coordinator/staff'` | reads `data-base-path`; **only the fallback literal** changes |
| `public/js/settings.js` | reads `data-action`; **zero URL literals** | **no edit needed** |
| `public/js/lecture_halls.js:89` | `/lecture-halls/` | already canonical — unchanged |

### Framework mechanics this refactor relies on

- **`Router::resolve()` matches exact paths first** (`app/core/Router.php:44`),
  before looping `{param}` routes. A literal route therefore can never be shadowed
  by a `{param}` route, whatever the registration order. The genuine collision risk
  is `{param}`-vs-`{param}`, which is resolved in registration order.
- **`Response::redirect()` and `Response::json()` both call `exit`**
  (`app/core/Response.php:21,33`). A shim closure that returns nothing is correct,
  and a guard's `return` after them is unreachable — kept for readability.
- **`Request::getBody()` merges a JSON body for any verb**, so the PUT route and
  every `fetch()` endpoint work without change.
- **The autoloader maps namespace directly to file path** (`bootstrap.php`) with no
  prefix mapping. Moving a controller *requires* editing its `namespace` line and
  every `use`/FQCN reference; a mismatch fails silently and surfaces as
  `Class not found`. Grep the old FQCN after every move.

---

## Phase 1 — guards consolidated into `Controller`

Five controllers carried a private `checkAccess()` and two a private `guard()`,
each a copy-paste of the others; ten more had the check inline. They are now four
helpers on `app/core/Controller.php`:

```php
protected function requireLogin(): ?string                        // no session -> /login
protected function requireRole(string ...$roles): ?string         // $_SESSION['role']
protected function requirePosition(string ...$positions): ?string // $_SESSION['position']
protected function guardJson(Response $r, string $key, string ...$allowed): bool
```

Contract: `null` = allowed; `''` = denied and a redirect already sent; any other
string = the rendered 403 page to return. `guardJson()` sends a 401 JSON body and
exits; passing no `$allowed` values means "any signed-in user".

### Guards after Phase 1

| Controller | Guard |
|---|---|
| `coordinator\WorkloadController::distribution` / `::scheduler` | position = `coordinator` |
| `coordinator\EvaluationsController` | position = `coordinator` |
| `coordinator\StaffController` (all actions) | position ∈ (`coordinator`, `in_charge`) — **the deliberate shared exception** |
| `in_charge\WorkloadController`, `in_charge\EvaluationsController`, `in_charge\AccountsController` | position = `in_charge` |
| all `instructor\*` | role = `academic_staff` |
| all `timetable_officer\*` | role = `timetable_officer` |

### The one deliberate behaviour change

Before this phase the two guard styles disagreed about what a **signed-in user with
the wrong role** should see: ten controllers redirected them to `/login`, six
rendered a 403. `requireRole()`/`requirePosition()` now render a 403 everywhere.
`/login` is reserved for having no session at all.

This is the only intended behaviour change in the whole refactor, and it makes the
authorization boundary explainable: *not signed in → login; signed in but not
allowed → 403.*

Two incidental JSON message changes fall out of it:

- signed-out callers of `/coordinator/staff/{code}/approve|reject` and
  `PUT /lecture-halls/{code}` now get `"Not authenticated"` where they got
  `"Unauthorized"` (a wrong-position caller still gets `"Unauthorized"`);
- a wrong-position caller of `POST …/accounts/verify` now gets **401
  "Unauthorized"** instead of sharing the **400 "No pending role change found."**
  response with the genuinely-different "nothing to verify" case.

---

## Phase 2 — canonical URLs

### The URL map

| Canonical | Method | Replaces | Who may reach it |
|---|---|---|---|
| `/timetable` | GET | `/timetable`, `/instructor/timetable` | any signed-in user (view differs by role) |
| `/courses` | GET | `/course-details`, `/instructor/my-courses` | any signed-in user (view differs by role) |
| `/staff` | GET | `/lecturers`, `/coordinator/staff` | timetable officer, coordinator, in-charge |
| `/staff/{code}/approve` | POST | `/coordinator/staff/{code}/approve` | position ∈ (coordinator, in_charge) |
| `/staff/{code}/reject` | POST | `/coordinator/staff/{code}/reject` | position ∈ (coordinator, in_charge) |
| `/lecture-halls` | GET | same | role = timetable_officer |
| `/lecture-halls/{code}` | PUT | same | role = timetable_officer |
| `/workload` | GET | `/instructor/workload` | role = academic_staff |
| `/workload/distribution` | GET | `/coordinator/workload/distribution`, `/in-charge/workload/distribution` | position ∈ (coordinator, in_charge) |
| `/workload/scheduler` | GET | `/coordinator/workload/scheduler` | position = coordinator |
| `/evaluations` | GET | `/instructor/evaluations`, `/coordinator/evaluations`, `/in-charge/evaluations` | role = academic_staff (view differs by position) |
| `/leave` | GET | `/instructor/leave` | role = academic_staff |
| `/messages` | GET | `/instructor/messages` | role = academic_staff |
| `/requests` | GET | `/instructor/requests` | role = academic_staff |
| `/settings` | GET, POST | `/settings`, `/instructor/settings` | any signed-in user |
| `/settings/handover` | GET | `/in-charge/accounts` | — (302 to `/settings#handover`) |
| `/settings/handover/change/{position}/{code}` | GET | `/in-charge/accounts/change/…` | position = in_charge |
| `/settings/handover/select/{position}/{code}` | GET | `/in-charge/accounts/select/…` | position = in_charge |
| `/settings/handover/select` | POST | `/in-charge/accounts/select` | position = in_charge |
| `/settings/handover/verify` | GET, POST | `/in-charge/accounts/verify` | position = in_charge |
| `/settings/handover/updated` | GET | `/in-charge/accounts/updated` | position = in_charge |

Auth routes (`/login`, `/signup`, `/forgot-password*`, `/logout`, `/`, `/about`,
`/dashboard`, `/oldabout`) were already role-free and are untouched.

### Two design points worth explaining

**Dispatch by session, deny by guard.** Where one URL serves several roles, the
route closure picks the controller from `$_SESSION` and the *controller's own*
guard decides access. The `else` branch of every dispatch is deliberately the
controller with the stricter guard, so a user who fits neither branch is refused
through the normal guard path. That is why no closure in `index.php` renders a 403
itself — `Controller::forbidden()` is `protected` and a bare closure could not
call it anyway.

**POST shims are aliases, not redirects.** A 302 answer to a POST makes the client
re-issue the request as a GET with no body. Five legacy POST/`fetch()` endpoints
therefore call the same controller method as their canonical route instead of
redirecting:

```
POST /coordinator/staff/{code}/approve   POST /in-charge/accounts/select
POST /coordinator/staff/{code}/reject    POST /in-charge/accounts/verify
POST /instructor/settings
```

The other 19 legacy paths are plain 302s from the `$legacyRedirects` map, plus two
parameterised GET shims (`…/accounts/change|select/{position}/{code}`) that
rebuild the target from their captured params.

### `dashboardUrlForRole()`

Both roles now land on `/timetable`, so the body collapses to `return '/timetable';`.
The method is kept — six call sites use it, and it remains the one place to change
if a role ever needs a different landing page again.

### Verified

- 62 routes registered; all 31 pre-refactor paths still resolve.
- 36 canonical routes, none containing a role name.
- All 19 legacy redirect targets resolve to a registered route.
- Every `render()`/`renderPartial()` target exists on disk.
- `php -l` clean on every modified file.
- `app/public/js/settings.js` needed no edit (reads `data-action`);
  `app/public/js/coordinator/staff.js` needed only its fallback literal
  (the live value comes from `data-base-path`).

## Phase 3 — collapsed stacks

Three duplicated stacks collapsed, one commit each so any of them reverts alone.

### 3a. Settings

`instructor/SettingsController` and `timetable_officer/SettingsController` were
the same file apart from the role they guarded and the view they rendered;
`instructor/settings.php` and `timetable_officer/settings.php` were the same nine
lines apart from a `$roleLabel` string.

Now `app/controllers/SettingsController.php` (namespace `app\controllers`) and
`app/views/settings.php`. `$roleLabel` is derived from the session. The guard is
a login check, not a role check — Settings is open to everybody — so `index()`
uses `requireLogin()` and `update()` uses `guardJson($response, 'role')` with no
allowed values. The In-Charge handover panel logic is unchanged.

### 3b. Workload distribution

`coordinator\WorkloadController::distribution` and
`in_charge\WorkloadController::distribution` differed in the position they
guarded, four strings, and which 20-line view they rendered — and both views set
`$mode = 'full'; $showSummaryCards = true;` and required the same
`components/workload_matrix.php`.

Now `app/controllers/WorkloadController.php` with `distribution()` and
`scheduler()`, rendering `app/views/workload_distribution.php`. The page copy
lives in a `COPY` constant keyed by position. **Both wordings and both wrapper
CSS classes are preserved verbatim** — `workload_matrix.css` styles
`.coordinator-workload-view` and `.in-charge-workload-view` separately, and the
Coordinator's heading ("Course Workload Matrix", allocating) and the In-Charge's
("Faculty Workload & Course Allocation", overseeing) are different on purpose.
The scheduler button renders only for the Coordinator.

`views/coordinator/workload_scheduler.php` moved to `views/workload_scheduler.php`
so the view path still mirrors the controller that renders it.

### 3c. Evaluations

Three controllers — `instructor/`, `coordinator/`, `in_charge/` — differed in what
they guarded and four strings each; the two that rendered anything both required
`components/evaluations_review.php`.

Now `app/controllers/EvaluationsController.php` and `app/views/evaluations.php`,
same `COPY`-keyed-by-position approach, both wordings and both wrapper classes
verbatim. A lecturer with no position is still redirected to `/courses`, because
evaluations are done per course module — which is what the instructor controller
already did, and which is why `views/instructor/evaluations.php` was unreachable.
It was deleted along with the controller that would have rendered it.

### 3d. Timetable — done. Courses — deliberately not.

This phase was assessed after 3a–3c rather than taken as a whole, because only
half of it improved the code.

**Timetable: merged.** `instructor/TimetableController` and
`timetable_officer/TimetableController` (56 + 62 lines) parsed the same three
filters with the same defaults and whitelists and called the same two models.
They differed in the role they guarded, four strings of page copy, and one extra
`RoomModel` query on the officer's side. Now one
`app/controllers/TimetableController.php` with the same `COPY`-keyed-by-role
approach as 3b and 3c — keyed by `role` here rather than `position`.

The two views stay separate and are genuinely different: the officer's grid is
editable, the academic staff one is read-only (209 vs 322 lines of markup). Only
the controller merged, so `views/instructor/timetable.php` and
`views/timetable_officer/timetable.php` keep their paths.

The officer-only `RoomModel` query stays officer-only — academic staff do not pay
for a query their view never reads.

**Courses: not merged, on purpose.** `instructor/CoursesController` is 244 lines,
of which ~210 is a hardcoded course catalogue plus per-course assignment and
evaluation logic. `timetable_officer/CoursesController` is 42 lines that call
three models. They share nothing but the class name. Merging them would produce
one class holding two unrelated methods — a worse arrangement than two small
focused controllers, and dedup for its own sake. `/courses` keeps its two-way
dispatch in `index.php`.

### Files deleted

| File | Phase |
|---|---|
| `app/controllers/instructor/SettingsController.php` | 3a |
| `app/controllers/timetable_officer/SettingsController.php` | 3a |
| `app/views/instructor/settings.php` | 3a |
| `app/views/timetable_officer/settings.php` | 3a |
| `app/controllers/coordinator/WorkloadController.php` | 3b |
| `app/controllers/in_charge/WorkloadController.php` | 3b |
| `app/views/coordinator/workload_distribution.php` | 3b |
| `app/views/in_charge/workload_distribution.php` | 3b |
| `app/controllers/instructor/EvaluationsController.php` | 3c |
| `app/controllers/coordinator/EvaluationsController.php` | 3c |
| `app/views/coordinator/evaluations.php` | 3c |
| `app/controllers/in_charge/EvaluationsController.php` | 3c |
| `app/views/in_charge/evaluations.php` | 3c |
| `app/views/instructor/evaluations.php` | 3c — was unreachable |
| `app/controllers/instructor/TimetableController.php` | 3d |
| `app/controllers/timetable_officer/TimetableController.php` | 3d |

Net: **10 controllers and 7 views removed, 4 controllers and 3 views added.**

### Still unreachable, deliberately left alone

- `AccountsController::index()` and `views/in_charge/accounts.php` — the
  standalone "Role Assignment" page. No route calls it: `/settings/handover`
  redirects to the handover tab inside Settings, exactly as `/in-charge/accounts`
  did before. Removing it is a separate cleanup, not part of a routing refactor.

## Verification record

Everything below was run against this branch.

### Static

- `php -l` clean on every `.php` file under `app/`.
- 62 routes registered; all 31 pre-refactor paths still resolve.
- 36 canonical routes, **none containing a role name**.
- All 19 legacy redirect targets resolve to a registered route.
- Every `render()` / `renderPartial()` target exists on disk.
- `grep -rn -oE "['\"\`]/(instructor|coordinator|in-charge)/" --include="*.php" --include="*.js" app/`
  returns hits **only** inside the `LEGACY ROUTE SHIMS` block of `index.php`.

### End-to-end (php -S against the seeded MySQL, all four account types)

**94 checks, 0 failures, 0 PHP errors in the server log.** Re-run unchanged after
the Phase 3d Timetable merge.

- **70 navigation checks** — every sidebar item for the timetable officer, junior
  academic staff, coordinator and in-charge; every legacy GET shim; the signed-out
  boundary; the wrong-role boundary; the in-charge handover GET steps.
- **24 write checks** — `POST /settings` for all four roles plus its validation
  and signed-out paths; `POST /staff/{code}/approve|reject`;
  `PUT /lecture-halls/{code}`; `POST /settings/handover/select|verify`; and each
  legacy POST alias, confirming it reaches the controller rather than redirecting.
- Rendered sidebars dumped per role: every `href` is role-free, and the in-charge
  Staff item now points at `/staff` rather than `/coordinator/staff`.
- Profile saves confirmed persisted in the database, including through the legacy
  `POST /instructor/settings` alias. The four staff rows touched by the test were
  restored to their `database/seeds/001_staff.sql` values afterwards.
- **Phase 3d regression check:** `/timetable` was rendered for both roles across
  three filter combinations, before and after the merge, and diffed. All six
  pages came back **byte-for-byte identical** (46–55 KB each). Invalid filter
  values (`?dept=BOGUS&sem=99&year=0`) still fall back to the defaults without
  error, and the officer's room dropdown is still populated while the read-only
  staff grid never runs that query.

### Not covered by the automated run

- Real browser rendering: CSS, JavaScript behaviour, and the visual state of each
  screen. The checks above assert status codes, redirect targets, JSON bodies and
  the absence of PHP errors — not that a page *looks* right.
- The OTP step of the handover flow end to end, which needs a working SMTP
  configuration to deliver the code.
- Apache/XAMPP rewrite rules. The run used PHP's built-in server with
  `app/public/index.php` as the router.

## Shim-removal checklist

The `LEGACY ROUTE SHIMS` block at the bottom of `app/public/index.php` is the whole
of it — deleting that block and the `$legacyRedirects` array removes every legacy
path at once. Before doing so:

- [ ] Every in-flight teammate branch has merged into `main`.
- [ ] `grep -rn -oE "['\"\`]/(instructor|coordinator|in-charge)/" --include="*.php" --include="*.js" app/`
      returns nothing outside the shim block.
- [ ] Same grep across the *merged* branches' diffs, in case one reintroduced an
      old `href`.
- [ ] Anyone with a bookmarked role-prefixed URL has been told (they will get a
      404, not a redirect, once the block goes).
- [ ] Delete the block, the `$legacyRedirects` array, and this checklist.
