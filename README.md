# Tour-Package-Recommendation-System-6th-sem-
##  Installation Steps (Configuration)
1. Download and unzip the project files to your local system.
2. Copy the `tms` folder and place it inside your web server's root directory:
   - **XAMPP**: `xampp/htdocs`
   - **WAMP**: `wamp/www`
   - **LAMP**: `var/www/html`

---
##  Database Configuration

1. Open **phpMyAdmin**.
2. Create a new database named `tms`.
3. Import the SQL file: `tms.sql` (available inside the zip/package).
4. Ensure your MySQL is running on **port 3308** (instead of the default 3306).  
   Update your connection settings (e.g., `config.php`) like this:

   ```php
   $con = mysqli_connect("localhost:3308", "root", "", "tms");


Login Details for admin : 
Open Your browser put inside browser “http://localhost/tms/admin”
Username : admin
Password : admin

