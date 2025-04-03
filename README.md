# 🏫 Custom School Parent Survey System
![DeepScan grade](https://deepscan.io/api/teams/26555/projects/29184/branches/938393/badge/grade.svg)
!(https://deepscan.io/dashboard#view=project&tid=26555&pid=29184&bid=938393)
![GitHub stars](https://img.shields.io/github/stars/addex12/School-CRM.png)
![GitHub last commit](https://img.shields.io/github/last-commit/addex12/School-CRM.png)
![GitHub contributors](https://img.shields.io/github/contributors/addex12/School-CRM.png)
![GitHub issues](https://img.shields.io/github/issues/addex12/School-CRM.png)
![GitHub releases](https://img.shields.io/github/releases/addex12/School-CRM.png)
![GitHub license](https://img.shields.io/github/license/addex12/School-CRM.png)
![GitHub size](https://img.shields.io/github/size/addex12/School-CRM.png)
![GitHub forks](https://img.shields.io/github/forks/addex12/School-CRM.png)
![GitHub watchers](https://img.shields.io/github/watchers/addex12/School-CRM.png)
![GitHub open issues](https://img.shields.io/github/issues-pr/addex12/School-CRM.png)
![GitHub closed issues](https://img.shields.io/github/issues-closed/addex12/School-CRM.png)
![GitHub open PRs](https://img.shields.io/github/issues-pr-closed/addex12/School-CRM.png)
   
A web-based system for schools to create, manage, and analyze parent surveys with admin dashboards, automated exports (PDF/Excel), and email integration.

---

## ✨ Features
- **Admin Dashboard**: Manage surveys, categories, users, and results.
- **Survey Builder**: Drag-and-drop form builder for custom surveys.
- **Role-Based Access**: Admins, teachers, parents, and students.
- **Analytics**: Visual charts for survey responses.
- **Export Tools**: Generate PDF/Excel/CSV reports.
- **Email Integration**: Send test emails and notifications.

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


2. **Database Setup**: 
   - Create a MySQL database.
   - Update `config.php` with database credentials.
   - Run `db.sql` to create the database schema.
   

3. **Configure PHP**:
   - Ensure `php.ini` has the following extensions enabled:
     ```ini
     extension=mbstring
     extension=gd
     extension=zip
     ```

4. **Manual Dependencies**:
   - Place `phpmailer/`, `mpdf/`, and `phpoffice/` folders in `vendor/` (already included).

5. **Web Server**:
   - Point your server to the project root (e.g., `htdocs/survey/`).

---

## 🚀 Usage

### Admin Access

- **Login**: Visit `/login.php` → Use admin credentials.
- **Dashboard**:
  - Create surveys with drag-and-drop fields.
  - Assign surveys to roles (parents/teachers/students).
  - View real-time response charts.
  - Export results to PDF/Excel/CSV.

### Parent/User Access

- **Survey Link**: Share `/user/survey.php?id=SURVEY_ID`.
- **Submit Responses**: Fill out assigned surveys.
- **Completion Tracking**: View completed surveys in `/user/dashboard.php`.

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
survey/
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

MIT License. See LICENSE for details.

---

## 🙏 Acknowledgments

- Icons by Font Awesome
- Charts by Chart.js

---

## 📞 Contact

- Email: gizawadugna@gmail.com
- LinkedIn: https://www.linkedin.com/in/eleganceict

## Developer

<div class="badge-base LI-profile-badge" data-locale="en_US" data-size="medium" data-theme="dark" data-type="VERTICAL" data-vanity="eleganceict" data-version="v1">
    <a class="badge-base__link LI-simple-link" href="https://et.linkedin.com/in/eleganceict?trk=profile-badge">Adugna Gizaw</a>
</div>



