<?php
$params = [
    'section' => 'projects',
];

if (isset($_GET['edit'])) {
    $params['edit_project'] = (int) $_GET['edit'];
}
if (isset($_GET['message'])) {
    $params['message'] = (string) $_GET['message'];
}
if (isset($_GET['type'])) {
    $params['type'] = (string) $_GET['type'];
}

header('Location: goals.php?' . http_build_query($params));
exit;
