<!-- Developer: Adugna Gizaw
Email: gizawadugna@gmail.com
Phone: +251925582067 -->

# School-CRM

School-CRM is a comprehensive Customer Relationship Management (CRM) system designed specifically for educational institutions. It helps manage student information, track academic progress, and facilitate communication between students, parents, and staff.

## Features

- Student Information Management
- Academic Progress Tracking
- Attendance Management
- Communication Tools
- Reporting and Analytics
- Staff (Users) and Role Management
- Lead Management System
- Attendance Management System
- Staff Work Management System
- Staff Daily Work Status Management
- Customer Management System
- Appointment Management System
- Call Follow-up History
- Files Management System
- Quote Management System
- Invoice Billing Management System
- Expense Management System
- Inventory Management System
- User Management System
- Customer Help Desk System
- Accounting Report
- GST Tax Management System
- Twilio WhatsApp Gateway Integration
- RestFul API & IP Security
- SMTP email integration

## Installation

1. Clone the repository:
    ```sh
    git clone https://github.com/addex12/School-CRM.git
    ```
2. Navigate to the project directory:
    ```sh
    cd School-CRM
    ```
3. Set up the database:
    ```sql
    CREATE DATABASE school_crm;
    ```
4. Import the database schema:
    ```sh
    mysql -u yourusername -p school_crm < database/schema.sql
    ```
5. Configure your web server to serve the project directory.
6. Run the installation script:
    ```sh
    php install.php
    ```

## Usage

1. Open your browser and navigate to your local server (e.g., `http://localhost/school-crm`).
2. Log in with your credentials.
3. Use the navigation menu to access different features:
    - **Students**: Manage student records.
    - **Teachers**: Manage teacher records.
    - **Courses**: Manage course information.
    - **Staff**: Manage staff and roles.
    - **Leads**: Manage leads.
    - **Attendance**: Track attendance.
    - **Work**: Manage staff work and daily status.
    - **Customers**: Manage customer information.
    - **Appointments**: Manage appointments.
    - **Files**: Manage files.
    - **Quotes**: Manage quotes.
    - **Invoices**: Manage invoices and billing.
    - **Expenses**: Manage expenses.
    - **Inventory**: Manage inventory.
    - **Help Desk**: Access customer help desk.
    - **Reports**: Generate accounting and GST tax reports.
    - **Settings**: Configure system settings, including Twilio WhatsApp Gateway and SMTP email integration.

## Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Make your changes.
4. Commit your changes (`git commit -m 'Add some feature'`).
5. Push to the branch (`git push origin feature-branch`).
6. Open a pull request.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contact

For any inquiries or support, please contact us at gizawadugna@gmail.com.
