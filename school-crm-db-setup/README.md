# School-CRM Database Setup

This project is designed to set up the School-CRM database, which includes all necessary tables and initial data for testing and development.

## Project Structure

```
school-crm-db-setup
├── sql
│   ├── create_tables.sql      # SQL statements to create necessary tables
│   └── seed_data.sql          # SQL statements to insert initial data
├── src
│   ├── db_connection.php       # Establishes a connection to the MySQL database
│   └── setup.php               # Executes SQL scripts to set up the database
└── README.md                   # Documentation for the project
```

## Getting Started

### Prerequisites

- PHP installed on your machine
- MySQL server running
- Access to a MySQL database

### Setting Up the Database

1. **Clone the repository** or download the project files to your local machine.

2. **Configure the database connection**:
   - Open `src/db_connection.php` and update the `$servername`, `$username`, `$password`, and `$dbname` variables as needed.

3. **Create the database**:
   - Use your MySQL client to create a new database named `school_crm`.

4. **Run the SQL scripts**:
   - Execute the `sql/create_tables.sql` script to create the necessary tables.
   - Execute the `sql/seed_data.sql` script to insert initial data into the tables.

### Running the Setup Script

You can also run the `src/setup.php` script to automate the execution of the SQL scripts. This will create the tables and seed the database with initial data.

### Usage

After setting up the database, you can start developing your School-CRM application using the provided database structure and initial data.

### License

This project is open-source and available for modification and distribution.