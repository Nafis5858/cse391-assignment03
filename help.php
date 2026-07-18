<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="topbar">
        <div class="brand">AutoCare Workshop</div>
        <nav>
            <a href="index.php">Book Appointment</a>
            <a href="admin.php">Admin Panel</a>
            <a href="help.php">Help</a>
        </nav>
    </header>

    <main class="container">
        <section class="hero">
            <h1>Help and instructions</h1>
            <p>Use this page to understand how the appointment system works.</p>
        </section>

        <section class="panel">
            <h2>How to use the application</h2>
            <ul>
                <li>Clients can book an appointment by entering their details and choosing a desired mechanic.</li>
                <li>The system checks whether the client already has an appointment on the same date.</li>
                <li>Each mechanic can receive up to four appointments per day.</li>
                <li>The admin panel shows all appointments and allows changing the assigned date or mechanic.</li>
            </ul>

            <h2>Requirements for a valid booking</h2>
            <ul>
                <li>Name must contain letters and spaces.</li>
                <li>Phone number must be numeric.</li>
                <li>Car engine number must be numeric.</li>
                <li>Appointment date must be selected and cannot be in the past.</li>
                <li>A mechanic must be chosen from the list.</li>
            </ul>
        </section>
    </main>
</body>
</html>
