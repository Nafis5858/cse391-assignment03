<?php
require_once 'db.php';

$message = '';
$messageType = '';
$selectedDate = $_POST['appointment_date'] ?? date('Y-m-d');
$selectedMechanic = (int) ($_POST['mechanic_id'] ?? 0);
$clientName = trim($_POST['client_name'] ?? '');
$clientAddress = trim($_POST['client_address'] ?? '');
$clientPhone = trim($_POST['client_phone'] ?? '');
$carLicense = trim($_POST['car_license_number'] ?? '');
$carEngine = trim($_POST['car_engine_number'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    if ($clientName === '' || !preg_match('/^[A-Za-z .-]{2,}$/', $clientName)) {
        $errors[] = 'Please enter a valid client name.';
    }

    if ($clientAddress === '') {
        $errors[] = 'Please enter the client address.';
    }

    if ($clientPhone === '' || !preg_match('/^\d{10,15}$/', $clientPhone)) {
        $errors[] = 'Phone number must contain only numbers and be 10 to 15 digits.';
    }

    if ($carLicense === '' || !preg_match('/^[A-Za-z0-9-]{2,20}$/', $carLicense)) {
        $errors[] = 'Please enter a valid car license number.';
    }

    if ($carEngine === '' || !preg_match('/^\d+$/', $carEngine)) {
        $errors[] = 'Car engine number must contain only digits.';
    }

    if (!isset($_POST['appointment_date']) || $_POST['appointment_date'] === '') {
        $errors[] = 'Please choose an appointment date.';
    } else {
        $selectedDate = $_POST['appointment_date'];
        if (strtotime($selectedDate) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Appointment date cannot be in the past.';
        }
    }

    if ($selectedMechanic === 0) {
        $errors[] = 'Please select a mechanic.';
    }

    if (empty($errors)) {
        if (hasAppointmentConflict($conn, $clientPhone, $carLicense, $selectedDate)) {
            $errors[] = 'This client already has an appointment on the selected date.';
        } else {
            $bookedCount = getMechanicBookedCount($conn, $selectedMechanic, $selectedDate);
            if ($bookedCount >= 4) {
                $errors[] = 'The selected mechanic is already fully booked for that date.';
            } else {
                $stmt = $conn->prepare("INSERT INTO appointments (client_name, client_address, client_phone, car_license_number, car_engine_number, appointment_date, mechanic_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssssi', $clientName, $clientAddress, $clientPhone, $carLicense, $carEngine, $selectedDate, $selectedMechanic);
                $stmt->execute();

                $message = 'Appointment submitted successfully.';
                $messageType = 'success';
                $clientName = '';
                $clientAddress = '';
                $clientPhone = '';
                $carLicense = '';
                $carEngine = '';
                $selectedMechanic = 0;
            }
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

$mechanics = getMechanicsWithAvailability($conn, $selectedDate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshop Appointment System</title>
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
            <h1>Reserve a mechanic for your vehicle</h1>
            <p>Book an appointment online and avoid the wait at the workshop.</p>
        </section>

        <section class="panel">
            <form id="appointment-form" method="post" action="index.php" novalidate>
                <?php if ($message !== ''): ?>
                    <div class="message <?= htmlspecialchars($messageType) ?>"><?= $message ?></div>
                <?php endif; ?>

                <div class="grid">
                    <label>
                        Client Name
                        <input type="text" name="client_name" value="<?= htmlspecialchars($clientName) ?>" required>
                    </label>
                    <label>
                        Address
                        <input type="text" name="client_address" value="<?= htmlspecialchars($clientAddress) ?>" required>
                    </label>
                    <label>
                        Phone Number
                        <input type="tel" name="client_phone" value="<?= htmlspecialchars($clientPhone) ?>" required>
                    </label>
                    <label>
                        Car License Number
                        <input type="text" name="car_license_number" value="<?= htmlspecialchars($carLicense) ?>" required>
                    </label>
                    <label>
                        Car Engine Number
                        <input type="text" name="car_engine_number" value="<?= htmlspecialchars($carEngine) ?>" required>
                    </label>
                    <label>
                        Appointment Date
                        <input type="date" name="appointment_date" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>" required>
                    </label>
                    <label>
                        Desired Mechanic
                        <select name="mechanic_id" required>
                            <option value="">Select a mechanic</option>
                            <?php foreach ($mechanics as $mechanic):
                                $freeSlots = (int) $mechanic['max_capacity'] - (int) $mechanic['booked_count'];
                                $disabled = $freeSlots <= 0 ? 'disabled' : '';
                                $selectedAttr = ((int) $mechanic['id'] === $selectedMechanic) ? 'selected' : '';
                            ?>
                                <option value="<?= (int) $mechanic['id'] ?>" <?= $selectedAttr ?> <?= $disabled ?>>
                                    <?= htmlspecialchars($mechanic['name']) ?> - <?= $freeSlots > 0 ? $freeSlots . ' free slots' : 'Full' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <button type="submit">Book Appointment</button>
            </form>
        </section>

        <section class="availability">
            <h2>Mechanic availability for <?= htmlspecialchars($selectedDate) ?></h2>
            <div class="cards">
                <?php foreach ($mechanics as $mechanic):
                    $freeSlots = (int) $mechanic['max_capacity'] - (int) $mechanic['booked_count'];
                ?>
                    <div class="card <?= $freeSlots <= 0 ? 'full' : '' ?>">
                        <h3><?= htmlspecialchars($mechanic['name']) ?></h3>
                        <p><?= $freeSlots > 0 ? $freeSlots . ' free slots left' : 'No free slots left' ?></p>
                        <small><?= (int) $mechanic['booked_count'] ?>/<?= (int) $mechanic['max_capacity'] ?> booked</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
        document.getElementById('appointment-form').addEventListener('submit', function (event) {
            const phone = document.querySelector('input[name="client_phone"]').value.trim();
            const engine = document.querySelector('input[name="car_engine_number"]').value.trim();
            const name = document.querySelector('input[name="client_name"]').value.trim();
            const mechanic = document.querySelector('select[name="mechanic_id"]').value;
            const date = document.querySelector('input[name="appointment_date"]').value;

            if (!name || !/^[A-Za-z .-]{2,}$/.test(name)) {
                event.preventDefault();
                alert('Please enter a valid client name.');
            } else if (!/^\d{10,15}$/.test(phone)) {
                event.preventDefault();
                alert('Phone number must contain only numbers and be 10 to 15 digits.');
            } else if (!/^\d+$/.test(engine)) {
                event.preventDefault();
                alert('Engine number must contain only digits.');
            } else if (!date) {
                event.preventDefault();
                alert('Please choose an appointment date.');
            } else if (!mechanic) {
                event.preventDefault();
                alert('Please select a mechanic.');
            }
        });
    </script>
</body>
</html>
