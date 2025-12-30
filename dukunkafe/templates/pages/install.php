<?php

// Public installer and initial setup wizard

$title = 'Instalasi & Initial Setup';
$pdo = null;
$errors = [];
$messages = [];

// Helper function to execute a multi-statement SQL script
function execute_sql_script(PDO $pdo, string $file_path): void {
    $sql = file_get_contents($file_path);
    // Normalize line endings and remove comments
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql); // Use # as delimiter to avoid conflict
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    // Split by semicolon followed by a newline
    $parts = preg_split('/;\s*\n/', $sql);
    $statements = array_filter(array_map('trim', $parts), fn($s) => $s !== '');
    
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

// Try to connect early using existing config
try {
    $pdo = get_pdo($config);
} catch (Exception $e) {
    $errors[] = 'Gagal koneksi database: ' . $e->getMessage();
}

// Detect installation state
$installed = false;
if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt && $stmt->fetchColumn()) {
            $cnt = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($cnt > 0) $installed = true;
        }
    } catch (Exception $e) { /* ignore */ }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    if (!$pdo) {
        $errors[] = 'Tidak dapat melanjutkan instalasi karena koneksi database gagal.';
    } else {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

            // 1) Execute install.sql
            $sql_path = __DIR__ . '/../../deploy/install.sql';
            if (file_exists($sql_path)) {
                execute_sql_script($pdo, $sql_path);
                $messages[] = 'Skema database dasar berhasil diinstal.';
            } else {
                $errors[] = 'File install.sql tidak ditemukan.';
            }

            // 1b) Apply core update schema
            if (empty($errors)) {
                $core_path = __DIR__ . '/../../deploy/update_schema_core_mysql.sql';
                if (file_exists($core_path)) {
                    execute_sql_script($pdo, $core_path);
                    $messages[] = 'Update skema inti berhasil diterapkan.';
                }
            }

            // 1c) Apply addons schema (optional)
            if (empty($errors)) {
                $addons_path = __DIR__ . '/../../deploy/update_schema_addons_mysql.sql';
                if (file_exists($addons_path)) {
                    execute_sql_script($pdo, $addons_path);
                    $messages[] = 'Skema add-ons berhasil diterapkan.';
                }
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            // 2) Apply initial settings if no errors occurred during schema setup
            if (empty($errors)) {
                $pairs = [
                    'cafe_name' => trim($_POST['cafe_name'] ?? 'Dukun Cafe'),
                    'cafe_address' => trim($_POST['cafe_address'] ?? ''),
                    'inventory_mode' => in_array(($_POST['inventory_mode'] ?? 'advanced'), ['simple','advanced'], true) ? $_POST['inventory_mode'] : 'advanced',
                    'tax_rate' => (string)floatval($_POST['tax_rate'] ?? '10'),
                    'service_rate' => (string)floatval($_POST['service_rate'] ?? '5'),
                    'app_installed' => '1',
                ];
                foreach ($pairs as $k => $v) {
                    $stmt = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
                    $stmt->execute([$k, $v]);
                }

                // Optional: logo upload
                if (isset($_FILES['cafe_logo']) && $_FILES['cafe_logo']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../../public/uploads/';
                    if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
                    $filename = 'logo_' . time() . '_' . basename($_FILES['cafe_logo']['name']);
                    $destination = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['cafe_logo']['tmp_name'], $destination)) {
                        $logo_url = '/uploads/' . $filename;
                        $stmt = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES ('cafe_logo',?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
                        $stmt->execute([$logo_url]);
                    }
                }

                // 3) Upsert admin user
                $admin_name = trim($_POST['admin_name'] ?? 'Administrator');
                $admin_email = trim($_POST['admin_email'] ?? 'admin@example.com');
                $admin_password = $_POST['admin_password'] ?? 'admin123';
                $hash = password_hash($admin_password, PASSWORD_BCRYPT);

                $existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $existing->execute([$admin_email]);
                if ($uid = $existing->fetchColumn()) {
                    $stmt = $pdo->prepare('UPDATE users SET name=?, password_hash=?, role="admin", active=1 WHERE id=?');
                    $stmt->execute([$admin_name, $hash, $uid]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,active,created_at) VALUES (?,?,?,?,1,NOW())');
                    $stmt->execute([$admin_name, $admin_email, $hash, 'admin']);
                }

                // Done – redirect to login
                redirect(base_url('login'));
            }

        } catch (Exception $e) {
            $errors[] = 'Gagal menjalankan skrip instalasi: ' . $e->getMessage();
        }
    }
}

$viewPath = __DIR__ . '/install.view.php';
