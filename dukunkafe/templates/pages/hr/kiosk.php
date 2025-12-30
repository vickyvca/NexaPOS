
<?php

// No auth needed for kiosk
$title = 'Attendance Kiosk';
$pdo = get_pdo($config);
$message = null;
$message_type = 'info'; // info, success, error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    $type = $_POST['type'] ?? ''; // 'IN' or 'OUT'

    if (empty($pin) || empty($type)) {
        $message = 'PIN and action type are required.';
        $message_type = 'error';
    } else {
        // Find employee by PIN
        $stmt = $pdo->prepare('SELECT * FROM employees WHERE pin = ? AND active = 1');
        $stmt->execute([$pin]);
        $employee = $stmt->fetch();

        if ($employee) {
            // Check last attendance status
            $stmt = $pdo->prepare('SELECT type FROM attendances WHERE employee_id = ? ORDER BY ts DESC LIMIT 1');
            $stmt->execute([$employee['id']]);
            $last_attendance_type = $stmt->fetchColumn();

            if ($type === 'IN') {
                if ($last_attendance_type === 'IN') {
                    $message = 'You have already clocked in, ' . htmlspecialchars($employee['name']) . '.';
                    $message_type = 'error';
                } else {
                    $insert_stmt = $pdo->prepare('INSERT INTO attendances (employee_id, type, ts) VALUES (?, ?, NOW())');
                    $insert_stmt->execute([$employee['id'], 'IN']);
                    $message = 'Welcome, ' . htmlspecialchars($employee['name']) . '. Clocked in successfully.';
                    $message_type = 'success';
                }
            } elseif ($type === 'OUT') {
                if ($last_attendance_type === 'OUT' || $last_attendance_type === false) {
                    $message = 'You have not clocked in yet, ' . htmlspecialchars($employee['name']) . '.';
                    $message_type = 'error';
                } else {
                    $insert_stmt = $pdo->prepare('INSERT INTO attendances (employee_id, type, ts) VALUES (?, ?, NOW())');
                    $insert_stmt->execute([$employee['id'], 'OUT']);
                    $message = 'Goodbye, ' . htmlspecialchars($employee['name']) . '. Clocked out successfully.';
                    $message_type = 'success';
                }
            }
        } else {
            $message = 'Invalid PIN. Please try again.';
            $message_type = 'error';
        }
    }
}

// This page is standalone, so we require the view directly.
require __DIR__ . '/kiosk.view.php';

