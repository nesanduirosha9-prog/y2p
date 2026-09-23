# Routing refactor — role names out of URLs

**Branch:** `refactor/resource-routes`
**Status:** Phase 1 complete. Phases 2–3 follow in separate commits.

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

*(written when Phase 3 lands)*

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
