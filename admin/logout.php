<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

unset($_SESSION['admin_portal_user_id'], $_SESSION['admin_portal_username'], $_SESSION['admin_portal_logged_at']);

header('Location: login.php?message=' . urlencode('Sesión admin cerrada.') . '&type=success');
exit;
