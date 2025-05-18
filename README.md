# Result Management System - Shanker Dev Campus

A comprehensive web-based Result Management System that allows students to view their semester results and enables administrators to manage academic records efficiently.

## Features

- **Student Portal**
  - View semester-wise results
  - Check subject-wise marks, grades, and GPA
  - Print results in a well-formatted layout
  - View important notices

- **Admin Dashboard**
  - Secure login system
  - Manage classes and subjects
  - Add/edit student information
  - Upload results (manual entry & CSV upload)
  - Post notices
  - View system statistics

## Technology Stack

- Frontend: HTML5, CSS3, Bootstrap 5.3
- Backend: Core PHP with PDO
- Database: MySQL
- Server: XAMPP

## Installation

1. **Prerequisites**
   - XAMPP (or similar PHP development environment)
   - PHP 7.4 or higher
   - MySQL 5.7 or higher

2. **Setup Steps**

   ```bash
   # Clone the repository to your htdocs folder
   git clone [repository-url] rms
   cd rms

   # Create the database
   # Open phpMyAdmin and import the database/schema.sql file

   # Configure database connection
   # Edit config/database.php with your database credentials if different from default
   ```

3. **Default Admin Credentials**
   - Username: admin
   - Password: admin123
   - **Important**: Change these credentials after first login!

4. **Access the System**
   - Student Portal: http://localhost/rms
   - Admin Panel: http://localhost/rms/admin

## Directory Structure

```
rms/
├── admin/              # Admin panel files
├── assets/            # Static resources (CSS, JS, images)
├── config/            # Configuration files
├── database/          # Database schema and migrations
├── includes/          # PHP helper classes
└── uploads/           # Uploaded files (if any)
```

## Security Features

- Password hashing using bcrypt
- PDO prepared statements for SQL injection prevention
- Session-based authentication
- Input sanitization
- XSS protection

## Grading System

| Grade | GPA Range | Percentage | Remarks      |
|-------|-----------|------------|--------------|
| A     | 4.00      | 90-100%    | Distinction  |
| A-    | 3.70-3.99 | 80-89.9%   | Very Good    |
| B+    | 3.30-3.69 | 70-79.9%   | First Div    |
| B     | 3.00-3.29 | 60-69.9%   | Second Div   |
| B-    | 2.70-2.99 | 50-59.9%   | Pass         |
| F     | < 2.70    | < 50%      | Fail         |

## Contributing

Please read CONTRIBUTING.md for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and queries, please contact the system administrator or raise an issue in the repository. 