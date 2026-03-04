# RISE – Institute & Student Management System

**RISE (Above The Ordinary)** is a modern web-based institute management system designed to help educational institutes manage students, academic programs, and administrative tasks efficiently.

The system provides a centralized platform for managing student registrations, programs, courses, subjects, marks entry, and certificate generation through a clean and responsive dashboard.

Built with **Core PHP, MySQL, Bootstrap 5, and modern UI practices**, RISE focuses on simplicity, performance, and usability for institutes and training centers.

---

# Overview

Managing student records and academic data manually can be complex and time-consuming. **RISE** simplifies this process by providing a structured digital system where administrators can manage academic operations efficiently.

The system allows administrators to control various modules such as student registration, course management, marks entry, ID card generation, and certificate generation from a single dashboard.

---

# Key Features

* Student Registration & Management
* Program, Course & Subject Management
* Role-Based Access (Super Admin & Admin)
* Marks Entry System
* Automatic Marksheet Generation
* Student ID Card Generation
* Certificate Generation
* Secure File Upload System
* Dashboard Analytics
* Responsive Modern UI

---

# Technology Stack

The project is built using modern web technologies:

* Core PHP
* MySQL Database
* Bootstrap 5
* JavaScript
* HTML5
* CSS3
* TCPDF (PDF generation)
* Chart.js (dashboard charts)

---

# System Modules

### Super Admin Panel

* Manage Admin accounts
* Manage Programs
* Manage Courses
* Manage Subjects
* Monitor system activity

### Institute Admin Panel

* Add and manage students
* Enter academic marks
* Generate student ID cards
* Generate marksheets
* Generate certificates

### Student Management

* Student registration
* Academic record management
* Document uploads
* Status tracking

### Academic Management

* Program setup
* Course setup
* Subject management
* Marks entry system

### Document Generation

* Student ID Cards
* Academic Marksheets
* Certificates

---

# Project Folder Structure

Example folder structure of the project:

```
RISE/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── includes/
│   ├── db.php
│   ├── config.php
│   ├── auth.php
│   └── functions.php
│
├── uploads/
│   ├── photos/
│   ├── signatures/
│   ├── documents/
│   ├── id_cards/
│   ├── marksheets/
│   └── certificates/
│
├── pages/
│   ├── dashboard.php
│   ├── students.php
│   ├── programs.php
│   ├── courses.php
│   ├── subjects.php
│   ├── marks_entry.php
│   └── wallet.php
│
├── pdf/
│   ├── generate_id_card.php
│   ├── generate_marksheet.php
│   └── generate_certificate.php
│
├── ajax/
│   ├── ajax_get_courses.php
│   ├── ajax_get_subjects.php
│   └── ajax_dashboard_stats.php
│
├── auth/
│   ├── login.php
│   └── logout.php
│
├── screenshots/
│   ├── landing-page.png
│   ├── superadmin-dashboard.png
│   ├── admin-dashboard.png
│   ├── student-management.png
│   ├── marks-entry.png
│   └── certificate.png
│
├── index.php
├── verify_student.php
└── README.md
```

---

# Screenshots

## Landing Page

![Landing Page](screenshots/landing-page.png)

---

## Super Admin Dashboard

![Super Admin Dashboard]  
<img width="1919" height="1077" alt="Screenshot 2026-03-04 130737" src="https://github.com/user-attachments/assets/b8937311-4e5d-4eeb-9677-f98056786b25" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 130800" src="https://github.com/user-attachments/assets/59c90ae2-0d91-49a3-8e9f-8d9ca5294e0e" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 130811" src="https://github.com/user-attachments/assets/17ea8d2f-447a-4c79-9bf8-5b0d82f8bfec" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 130818" src="https://github.com/user-attachments/assets/56aab0a7-ec3c-4669-b75d-4fea255e1066" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 130825" src="https://github.com/user-attachments/assets/73f1176f-2486-439f-ac65-241e7a27eedf" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 130832" src="https://github.com/user-attachments/assets/b27b28d6-75ac-42ef-90b6-08e57f3668e0" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 130840" src="https://github.com/user-attachments/assets/22c04f3d-2e67-49c8-8c8e-a5c111c8e41a" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 130848" src="https://github.com/user-attachments/assets/080354ef-7716-4dce-a595-3a04c407892a" />
<img width="1919" height="1078" alt="Screenshot 2026-03-04 130855" src="https://github.com/user-attachments/assets/f329aa57-b29c-48ae-b2dc-3474e8862627" />
<img width="1919" height="1077" alt="Screenshot 2026-03-04 130903" src="https://github.com/user-attachments/assets/fd98c0de-25fc-44b9-b0f2-9fd4afe540fb" />
<img width="1919" height="1076" alt="Screenshot 2026-03-04 130912" src="https://github.com/user-attachments/assets/cdcced48-c2c9-475f-ac7b-6f1c131874fa" />




Super Admin can manage:

* Admin accounts
* Programs
* Courses
* Subjects
* System analytics

---

## Institute Admin Dashboard

![Admin Dashboard]
 
<img width="1919" height="1079" alt="Screenshot 2026-03-04 131558" src="https://github.com/user-attachments/assets/90fa1f9c-c4f1-4909-bcc4-9f6c05445b1f" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 131544" src="https://github.com/user-attachments/assets/33b45264-2547-493b-837c-f3fd719f44bf" />
<img width="1918" height="1079" alt="Screenshot 2026-03-04 131537" src="https://github.com/user-attachments/assets/fac1d33c-28c0-470a-aea2-06e488218acf" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 131523" src="https://github.com/user-attachments/assets/1f768eaf-77bb-40dc-9e51-4055397fe71f" />
<img width="1918" height="1079" alt="Screenshot 2026-03-04 131516" src="https://github.com/user-attachments/assets/34829932-5610-479e-8cc4-de10cf7c2f72" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 131507" src="https://github.com/user-attachments/assets/0da70d0b-b025-4910-b371-8a2351bb1aff" />
<img width="1919" height="1079" alt="Screenshot 2026-03-04 131455" src="https://github.com/user-attachments/assets/72719d59-5f3d-45c2-b90d-5ba19aefd723" />
<img width="1918" height="1079" alt="Screenshot 2026-03-04 130950" src="https://github.com/user-attachments/assets/0faae035-941a-4abf-94ff-e8379918b4f5" />



Institute Admin can manage:

* Student registration
* Student data
* Marks entry
* Document generation

---

## Student Management

![Student Management]
<img width="1918" height="1079" alt="Screenshot 2026-03-04 131537" src="https://github.com/user-attachments/assets/28d62f55-2c5b-415d-9268-2e2f74d6ba01" />


Admins can add and manage student records including academic details and documents.

---

## Marks Entry System

![Marks Entry]
<img width="1918" height="1079" alt="Screenshot 2026-03-04 131516" src="https://github.com/user-attachments/assets/9ec8c964-c00e-44cd-b23c-8e77ef5514b9" />



Marks can be entered subject-wise and the system automatically calculates grades and percentages.

---

## Certificate Generation

![Certificate]
<img width="1919" height="1079" alt="Screenshot 2026-03-04 131610" src="https://github.com/user-attachments/assets/6b1af797-9f53-436c-ae45-de7416a5af17" />



Certificates are automatically generated once students complete their program.

---

# Installation Guide

Follow these steps to run the project locally.

### 1. Clone the repository

```
git clone https://github.com/yourusername/rise.git
```

### 2. Move the project

Place the project inside your web server directory.

Example (XAMPP):

```
xampp/htdocs/rise
```

### 3. Create Database

Open **phpMyAdmin** and create a database:

```
rise_db
```

### 4. Import Database

Import the SQL file included in the repository.

### 5. Configure Database

Update database credentials inside:

```
includes/config.php
```

### 6. Run the Project

Open your browser and visit:

```
http://localhost/rise
```

---

# Future Improvements

Future updates may include:

* Online admission system
* Student login portal
* Attendance management
* Online exam system
* Advanced analytics dashboard
* Mobile application integration

---

# License

This project is available for **educational and development purposes**.

---

# Developer

Developed by **Najeeb Ahmad**

Web Developer | Software Developer
