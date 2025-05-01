# 🌍 Production Deployment

## Hosting Requirements

- PHP 7.4+ (shared hosting, VPS, or cloud)
- MySQL/MariaDB
- Apache/Nginx

## Steps

1. **Upload Files:**
   - Upload all files from `School-CRM` to your server's web root.

2. **Create Database:**
   - Use your hosting control panel to create a DB and user.
   - Import `db.sql` via phpMyAdmin.

3. **Configure Database:**
   - Edit `/includes/config.php` with your credentials.

4. **Set Permissions:**
   - Ensure `backups`, `logs`, `uploads` are writable (`755` or `775`).

5. **Domain Setup:**
   - Point your domain/subdomain to the CRM folder.

6. **Access:**
   - Visit your domain to access School CRM.

---

For troubleshooting, see [Troubleshooting](Troubleshooting).
