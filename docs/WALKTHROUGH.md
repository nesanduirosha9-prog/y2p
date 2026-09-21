Project walkthrough — y2p mini PHP MVC

This document explains the purpose of each file in the repository and the classes/functions contained in them. It's intended as a quick reference to understand the framework wiring.

Top-level files

- `bootstrap.php`: Autoloader
  - Purpose: simple PSR-4-like autoloader, converts namespaces to path and requires files.
  - Key behavior: registers `spl_autoload_register` which maps `Namespace\Class` → `Namespace/Class.php`.

Public entry

- `app/public/index.php`: Front controller
  - Purpose: bootstraps the application, registers routes, starts the app lifecycle.
  - Main actions:
    - create `Application` with project root
    - create `Router` and register routes (GET `/`, `/about`, `/api/users`, `/oldabout`)
    - call `$app->useRouter($router)` then `$app->run()`

Core framework (app/core)

- `app/core/Application.php`: Application container + lifecycle
  - Purpose: central app object, stores `ROOT_DIR`, global `app` instance, holds a tiny service container, coordinates request/response and dispatch.
  - Important properties:
    - `public static string $ROOT_DIR` — root directory used by views/layouts
    - `public static Application $app` — global convenience instance
    - `$router`, `$request`, `$response` — lifecycle objects
  - Key methods:
    - `useRouter(Router $router)` — register the router before `run()`
    - `getRouter()`, `getRequest()`, `getResponse()` — accessors
    - `set($key, $value)` / `get($key)` — tiny container storage
    - `run()` — creates `Request` and `Response`, ensures a router exists, calls `Router::resolve()` and echoes returned string results; catches exceptions and maps them to 500 responses.

- `app/core/Router.php`: Router / dispatcher
  - Purpose: match incoming requests to registered callbacks and support simple parameterized routes.
  - Internal structure: `$routes` array keyed by method (`get`, `post`, `put`, `delete`) mapping path → callable.
  - Registration helpers: `get()`, `post()`, `put()`, `delete()`.
  - `resolve(Request $request, Response $response)`:
    - Gets path and method from `Request`.
    - Tries exact path match first.
    - If no exact match, tries parameterized routes with `{name}` syntax (converted to named regex groups) and if matched, extracts params and calls the callback with `(Request, Response, $params)` and stores params on the `Request` via `setRouteParams()`.
    - If nothing matches, sets 404 and returns the `HomeController::notFound()` page.

- `app/core/Request.php`: Request abstraction
  - Purpose: encapsulates request data and provide parsing helpers.
  - Key methods:
    - `getPath()` — returns URL path (no query string)
    - `getMethod()` — returns HTTP method (lowercased)
    - `isGet()`, `isPost()` — convenience checks
    - `getBody()` — returns POST/form data and additionally parses JSON body when `Content-Type: application/json` is present (reads `php://input` and merges JSON into body)
    - `getQueryParams()` — returns `$_GET`
    - `setRouteParams(array $params)` / `getRouteParam(name, default)` — storage for route params populated by `Router`
    - `getHeaders()` — returns headers normalized from `$_SERVER`

- `app/core/Response.php`: Response helpers
  - Purpose: centralize response operations (status codes, headers, JSON, redirects, cookies)
  - Key methods:
    - `setStatusCode(int)` — wrapper around `http_response_code()`
    - `redirect(string)` — `Location:` header + `exit`
    - `json(array, int)` — sets JSON header, echoes JSON and `exit`
    - `send(string, int, string)` — send arbitrary content with content-type (does not `exit`)
    - `setContentType(string)` — convenience header setter
    - `setCookie(...)` — thin wrapper over `setcookie()`
    - `setHeader(name, value)` — generic header setter

- `app/core/Controller.php`: Base controller and view helpers
  - Purpose: provide helper methods used by controllers to render views and redirect.
  - Key methods:
    - `render(view, params)` — extracts `$params` into variables, renders the view into `$content`, then loads the layout at `views/layouts/main.php` and returns the final HTML string.
    - `renderPartial(view, params)` — returns the view output without layout (useful for fragments / AJAX)
    - `redirect(url)` — convenience to call `Application::$app->getResponse()->redirect($url)`

Controllers (app/controllers)

- `app/controllers/HomeController.php`
  - Purpose: example controller providing the home and about pages and sample API endpoints.
  - Methods:
    - `index()` — fetches users from `UserModel` and returns `render('home/index', ['title'=>..., 'users'=>...])`
    - `about()` — returns `render('home/about', ['title'=>'About'])`
    - `usersJson(Response $response)` — uses the `Response::json()` helper to return JSON data
    - `oldAbout(Response $response)` — demonstrates `Response::redirect()` to a new route
    - `notFound()` — returns the 404 view via `render('errors/notfound')`

Models (app/models)

- `app/models/UserModel.php`
  - Purpose: example/simple model returning stubbed user data.
  - Methods:
    - `getAllUsers(): array` — returns an array of users (stubbed — replace with DB queries in real apps)

Views (app/views)

- `app/views/layouts/main.php` — main HTML layout used by `Controller::render()`; expects `$content` and `$title` variables.
- `app/views/home/index.php` — home page view; expects `$users` and optional `$title`.
- `app/views/home/about.php` — about page view (static content).
- `app/views/home.php` — small partial used in examples that lists users.
- `app/views/errors/notfound.php` — 404 page.

Public assets

- `app/public/manifest.json` — PWA manifest describing icons and metadata.
- `app/public/service-worker.js` — (if present) service worker for the PWA (not documented here in detail).

How a request flows (summary)

1. HTTP request arrives at the webserver configured to serve `app/public/index.php`.
2. `index.php` requires `bootstrap.php` (autoloader), creates `Application` and `Router`, registers routes, calls `$app->useRouter($router)` and `$app->run()`.
3. `Application::run()` creates `Request` and `Response` objects and calls `Router::resolve()`.
4. `Router::resolve()` finds a matching route (exact or parameterized). If a parameterized route matches, params are stored on `Request` and passed to the callback.
5. The callback (often an anonymous function in `index.php`) creates a controller or calls a controller method. The controller uses models to obtain data and calls `render()` to produce HTML or calls `Response::json()` / `redirect()` for APIs and redirects.
6. `Controller::render()` captures the view output and wraps it with `views/layouts/main.php`.

Notes & next steps

- This is a lightweight learning framework. If you want this to become a more complete framework I can:
  - add middleware support (before/after handlers)
  - add a routing DSL with named route registration and URL generation
  - add a simple ORM or database connection service in `Application` container
  - add unit tests for routing and controller dispatch

---

Generated by pairing with the codebase. If you want this as a file in a different format or to include code excerpts inline, tell me which format you prefer.
