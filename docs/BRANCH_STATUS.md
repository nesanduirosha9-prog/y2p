# Branch Status — `feature-login`

**Base commit:** `b794a42 Initial commit`
**Committed since then:** The initial feature set has been committed (see commit `25eacb7`). The summary below details the features built on top of the original framework.

## Summary

The initial commit was a bare-bones PHP MVC skeleton: a router, a home/about page, and a stub `UserModel` returning hardcoded data. Despite the branch name, two features have been built on top of it since:

1. **Authentication** — login, signup, forgot-password — plus the framework upgrades that feature needed (per-controller layouts, JSON request bodies, route params, sessions, a real database layer).
2. **Timetable Officer dashboard** — a sidebar+header shell shared by all officer screens, and the Timetable page itself (department/semester/year filters, the weekly grid, and the full "schedule a course session" interaction), built from the Figma wireframes at `figma.com/design/QmyokbiCEdP3G6aQVpyTIo`.

None of it is committed yet — this doc is a snapshot to review before you start staging/committing.

## At a glance

| | Count | What |
|---|---|---|
| Modified | 14 files | Core framework (`app/core/*`), routing wiring, `UserModel`, views, `bootstrap.php` |
| New | 21 paths | `AuthController`, `TimetableController`, `Database`, `config.php`, `.htaccess`, auth layout + 3 views, dashboard layout + timetable view, 7 CSS files, 4 JS files, `docs/` |

---

## 1. Core framework changes (modified files)

| File | What changed | Why |
|---|---|---|
| `bootstrap.php` | Starts the session (`session_start()`), loads `config.php` | Login state needs `$_SESSION`; DB credentials need loading before anything connects |
| `app/core/Application.php` | Adds a global `Application::$app` singleton, `getRouter()/getRequest()/getResponse()` accessors, a tiny `set()/get()` key-value container, and wraps `run()` in try/catch (500 + message on uncaught exceptions, guards against a missing router) | `Controller::redirect()` (new) needs a way to reach the current `Response` without it being passed in; the try/catch turns a crash into a proper 500 instead of a blank page |
| `app/core/Controller.php` | Adds a per-controller `$layout` property (default `main`) + `setLayout()`; `render()` now uses `$this->layout` instead of a hardcoded `views/layouts/main.php`; adds `renderPartial()` (view without layout) and a `redirect()` shortcut | Auth pages and the dashboard/timetable pages each use a different HTML shell than the rest of the site — `AuthController` selects the `auth` layout, `TimetableController` selects the `dashboard` layout |
| `app/core/Request.php` | Adds JSON body parsing (merges `php://input` into the body array when `Content-Type: application/json`), `setRouteParams()/getRouteParam()`, `getHeaders()` | The auth JS sends `fetch()` requests as JSON; the old `getBody()` only read `$_POST`, so JSON logins/signups would have arrived empty |
| `app/core/Response.php` | Adds `send()` (arbitrary content + status without forcing `exit`), `setContentType()`, `setCookie()` | General-purpose response helpers not yet used by name in `AuthController` (which builds JSON manually) but rounds out the Response API to match Request/Router's growth |
| `app/core/Router.php` | Adds `post()/put()/delete()` registration methods; adds parameterized route matching (`/user/{id}` → named regex groups), storing matched params on the `Request` and passing them to the callback | Login/signup/reset are POST endpoints — the router previously only supported `GET`. Param matching isn't used by any route yet but was added alongside it |
| `app/public/index.php` | Registers `/login`, `/signup`, `/forgot-password` (GET + POST each), `/logout`, `/timetable` (GET), and `/dashboard` (redirects to `/timetable`) | Exposes both features as actual URLs |
| `app/models/UserModel.php` | Stub `getAllUsers()` replaced entirely with real, PDO-backed `findByEmail()`, `create()` (hashes password with `password_hash`), `updatePassword()` (same) | The old model returned hardcoded fake users; login/signup/reset need real persistence |
| `app/controllers/HomeController.php`, `app/views/errors/notfound.php`, `app/views/home.php`, `app/views/home/about.php`, `app/views/home/index.php`, `app/views/layouts/main.php` | Comments and minor copy fixes only | Documentation pass — no behavioral change |

## 2. Authentication feature (untracked files)

| File | Purpose |
|---|---|
| `config.php` | DB credentials (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME=staffsync_db`) via `define()` |
| `app/core/Database.php` | PDO singleton connection. The schema logic has been extracted from here and is now managed by a dedicated migration script (`database/migrate.php`). |
| `database/` | Contains `migrate.php` script along with `migrations/` and `seeds/` folders for robust schema management. |
| `app/controllers/AuthController.php` | Sets layout to `auth` in its constructor. GET handlers (`loginView`, `signupView`, `forgotPasswordView`) render the auth views (redirecting to `/dashboard` if already logged in). POST handlers: `login()` verifies the password with `password_verify` and sets `$_SESSION['user_id']`/`user_email`; `signup()` rejects an already-registered email, otherwise hashes + stores; `resetPassword()` looks the user up by email and overwrites the password hash; `logout()` destroys the session. All POST handlers respond with JSON via a private `jsonResponse()` helper |
| `app/views/layouts/auth.php` + `app/views/auth/{login,signup,forgot_password}.php` | Dedicated auth-page layout and views (no main nav/footer), migrated from standalone HTML mockups — see `docs/auth_migration_log.md` for the full migration narrative |
| `app/public/css/{login,signup,forgot_password}.css` | Page-specific styling for each auth page |
| `app/public/js/{login,signup,forgot_password}.js` | Client-side logic — calls `fetch()` against the POST routes |

## 3. How the login flow fits together

1. User visits `/login` → `AuthController::loginView()` renders `auth/login.php` inside the `auth` layout.
2. `login.js` submits the form via `fetch()` (JSON) to `POST /login`.
3. `Request::getBody()` parses the JSON body → `AuthController::login()` calls `UserModel::findByEmail()` and `password_verify()`.
4. On success: `$_SESSION['user_id']`/`user_email` are set, and a JSON response `{success: true, redirect: '/dashboard'}` is returned.
5. `login.js` reads the JSON and redirects the browser to `/dashboard'`, which immediately redirects to `/timetable`.

Signup and password-reset follow the same shape (render view → JS posts JSON → controller validates against `UserModel` → JSON response → JS redirects).

---

## 4. Timetable Officer dashboard (this update)

Built from the Figma wireframes page `Timetable_Officer` (file `QmyokbiCEdP3G6aQVpyTIo`). What looked like ~30 separate frames (Login, SignUp, Dashboard, Timetable×8 variants, Courses×8, Lecturers×3, ScheduleCourse→SelectSlots→SelectDetails→AddedSlot, Notifications…) turned out to decompose into:

- The auth frames → already covered by the existing `AuthController`/`auth` layout above.
- Two frames named "University Staff Management System" → turned out to be steps 2–3 of the *password-reset* flow (OTP verification, new password), **not** a dashboard — no code needed, already covered by `forgot_password.php`.
- All the "Timetable → CS/IS → Sem1/Sem2 → Y1–Y4" frames, plus "ScheduleCourse → SelectSlots → SelectDetails → AddedSlot" → **one single page** with different filter state and one interaction flow (select slots → confirm → fill a modal → block appears). That page is what got built.
- Courses, Lecturers, Notifications → **UI and Read-only views built.** The pages display data from the database, but Create/Update/Delete actions are still client-side only and do not persist. Settings is still pending.

| File | Purpose |
|---|---|
| `app/views/layouts/dashboard.php` | Shared shell for every officer screen: dark sidebar (logo, nav links, Sign Out) + white header (page title, notification/theme icons, user chip). Expects `$content`, `$active` (which nav item to highlight), `$pageTitle`/`$pageSubtitle`, optional `$notificationCount` |
| `app/public/css/dashboard.css` | Styling for that shell only (sidebar, header) — page-specific styling lives in each page's own CSS file, same pattern as the auth pages |
| `app/controllers/TimetableController.php` | See function breakdown below |
| `app/views/timetable/index.php` | The page content: toolbar (dept/sem segmented controls, lecturer/room filter selects, "Schedule Course" button), year side-tabs, the weekly grid (built as a CSS Grid, session blocks placed by `grid-row`/`grid-column`), the legend, the slot-selection confirm bar, and two modals (schedule-session form, read-only session details) |
| `app/public/css/timetable.css` | All styling for the toolbar, grid, blocks, legend, confirm bar, and both modals |
| `app/public/js/timetable.js` | All client-side interaction — see function breakdown below |
| `app/public/.htaccess` | Apache rewrite rules (`RewriteRule ^ index.php`) so pretty URLs like `/timetable` work under Apache/XAMPP the same way they already do under PHP's built-in dev server. Not needed for `php -S`, which auto-forwards unmatched paths on its own |

### `TimetableController` function-by-function

- **`catalog(): array`** — a hardcoded lookup of `dept → year → [course code => [title, lecturer]]`. Used to populate the "Course Module" dropdown in the scheduling modal. There is no `courses` table yet, so this stands in for one.
- **`sessions(): array`** — a hardcoded lookup of `"dept|sem|year" => [session, ...]`, each session having `day`, `start` (24h hour), `duration` (hours), `code`, `title`, `location`, `type` (`lecture`/`tutorial`/`lab`/`practical`). Only `cs|1|1`, `cs|1|2`, and `is|1|1` have sample data (matching what the Figma screens actually showed) — every other dept/sem/year combination renders as a legitimately empty timetable. There is no `timetable_sessions` table yet, so this stands in for one.
- **`index(Request $request)`** — the only route handler.
  1. Guards the page: if `$_SESSION['user_id']` isn't set, redirects to `/login` (same pattern as the old placeholder `/dashboard` route).
  2. Reads `dept`/`sem`/`year` from the query string, whitelists them against known values (`cs`/`is`, `1`/`2`, `1`–`4`), defaulting to `cs`/`1`/`1`.
  3. Looks up that combination's courses and sessions from `catalog()`/`sessions()`.
  4. Renders `timetable/index` inside the `dashboard` layout, passing all of the above plus `active: 'timetable'` and `notificationCount: 2` (hardcoded — there's no notifications feature yet either).

Switching department/semester/year is a normal page navigation — the toolbar tabs are `<a href="/timetable?dept=...&sem=...&year=...">` links, not JS. Simpler and more robust than client-side filtering, and consistent with how this framework already works (no JS routing anywhere else).

### `timetable.js` function-by-function

Everything is one `DOMContentLoaded` listener with these responsibilities:

- **`enterSelectionMode()` / `exitSelectionMode()`** — toggled by the "Schedule Course" button. Entering adds a `.selecting` class to the page (which the CSS uses to make free grid cells clickable/hoverable), swaps the button to a red "Cancel", and reveals the bottom confirm bar. Exiting reverses all of that and clears any in-progress selection.
- **Grid click handler (delegated on `.tt-grid`)** — one listener does double duty:
  - Click on a `.tt-block` (an existing session) → `openDetails()`, regardless of selection mode.
  - Click on a free `.tt-cell` while `selecting` is true → toggles that cell into/out of `selectedCells`. Picking a cell on a different day than the current selection clears the old selection first (a session can only span one day).
- **`updateConfirmBar()`** — keeps the "N slots selected — <Day>" text and the Confirm button's disabled state in sync with `selectedCells`.
- **Confirm button click** → computes the min/max hour across `selectedCells`, formats it as e.g. "Fri 8AM–10AM", writes that into the modal, resets the modal's fields, and shows it. Selection mode and the confirm bar stay active behind the modal (matches the Figma flow, where Back returns to slot-picking).
- **`courseModule` change handler** — reads the `data-lecturer` attribute off the selected `<option>` and shows "Lecturer: …" under the dropdown.
- **`sessionTypeToggle` click handler** — simple exclusive-select behavior across the four type buttons (Lecture/Tutorial/Lab/Practical).
- **Add to Timetable click** → validates a course was picked, builds a new `.tt-block` element (styled via `type-<type>` CSS class, positioned with inline `grid-column`/`grid-row` matching the selected day/hours), removes the now-occupied `.tt-cell` placeholders, appends the block to the grid, then closes the modal and exits selection mode. **This only mutates the DOM for the current page view** — there is no `timetable_sessions` table, so a reload reverts it. See "How the database is handled" below.
- **`openDetails()` / details modal close handlers** — read a block's `data-*` attributes (code, title, location, type, day, start, duration) into the read-only details modal.
- **Lecturer/room filter selects** — build a `code → lecturer` map from the modal's own `<option data-lecturer>` attributes (so it doesn't need a second source of truth), then show/hide `.tt-block` elements whose code/location don't match the current filter values.

---

## 5. Bug fix: missing `global.css` (found while testing this update)

`app/views/layouts/auth.php` has always linked `<link rel="stylesheet" href="/css/global.css">`, but that file never actually existed — a leftover from the original auth migration (`auth_migration_log.md` describes creating it, but it was never committed). The practical effect:

- **`forgot_password.php` was unaffected** — `forgot_password.css` happens to be fully self-contained (it defines the whole two-panel layout itself), so the missing `global.css` 404s harmlessly there.
- **`login.php` and `signup.php` were broken.** Both views use a shared `.login-layout-container` / `.left-panel` / `.right-panel` structure, but `login.css` only adds a few properties on top of that structure (assuming a base already exists), and `signup.css` doesn't define `.left-panel`, `.branding`, `.feature-title`, `.feature-description`, or `.right-footer` **at all**. Without `global.css`, both pages rendered as unstyled, top-to-bottom stacked HTML.

**Fix:** added `app/public/css/global.css` with the shared base — reset, `.login-layout-container` (flex, full height), `.left-panel` (dark navy gradient, matching the palette already proven in `forgot_password.css`, flex-column so the footer sits at the bottom), branding/logo/feature-text styles, and fallback `.right-panel`/`.right-footer` rules. Verified with screenshots — `/login`, `/signup`, and `/forgot-password` all render the intended dark-sidebar-plus-white-form-card layout now.

## 6. Local environment changes (this update)

These aren't feature code — they're what it took to actually run the app on this machine and see the UI.

| Change | Why |
|---|---|
| `config.php`: `DB_USER`/`DB_PASS` changed from `root`/`''` to `staffsync`/`staffsync_dev_pw` | The system MariaDB on this machine authenticates `root` via the `unix_socket` plugin, which only accepts connections from the OS `root` account — not the PDO connection PHP makes as the regular dev user. Connecting as `root` with an empty password over TCP therefore fails with `Access denied`. A dedicated `staffsync` user with a real password sidesteps that (see step-by-step run instructions below for the exact `CREATE USER` command — it has to be run with `sudo` interactively, which an agent session can't do) |
| `app/public/.htaccess` added | Needed only if serving via Apache/XAMPP instead of `php -S`. XAMPP's Apache has `mod_rewrite` enabled by default; `AllowOverride All` still has to be set on the vhost's `<Directory>` block for the `.htaccess` to take effect (see run instructions) |

**Not committed anywhere, machine-specific:** an XAMPP vhost on port 8081 pointing at this repo's `app/public`, and the `staffsync` MySQL user — both live outside this repo (in `/opt/lampp/etc/httpd.conf` and MariaDB's own user table), so they don't show up in `git status` and have to be recreated on any other machine.

## 7. How to run this locally — step by step

Two ways to preview it. **Option A is simpler** and is what was used to verify the Timetable page during development; Option B is only worth it if you specifically need Apache/XAMPP.

### Option A — PHP's built-in dev server (fastest)

1. Make sure MariaDB is running: `sudo systemctl start mariadb` (or `sudo service mariadb start`).
2. Create the dedicated DB user, once, ever (skip if already done):
   ```
   sudo mysql -e "CREATE USER IF NOT EXISTS 'staffsync'@'127.0.0.1' IDENTIFIED BY 'staffsync_dev_pw'; GRANT ALL PRIVILEGES ON staffsync_db.* TO 'staffsync'@'127.0.0.1'; FLUSH PRIVILEGES;"
   ```
   This has to match `config.php`'s `DB_USER`/`DB_PASS` — it already does, both are `staffsync` / `staffsync_dev_pw`.
3. From the repo root, run the database migrations and seed data: `php database/migrate.php --seed`
4. Start the server: `php -S 127.0.0.1:8099 -t app/public`
5. Open `http://127.0.0.1:8099/login`, log in with a seeded account (or signup). You'll land on `/timetable`.

### Option B — XAMPP / Apache

1. Steps 1–2 from Option A (MariaDB running, `staffsync` user created) still apply — XAMPP's Apache will use the same system MariaDB, not its own bundled one, to avoid a port-3306 conflict.
2. Copy the repo into XAMPP's webroot: `sudo cp -r /home/c_dul/Documents/Dev/y2p /opt/lampp/htdocs/y2p`
3. Give it a dedicated port so it doesn't collide with anything else already in `htdocs`. Add to `/opt/lampp/etc/httpd.conf`:
   ```
   Listen 8081
   <VirtualHost *:8081>
       DocumentRoot "/opt/lampp/htdocs/y2p/app/public"
       <Directory "/opt/lampp/htdocs/y2p/app/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
   The `DocumentRoot` **must** point at `app/public`, not the repo root — otherwise `config.php` and everything else under `app/` becomes web-reachable.
4. `sudo /opt/lampp/lampp startapache`
5. Open `http://localhost:8081/signup`, same flow as Option A from here.

**Re-syncing after edits:** Option A always reflects the live repo (no copy step). Option B is a copy — if you edit files in `/home/c_dul/Documents/Dev/y2p` after copying into `htdocs`, re-run step 2's `cp -r` (or symlink instead of copying) to see changes reflected under Apache.

---

## 8. How authentication works, end to end

- **Session, not tokens.** `bootstrap.php` calls `session_start()` on every request. A logged-in user is just `$_SESSION['user_id']` + `$_SESSION['user_email']` being set — no JWT, no remember-me cookie, no CSRF token yet.
- **Passwords** are never stored or compared in plaintext: `UserModel::create()`/`updatePassword()` hash with PHP's `password_hash()` (bcrypt by default); `AuthController::login()` compares with `password_verify()`.
- **Route guarding is manual, per-controller.** There's no middleware layer — `AuthController::loginView()`/`signupView()` check `isset($_SESSION['user_id'])` and redirect *away* from auth pages if already logged in; `TimetableController::index()` checks the same session key and redirects *to* `/login` if it's missing. Any new controller that needs auth has to repeat this check itself.
- **Logout** (`AuthController::logout()`) just calls `session_destroy()` and redirects to `/login`.
- **Frontend talks to the backend as JSON**, not classic form posts: `login.js`/`signup.js`/`forgot_password.js` all `fetch()` the POST routes with `Content-Type: application/json`, and `AuthController`'s POST handlers reply with `{success, message, redirect}` JSON (via a private `jsonResponse()` helper) rather than doing a server-side redirect — the JS reads `redirect` and does `window.location.href` itself.

## 9. How the database is handled

- **One PDO singleton.** `app/core/Database.php` holds a single static `PDO` instance; `Database::getConnection()` lazily creates it on first call and returns the same instance after that.
- **Migration System.** The database schema is managed via `database/migrate.php`. You must run `php database/migrate.php --seed` manually to create the database, tables, and seed data.
- **Multiple entities exist.** Models exist for `User`, `Course`, `Lecturer`, `Instructor`, `Notification`, and `TimetableSession`. The UI reads real data from these tables.
- **CRUD is mostly Read-only.** While the data is fetched from the database, operations like "Add to Timetable", "Add Course", or "Edit Lecturer" only append elements client-side (e.g., via `timetable.js` or `courses.js`). Nothing is written back to the server yet, so changes do not survive a page reload.
  - Building full persistence means adding `POST`/`PUT`/`DELETE` routes and updating the controllers (e.g., `CoursesController`, `TimetableController`) to handle submissions and update the respective tables.
- **Credentials** live in `config.php` (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`), loaded once in `bootstrap.php` before anything else runs. This file is untracked/local (see section 5 for why the credentials in it changed on this machine).

---

## 10. Related docs

- [`WALKTHROUGH.md`](WALKTHROUGH.md) — file-by-file reference for the core framework (predates the Timetable feature; still accurate for `app/core/*`)
- [`MVC_Architecture_Explanation.md`](MVC_Architecture_Explanation.md) — conceptual MVC primer
- [`auth_migration_log.md`](auth_migration_log.md) — detailed narrative specifically on migrating the auth HTML mockups into the MVC structure (layouts, views, asset moves, wiring)

This doc is the complement to those: a snapshot of *what's currently uncommitted and why*, rather than a standing architecture reference.
