# MySQL and phpMyAdmin Project

This project sets up a MySQL server along with phpMyAdmin for database management using Docker. Below are the instructions for setting up and using the project.

## Project Structure

```
mysql-phpmyadmin-project
├── docker-compose.yml       # Defines the services for MySQL and phpMyAdmin
├── .env                     # Contains environment variables for configuration
├── .gitignore               # Specifies files to be ignored by Git
├── README.md                # Documentation for the project
├── db
│   └── init
│       └── init.sql        # SQL commands to initialize the database
├── phpmyadmin
│   └── config.user.inc.php  # User-specific configuration for phpMyAdmin
└── scripts
    ├── backup.sh           # Script to backup the MySQL database
    └── restore.sh          # Script to restore the MySQL database
```

## Setup Instructions

1. **Clone the Repository**
   Clone this repository to your local machine using:
   ```
   git clone <repository-url>
   ```

2. **Navigate to the Project Directory**
   ```
   cd mysql-phpmyadmin-project
   ```

3. **Configure Environment Variables**
   Edit the `.env` file to set your database credentials and other configuration settings.

4. **Start the Services**
   Use Docker Compose to start the MySQL server and phpMyAdmin:
   ```
   docker-compose up -d
   ```

5. **Access phpMyAdmin**
   Open your web browser and go to `http://localhost:8080` to access phpMyAdmin. Use the credentials defined in your `.env` file to log in.

## Usage Guidelines

- **Database Initialization**
  The `db/init/init.sql` file contains SQL commands to create tables and insert initial data. You can modify this file as needed.

- **Backup and Restore**
  Use the `scripts/backup.sh` script to create a backup of your MySQL database. To restore from a backup, use the `scripts/restore.sh` script.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.