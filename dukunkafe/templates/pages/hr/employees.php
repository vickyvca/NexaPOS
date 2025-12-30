
<?php

require_auth(['admin', 'hr']);

$title = 'HR - Employees';
$pdo = get_pdo($config);

// Handle POST request for Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $nik = $_POST['nik'] ?? '';
    $pin = $_POST['pin'] ?? '';
    $role_hint = $_POST['role_hint'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO employees (nik, name, pin, role_hint, active) VALUES (?, ?, ?, ?, ?)');
            // In a real app, PIN should be hashed. For this simple kiosk, we store it as-is.
            $stmt->execute([$nik, $name, $pin, $role_hint, 1]);
        } elseif ($action === 'update' && $id) {
            if (!empty($pin)) {
                $stmt = $pdo->prepare('UPDATE employees SET nik=?, name=?, pin=?, role_hint=?, active=? WHERE id=?');
                $stmt->execute([$nik, $name, $pin, $role_hint, $active, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE employees SET nik=?, name=?, role_hint=?, active=? WHERE id=?');
                $stmt->execute([$nik, $name, $role_hint, $active, $id]);
            }
        }
        redirect(base_url('hr/employees'));
    } catch (PDOException $e) {
        // Handle potential duplicate NIK error
        die("Database error: " . $e->getMessage());
    }
}

// Fetch all employees
$stmt = $pdo->query('SELECT * FROM employees ORDER BY name ASC');
$employees = $stmt->fetchAll();

$roles = ['admin', 'kasir', 'waiter', 'kitchen', 'manager', 'hr'];

view('hr/employees', [
    'title' => $title,
    'employees' => $employees,
    'roles' => $roles,
]);
