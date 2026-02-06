IMS566 (ADVANCED WEB DESIGN DEVELOPMENT AND CONTENT MANAGEMENT)
Group Assignment 

Lecturer: Sir Faizal Haini Bin Fadzil
Session: OCT2025 - FEB2026
Class: D1CDIM2624A
Group Team Members:
1. Nur Fadhilah Binti Arifin (2024438458)
2. Nurul Absarina Binti Adanan (2024903057)
3. Nur Alia Izzati Binti Azman (2024764737)

Our presentation slide link: https://shorturl.at/RIWgh
GitHub Link: https://github.com/dilaarifin/MyBeachCare-System-Management.git

MyBeachCare

MyBeachCare is a community-driven platform dedicated to protecting shores. It allows users to participate in beach cleanup events, report coastal issues, and engage in gamified environmental conservation.

Features

* Beach Cleanup Events: Browse and register for upcoming cleanup events.
* Voting System: Community voting for beach cleanup priorities.
* User Profiles: Personal dashboards with activity tracking.
* Reporting System: Report pollution, waste, or safety hazards at beaches.
* Gamification: Earn points and badges for participation and reporting.
* Admin Dashboard: Manage beaches, events, reports, rewards and users.
* Responsive Design: Modern UI built with TailwindCSS.

System Requirements

* Web Browser: Preferable you use Google Chrome Guest Mode to avoid system heavy loads
* XAMPP (Apache Web Server & MariaDB/MySQL)
* PHP 8.0 or higher
* Frontend: HTML5. CSS3, JavaScript
* Styling: TailwindCSS
* Alerts: SweetAlert2
* Server: Apache (via XAMPP)

Installation Guide

1. Download & Extract

* Download or clone the project files.
* Place the project folder inside your XAMPP htdocs directory (e.g., C:\xampp\htdocs\mybeachcare).

2. Database Setup

* Open XAMPP Control Panel and start Apache and MySQL.
* Open your web browser and go to PHPMyAdmin ([http://localhost/phpmyadmin](http://localhost/phpmyadmin)).
* Create a new database named "mybeachcare".
* Import the mybeachcare.sql file located in the project root directory into this new database.

3. Configuration

* Verify the database connection settings in includes/db_conn.php.
* The default settings are configured for a standard XAMPP environment:

```php
$sname = "localhost";
$uname = "root";
$password = "";
$db_name = "mybeachcare";
```

If your MySQL root password is different, update the $password variable in this file.

4. Run the Project

* Open your web browser.
* Navigate to the project URL: [http://localhost/mybeachcare](http://localhost/mybeachcare) (replace mybeachcare with your actual folder name if different).

Account Access & Credentials

User Access (Demo)
To access the platform as a standard user:

1. Go to the Sign Up page.
2. Register a new account with your details.
3. Log in to access user features like reporting and event registration.

Existed Users Account:
1. minah@gmail.com (password: minah123)
2. aliabu@gmail.com (password: ali123)

Admin Access (Demo)
* The database includes pre-existing admin accounts ([dila@gmail.com](mailto:dila@gmail.com), etc.), but their passwords are hashed. To access the Admin Dashboard, follow these steps to create your own admin account:

Existed Admin Account:
1. dila@gmail.com (password: dila123)
2. absa@gmail.com (password: absa123)
3. alia@gmail.com (password: alia123)

If you want to create another new admin account: 
1. Register a new account via the Sign Up page (e.g., username: admin_demo).
2. Go to PHPMyAdmin and open the mybeachcare database.
3. Browse the users table.
4. Find your newly created user record.
5. Change the value in the role column from user to admin.
6. Refresh the website and you will now have access to the Admin Dashboard upon login.

Gamification:
How to get Badges:
1. 1st clean-up
2. Top voter 10 times
3. 5 events joined

1 event = 10 points

PROJECT STRUCTURE

* admin/ - Admin panel for managing content.
* includes/ - Database connection and reusable logic.
* uploads/ - User-generated content (profile pictures, report images).
* img/ & vid/ - Static site assets.
* index.php - Homepage.
* login.php / signup.php - Authentication pages.
