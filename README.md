# Attendance Management System Using QR Code

## About the Project

The Attendance Management System is a web-based application developed to simplify student attendance recording using QR code technology. Teachers can generate QR codes for attendance sessions, and students can scan them to access the attendance system.

The system uses PHP for backend processing and MySQL for database management. It aims to reduce manual work and make attendance management more convenient and efficient.

## Features

- Teacher login and authentication
- Subject management
- QR code generation for attendance sessions
- QR code scanning by students
- Student authentication
- QR token validation
- Attendance record storage
- Database-based attendance management

## Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Development Environment:** XAMPP
- **Version Control:** Git and GitHub

## Project Structure

```text
PROJECT/
├── frontend/
├── backend/
├── Database/
├── uploads/
└── README.md
```

**Note:** The `uploads/` directory is excluded from GitHub using `.gitignore`, if configured correctly.

## How It Works

1. The teacher logs into the system.
2. The teacher selects a subject.
3. The system generates a QR code for the attendance session.
4. The student scans the QR code using a mobile device.
5. The system validates the QR token.
6. The student logs in if authentication is required.
7. The system records attendance in the database.
8. A confirmation message is displayed.

## Installation and Setup

1. Install and start Apache and MySQL using XAMPP.
2. Copy the project folder into the `htdocs` directory.
3. Import the provided SQL file from the `Database/` directory into phpMyAdmin.
4. Configure the database connection settings in the PHP configuration file.
5. Open the project through your browser using the appropriate localhost URL.
6. Log in and test the teacher and student workflows.

## Database

The project uses MySQL to store and manage the records required for attendance processing. Import the SQL file provided in the `Database/` directory before running the application.

## Future Enhancements

- Attendance reports and summaries
- Improved dashboard and reporting features
- Additional attendance validation mechanisms
- Enhanced security and access control

## Author

**Bhavesh Khedkar**

GitHub: [@bhaveshkhedkar651-art](https://github.com/bhaveshkhedkar651-art)

<img width="1580" height="2512" alt="mermaid-diagram" src="https://github.com/user-attachments/assets/5e4cabf0-3a97-487b-933b-26d67e77c3d1" />

## License

This project is intended for educational purposes.
