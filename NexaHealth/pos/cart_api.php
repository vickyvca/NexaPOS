<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir']);
header('Content-Type: application/json');

$pdo = getPDO();
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function pick_price(array $item, int $level): float {
    if ($level === 2 && !empty($item['sell_price_lv2'])) return (float)$item['sell_price_lv2'];
    if ($level === 3 && !empty($item['sell_price_lv3'])) return (float)$item['sell_price_lv3'];
    return (float)$item['sell_price'];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!in_array($action, ['cart']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

try {
    if ($action === 'add') {
        $code = trim($_POST['code'] ?? '');
        $level = (int)($_POST['level'] ?? 1);
        if ($code === '') throw new Exception('Kode kosong');
        $stmt = $pdo->prepare("SELECT * FROM items WHERE (code=? OR barcode=?) AND is_active=1");
        $stmt->execute([$code,$code]);
        $item = $stmt->fetch();
        if (!$item) throw new Exception('Barang tidak ditemukan');
        $id = $item['id'];
        $price = pick_price($item, $level);
        $autoDisc = !empty($item['discount_pct']) ? ($price * ($item['discount_pct']/100)) : 0;
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = ['name'=>$item['name'],'price'=>$price,'qty'=>1,'discount'=>$autoDisc,'level'=>$level];
        } else {
            $_SESSION['cart'][$id]['qty']++;
            $_SESSION['cart'][$id]['price'] = $price;
            $_SESSION['cart'][$id]['level'] = $level;
        }
        echo json_encode(['ok'=>1,'cart'=>$_SESSION['cart']]);
        exit;
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $level = (int)($_POST['level'] ?? 1);
        if (isset($_SESSION['cart'][$id])) {
            $item = $pdo->prepare("SELECT sell_price,sell_price_lv2,sell_price_lv3 FROM items WHERE id=?");
            $item->execute([$id]);
            $row = $item->fetch();
            if ($row) {
                $_SESSION['cart'][$id]['price'] = pick_price($row, $level);
                $_SESSION['cart'][$id]['level'] = $level;
            }
            $_SESSION['cart'][$id]['qty'] = max(1,(int)($_POST['qty'] ?? 1));
            $_SESSION['cart'][$id]['discount'] = (float)($_POST['discount'] ?? 0);
        }
        echo json_encode(['ok'=>1,'cart'=>$_SESSION['cart']]);
        exit;
    }
    if ($action === 'remove') {
        unset($_SESSION['cart'][(int)($_POST['id'] ?? 0)]);
        echo json_encode(['ok'=>1,'cart'=>$_SESSION['cart']]);
        exit;
    }
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode(['ok'=>1,'cart'=>$_SESSION['cart']]);
        exit;
    }
    if ($action === 'sync') {
        $json = $_POST['cart_json'] ?? '[]';
        $cart = json_decode($json, true);
        if (!is_array($cart)) $cart = [];
        $_SESSION['cart'] = $cart;
        echo json_encode(['ok'=>1,'cart'=>$_SESSION['cart']]);
        exit;
    }
    if ($action === 'cart') {
        echo json_encode(['ok'=>1,'cart'=>$_SESSION['cart']]);
        exit;
    }
    throw new Exception('Invalid action');
} catch (Exception $e) {
    echo json_encode(['ok'=>0,'msg'=>$e->getMessage()]);
    exit;
}
