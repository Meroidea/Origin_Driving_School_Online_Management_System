# Origin Driving School Management System

A comprehensive database-driven website for managing driving school operations.

**Created for DWIN309 Final Assessment at Kent Institute Australia**

---

## 📋 Project Overview

The Origin Driving School Management System is a full-featured web application designed to streamline the daily operations of a driving school. It provides complete functionality for managing students, instructors, lessons, courses, invoices, payments, vehicles, and communications.

### Group Members
- **Sujan Darji** (Student ID: K231673) - Database schema, user authentication, student management, invoicing
- **Anthony Allan Regalado** (Student ID: K231715) - Instructor & lesson scheduling, UI/UX, vehicle management, communications

---

## 🎨 Design Specifications

### Color Palette
- **Primary Color**: `#4e7e95` (Blue)
- **Secondary Color**: `#e78759` (Orange)
- **Light/Background**: `#e5edf0` (Light Blue)
- **Additional Colors**: Success (#27ae60), Error (#e74c3c), Warning (#f39c12)

### Typography
- **Font Family**: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif
- **Base Font Size**: 16px
- **Line Height**: 1.6

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Server**: Apache (XAMPP)
- **Icons**: Font Awesome 6.4.0
- **Architecture**: MVC (Model-View-Controller)

---

## 📁 Project Structure

```
origin_driving_school/
├── app/
│   ├── core/
│   │   ├── Database.php           # Database connection class
│   │   └── Model.php               # Base model class
│   ├── models/
│   │   ├── User.php                # User authentication
│   │   ├── Student.php             # Student management
│   │   ├── Instructor.php          # Instructor management
│   │   ├── Lesson.php              # Lesson/scheduling
│   │   ├── Invoice.php             # Invoice management
│   └── ├── CourseAndOther.php              # Course management
│
│── config/
│   └── config.php  
│                # Configuration settings
├── branches/
│   └──index.php
│
├── communications/
│   └── index.php
│   └── view.php
│
├── courses/
│   └── index.php  
│
├── instructors/
│   └── index.php 
│   └── create.php 
│   └── delete.php 
│   └── edit.php 
│   └── view.php 
│   └── lessons.php
│   └── profile.php
│   └── schedule.php 
│   └── students.php 
│
├── invoices/
│   └── index.php 
│ 
├── payments/
│   └── index.php
│ 
├── lessons/
│   └── index.php  
│   └──view.php
│   └──calendar.php
│   └──bulk_action.php
│ 
├── staff/
│   └── index.php
│ 
├── students/
│   └── index.php 
│   └── book-lesson.php
│   └── profile.php
│   └── progress.php
│   └── create.php 
│   └── delete.php 
│   └── edit.php 
│   └── view.php 
│   └── lessons.php 
│   └── invoices.php 
│ 
├── vehicles/
│   └── index.php
│ 
├── public/
│   ├── css/
│   │   ├── style.css               # Main stylesheet
│   │   └── dashboard.css           # Dashboard styles
│   ├── js/
│   │   ├── script.js               # Main JavaScript
│   │   └── dashboard.js            # Dashboard JavaScript
│   └── uploads/                    # File uploads directory
|       ├── .htaccess
├── views/
│   └── layouts/
│       ├── header.php              # Header layout
│       ├── footer.php              # Footer layout
│       └── sidebar.php             # Sidebar navigation
├── index.php                       # Homepage
├── login.php                       # Login page
├── logout.php                      # Logout handler
├── dashboard.php                   # Main dashboard
├── origin_driving_school_database.sql  # Database schema + seed data
└── README.md                       # This file
```

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP (or similar) with PHP 7.4+ and MySQL/MariaDB
- Web browser (Chrome, Firefox, Edge recommended)
- Text editor (VS Code recommended)

### Step-by-Step Setup

#### 1. Install XAMPP
1. Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Install XAMPP to `C:\xampp` (Windows) or `/opt/lampp` (Linux)
3. Start Apache and MySQL services from XAMPP Control Panel

#### 2. Create Project Directory
1. Navigate to `C:\xampp\htdocs\` (or your web server root)
2. Create a new folder named `origin_driving_school`

#### 3. Copy Project Files
Copy all project files into the `origin_driving_school` directory:
```
htdocs/
└── origin_driving_school/
    ├── app/
    ├── config/
    ├── public/
    ├── views/
    ├── index.php
    ├── login.php
    └── (other files)
```

#### 4. Create Database
1. Open phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click "Import" tab
3. Choose the `origin_driving_school_database.sql` file
4. Click "Go" to import

The database `origin_driving_school` will be created and seeded with sample
branches, users, courses, and related records.

#### 5. Configure the Application
Review `config/config.php` and, if needed, update the database credentials to
match your environment (the XAMPP defaults are user `root` with an empty
password). Update `APP_URL` if you serve the app from a different path.

#### 6. Launch
Open [http://localhost/origin_driving_school/](http://localhost/origin_driving_school/)
in your browser.

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

## 🛡️ Security Highlights

- PDO **prepared statements** for all database access (SQL injection protection)
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- Session-based **CSRF tokens** on authentication forms
- `HttpOnly`, cookie-only session configuration with a configurable lifetime
- **Role-based access control** via `requireLogin()` and `requireRole()`
- Input sanitisation to mitigate cross-site scripting (XSS)

> For production: disable `display_errors` in `config/config.php`, replace the
> demo accounts, and connect with a least-privilege database user rather than
> `root`.