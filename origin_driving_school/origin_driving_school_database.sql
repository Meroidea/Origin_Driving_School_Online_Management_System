-- =====================================================
-- Origin Driving School Management System
-- Complete Database Creation Script
-- @author SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715
-- @version 2.0
-- For phpMyAdmin/MySQL/MariaDB
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Set character encoding
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Drop and create database
DROP DATABASE IF EXISTS origin_driving_school;
CREATE DATABASE origin_driving_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE origin_driving_school;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Branches Table
CREATE TABLE branches (
    branch_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    suburb VARCHAR(100) NOT NULL,
    state VARCHAR(50) DEFAULT 'Victoria',
    postcode VARCHAR(10) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_branch_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table (Main authentication table)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_role ENUM('admin', 'instructor', 'student', 'staff') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (user_role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses Table
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    number_of_lessons INT NOT NULL,
    lesson_duration INT NOT NULL COMMENT 'Duration in minutes',
    price DECIMAL(10, 2) NOT NULL,
    course_type ENUM('beginner', 'intermediate', 'advanced', 'test_preparation', 'refresher') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_course_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Instructors Table (Updated schema with additional fields)
CREATE TABLE instructors (
    instructor_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    branch_id INT NOT NULL,
    certificate_number VARCHAR(50) NOT NULL,
    adta_membership VARCHAR(50) NULL,
    wwc_card_number VARCHAR(50) NULL,
    license_expiry DATE NULL,
    certification_expiry DATE NULL,
    police_check_date DATE NULL,
    medical_check_date DATE NULL,
    date_joined DATE NOT NULL,
    hourly_rate DECIMAL(10, 2) NOT NULL,
    specialization VARCHAR(100) NULL,
    bio TEXT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id),
    INDEX idx_instructor_user (user_id),
    INDEX idx_instructor_branch (branch_id),
    INDEX idx_instructor_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff Table
CREATE TABLE staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    branch_id INT NOT NULL,
    position VARCHAR(100) NOT NULL,
    hire_date DATE NOT NULL,
    salary DECIMAL(10, 2) NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id),
    INDEX idx_staff_user (user_id),
    INDEX idx_staff_branch (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students Table
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    license_number VARCHAR(50) NULL,
    license_status ENUM('learner', 'probationary', 'full', 'overseas', 'none') DEFAULT 'none',
    date_of_birth DATE NOT NULL,
    address VARCHAR(255) NOT NULL,
    suburb VARCHAR(100) NOT NULL,
    postcode VARCHAR(10) NOT NULL,
    emergency_contact_name VARCHAR(100) NOT NULL,
    emergency_contact_phone VARCHAR(20) NOT NULL,
    medical_conditions TEXT NULL,
    enrollment_date DATE NOT NULL,
    branch_id INT NOT NULL,
    assigned_instructor_id INT NULL,
    total_lessons_completed INT DEFAULT 0,
    test_ready TINYINT(1) DEFAULT 0,
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id),
    FOREIGN KEY (assigned_instructor_id) REFERENCES instructors(instructor_id),
    INDEX idx_student_user (user_id),
    INDEX idx_student_instructor (assigned_instructor_id),
    INDEX idx_student_branch (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehicles Table
CREATE TABLE vehicles (
    vehicle_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    registration_number VARCHAR(20) UNIQUE NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    transmission ENUM('manual', 'automatic') NOT NULL,
    color VARCHAR(30) NOT NULL,
    registration_expiry DATE NOT NULL,
    last_service_date DATE NOT NULL,
    next_service_due DATE NOT NULL,
    insurance_expiry DATE NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    notes TEXT NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id),
    INDEX idx_vehicle_branch (branch_id),
    INDEX idx_vehicle_available (is_available),
    INDEX idx_registration (registration_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lessons Table
CREATE TABLE lessons (
    lesson_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    instructor_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    course_id INT NOT NULL,
    lesson_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    dropoff_location VARCHAR(255) NULL,
    lesson_type ENUM('theory', 'practical', 'test_preparation') DEFAULT 'practical',
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    instructor_notes TEXT NULL,
    student_performance_rating TINYINT(1) NULL COMMENT '1-5 rating',
    skills_practiced TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(instructor_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    INDEX idx_lesson_date (lesson_date),
    INDEX idx_lesson_student (student_id),
    INDEX idx_lesson_instructor (instructor_id),
    INDEX idx_lesson_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices Table
CREATE TABLE invoices (
    invoice_id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(20) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL,
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
    balance_due DECIMAL(10, 2) NOT NULL,
    status ENUM('unpaid', 'partially_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'unpaid',
    notes TEXT NULL,
    last_reminder_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    INDEX idx_invoice_student (student_id),
    INDEX idx_invoice_status (status),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_invoice_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'paypal') NOT NULL,
    transaction_reference VARCHAR(100) NULL,
    notes TEXT NULL,
    processed_by INT NOT NULL COMMENT 'User ID of staff/admin who processed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id),
    INDEX idx_payment_invoice (invoice_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notes Table
CREATE TABLE notes (
    note_id INT PRIMARY KEY AUTO_INCREMENT,
    related_to_type ENUM('student', 'instructor', 'staff') NOT NULL,
    related_to_id INT NOT NULL,
    created_by INT NOT NULL,
    note_title VARCHAR(200) NOT NULL,
    note_content TEXT NOT NULL,
    is_important TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_note_related (related_to_type, related_to_id),
    INDEX idx_note_created (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attachments Table
CREATE TABLE attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    related_to_type ENUM('student', 'instructor', 'staff', 'note') NOT NULL,
    related_to_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    INDEX idx_attachment_related (related_to_type, related_to_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type ENUM('lesson_reminder', 'payment_due', 'system', 'message') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_notification_user (user_id),
    INDEX idx_notification_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Communications Table
CREATE TABLE communications (
    communication_id INT PRIMARY KEY AUTO_INCREMENT,
    sent_by INT NOT NULL,
    recipient_type ENUM('individual', 'all_students', 'all_instructors', 'all_staff', 'all_users') NOT NULL,
    recipient_id INT NULL COMMENT 'NULL if sent to group',
    method ENUM('email', 'sms', 'both') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    recipient_count INT DEFAULT 0,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sent_by) REFERENCES users(user_id),
    INDEX idx_comm_sent_by (sent_by),
    INDEX idx_comm_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type VARCHAR(50) NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATA INSERTION
-- =====================================================

-- Insert Branches
INSERT INTO branches (branch_name, address, suburb, state, postcode, phone, email) VALUES
('Origin CBD', '123 Collins Street', 'Melbourne CBD', 'Victoria', '3000', '03-9123-4567', 'cbd@origindrivingschool.com.au'),
('Origin Bayside', '45 Beach Road', 'St Kilda', 'Victoria', '3182', '03-9555-1234', 'bayside@origindrivingschool.com.au'),
('Origin Eastern', '78 Maroondah Highway', 'Ringwood', 'Victoria', '3134', '03-9870-5678', 'eastern@origindrivingschool.com.au');

-- Insert Users (Password for all: password)
-- Admin User
INSERT INTO users (email, password_hash, user_role, first_name, last_name, phone) VALUES
('admin@origindrivingschool.com.au', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System', 'Administrator', '03-9000-0001');

-- Staff Users
INSERT INTO users (email, password_hash, user_role, first_name, last_name, phone) VALUES
('sarah.johnson@origindrivingschool.com.au', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Sarah', 'Johnson', '03-9000-0002'),
('michael.chen@origindrivingschool.com.au', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Michael', 'Chen', '03-9000-0003'),
('jessica.brown@origindrivingschool.com.au', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Jessica', 'Brown', '03-9000-0004');

-- Instructor Users
INSERT INTO users (email, password_hash, user_role, first_name, last_name, phone) VALUES
('david.smith@origindrivingschool.com.au', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 'David', 'Smith', '0412-345-678'),
('emma.wilson@origindrivingschool.com.au', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 'Emma', 'Wilson', '0423-456-789'),
('james.brown@origindrivingschool.com.au', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 'James', 'Brown', '0434-567-890'),
('michael.johnson@origin-driving.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 'Michael', 'Johnson', '0412345678'),
('sarah.williams@origin-driving.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 'Sarah', 'Williams', '0423456789'),
('david.brown@origin-driving.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 'David', 'Brown', '0434567890'),
('emma.davis@origin-driving.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 'Emma', 'Davis', '0445678901');

-- Student Users
INSERT INTO users (email, password_hash, user_role, first_name, last_name, phone) VALUES
('olivia.taylor@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Olivia', 'Taylor', '0456-123-789'),
('liam.anderson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Liam', 'Anderson', '0467-234-890'),
('sophia.martinez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Sophia', 'Martinez', '0478-345-901'),
('noah.wilson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Noah', 'Wilson', '0489-456-123'),
('isabella.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Isabella', 'Garcia', '0490-567-234');

-- Insert Staff Records
INSERT INTO staff (user_id, branch_id, position, hire_date, salary) VALUES
(2, 1, 'Branch Manager', '2020-01-15', 65000.00),
(3, 2, 'Operations Coordinator', '2021-03-20', 55000.00),
(4, 3, 'Customer Service Manager', '2021-06-10', 58000.00);

-- Insert Courses
INSERT INTO courses (course_name, course_code, description, number_of_lessons, lesson_duration, price, course_type) VALUES
('Beginner Package', 'BEG-10', 'Complete beginner course covering all basics of driving', 10, 60, 750.00, 'beginner'),
('Intermediate Package', 'INT-15', 'Intermediate course for learners with some experience', 15, 60, 1050.00, 'intermediate'),
('Test Preparation Package', 'TEST-5', 'Intensive preparation for VicRoads driving test', 5, 90, 500.00, 'test_preparation'),
('Advanced Skills', 'ADV-8', 'Advanced driving techniques and defensive driving', 8, 60, 640.00, 'advanced'),
('Refresher Course', 'REF-5', 'Refresher for licensed drivers returning to driving', 5, 60, 375.00, 'refresher');

-- Insert Instructors
INSERT INTO instructors (user_id, branch_id, certificate_number, adta_membership, wwc_card_number, license_expiry, certification_expiry, police_check_date, medical_check_date, date_joined, hourly_rate, specialization, bio) VALUES
(5, 1, 'CERT-IV-2019-001', 'ADTA-VIC-12345', NULL, NULL, NULL, '2024-01-15', '2024-01-20', '2019-06-01', 75.00, 'Manual Transmission', 'Experienced instructor with 6 years teaching manual and automatic vehicles.'),
(6, 2, 'CERT-IV-2020-002', 'ADTA-VIC-12346', NULL, NULL, NULL, '2024-02-10', '2024-02-15', '2020-03-15', 75.00, 'Test Preparation', 'Specialist in VicRoads test preparation with 98% pass rate.'),
(7, 3, 'CERT-IV-2021-003', 'ADTA-VIC-12347', NULL, NULL, NULL, '2024-01-25', '2024-01-30', '2021-08-01', 70.00, 'Nervous Learners', 'Patient instructor specializing in helping anxious learners build confidence.'),
(8, 1, 'INS-2024-001', NULL, NULL, '2026-12-31', '2027-06-30', NULL, NULL, '2023-01-15', 55.00, 'Highway Driving, Defensive Driving', 'Experienced instructor specializing in highway and defensive driving techniques.'),
(9, 1, 'INS-2024-002', NULL, NULL, '2027-06-30', '2028-01-15', NULL, NULL, '2023-03-20', 50.00, 'Parking, City Driving', 'Patient instructor with expertise in parking and city navigation.'),
(10, 1, 'INS-2024-003', NULL, NULL, '2026-08-15', '2027-12-20', NULL, NULL, '2022-06-10', 60.00, 'Manual Transmission, Advanced Driving', 'Senior instructor with a decade of experience in all driving conditions.'),
(11, 1, 'INS-2024-004', NULL, NULL, '2027-03-20', '2028-05-10', NULL, NULL, '2023-05-05', 52.00, 'Beginner Training, Theory Classes', 'Friendly instructor perfect for nervous beginners and theory preparation.');

-- Insert Students
INSERT INTO students (user_id, license_number, license_status, date_of_birth, address, suburb, postcode, emergency_contact_name, emergency_contact_phone, enrollment_date, branch_id, assigned_instructor_id) VALUES
(12, 'L1234567', 'learner', '2006-05-15', '12 Oak Street', 'Melbourne', '3000', 'Robert Taylor', '0412-987-654', '2024-01-10', 1, 1),
(13, 'L2345678', 'learner', '2005-08-22', '34 Elm Avenue', 'St Kilda', '3182', 'Jennifer Anderson', '0423-876-543', '2024-02-05', 2, 2),
(14, NULL, 'none', '2006-11-30', '56 Pine Road', 'Ringwood', '3134', 'Carlos Martinez', '0434-765-432', '2024-03-01', 3, 3),
(15, 'L3456789', 'learner', '2006-03-18', '23 Queen Street', 'Melbourne CBD', '3000', 'Emily Wilson', '0456-789-012', '2024-04-10', 1, 1),
(16, 'L4567890', 'learner', '2006-07-25', '67 High Street', 'St Kilda', '3182', 'Miguel Garcia', '0467-890-123', '2024-04-15', 2, 2);

-- Insert Vehicles
INSERT INTO vehicles (branch_id, registration_number, make, model, year, transmission, color, registration_expiry, last_service_date, next_service_due, insurance_expiry) VALUES
(1, 'ABC123', 'Toyota', 'Corolla', 2022, 'automatic', 'White', '2025-06-30', '2024-08-15', '2025-02-15', '2025-12-31'),
(1, 'DEF456', 'Mazda', '3', 2023, 'manual', 'Silver', '2025-09-30', '2024-09-20', '2025-03-20', '2025-12-31'),
(2, 'GHI789', 'Honda', 'Civic', 2022, 'automatic', 'Blue', '2025-07-31', '2024-08-25', '2025-02-25', '2025-12-31'),
(3, 'JKL012', 'Toyota', 'Yaris', 2023, 'automatic', 'Red', '2025-08-31', '2024-09-10', '2025-03-10', '2025-12-31');

-- Insert Lessons
INSERT INTO lessons (student_id, instructor_id, vehicle_id, course_id, lesson_date, start_time, end_time, pickup_location, dropoff_location, lesson_type, status) VALUES
(1, 1, 1, 1, '2024-09-15', '10:00:00', '11:00:00', '12 Oak Street, Melbourne', '12 Oak Street, Melbourne', 'practical', 'completed'),
(1, 1, 1, 1, '2024-09-22', '10:00:00', '11:00:00', '12 Oak Street, Melbourne', '12 Oak Street, Melbourne', 'practical', 'completed'),
(2, 2, 3, 3, '2024-09-18', '14:00:00', '15:30:00', '34 Elm Avenue, St Kilda', '34 Elm Avenue, St Kilda', 'test_preparation', 'completed'),
(3, 3, 4, 1, '2024-10-05', '09:00:00', '10:00:00', '56 Pine Road, Ringwood', '56 Pine Road, Ringwood', 'practical', 'scheduled'),
(1, 1, 1, 1, '2025-10-20', '10:00:00', '11:00:00', '12 Oak Street, Melbourne', '12 Oak Street, Melbourne', 'practical', 'scheduled');

-- Insert Invoices
INSERT INTO invoices (invoice_number, student_id, course_id, issue_date, due_date, subtotal, tax_amount, total_amount, amount_paid, balance_due, status) VALUES
('INV-2024-001', 1, 1, '2024-01-10', '2024-01-24', 750.00, 75.00, 825.00, 825.00, 0.00, 'paid'),
('INV-2024-002', 2, 3, '2024-02-05', '2024-02-19', 500.00, 50.00, 550.00, 0.00, 550.00, 'overdue'),
('INV-2024-003', 3, 1, '2024-03-01', '2024-03-15', 750.00, 75.00, 825.00, 400.00, 425.00, 'partially_paid');

-- Insert Payments
INSERT INTO payments (invoice_id, payment_date, amount, payment_method, transaction_reference, processed_by) VALUES
(1, '2024-01-15', 825.00, 'credit_card', 'TXN-CC-20240115-001', 1),
(3, '2024-03-05', 400.00, 'bank_transfer', 'TXN-BT-20240305-001', 2);

-- Insert System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Origin Driving School', 'text', 'Website name'),
('site_email', 'info@origindrivingschool.com.au', 'email', 'Main contact email'),
('site_phone', '1300-ORIGIN', 'text', 'Main contact phone'),
('tax_rate', '10', 'number', 'GST Tax Rate Percentage'),
('lesson_cancellation_hours', '24', 'number', 'Hours before lesson to allow cancellation'),
('currency_symbol', '$', 'text', 'Currency symbol'),
('date_format', 'd/m/Y', 'text', 'Date display format'),
('smtp_host', '', 'text', 'SMTP server host'),
('smtp_port', '587', 'number', 'SMTP server port'),
('smtp_username', '', 'text', 'SMTP username'),
('smtp_password', '', 'password', 'SMTP password'),
('smtp_encryption', 'tls', 'text', 'SMTP encryption type');

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- View: Student Details
CREATE VIEW vw_student_details AS
SELECT 
    s.student_id,
    u.user_id,
    u.email,
    u.first_name,
    u.last_name,
    u.phone,
    s.license_number,
    s.license_status,
    s.date_of_birth,
    s.address,
    s.suburb,
    s.postcode,
    s.enrollment_date,
    b.branch_name,
    CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
    s.total_lessons_completed,
    s.test_ready,
    u.is_active
FROM students s
INNER JOIN users u ON s.user_id = u.user_id
INNER JOIN branches b ON s.branch_id = b.branch_id
LEFT JOIN instructors i ON s.assigned_instructor_id = i.instructor_id
LEFT JOIN users iu ON i.user_id = iu.user_id;

-- View: Lesson Details
CREATE VIEW vw_lesson_details AS
SELECT 
    l.lesson_id,
    l.student_id,
    l.instructor_id,
    l.vehicle_id,
    l.course_id,
    l.lesson_date,
    l.start_time,
    l.end_time,
    l.pickup_location,
    l.dropoff_location,
    l.lesson_type,
    l.status,
    l.instructor_notes,
    l.student_performance_rating,
    l.skills_practiced,
    l.created_at,
    l.updated_at,
    CONCAT(su.first_name, ' ', su.last_name) AS student_name,
    su.email AS student_email,
    su.phone AS student_phone,
    CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
    iu.email AS instructor_email,
    iu.phone AS instructor_phone,
    v.registration_number,
    v.make,
    v.model,
    c.course_name,
    b.branch_name
FROM lessons l
INNER JOIN students s ON l.student_id = s.student_id
INNER JOIN users su ON s.user_id = su.user_id
INNER JOIN instructors i ON l.instructor_id = i.instructor_id
INNER JOIN users iu ON i.user_id = iu.user_id
LEFT JOIN vehicles v ON l.vehicle_id = v.vehicle_id
LEFT JOIN courses c ON l.course_id = c.course_id
INNER JOIN branches b ON s.branch_id = b.branch_id;

-- View: Instructor Schedule
CREATE VIEW vw_instructor_schedule AS
SELECT 
    l.lesson_id,
    l.lesson_date,
    l.start_time,
    l.end_time,
    CONCAT(iu.first_name, ' ', iu.last_name) AS instructor_name,
    CONCAT(su.first_name, ' ', su.last_name) AS student_name,
    l.pickup_location,
    v.registration_number AS vehicle,
    l.status
FROM lessons l
INNER JOIN instructors i ON l.instructor_id = i.instructor_id
INNER JOIN users iu ON i.user_id = iu.user_id
INNER JOIN students s ON l.student_id = s.student_id
INNER JOIN users su ON s.user_id = su.user_id
INNER JOIN vehicles v ON l.vehicle_id = v.vehicle_id;

-- View: Invoice Summary
CREATE VIEW vw_invoice_summary AS
SELECT 
    i.invoice_id,
    i.invoice_number,
    CONCAT(u.first_name, ' ', u.last_name) AS student_name,
    c.course_name,
    i.issue_date,
    i.due_date,
    i.total_amount,
    i.amount_paid,
    i.balance_due,
    i.status
FROM invoices i
INNER JOIN students s ON i.student_id = s.student_id
INNER JOIN users u ON s.user_id = u.user_id
INNER JOIN courses c ON i.course_id = c.course_id;

-- View: Invoice Details
CREATE VIEW vw_invoice_details AS
SELECT 
    i.invoice_id,
    i.invoice_number,
    i.student_id,
    i.course_id,
    i.issue_date,
    i.due_date,
    i.subtotal,
    i.tax_amount,
    i.total_amount,
    i.amount_paid,
    i.balance_due,
    i.status,
    i.notes,
    i.created_at,
    i.updated_at,
    CONCAT(u.first_name, ' ', u.last_name) AS student_name,
    u.email AS student_email,
    u.phone AS student_phone,
    c.course_name,
    c.course_code,
    b.branch_name
FROM invoices i
INNER JOIN students s ON i.student_id = s.student_id
INNER JOIN users u ON s.user_id = u.user_id
INNER JOIN courses c ON i.course_id = c.course_id
INNER JOIN branches b ON s.branch_id = b.branch_id;

-- View: Instructor Performance
CREATE VIEW vw_instructor_performance AS
SELECT 
    i.instructor_id,
    CONCAT(u.first_name, ' ', u.last_name) AS instructor_name,
    b.branch_name,
    COUNT(DISTINCT l.lesson_id) AS total_lessons,
    COUNT(DISTINCT CASE WHEN l.status = 'completed' THEN l.lesson_id END) AS completed_lessons,
    COUNT(DISTINCT CASE WHEN l.status = 'cancelled' THEN l.lesson_id END) AS cancelled_lessons,
    COUNT(DISTINCT s.student_id) AS total_students,
    AVG(l.student_performance_rating) AS avg_rating
FROM instructors i
INNER JOIN users u ON i.user_id = u.user_id
INNER JOIN branches b ON i.branch_id = b.branch_id
LEFT JOIN lessons l ON i.instructor_id = l.instructor_id
LEFT JOIN students s ON i.instructor_id = s.assigned_instructor_id
GROUP BY i.instructor_id, CONCAT(u.first_name, ' ', u.last_name), b.branch_name;

-- =====================================================
-- FINALIZE
-- =====================================================

COMMIT;

