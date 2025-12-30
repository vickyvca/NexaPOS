
<?php

require_auth(['admin', 'kasir', 'waiter']);

$title = 'Point of Sale';
$pdo = get_pdo($config);

// Check for any active cash session in the current branch (required for all roles)
$branch_id = get_current_branch_id();
$active_branch_session_stmt = $pdo->prepare(
    "SELECT cs.id
     FROM cash_sessions cs
     JOIN users u ON u.id = cs.user_id
     WHERE cs.closed_at IS NULL AND (u.branch_id = ? OR u.branch_id IS NULL)
     LIMIT 1"
);
$active_branch_session_stmt->execute([$branch_id]);
$has_active_cashier = (bool)$active_branch_session_stmt->fetchColumn();

if (!$has_active_cashier) {
    // No cashier shift active for this branch; block POS usage
    $title = 'Shift Belum Dimulai';
    $viewPath = __DIR__ . '/../partials/pos_shift_required.view.php';
    require __DIR__ . '/../layout.php';
    exit();
}

// Expose flag to view for UI indicator
$cashier_shift_active = true;

// --- Data Loading for the View ---

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch menus
$menus = $pdo->query("SELECT * FROM menus WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Fetch addons
$addon_groups = $pdo->query("SELECT * FROM addon_groups")->fetchAll(PDO::FETCH_ASSOC);
$addons = $pdo->query("SELECT * FROM addons")->fetchAll(PDO::FETCH_ASSOC);
$menu_addon_groups = $pdo->query("SELECT * FROM menu_addon_groups")->fetchAll(PDO::FETCH_ASSOC);

// Organize addons by group
$addons_by_group = [];
foreach ($addons as $addon) {
    $addons_by_group[$addon['addon_group_id']][] = $addon;
}

// Organize addon groups by menu
$menu_addons = [];
foreach ($menu_addon_groups as $mapping) {
    $menu_addons[$mapping['menu_id']][] = $mapping['addon_group_id'];
}

// Annotate menus with has_addons and group ids for robust client usage
foreach ($menus as &$m) {
    $mid = $m['id'];
    $groups = $menu_addons[$mid] ?? [];
    $m['has_addons'] = !empty($groups);
    $m['addon_group_ids'] = $groups;
}
unset($m);


// Fetch tables with status (filtered by current branch)
$bid = get_current_branch_id();
$sql = "
    SELECT 
        t.id, t.code, t.name, t.area, t.capacity, t.status as base_status,
        (SELECT o.status FROM orders o WHERE o.table_id = t.id AND o.branch_id = {$bid} ORDER BY o.created_at DESC LIMIT 1) as last_order_status,
        (SELECT GROUP_CONCAT(oi.status) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.table_id = t.id AND o.status = 'OPEN' AND o.branch_id = {$bid}) as item_statuses
    FROM tables t
";

$stmt = $pdo->query($sql);
$tables_data = $stmt->fetchAll();

$tables_with_status = [];
foreach ($tables_data as $table) {
    $status = $table['base_status']; // Default to base status

    if ($table['last_order_status'] === 'OPEN') {
        $status = 'OCCUPIED'; // General occupied status
        if ($table['item_statuses']) {
            $item_statuses = explode(',', $table['item_statuses']);
            if (in_array('QUEUED', $item_statuses) || in_array('IN_PROGRESS', $item_statuses)) {
                $status = 'COOKING';
            }
            
            $all_ready = true;
            foreach($item_statuses as $is) {
                if ($is !== 'READY') {
                    $all_ready = false;
                    break;
                }
            }
            if ($all_ready && !empty($item_statuses)) {
                $status = 'READY';
            }
        }
    }

    $tables_with_status[] = [
        'id' => $table['id'],
        'name' => $table['name'],
        'capacity' => $table['capacity'],
        'status' => $status,
    ];
}



// Fetch settings
$settings = load_settings($pdo);
// Align with settings form keys
$tax_rate = (float)($settings['tax_rate'] ?? 10);
$service_rate = (float)($settings['service_rate'] ?? 5);
$inventory_mode = $settings['inventory_mode'] ?? 'advanced';


// --- Order Submission Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_json = $_POST['cart'] ?? '[]';
    $cart = json_decode($cart_json, true);

    $customer_name = $_POST['customer_name'] ?? 'Walk-in';
    $table_id = $_POST['table_id'] === 'take_away' ? null : ($_POST['table_id'] ?? null);
    $channel = $table_id ? 'DINE_IN' : 'TAKE_AWAY';
    $payment_method = $_POST['payment_method'] ?? 'CASH';
    $paid_total = (float)($_POST['paid_total'] ?? 0);

    if (empty($cart)) {
        die("Cart is empty!");
    }

    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['qty'];
        if (!empty($item['addons'])) {
            foreach ($item['addons'] as $addon) {
                $subtotal += $addon['price'] * $item['qty'];
            }
        }
    }

    $tax = $subtotal * ($tax_rate / 100);
    $service = $subtotal * ($service_rate / 100);
    $total = $subtotal + $tax + $service;

    try {
        $pdo->beginTransaction();

        // 1. Create Order with OPEN status
        // Generate human-friendly order number with date to ensure uniqueness across days:
        // Example: ORD-TA-240229-001 or ORD-DI-240229-001
        $type = ($channel === 'TAKE_AWAY') ? 'TA' : 'DI';
        $dateCode = date('ymd');
        $prefix = 'ORD-' . $type . '-' . $dateCode . '-';

        // Find the last sequence for this prefix and increment
        $max_stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(order_no, '-', -1) AS UNSIGNED))
                                    FROM orders
                                    WHERE order_no LIKE ?");
        $like = $prefix . '%';
        $max_stmt->execute([$like]);
        $last = (int)($max_stmt->fetchColumn() ?: 0);
        $next_seq = $last + 1;
        if ($next_seq > 999) { $next_seq = 1; }

        // Insert order with small retry loop on duplicate order_no (race condition safety)
        $attempts = 0;
        while (true) {
            $order_no = $prefix . str_pad((string)$next_seq, 3, '0', STR_PAD_LEFT);
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO orders (order_no, table_id, customer_name, channel, status, subtotal, tax, service, total, created_at, branch_id)
                    VALUES (?, ?, ?, ?, 'OPEN', ?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([$order_no, $table_id, $customer_name, $channel, $subtotal, $tax, $service, $total, get_current_branch_id()]);
                break; // success
            } catch (Exception $e) {
                // Fallback for DBs without branch_id or duplicate key handling
                $msg = $e->getMessage();
                if (stripos($msg, 'branch_id') !== false) {
                    $stmt = $pdo->prepare("
                        INSERT INTO orders (order_no, table_id, customer_name, channel, status, subtotal, tax, service, total, created_at)
                        VALUES (?, ?, ?, ?, 'OPEN', ?, ?, ?, ?, NOW())
                    ");
                    try {
                        $stmt->execute([$order_no, $table_id, $customer_name, $channel, $subtotal, $tax, $service, $total]);
                        break; // success
                    } catch (Exception $e2) {
                        // If duplicate order_no, bump sequence and retry a few times
                        if (stripos($e2->getMessage(), 'Duplicate entry') !== false && stripos($e2->getMessage(), 'order_no') !== false && $attempts < 5) {
                            $attempts++;
                            $next_seq++;
                            if ($next_seq > 999) { $next_seq = 1; }
                            continue;
                        }
                        throw $e2;
                    }
                } else if (stripos($msg, 'Duplicate entry') !== false && stripos($msg, 'order_no') !== false && $attempts < 5) {
                    // Duplicate on first path: bump sequence and retry
                    $attempts++;
                    $next_seq++;
                    if ($next_seq > 999) { $next_seq = 1; }
                    continue;
                } else {
                    throw $e;
                }
            }
        }
        $order_id = $pdo->lastInsertId();

        // 2a. If a table is used, mark it as OCCUPIED and set the timestamp
        if ($table_id) {
            $table_status_stmt = $pdo->prepare("
                UPDATE tables SET status = 'OCCUPIED', status_updated_at = NOW() WHERE id = ?
            ");
            $table_status_stmt->execute([$table_id]);
        }

        // 2. Create Order Items
        $item_stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, menu_id, qty, price, status, notes)
            VALUES (?, ?, ?, ?, 'QUEUED', ?)
        ");
        $addon_stmt = $pdo->prepare("
            INSERT INTO order_item_addons (order_item_id, addon_id, price)
            VALUES (?, ?, ?)
        ");

        foreach ($cart as $item) {
            $item_stmt->execute([
                $order_id, 
                $item['id'], 
                $item['qty'], 
                $item['price'],
                $item['notes'] ?? ''
            ]);
            $order_item_id = $pdo->lastInsertId();

            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addon) {
                    $addon_stmt->execute([
                        $order_item_id,
                        $addon['id'],
                        $addon['price']
                    ]);
                }
            }
        }
        
        // 3. Stock Deduction & HPP Calculation (Advanced Mode Only)
        if ($inventory_mode === 'advanced') {
            $total_hpp = 0;
            $bom_stmt = $pdo->prepare('SELECT material_id, qty FROM menu_bom WHERE menu_id = ?');
            $last_cost_stmt = $pdo->prepare('SELECT unit_cost FROM stock_moves WHERE material_id = ? AND move_type = \'IN\' ORDER BY created_at DESC LIMIT 1');
            $stock_update_stmt = $pdo->prepare('UPDATE stock_cards SET qty_on_hand = qty_on_hand - ? WHERE material_id = ?');
            $stock_move_stmt = $pdo->prepare('INSERT INTO stock_moves (material_id, move_type, qty, uom, ref_type, ref_id, unit_cost) VALUES (?, \'OUT\', ?, (SELECT uom FROM materials WHERE id = ?), \'ORDER\', ?, ?)');

            foreach ($cart as $item) {
                $bom_stmt->execute([$item['id']]);
                $recipe_items = $bom_stmt->fetchAll();

                foreach ($recipe_items as $recipe_item) {
                    $qty_to_deduct = $recipe_item['qty'] * $item['qty'];

                    $last_cost_stmt->execute([$recipe_item['material_id']]);
                    $unit_cost = $last_cost_stmt->fetchColumn();
                    if ($unit_cost === false) { $unit_cost = 0; }
                    $total_hpp += $qty_to_deduct * $unit_cost;

                    $stock_update_stmt->execute([$qty_to_deduct, $recipe_item['material_id']]);
                    $stock_move_stmt->execute([$recipe_item['material_id'], $qty_to_deduct, $recipe_item['material_id'], $order_id, $unit_cost]);
                }
            }

            // 4. Journal Entry for HPP/COGS only
            if ($total_hpp > 0) {
                $acc_hpp = 4; // HPP
                $acc_persediaan = 5; // Persediaan
                $journal_stmt = $pdo->prepare('INSERT INTO journals (date, ref_type, ref_id, memo) VALUES (CURDATE(), \'ORDER\', ?, ?)');
                $journal_line_stmt = $pdo->prepare('INSERT INTO journal_lines (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)');
                
                $journal_stmt->execute([$order_id, 'HPP untuk ' . $order_no]);
                $journal_id_hpp = $pdo->lastInsertId();
                $journal_line_stmt->execute([$journal_id_hpp, $acc_hpp, $total_hpp, 0]); // Dr. HPP
                $journal_line_stmt->execute([$journal_id_hpp, $acc_persediaan, 0, $total_hpp]); // Cr. Persediaan
            }
        }

        // 5. If paid_total provided, update payment fields and optionally close order
        if ($paid_total > 0) {
            $close = $paid_total >= $total;
            $upd = $pdo->prepare("UPDATE orders SET payment_method = ?, paid_total = ?, status = ?, closed_at = IF(?,'" . date('Y-m-d H:i:s') . "', closed_at) WHERE id = ?");
            $upd->execute([$payment_method, $paid_total, $close ? 'CLOSED' : 'OPEN', $close ? 1 : 0, $order_id]);
        }

        $pdo->commit();

        redirect(base_url('pos?success=1&order_no=' . $order_no . '&order_id=' . $order_id));

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Failed to create order: " . $e->getMessage());
    }
}

// This is a standalone page, require the view directly
require __DIR__ . '/pos.view.php';

