# 🏫 School CRM System

[![DeepScan grade](https://deepscan.io/api/teams/26555/projects/29184/branches/938393/badge/grade.svg)](https://deepscan.io/dashboard#view=project&pid=29184&bid=938393)
[![GitHub stars](https://img.shields.io/github/stars/addex12/School-CRM?style=social)](https://github.com/addex12/School-CRM/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/addex12/School-CRM?style=social)](https://github.com/addex12/School-CRM/network/members)
[![GitHub last commit](https://img.shields.io/github/last-commit/addex12/School-CRM?color=blue)](https://github.com/addex12/School-CRM/commits/main)
[![GitHub contributors](https://img.shields.io/github/contributors/addex12/School-CRM)](https://github.com/addex12/School-CRM/graphs/contributors)
[![GitHub issues](https://img.shields.io/github/issues/addex12/School-CRM)](https://github.com/addex12/School-CRM/issues)
[![GitHub pull requests](https://img.shields.io/github/issues-pr/addex12/School-CRM)](https://github.com/addex12/School-CRM/pulls)
[![GitHub releases](https://img.shields.io/github/v/release/addex12/School-CRM?include_prereleases)](https://github.com/addex12/School-CRM/releases)
[![GitHub license](LICENSE)
[![Apache 2.0](https://img.shields.io/badge/license-Apache%202.0-blue.svg)](LICENSE)
[![Website](https://img.shields.io/badge/website-flipperschool.com-blue)](https://crm.flipperschool.com)
[![Demo](https://img.shields.io/badge/demo-live-green)](https://crm.flipperschool.com)
[![Twitter Follow](https://img.shields.io/twitter/follow/eleganceict1?style=social)](https://twitter.com/eleganceict1)

---

## Overview

**School CRM** is a modern, full-featured, open-source School Management and CRM system built with PHP, MySQL, and JavaScript. It is designed for schools, colleges, and educational institutions to manage users, surveys, tickets, feedback, announcements, knowledge base, and more, with a beautiful, responsive, and interactive UI.

---

## 🚀 Features

### User Management
- Role-based access control (Admin, Teacher, Student, Parent, etc.)
- Add, edit, delete, and bulk import users (CSV)
- Active/online user tracking and management
- User profile management and password reset
- Bulk actions: status, role, export, delete

### Survey Management
- Create, edit, and manage surveys with categories and roles
- Assign surveys to specific roles or make public
- Survey builder with drag-and-drop (JS)
- Survey statistics, participation charts, and export
- User dashboard for available, completed, and pending surveys
- Survey responses view and export

### Ticketing & Support
- Support ticket system for users (students, parents, teachers)
- Admin ticket management: assign, resolve, close, delete
- Ticket responses and status tracking
- Knowledge base for self-service support

### Communication
- Internal messaging/chat system
- Announcements (public or targeted to roles)
- Bulk email to user categories or imported emails (CSV)
- Feedback submission and management

### Dashboard & Analytics
- ERPNext-inspired, responsive dashboards for users and admins
- Real-time stats: students, teachers, parents, users, tickets, surveys
- Recent activity logs, feedback, and tickets
- Interactive charts (Chart.js) for survey participation, feedback ratings, ticket status

### Classes & Curriculum
- Manage classes, sections, curriculums, and class levels
- Assign students and teachers to classes/sections

### Audit & Logs
- Audit trail and system logs for accountability
- Activity logs, error logs, and log clearing (manual/scheduled)
- Downloadable logs and backup

### Backup & Restore
- Full system backup (database + files) and restore
- Downloadable backup archives

### Security & Best Practices
- CSRF protection, input validation, and prepared statements
- Session management and authentication
- Apache 2.0 licensed

### Developer & Extensibility
- Modular codebase (MVC-inspired)
- RESTful backend API (Node.js/Express for AI integration)
- Easy to extend with new modules and integrations

---

## 📦 Folder Structure

```
School-CRM/
├── admin/                # Admin panel, dashboards, management modules
├── assets/               # CSS, JS, images, icons
├── backend/              # Node.js/Express backend for AI/API
├── includes/             # Shared PHP includes (auth, config, db, etc.)
├── migrations/           # SQL migration scripts
├── user/                 # User-facing dashboard and modules
├── backups/              # System backup archives
├── logs/                 # System and activity logs
├── config/               # JSON configs for dashboard, sidebar, etc.
├── LICENSE               # Apache 2.0 License
├── README.md             # This file
└── ...                   # Other PHP entry points and scripts
```

---

## 🛠️ Tech Stack

- PHP 7.4+ (Backend)
- MySQL/MariaDB (Database)
- JavaScript (Frontend, AJAX, Chart.js)
- Node.js/Express (AI/REST API integration)
- HTML5, CSS3 (Responsive, adugna-ERPNext-inspired UI)
- FontAwesome, Bootstrap Icons

---

## 📋 Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/addex12/School-CRM.git
   ```
2. **Configure your database:**
   - Import `db.sql` into MySQL/MariaDB.
   - Update `/includes/config.php` with your DB credentials.

3. **Set up Apache/Nginx:**
   - Point your web root to `/School-CRM/`.
   - Ensure `mod_rewrite` is enabled for `.htaccess`.

4. **Set file permissions:**
   - `chmod -R 755 backups logs uploads`

5. **(Optional) Configure Node.js backend:**
   - `cd backend && npm install && node server.js`

6. **Access the system:**
   - Visit `http://localhost/School-CRM/` in your browser.

---

## 👤 Developer

- **Adugna Gizaw**
  - [LinkedIn](https://www.linkedin.com/in/eleganceict)
  - [Twitter](https://twitter.com/eleganceict1)
  - [GitHub](https://github.com/addex12)
  - Email: gizawadugna@gmail.com

---

## 📄 License

This project is licensed under the [Apache License 2.0](LICENSE).

```
                                 Apache License
                           Version 2.0, January 2004
                        http://www.apache.org/licenses/
   TERMS AND CONDITIONS FOR USE, REPRODUCTION, AND DISTRIBUTION
   ... (full license text in LICENSE file) ...
```

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

---

## 🌐 Links

- [Live Demo](https://crm.flipperschool.com)
- [Documentation](https://github.com/addex12/School-CRM/wiki)
- [Report Issues](https://github.com/addex12/School-CRM/issues)
- [Releases](https://github.com/addex12/School-CRM/releases)
- [Contributors](https://github.com/addex12/School-CRM/graphs/contributors)

---

> © <?= date('Y') ?> Adugna Gizaw. School CRM System. All rights reserved.



