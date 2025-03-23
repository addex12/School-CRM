# School CRM System

## Overview
The School CRM System is a comprehensive web application built using Laravel, PHP, and JavaScript. It is designed to facilitate the management of students, teachers, and parents within a school environment. The application provides an administrator dashboard for managing users and system settings, as well as user dashboards for students, teachers, and parents to interact with surveys, chat, and provide feedback.

## Key Features

### Administrator Dashboard
- **Manage Users**: Perform CRUD operations for Students, Teachers, and Parents, including user role management.
- **Manage Communication Setup**: Configure email settings for notifications and integrate SMS gateways if desired.
- **Manage System Settings**: Manage general school information and academic year settings.
- **Manage Surveys for Parents**: Create, edit, publish surveys, and view survey results.

### User Dashboards
- **Login and Registration**: Secure user authentication with registration forms.
- **Show Active Surveys**: Display available surveys based on user roles and allow submission of responses.
- **Chat**: Real-time chat functionality using Laravel Echo with WebSockets.
- **Feedback/Questions**: Forms for submitting feedback or questions with the ability to view responses.

## Technology Stack
- **Backend**: Laravel (PHP framework), MySQL (database).
- **Frontend**: HTML, CSS, JavaScript, Blade templates, optional frameworks like Vue.js or React.js.
- **Real-time Communication**: Laravel Echo with WebSockets or Pusher for chat functionality.

## Installation
1. Clone the repository:
   ```
   git clone <repository-url>
   ```
2. Navigate to the project directory:
   ```
   cd school-crm
   ```
3. Install dependencies:
   ```
   composer install
   npm install
   ```
4. Set up the environment file:
   ```
   cp .env.example .env
   ```
5. Generate the application key:
   ```
   php artisan key:generate
   ```
6. Run migrations and seed the database:
   ```
   php artisan migrate --seed
   ```
7. Start the development server:
   ```
   php artisan serve
   ```

## Usage
- Access the application at `http://localhost:8000`.
- Use the administrator dashboard to manage users and settings.
- Users can log in to their respective dashboards to participate in surveys and communicate via chat.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for details.