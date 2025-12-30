<?php

require_auth(['admin', 'manager']);

$title = 'Menu Recipes';
$pdo = get_pdo($config);

$menus = $pdo->query('SELECT id, sku, name, category_id FROM menus ORDER BY name ASC')->fetchAll();

$viewPath = __DIR__ . '/inventory_menus.view.php';
require __DIR__ . '/../layout.php';
