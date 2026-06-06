<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$action = get('action', '');
$id = (int) get('id', 0);

$params = [];

if ($action === 'detalji' && $id) {
    $params['action'] = 'detalji';
    $params['id'] = $id;
} elseif ($action === 'izmeni' && $id) {
    $params['action'] = 'izmeni';
    $params['id'] = $id;
} elseif ($action === 'obrisi' && $id) {
    $params['action'] = 'obrisi';
    $params['id'] = $id;
} elseif ($status = get('status', '')) {
    $params['status'] = $status;
}

$query = $params ? '?' . http_build_query($params) : '';
redirect('admin/' . $query);
