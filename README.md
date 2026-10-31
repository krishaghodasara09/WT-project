# StudentHub – Student Management Portal

## 1. Project Introduction

StudentHub is a web-based student management portal designed to simplify college activities. It allows students to register, log in, view their profiles, explore college events, and register for events.

Administrators can manage student records, manage events, and monitor registrations through an admin dashboard.

## 2. Features

### Student Features
- Student registration and login.
- Secure password storage.
- Student dashboard.
- View personal profile.
- View available college events.
- Register for events.
- Contact and feedback pages.

### Admin Features
- Secure admin login.
- Admin dashboard with statistics.
- View, add, edit, search, and delete student records.
- Add, update, and delete events.
- Upload event posters.
- View latest event registrations.
- Manage events through Fetch API.

### Security Features
- Password hashing using PHP.
- Password verification during login.
- PHP session-based authentication.
- Role-based access control.
- Prepared SQL statements.
- CSRF token validation.
- Session timeout after 15 minutes.
- Server-side form validation.

## 3. Technologies Used

- HTML5
- CSS3
- JavaScript
- PHP 8.4
- MySQL
- MySQLi
- Fetch API
- XAMPP
- Apache Server
- Visual Studio Code
- Git and GitHub

## 4. Running the Project

Open a web browser and visit:

`http://studenthub.local`

New students can register through the registration page and log in using their roll number and password.

Administrators can access the admin dashboard after logging in with an account assigned the admin role.

## 5. Testing

The following functionalities are tested:

- Student registration and login.
- Admin login and dashboard access.
- Role-based access restrictions.
- Student management operations.
- Event management operations.
- Student event registration.
- Prevention of duplicate event registrations.
- Fetch API operations for adding, updating, viewing, and deleting events.
- Session timeout and logout.
- Responsive layouts on mobile screens.
- Apache virtual host deployment.

## 6. Deployment

StudentHub is deployed locally using XAMPP with Apache and MySQL. The project is maintained in a GitHub repository.

## 7. Conclusion

StudentHub provides a centralized platform for managing student information and college events. It combines frontend technologies with PHP and MySQL to provide authentication, database operations, event management, and role-based dashboards.