# Parent Survey System

A comprehensive system for schools to collect feedback from parents, teachers, and students.

## Features

- **User Roles**: Admin, Teacher, Parent, Student
- **Survey Management**: Create, edit, and manage surveys
- **Response Collection**: Collect and analyze responses
- **Reporting**: Generate reports and export data
- **Communication**: Built-in chat system
- **Feedback**: Collect system feedback from users

## Installation

1. Clone the repository
2. Import the SQL file (`parent_survey_system.sql`) into your MySQL database
3. Configure database settings in `includes/db.php`
4. Set up your web server to point to the project directory

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)

## Configuration

Edit the following files for configuration:
- `includes/config.php` - Base configuration
- `includes/db.php` - Database connection
- `admin/settings.php` - System settings after installation

## Usage

1. Log in as admin (default credentials may need to be set up)
2. Create surveys and assign target audiences
3. Users will see available surveys in their dashboard
4. View results and reports in the admin panel

## License

This project is open-source under the MIT License.