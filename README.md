# Origin Driving School Management System

A comprehensive, database-driven web application for managing the day-to-day
operations of a driving school — students, instructors, lessons, courses,
invoices, payments, vehicles, and communications.

**Created for the DWIN309 Final Assessment at Kent Institute Australia.**

---

## 📋 Project Overview

The Origin Driving School Management System streamlines the administration of a
multi-branch driving school. It provides role-based portals for administrators,
staff, instructors, and students, each with a tailored dashboard and feature
set. The application is built on a lightweight, custom MVC framework in PHP and
uses PDO with prepared statements throughout for secure database access.

### Authors

| Name | Student ID | Role |
|------|-----------|------|
| Sujan Darji | K231673 | Full-stack development, database design, authentication |
| Anthony Allan Regalado | K231715 | Full-stack development, UI/UX, scheduling & billing |

<img width="669" height="940" alt="Origin Driving School login screen" src="https://github.com/user-attachments/assets/b3d96be7-c9d6-456e-ad43-6a143e7d245b" />

---

## ✨ Key Features

### Administrator & Staff
- Dashboard with live statistics (active students, instructors, upcoming lessons, pending invoices)
- Student management (create, view, edit, delete, progress tracking)
- Instructor management and scheduling
- Lesson management with calendar view and bulk actions
- Course catalogue management
- Invoice and payment processing (GST-aware)
- Fleet (vehicle) management with service/registration expiry tracking
- Multi-branch management
- Communications (email/SMS broadcasts)
- Reports and system settings (admin only)

### Instructor
- Personal dashboard (today's lessons, upcoming lessons, students, monthly completions)
- Daily schedule and lesson history
- Assigned students overview
- Profile management

### Student
- Personal dashboard (upcoming lessons, completed lessons, pending payments, test readiness)
- Online lesson booking
- Progress tracking
- Invoices and payment history
- Profile management

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Backend | PHP 7.4+ |
| Database | MySQL / MariaDB (accessed via PDO) |
| Architecture | Custom MVC (Model–View–Controller) |
| Icons | Font Awesome 6.4.0 |
| Server | Apache (XAMPP recommended for local development) |

---

## 🎨 Design Specifications

### Colour Palette
- **Primary**: `#4e7e95` (Blue)
- **Secondary**: `#e78759` (Orange)
- **Light / Background**: `#e5edf0` (Light Blue)
- **Status**: Success `#27ae60`, Error `#e74c3c`, Warning `#f39c12`

### Typography
- **Font Family**: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif
- **Base Font Size**: 16px
- **Line Height**: 1.6

---

## 📁 Project Structure

```
origin_driving_school/
├── app/
│   ├── core/
│   │   ├── Database.php            # PDO singleton connection & query helpers
│   │   └── Model.php               # Abstract base model (CRUD, pagination, validation)
│   └── models/
│       ├── User.php                # Authentication & user accounts
│       ├── Student.php             # Student records
│       ├── Instructor.php          # Instructor records
│       ├── Lesson.php              # Lessons, scheduling & analytics
│       ├── Invoice.php             # Invoicing & billing
│       └── CourseAndOther.php      # Courses, vehicles, branches & shared entities
├── config/
│   └── config.php                  # Configuration, helpers, autoloader, session setup
├── branches/index.php              # Branch management
├── communications/                 # Messaging (index.php, view.php)
├── courses/index.php               # Course catalogue
├── instructors/                    # Instructor module (index, create, edit, view, ...)
├── invoices/index.php              # Invoice listing
├── payments/index.php              # Payment processing
├── lessons/                        # Lesson module (index, view, calendar, bulk_action)
├── staff/index.php                 # Staff management
├── students/                       # Student module (index, book-lesson, progress, ...)
├── vehicles/index.php              # Fleet management
├── reports/index.php               # Reports
├── settings/index.php              # System settings
├── public/
│   ├── css/                        # style.css, dashboard.css
│   ├── js/                         # script.js, dashboard.js
│   └── uploads/                    # User-uploaded files (protected by .htaccess)
├── views/
│   └── layouts/                    # header.php, footer.php, sidebar.php
├── index.php                       # Public homepage
├── login.php                       # Login page
├── logout.php                      # Logout handler
├── dashboard.php                   # Role-based dashboard
├── notifications.php               # Notifications
├── test_system.php                 # Standalone component/self-test script
└── origin_driving_school_database.sql  # Complete database schema + seed data
```

---

## 🗄️ Database

The schema is defined in **`origin_driving_school/origin_driving_school_database.sql`**
and contains the following core tables:

`branches`, `users`, `courses`, `instructors`, `staff`, `students`, `vehicles`,
`lessons`, `invoices`, `payments`, `notes`, `attachments`, `notifications`,
`communications`, and `system_settings`.

The `users` table is the central authentication table; role-specific data lives
in the `students`, `instructors`, and `staff` tables, each linked back to
`users` via `user_id`. The script drops and recreates the `origin_driving_school`
database and seeds it with sample branches, users, courses, and related records.

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP (or an equivalent stack) with **PHP 7.4+** and **MySQL/MariaDB**
- A modern web browser (Chrome, Firefox, or Edge)
- A code editor such as VS Code (optional)

### Steps

1. **Install and start XAMPP.** Start the **Apache** and **MySQL** services from
   the XAMPP Control Panel.

2. **Deploy the project.** Copy the `origin_driving_school` folder into your web
   root (e.g. `C:\xampp\htdocs\` on Windows or `/opt/lampp/htdocs/` on Linux).

3. **Import the database.**
   - Open phpMyAdmin at [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
   - Click the **Import** tab.
   - Choose `origin_driving_school/origin_driving_school_database.sql`.
   - Click **Go**. This creates the `origin_driving_school` database and loads the
     sample data.

4. **Configure the application.** Review `origin_driving_school/config/config.php`
   and adjust the database credentials if your setup differs from the XAMPP
   defaults:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'origin_driving_school');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // XAMPP default is an empty password
   ```

   If you serve the app from a different path, update `APP_URL` accordingly.

5. **Open the application** at
   [http://localhost/origin_driving_school/](http://localhost/origin_driving_school/).

---

## 🔐 Default Login Credentials

The seed data provides one account per role. **These are demo credentials —
change or remove them before any production use.**

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@origindrivingschool.com.au` | `password` |
| Instructor | `david.smith@origindrivingschool.com.au` | `password` |
| Student | `olivia.taylor@email.com` | `password` |

---

## 🛡️ Security

The application applies several standard web-security practices:

- **SQL injection protection** — all database access uses PDO **prepared
  statements** with bound parameters.
- **Password hashing** — passwords are stored using PHP's `password_hash()`
  (bcrypt) and verified with `password_verify()`.
- **CSRF protection** — session-based CSRF tokens are generated and verified on
  authentication forms.
- **Session hardening** — `HttpOnly` and cookie-only session settings, a named
  session, and a configurable lifetime.
- **Role-based access control** — `requireLogin()` and `requireRole()` guard each
  page against unauthorised access.
- **Input sanitisation** — user input is sanitised before output to mitigate XSS.

> **Note for production:** disable error display (`display_errors`) in
> `config/config.php`, replace the demo accounts, and use a dedicated database
> user with least-privilege permissions instead of `root`.

---

## 🧪 Testing

A standalone self-test script is provided at
`origin_driving_school/test_system.php`. With the app deployed and the database
imported, browse to
[http://localhost/origin_driving_school/test_system.php](http://localhost/origin_driving_school/test_system.php)
to run a series of checks against the database connection and models.

---

## 📄 Licence

This project was developed for academic purposes as part of the DWIN309 unit at
Kent Institute Australia. It is intended for educational and demonstration use.
