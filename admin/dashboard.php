<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (!defined('ADMIN_PORTAL_ENABLED') || ADMIN_PORTAL_ENABLED !== true) {
    http_response_code(404);
    exit('Not Found');
}

if (empty($_SESSION['admin_portal_user_id'])) {
    header('Location: login.php');
    exit;
}

header('Location: database.php?section=balance');
exit;
