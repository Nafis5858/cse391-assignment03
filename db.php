<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$host = isset($dbHost) ? $dbHost : (getenv('DB_HOST') ?: 'localhost');
$dbName = isset($dbName) ? $dbName : (getenv('DB_NAME') ?: 'car_workshop');
$dbUser = isset($dbUser) ? $dbUser : (getenv('DB_USER') ?: 'root');
$dbPass = isset($dbPass) ? $dbPass : (getenv('DB_PASS') ?: '');

$conn = new mysqli($host, $dbUser, $dbPass);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
$conn->select_db($dbName);

$conn->query("CREATE TABLE IF NOT EXISTS mechanics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    max_capacity INT NOT NULL DEFAULT 4
)");

$conn->query("CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(150) NOT NULL,
    client_address TEXT NOT NULL,
    client_phone VARCHAR(20) NOT NULL,
    car_license_number VARCHAR(50) NOT NULL,
    car_engine_number VARCHAR(50) NOT NULL,
    appointment_date DATE NOT NULL,
    mechanic_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE CASCADE,
    INDEX idx_date (appointment_date),
    INDEX idx_phone_date (client_phone, appointment_date),
    INDEX idx_license_date (car_license_number, appointment_date)
)");

$mechanicCountResult = $conn->query("SELECT COUNT(*) AS total FROM mechanics");
$mechanicCount = (int) $mechanicCountResult->fetch_assoc()['total'];

if ($mechanicCount === 0) {
    $defaultMechanics = ['Arif Rahman', 'Mizan Hossain', 'Sakib Ahmed', 'Rahim Uddin', 'Nabil Hasan'];
    $stmt = $conn->prepare("INSERT INTO mechanics (name, max_capacity) VALUES (?, 4)");
    foreach ($defaultMechanics as $mechanicName) {
        $stmt->bind_param('s', $mechanicName);
        $stmt->execute();
    }
    $stmt->close();
}

function getMechanicsWithAvailability($conn, $selectedDate)
{
    $stmt = $conn->prepare("SELECT m.id, m.name, m.max_capacity, COUNT(a.id) AS booked_count
        FROM mechanics m
        LEFT JOIN appointments a ON a.mechanic_id = m.id AND a.appointment_date = ?
        GROUP BY m.id
        ORDER BY m.name");
    $stmt->bind_param('s', $selectedDate);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function hasAppointmentConflict($conn, $phone, $license, $date)
{
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND (client_phone = ? OR car_license_number = ?)");
    $stmt->bind_param('sss', $date, $phone, $license);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function hasAppointmentConflictExceptId($conn, $phone, $license, $date, $excludeId)
{
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND id <> ? AND (client_phone = ? OR car_license_number = ?)");
    $stmt->bind_param('siss', $date, $excludeId, $phone, $license);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function getMechanicBookedCount($conn, $mechanicId, $date, $excludeId = null)
{
    if ($excludeId === null) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM appointments WHERE mechanic_id = ? AND appointment_date = ?");
        $stmt->bind_param('is', $mechanicId, $date);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM appointments WHERE mechanic_id = ? AND appointment_date = ? AND id <> ?");
        $stmt->bind_param('isi', $mechanicId, $date, $excludeId);
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int) $result['total'];
}

function getAllAppointments($conn)
{
    $result = $conn->query("SELECT a.id, a.client_name, a.client_address, a.client_phone, a.car_license_number, a.car_engine_number, a.appointment_date, a.created_at, m.name AS mechanic_name
        FROM appointments a
        JOIN mechanics m ON a.mechanic_id = m.id
        ORDER BY a.appointment_date DESC, a.created_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAppointmentById($conn, $id)
{
    $stmt = $conn->prepare("SELECT a.id, a.client_name, a.client_address, a.client_phone, a.car_license_number, a.car_engine_number, a.appointment_date, a.mechanic_id, m.name AS mechanic_name
        FROM appointments a
        JOIN mechanics m ON a.mechanic_id = m.id
        WHERE a.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
