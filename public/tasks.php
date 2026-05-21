<?php
$params = [
    'section' => 'tasks',
];

if (isset($_GET['edit'])) {
    $params['edit_task'] = (int) $_GET['edit'];
}
if (isset($_GET['message'])) {
    $params['message'] = (string) $_GET['message'];
}
if (isset($_GET['type'])) {
    $params['type'] = (string) $_GET['type'];
}

header('Location: goals.php?' . http_build_query($params));
exit;
