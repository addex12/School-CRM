# 🏫 Custom School Parent Survey System

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
   ```
   git clone https://github.com/yourusername/school-survey-system.git
       Database Setup:

        Import parent_survey_system.sql:
    

        mysql -u root -p parent_survey_system < parent_survey_system.sql

        Update includes/db.php with your MySQL credentials.

    Configure PHP:

        Ensure php.ini has:
        ini
        

        extension=mbstring
        extension=gd
        extension=zip

    Manual Dependencies:

        Place phpmailer/, mpdf/, and phpoffice/ folders in vendor/ (already included).

    Web Server:

        Point your server to the project root (e.g., htdocs/survey/).

🚀 Usage
Admin Access

    Login: Visit /login.php → Use admin credentials.

    Dashboard:

        Create surveys with drag-and-drop fields.

        Assign surveys to roles (parents/teachers/students).

        View real-time response charts.

        Export results to PDF/Excel/CSV.

Parent/User Access

    Survey Link: Share /user/survey.php?id=SURVEY_ID.

    Submit Responses: Fill out assigned surveys.

    Completion Tracking: View completed surveys in /user/dashboard.php.

📸 Screenshots
Admin Dashboard	Survey Builder	Results Export
Admin Dashboard	Survey Builder	Export

🧰 Technologies Used

    Backend: PHP, MySQL

    Frontend: HTML5, CSS3, JavaScript (Chart.js, Sortable.js)

    Libraries:

        PHPMailer (Emails)

        mPDF (PDF exports)

        PhpSpreadsheet (Excel exports)

📂 Directory Structure

survey/
├── admin/            # Admin panels
├── assets/           # CSS/JS/Images
├── includes/         # Config, DB, auth
├── user/             # Parent/student views
├── vendor/           # Manual dependencies
├── index.php         # Landing page
└── README.md         # This file

📜 License

MIT License. See LICENSE for details.
🙏 Acknowledgments

    Icons by Font Awesome

    Charts by Chart.js


---

### How to Use This README:
1. Replace `yourusername` in the clone URL with your GitHub username.
2. Add actual screenshots to a `/screenshots` folder and update paths.
3. Customize the license if needed.

This README provides clear setup instructions, usage guidelines, and credits all dependencies. Let me know if you need adjustments! 🚀