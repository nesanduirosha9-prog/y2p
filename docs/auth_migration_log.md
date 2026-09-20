# Authentication Pages Migration Log

## Overview
This document outlines the changes made to integrate the standalone HTML authentication pages (`index.html`, `signup.html`, `forgot_password.html`) into our custom MVC framework.

## Changes Made

### 1. Updated Core Architecture
**File:** `app/core/Controller.php`
- **What:** Added a `$layout` property and a `setLayout()` method. Updated the `render()` method to use `$this->layout` instead of hardcoding `main.php`.
- **Why:** The authentication pages have a different HTML structure and don't include the main navigation bar and footer. By supporting dynamic layouts, we can use a dedicated `auth.php` layout for these pages while keeping the main layout intact for the rest of the application.

### 2. Created Auth Layout
**File:** `app/views/layouts/auth.php`
- **What:** Extracted the common HTML boilerplate (`<!DOCTYPE html>`, `<head>`, `<body>`) from the provided HTML files. Included FontAwesome and the global stylesheet.
- **Why:** To follow the DRY (Don't Repeat Yourself) principle. This ensures that any changes to the head section (like updating FontAwesome or global CSS) only need to be done in one place.

### 3. Migrated Views
**Files:** 
- `app/views/auth/login.php`
- `app/views/auth/signup.php`
- `app/views/auth/forgot_password.php`
- **What:** Copied the body content from the respective HTML files into these new view files. Stripped the `<head>` and `<body>` wrapper tags.
- **Why:** In MVC, views are typically partials injected into a layout. By removing the layout boilerplate, these views act precisely as the `$content` variable in `auth.php`.
- **What else:** Updated the CSS and JS links to use absolute paths (`/js/login.js` instead of `../../public/js/login.js`), and updated internal page links (e.g., `<a href="/signup">` instead of `signup.html`) to prepare for MVC routing.

### 4. Migrated Assets (CSS & JS)
**Files Moved:**
- `login.css`, `signup.css`, `forgot_password.css` -> Moved to `app/public/css/`
- `login.js`, `signup.js`, `forgot_password.js` -> Moved to `app/public/js/`
- **What & Why:** The styling and client-side logic scripts originally placed in the root directory were moved into their respective `public` subdirectories. This correctly separates static assets from backend logic and templates. The layout and view files were already updated in the previous steps to expect these files at `/css/...` and `/js/...`.

### 5. Backend Infrastructure & Database
**Files Created:**
- `bootstrap.php` (Modified)
- `config.php`
- `app/core/Database.php`
- `app/models/UserModel.php`
- **What & Why:** 
  - We introduced `session_start()` in `bootstrap.php` to handle login state via `$_SESSION`.
  - `config.php` holds MySQL credentials.
  - `Database.php` handles PDO connections and features an **Auto-Setup mechanism**: it automatically creates the `staffsync_db` database and the `users` table upon the first run, easing the onboarding process for contributors.
  - `UserModel.php` is responsible for querying the `users` table, ensuring passwords are saved securely using PHP's `password_hash()`.

### 6. Authentication Controller & Routing
**Files:**
- `app/controllers/AuthController.php`
- `app/public/index.php` (Modified)
- **What & Why:** 
  - The `AuthController` handles both rendering the views (GET) and processing the authentication logic (POST).
  - Routes (`/login`, `/signup`, `/forgot-password`, `/logout`) were registered in `index.php`. The POST routes return JSON responses to power the dynamic frontend.

### 7. Frontend to Backend Wiring
**Files Modified:**
- `app/public/js/login.js`, `signup.js`, `forgot_password.js`
- **What & Why:** Originally, these scripts simulated successful submissions using dummy redirects. They were updated to collect form inputs (e.g., email and password) and send them to the backend using the asynchronous `fetch` API. This allows the UI to show real error messages or gracefully transition states based on actual backend responses without reloading the page.

## System Flow Summary
1. A user visits `/signup`. The `AuthController` renders the signup view.
2. The user progresses through the frontend JS multi-step form.
3. At step 3, `signup.js` uses `fetch()` to POST the email and password to `/signup`.
4. `AuthController::signup()` validates the data, checks `UserModel`, and securely stores the new user in the auto-created database. It returns a JSON success response.
5. The frontend reads the JSON and redirects the user to `/login`.
6. Upon logging in, `AuthController::login()` validates the credentials against the database and sets `$_SESSION['user_id']`.
