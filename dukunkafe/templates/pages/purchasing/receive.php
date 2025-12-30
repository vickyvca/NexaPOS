
<?php

require_auth(['admin', 'manager']);

$title = 'Purchasing - Receive Items';
$pdo = get_pdo($config);

// Handle POST request to receive a PO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_id = $_POST['purchase_id'] ?? null;

    if ($purchase_id) {
        try {
            $pdo->beginTransaction();

            // 1. Fetch PO and its items
            $po_stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = ? AND status = 'ORDERED'");
            $po_stmt->execute([$purchase_id]);
            $po = $po_stmt->fetch();

            if (!$po) {
                throw new Exception("Purchase Order not found or not in 'ORDERED' status.");
            }

            $items_stmt = $pdo->prepare("SELECT * FROM purchase_items WHERE purchase_id = ?");
            $items_stmt->execute([$purchase_id]);
            $items = $items_stmt->fetchAll();

            // 2. Update PO status
            $update_po_stmt = $pdo->prepare("UPDATE purchases SET status = 'RECEIVED', received_at = NOW() WHERE id = ?");
            $update_po_stmt->execute([$purchase_id]);

            // 3. Update stock cards and stock moves
            $stock_card_update_stmt = $pdo->prepare("INSERT INTO stock_cards (material_id, qty_on_hand, uom) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE qty_on_hand = qty_on_hand + VALUES(qty_on_hand)");
            $stock_move_stmt = $pdo->prepare('INSERT INTO stock_moves (material_id, move_type, qty, uom, ref_type, ref_id, unit_cost) VALUES (?, \'IN\', ?, ?, \'PURCHASE\', ?, ?)');

            foreach ($items as $item) {
                $stock_card_update_stmt->execute([$item['material_id'], $item['qty'], $item['uom']]);
                $stock_move_stmt->execute([$item['material_id'], $item['qty'], $item['uom'], $item['purchase_id'], $item['price']]);
            }

            // 4. Create Journal Entry for the purchase
            $acc_persediaan = 5; // Persediaan
            $acc_hutang_supplier = 6; // Hutang Supplier

            $journal_stmt = $pdo->prepare('INSERT INTO journals (date, ref_type, ref_id, memo) VALUES (CURDATE(), \'PURCHASE\', ?, ?)');
            $journal_line_stmt = $pdo->prepare('INSERT INTO journal_lines (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)');

            $journal_stmt->execute([$purchase_id, 'Pembelian barang dari ' . $po['po_no']]);
            $journal_id = $pdo->lastInsertId();

            $journal_line_stmt->execute([$journal_id, $acc_persediaan, $po['total'], 0]); // Dr. Persediaan
            $journal_line_stmt->execute([$journal_id, $acc_hutang_supplier, 0, $po['total']]); // Cr. Hutang Supplier

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Failed to receive PO: " . $e->getMessage());
        }
    }
    redirect(base_url('purchasing/receive'));
}


// Fetch all ORDERED purchase orders
$ordered_pos = $pdo->query("SELECT p.*, s.name as supplier_name FROM purchases p JOIN suppliers s ON p.supplier_id = s.id WHERE p.status = 'ORDERED' ORDER BY p.created_at ASC")->fetchAll();

view('purchasing/receive', [
    'title' => $title,
    'ordered_pos' => $ordered_pos,
]);

