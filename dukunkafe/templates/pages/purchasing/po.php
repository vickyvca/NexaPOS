
<?php

require_auth(['admin', 'manager']);

$title = 'Purchasing - Purchase Orders';
$pdo = get_pdo($config);

// Handle POST request to create a new PO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'] ?? null;
    $items_json = $_POST['items'] ?? '[]';
    $items = json_decode($items_json, true);
    $status = $_POST['status'] ?? 'DRAFT';

    if ($supplier_id && !empty($items)) {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }
        // For simplicity, tax/discount on purchases are handled manually for now.
        $total = $subtotal;

        try {
            $pdo->beginTransaction();

            // 1. Create Purchase Order
            $po_no = 'PO-' . date('Ymd') . '-' . strtoupper(uniqid());
            $stmt = $pdo->prepare('INSERT INTO purchases (po_no, supplier_id, status, subtotal, total, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$po_no, $supplier_id, $status, $subtotal, $total]);
            $purchase_id = $pdo->lastInsertId();

            // 2. Create Purchase Items
            $item_stmt = $pdo->prepare('INSERT INTO purchase_items (purchase_id, material_id, qty, uom, price) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $item) {
                $item_stmt->execute([$purchase_id, $item['id'], $item['qty'], $item['uom'], $item['price']]);
            }

            $pdo->commit();
            redirect(base_url('purchasing/po'));
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Failed to create PO: " . $e->getMessage());
        }
    }
}

// Fetch data for the view
$purchase_orders = $pdo->query("SELECT p.*, s.name as supplier_name FROM purchases p JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.created_at DESC")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();
$materials = $pdo->query("SELECT id, name, uom FROM materials WHERE active = 1 ORDER BY name ASC")->fetchAll();

view('purchasing/po', [
    'title' => $title,
    'purchase_orders' => $purchase_orders,
    'suppliers' => $suppliers,
    'materials' => $materials,
]);
