<?php

require_auth(['admin', 'manager']);

$pdo = get_pdo($config);

// Handle Restock Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'])) {
    $material_id = $_POST['material_id'];
    $qty = (float)($_POST['qty'] ?? 0);
    $total_cost = (float)($_POST['total_cost'] ?? 0);

    if ($material_id && $qty > 0) {
        $unit_cost = ($total_cost > 0 && $qty > 0) ? $total_cost / $qty : 0;

        try {
            $pdo->beginTransaction();

            // 1. Update or Insert into stock_cards
            $sc_stmt = $pdo->prepare('SELECT id FROM stock_cards WHERE material_id = ?');
            $sc_stmt->execute([$material_id]);
            if ($sc_stmt->fetch()) {
                // Update existing stock card
                $update_sc_stmt = $pdo->prepare('UPDATE stock_cards SET qty_on_hand = qty_on_hand + ? WHERE material_id = ?');
                $update_sc_stmt->execute([$qty, $material_id]);
            } else {
                // Insert new stock card
                $insert_sc_stmt = $pdo->prepare('INSERT INTO stock_cards (material_id, qty_on_hand, uom) VALUES (?, ?, (SELECT uom FROM materials WHERE id = ?))');
                $insert_sc_stmt->execute([$material_id, $qty, $material_id]);
            }

            // 2. Record the stock movement
            $sm_stmt = $pdo->prepare("
                INSERT INTO stock_moves (material_id, move_type, qty, uom, ref_type, unit_cost, created_at)
                VALUES (?, 'IN', ?, (SELECT uom FROM materials WHERE id = ?), 'RESTOCK', ?, NOW())
            ");
            $sm_stmt->execute([$material_id, $qty, $material_id, $unit_cost]);

            $pdo->commit();
            $success_message = "Stok berhasil ditambahkan.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Gagal memperbarui stok: " . $e->getMessage();
        }
    }
}

// Fetch data for the view
$materials = $pdo->query('SELECT id, name, uom FROM materials ORDER BY name ASC')->fetchAll();
$stock_cards_raw = $pdo->query('SELECT material_id, qty_on_hand FROM stock_cards')->fetchAll(PDO::FETCH_KEY_PAIR);

$material_stocks = [];
foreach ($materials as $material) {
    $material_stocks[] = [
        'id' => $material['id'],
        'name' => $material['name'],
        'uom' => $material['uom'],
        'current_stock' => $stock_cards_raw[$material['id']] ?? 0
    ];
}

$title = 'Penerimaan Barang / Restock';
$viewPath = __DIR__ . '/purchasing_receive.view.php';
require __DIR__ . '/../layout.php';
