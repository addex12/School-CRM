# 🛠️ Installation Guide

## Prerequisites

- PHP 7.4+ (with extensions: mysqli, mbstring, etc.)
- MySQL or MariaDB
- Apache/Nginx web server
- Node.js (optional, for backend AI/API)

## Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/addex12/School-CRM.git
   ```

2. **Database Setup:**
   - Import `db.sql` into your MySQL/MariaDB database.
   - Create a user and grant privileges.

3. **Configure Database Connection:**
   - Edit `/includes/config.php` with your DB credentials.

4. **Set File Permissions:**
   - Ensure `backups`, `logs`, and `uploads` are writable:
     ```bash
     chmod -R 755 backups logs uploads
     ```

5. **Web Server Setup:**
   - Point your web root to `/School-CRM/`.
   - Enable `mod_rewrite` for Apache.

6. **Node.js Backend (Optional):**
   - `cd backend && npm install && node server.js`

7. **Access the System:**
   - Visit `http://localhost/School-CRM/` in your browser.

---

For production deployment, see [Production Deployment](Production-Deployment).
