# y2p MVC

This guide is to understand how our custom-built PHP framework works. This project use the **MVC (Model-View-Controller)** pattern, which is a standard way to organize code to keep it clean, understandable, and scalable.

## What is MVC?
- **Model**: Handles data and business logic (e.g., fetching information from a MySQL database).
- **View**: Handles what the user sees (HTML, CSS, JS).
- **Controller**: The brain that connects Models and Views. It receives a request, asks the Model for data, and passes that data to the View to display on the screen.

---

## Project Structure

### 1. The Starting Point (Entry Point)
- `bootstrap.php`: This file automatically loads our PHP classes so we don't have to manually `include` or `require` them everywhere. It keeps our code clean.
- `app/public/index.php`: This is the **Front Controller**. Every single request (like visiting your website) goes through this file first. It sets up the app, defines the URLs (routes), and starts everything.

### 2. The Core Engine (`app/core/`)
These files make the framework function. They handle the behind-the-scenes work.
- `Application.php`: The main app container. It holds the Router, Request, and Response objects and coordinates the whole lifecycle of a user's visit.
- `Router.php`: The "Traffic Cop". It looks at the URL the user visited (like `/about`) and decides which Controller should handle it.
- `Request.php`: Gathers all the information about the user's request (e.g., form data they submitted, or the URL they visited).
- `Response.php`: Helps send data back to the user, like redirecting them to another page, setting cookies, or sending JSON data.
- `Controller.php`: The base class that all our specific controllers will inherit from. It provides useful tools, like a `render()` function to easily display HTML pages.

### 3. The Controllers (`app/controllers/`)
- `HomeController.php`: An example controller. It contains methods (actions) like `index()` for the home page and `about()` for the about page. It fetches data and tells the View to render it.

### 4. The Models (`app/models/`)
- `UserModel.php`: A simple example model. In a real app, this file connects to your MySQL database to get user data. 

### 5. The Views (`app/views/`)
- `layouts/main.php`: The master HTML template. It contains the `<head>`, navbar, and footer. It injects the specific page content into the middle so you don't repeat HTML structure on every page.
- `home/index.php`: The specific HTML for the home page.
- `home/about.php`: The specific HTML for the about page.
- `errors/notfound.php`: The custom 404 Error page shown when a user visits a URL that doesn't exist.

---

## How a Request Flows (Step-by-Step)

Imagine a user visits `http://yourwebsite.com/about`:

1. **The Request Arrives**: The web server directs the user to `app/public/index.php`.
2. **Setup**: The app starts up and looks at the URL (`/about`).
3. **Routing**: `Router.php` checks its list of known URLs. It finds `/about` and sees that it belongs to `HomeController->about()`.
4. **The Controller Acts**: The `about()` method in `HomeController` runs. It might ask a Model for data (though not needed for a simple about page).
5. **Rendering the View**: The Controller calls `render('home/about')`. This grabs the HTML from `app/views/home/about.php`, wraps it inside `app/views/layouts/main.php`, and sends the final designed webpage back to the user!

---
