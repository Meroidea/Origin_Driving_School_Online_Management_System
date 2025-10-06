# Origin Driving School Management System

A comprehensive database-driven website for managing driving school operations.

---

## 📋 Project Overview

The Origin Driving School Management System is a full-featured web application designed to streamline the daily operations of a driving school. It provides complete functionality for managing students, instructors, lessons, courses, invoices, payments, vehicles, and communications.

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
Login Credentials:
Admin: admin@origindrivingschool.com.au / pw is 'password'
Instructor: david.smith@origindrivingschool.com.au / pw is 'password'
Student: olivia.taylor@email.com / pw is 'password'

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Server**: Apache (XAMPP)
- **Icons**: Font Awesome 6.4.0
- **Architecture**: MVC (Model-View-Controller)
<img width="669" height="940" alt="Screenshot 2025-10-06 at 23 01 38" src="https://github.com/user-attachments/assets/b3d96be7-c9d6-456e-ad43-6a143e7d245b" />

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
├── database_schema.sql             # Database schema
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

The database `origin_driving_school` will be
