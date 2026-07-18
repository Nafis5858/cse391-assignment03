# Car Workshop Appointment System

A simple PHP + MySQL web application for booking car service appointments.

## Features
- Client booking form with validation
- Mechanic availability display and capacity tracking
- Duplicate appointment prevention on the same date
- Admin panel to view and edit appointments
- GitHub Actions FTP deployment workflow included

## Run locally
1. Place the project folder inside your web server root (for example, XAMPP `htdocs`).
2. Start Apache and MySQL.
3. Copy `config.sample.php` to `config.php` and update the values, or set environment variables:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
4. Open the project in a browser:
   - User page: `http://localhost/Assignment-03/index.php`
   - Admin page: `http://localhost/Assignment-03/admin.php`

## Deployment
The repository includes a GitHub Actions workflow at `.github/workflows/deploy.yml` to deploy files via FTP.

To use it:
1. Add these GitHub repository secrets:
   - `FTP_SERVER`
   - `FTP_USERNAME`
   - `FTP_PASSWORD`
   - `FTP_PORT`
   - `FTP_PATH`
2. Push changes to `master` or `main`.
3. The workflow will automatically deploy the repository contents to your FTP host.

## Database
The application creates the database and tables automatically on first run when the database user has permission to do so.
If your hosting provider does not allow automatic database creation, create the database manually and configure `config.php` accordingly.
