# 🏫 School CRM System

![DeepScan grade](https://deepscan.io/api/teams/26555/projects/29184/branches/938393/badge/grade.svg)
![GitHub stars](https://img.shields.io/github/stars/addex12/School-CRM.png)
![GitHub last commit](https://img.shields.io/github/last-commit/addex12/School-CRM.png)
![GitHub contributors](https://img.shields.io/github/contributors/addex12/School-CRM.png)
![GitHub issues](https://img.shields.io/github/issues/addex12/School-CRM.png)
![GitHub releases](https://img.shields.io/github/releases/addex12/School-CRM.png)
![GitHub license](https://img.shields.io/github/license/addex12/School-CRM.png)

A web-based system designed for schools to manage surveys, users, and analytics efficiently. This CRM system includes admin dashboards, automated exports, email integration, and more.

---

## ✨ Features

- **Admin Dashboard**: Manage surveys, users, categories, and results.
- **Survey Builder**: Drag-and-drop interface for creating custom surveys.
- **Role-Based Access**: Separate roles for admins, teachers, parents, and students.
- **Analytics**: Visual charts for survey responses.
- **Export Tools**: Generate reports in PDF, Excel, or CSV formats.
- **Email Integration**: Send notifications and test emails.
- **Audit Logs**: Track system activities and changes.

---

## 🛠️ Installation

### Requirements

- PHP 7.4+ (`mbstring`, `gd`, `zip`, `dom` extensions)
- MySQL 5.7+
- Web server (Apache/Nginx)

### Steps

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/addex12/School-CRM.git
   cd School-CRM
   ```

2. **Database Setup**:
   - Create a MySQL database.
   - Update `config.php` with database credentials.
   - Import the `db.sql` file to set up the database schema.

3. **Configure PHP**:
   - Ensure the following extensions are enabled in `php.ini`:
     ```ini
     extension=mbstring
     extension=gd
     extension=zip
     ```

4. **Dependencies**:
   - Place `PHPMailer/`, `mPDF/`, and `PhpSpreadsheet/` in the `vendor/` directory (already included).

5. **Web Server Configuration**:
   - Point your server to the project root (e.g., `/opt/lampp/htdocs/School-CRM`).

---

## 🚀 Usage

### Admin Access

- **Login**: Visit `/login.php` and use admin credentials.
- **Dashboard**:
  - Create surveys with drag-and-drop fields.
  - Assign surveys to specific roles (parents, teachers, students).
  - View real-time response analytics.
  - Export results to PDF, Excel, or CSV.

### User Access

- **Survey Links**: Share `/user/survey.php?id=SURVEY_ID`.
- **Submit Responses**: Users can fill out assigned surveys.
- **Track Progress**: View completed surveys in `/user/dashboard.php`.

---

## 📸 Screenshots

| Admin Dashboard | Survey Builder | Results Export |
|-----------------|----------------|----------------|
| ![Admin Dashboard](screenshots/admin_dashboard.png) | ![Survey Builder](screenshots/survey_builder.png) | ![Results Export](screenshots/results_export.png) |

---

## 🧰 Technologies Used

- **Backend**: PHP, MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Chart.js, Sortable.js)
- **Libraries**:
  - PHPMailer (Emails)
  - mPDF (PDF exports)
  - PhpSpreadsheet (Excel exports)

---

## 📂 Directory Structure

```
School-CRM/
├── admin/            # Admin panels
├── assets/           # CSS/JS/Images
├── includes/         # Config, DB, auth
├── user/             # Parent/student views
├── vendor/           # Manual dependencies
├── index.php         # Landing page
└── README.md         # This file
```

---

## 📜 License

This project is licensed under the Apache License 2.0. See the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Icons by Font Awesome
- Charts by Chart.js

---

## 📞 Contact

- Email: gizawadugna@gmail.com
- LinkedIn: [Adugna Gizaw](https://www.linkedin.com/in/eleganceict)

---



