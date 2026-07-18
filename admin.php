<?php
require_once 'db.php';

$message = '';
$messageType = '';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $selectedDate = trim($_POST['appointment_date'] ?? '');
    $mechanicId = (int) ($_POST['mechanic_id'] ?? 0);

    if ($appointmentId <= 0) {
        $message = 'Invalid appointment request.';
        $messageType = 'error';
    } else {
        $appointment = getAppointmentById($conn, $appointmentId);
        if (!$appointment) {
            $message = 'Appointment not found.';
            $messageType = 'error';
        } elseif ($selectedDate === '' || strtotime($selectedDate) < strtotime(date('Y-m-d'))) {
            $message = 'Please choose a valid appointment date.';
            $messageType = 'error';
        } elseif ($mechanicId <= 0) {
            $message = 'Please select a mechanic.';
            $messageType = 'error';
        } elseif (hasAppointmentConflictExceptId($conn, $appointment['client_phone'], $appointment['car_license_number'], $selectedDate, $appointmentId)) {
            $message = 'This client already has an appointment on the selected date.';
            $messageType = 'error';
        } else {
            $bookedCount = getMechanicBookedCount($conn, $mechanicId, $selectedDate, $appointmentId);
            if ($bookedCount >= 4) {
                $message = 'The selected mechanic is already fully booked for that date.';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, mechanic_id = ? WHERE id = ?");
                $stmt->bind_param('sii', $selectedDate, $mechanicId, $appointmentId);
                $stmt->execute();
                $message = 'Appointment updated successfully.';
                $messageType = 'success';
                $editId = 0;
            }
        }
    }
}

$appointments = getAllAppointments($conn);
$editingAppointment = null;
if ($editId > 0) {
    $editingAppointment = getAppointmentById($conn, $editId);
}

$selectedDateForMechanics = $editingAppointment['appointment_date'] ?? date('Y-m-d');
$mechanics = getMechanicsWithAvailability($conn, $selectedDateForMechanics);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="topbar">
        <div class="brand">AutoCare Workshop</div>
        <nav>
            <a href="index.php">Book Appointment</a>
            <a href="admin.php">Admin Panel</a>
        </nav>
    </header>

    <main class="container">
        <section class="hero">
            <h1>Admin Appointment List</h1>
            <p>Review client requests, change the appointment date, and reassign mechanics.</p>
        </section>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($editingAppointment): ?>
            <section class="panel">
                <h2>Update appointment for <?= htmlspecialchars($editingAppointment['client_name']) ?></h2>
                <form method="post" action="admin.php">
                    <input type="hidden" name="appointment_id" value="<?= (int) $editingAppointment['id'] ?>">
                    <div class="grid">
                        <label>
                            Appointment Date
                            <input type="date" name="appointment_date" value="<?= htmlspecialchars($editingAppointment['appointment_date']) ?>" min="<?= date('Y-m-d') ?>" required>
                        </label>
                        <label>
                            Mechanic
                            <select name="mechanic_id" required>
                                <option value="">Select a mechanic</option>
                                <?php foreach ($mechanics as $mechanic):
                                    $freeSlots = (int) $mechanic['max_capacity'] - (int) $mechanic['booked_count'];
                                    $selectedAttr = ((int) $mechanic['id'] === (int) $editingAppointment['mechanic_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?= (int) $mechanic['id'] ?>" <?= $selectedAttr ?>>
                                        <?= htmlspecialchars($mechanic['name']) ?> - <?= $freeSlots > 0 ? $freeSlots . ' free slots' : 'Full' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <button type="submit">Save Changes</button>
                </form>
            </section>
        <?php endif; ?>

        <section class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Phone</th>
                        <th>License</th>
                        <th>Date</th>
                        <th>Mechanic</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?= htmlspecialchars($appointment['client_name']) ?></td>
                            <td><?= htmlspecialchars($appointment['client_phone']) ?></td>
                            <td><?= htmlspecialchars($appointment['car_license_number']) ?></td>
                            <td><?= htmlspecialchars($appointment['appointment_date']) ?></td>
                            <td><?= htmlspecialchars($appointment['mechanic_name']) ?></td>
                            <td><a href="admin.php?edit=<?= (int) $appointment['id'] ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
