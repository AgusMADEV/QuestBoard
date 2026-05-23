<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Models/AppSettings.php';
require_once __DIR__ . '/../app/Models/AdminPortalUser.php';

function clearAdminPortalSession(): void
{
    unset($_SESSION['admin_portal_user_id'], $_SESSION['admin_portal_username'], $_SESSION['admin_portal_logged_at']);
}

function isAdminPortalSessionExpired(): bool
{
    $timeoutSeconds = defined('ADMIN_PORTAL_SESSION_TIMEOUT_SECONDS') ? (int) ADMIN_PORTAL_SESSION_TIMEOUT_SECONDS : 900;
    $loggedAt = (int) ($_SESSION['admin_portal_logged_at'] ?? 0);

    return $loggedAt <= 0 || (time() - $loggedAt) > max($timeoutSeconds, 60);
}

if (!defined('ADMIN_PORTAL_ENABLED') || ADMIN_PORTAL_ENABLED !== true) {
    http_response_code(404);
    exit('Not Found');
}

if (empty($_SESSION['admin_portal_user_id'])) {
    header('Location: login.php');
    exit;
}

if (isAdminPortalSessionExpired()) {
    clearAdminPortalSession();
    header('Location: login.php?message=' . urlencode('Sesion expirada por inactividad.') . '&type=error');
    exit;
}

$_SESSION['admin_portal_logged_at'] = time();

$settingsModel = new AppSettings();
$adminUserModel = new AdminPortalUser();
$adminUserId = (int) ($_SESSION['admin_portal_user_id'] ?? 0);
$adminUsername = (string) ($_SESSION['admin_portal_username'] ?? 'admin');
$keys = [
    'REWARD_POINTS_PER_XP',
    'REWARD_HABIT_BASE_XP',
    'REWARD_TASK_BASE_XP',
    'REWARD_GOAL_BASE_XP_DAILY',
    'REWARD_GOAL_BASE_XP_WEEKLY',
    'REWARD_GOAL_BASE_XP_MONTHLY',
    'REWARD_GOAL_BASE_XP_QUARTERLY',
    'REWARD_GOAL_BASE_XP_YEARLY',
    'REWARD_GOAL_BASE_XP_FUTURE',
    'INDULGENCE_REPEAT_COST_MULTIPLIER',
    'COSMETIC_PRICE_MULTIPLIER',
];

$defaults = [
    'REWARD_POINTS_PER_XP' => defined('REWARD_POINTS_PER_XP') ? (string) REWARD_POINTS_PER_XP : '0.5',
    'REWARD_HABIT_BASE_XP' => defined('REWARD_HABIT_BASE_XP') ? (string) REWARD_HABIT_BASE_XP : '10',
    'REWARD_TASK_BASE_XP' => defined('REWARD_TASK_BASE_XP') ? (string) REWARD_TASK_BASE_XP : '12',
    'REWARD_GOAL_BASE_XP_DAILY' => defined('REWARD_GOAL_BASE_XP_DAILY') ? (string) REWARD_GOAL_BASE_XP_DAILY : '16',
    'REWARD_GOAL_BASE_XP_WEEKLY' => defined('REWARD_GOAL_BASE_XP_WEEKLY') ? (string) REWARD_GOAL_BASE_XP_WEEKLY : '30',
    'REWARD_GOAL_BASE_XP_MONTHLY' => defined('REWARD_GOAL_BASE_XP_MONTHLY') ? (string) REWARD_GOAL_BASE_XP_MONTHLY : '50',
    'REWARD_GOAL_BASE_XP_QUARTERLY' => defined('REWARD_GOAL_BASE_XP_QUARTERLY') ? (string) REWARD_GOAL_BASE_XP_QUARTERLY : '70',
    'REWARD_GOAL_BASE_XP_YEARLY' => defined('REWARD_GOAL_BASE_XP_YEARLY') ? (string) REWARD_GOAL_BASE_XP_YEARLY : '95',
    'REWARD_GOAL_BASE_XP_FUTURE' => defined('REWARD_GOAL_BASE_XP_FUTURE') ? (string) REWARD_GOAL_BASE_XP_FUTURE : '110',
    'INDULGENCE_REPEAT_COST_MULTIPLIER' => defined('INDULGENCE_REPEAT_COST_MULTIPLIER') ? (string) INDULGENCE_REPEAT_COST_MULTIPLIER : '1.25',
    'COSMETIC_PRICE_MULTIPLIER' => '1.0',
];

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');
        $minPasswordLength = defined('ADMIN_PORTAL_PASSWORD_MIN_LENGTH') ? (int) ADMIN_PORTAL_PASSWORD_MIN_LENGTH : 12;
        $hasUpper = preg_match('/[A-Z]/', $newPassword) === 1;
        $hasLower = preg_match('/[a-z]/', $newPassword) === 1;
        $hasDigit = preg_match('/\d/', $newPassword) === 1;
        $hasSymbol = preg_match('/[^a-zA-Z\d]/', $newPassword) === 1;

        if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirm === '') {
            $message = 'Completa todos los campos de contraseña.';
            $messageType = 'error';
        } elseif (strlen($newPassword) < max($minPasswordLength, 10)) {
            $message = 'La nueva contraseña debe tener al menos ' . max($minPasswordLength, 10) . ' caracteres.';
            $messageType = 'error';
        } elseif (!$hasUpper || !$hasLower || !$hasDigit || !$hasSymbol) {
            $message = 'La nueva contraseña debe incluir mayuscula, minuscula, numero y simbolo.';
            $messageType = 'error';
        } elseif (hash_equals($currentPassword, $newPassword)) {
            $message = 'La nueva contraseña debe ser diferente de la actual.';
            $messageType = 'error';
        } elseif ($newPassword !== $newPasswordConfirm) {
            $message = 'La confirmación de contraseña no coincide.';
            $messageType = 'error';
        } else {
            $admin = $adminUserModel->verifyCredentials($adminUsername, $currentPassword);

            if ($admin === null || (int) $admin['id'] !== $adminUserId) {
                $message = 'La contraseña actual no es correcta.';
                $messageType = 'error';
            } elseif ($adminUserModel->updatePasswordById($adminUserId, $newPassword)) {
                $message = 'Contraseña actualizada correctamente.';
                $messageType = 'success';
            } else {
                $message = 'No se pudo actualizar la contraseña.';
                $messageType = 'error';
            }
        }
    }

    if ($action === 'reset') {
        $settingsModel->deleteMany($keys);
        header('Location: dashboard.php?message=' . urlencode('Valores reseteados a defaults de config.') . '&type=success');
        exit;
    }

    if ($action !== 'save') {
        $current = array_merge($defaults, $settingsModel->getMany($keys));
    }

    if ($action === 'save') {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = trim((string) ($_POST[$key] ?? $defaults[$key]));
        }

        $validationRules = [
            'REWARD_POINTS_PER_XP' => [0.1, 2.0],
            'REWARD_HABIT_BASE_XP' => [1, 100],
            'REWARD_TASK_BASE_XP' => [1, 120],
            'REWARD_GOAL_BASE_XP_DAILY' => [1, 300],
            'REWARD_GOAL_BASE_XP_WEEKLY' => [1, 300],
            'REWARD_GOAL_BASE_XP_MONTHLY' => [1, 400],
            'REWARD_GOAL_BASE_XP_QUARTERLY' => [1, 500],
            'REWARD_GOAL_BASE_XP_YEARLY' => [1, 700],
            'REWARD_GOAL_BASE_XP_FUTURE' => [1, 900],
            'INDULGENCE_REPEAT_COST_MULTIPLIER' => [1.0, 3.0],
            'COSMETIC_PRICE_MULTIPLIER' => [0.1, 3.0],
        ];

        $errors = [];

        foreach ($validationRules as $key => [$min, $max]) {
            if (!is_numeric($values[$key])) {
                $errors[] = $key . ' debe ser numérico.';
                continue;
            }

            $num = (float) $values[$key];
            if ($num < $min || $num > $max) {
                $errors[] = $key . ' debe estar entre ' . $min . ' y ' . $max . '.';
            }
        }

        if (empty($errors)) {
            $settingsModel->upsertMany($values);
            header('Location: dashboard.php?message=' . urlencode('Balance actualizado correctamente.') . '&type=success');
            exit;
        }

        $message = implode(' ', $errors);
        $messageType = 'error';
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = (string) $_GET['message'];
    $messageType = (string) $_GET['type'];
}

$current = array_merge($defaults, $settingsModel->getMany($keys));

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Portal | <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
</head>
<body class="lifequest-app">
    <main class="lq-main crud-main" style="max-width: 1000px; margin: 0 auto;">
        <header class="crud-hero" style="display:flex; justify-content:space-between; gap:12px; align-items:center;">
            <div>
                <h1>Admin Portal</h1>
                <p>Panel privado para balance y economía.</p>
            </div>
            <div>
                <span style="margin-right:10px;">Usuario: <?= e($adminUsername) ?></span>
                <a class="btn btn-secondary" href="logout.php">Salir</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="lq-crud-layout">
            <article class="lq-form-panel">
                <div class="lq-panel-header">
                    <h2>Recompensas automáticas</h2>
                </div>
                <form method="POST" class="lq-form">
                    <input type="hidden" name="action" value="save">

                    <div class="lq-form-row">
                        <label>Points por XP
                            <input type="number" step="0.01" min="0.1" max="2" name="REWARD_POINTS_PER_XP" value="<?= e((string) $current['REWARD_POINTS_PER_XP']) ?>">
                        </label>
                        <label>Base XP hábito
                            <input type="number" min="1" max="100" name="REWARD_HABIT_BASE_XP" value="<?= e((string) $current['REWARD_HABIT_BASE_XP']) ?>">
                        </label>
                    </div>

                    <div class="lq-form-row">
                        <label>Base XP misión
                            <input type="number" min="1" max="120" name="REWARD_TASK_BASE_XP" value="<?= e((string) $current['REWARD_TASK_BASE_XP']) ?>">
                        </label>
                        <label>Goal diario
                            <input type="number" min="1" max="300" name="REWARD_GOAL_BASE_XP_DAILY" value="<?= e((string) $current['REWARD_GOAL_BASE_XP_DAILY']) ?>">
                        </label>
                    </div>

                    <div class="lq-form-row">
                        <label>Goal semanal
                            <input type="number" min="1" max="300" name="REWARD_GOAL_BASE_XP_WEEKLY" value="<?= e((string) $current['REWARD_GOAL_BASE_XP_WEEKLY']) ?>">
                        </label>
                        <label>Goal mensual
                            <input type="number" min="1" max="400" name="REWARD_GOAL_BASE_XP_MONTHLY" value="<?= e((string) $current['REWARD_GOAL_BASE_XP_MONTHLY']) ?>">
                        </label>
                    </div>

                    <div class="lq-form-row">
                        <label>Goal trimestral
                            <input type="number" min="1" max="500" name="REWARD_GOAL_BASE_XP_QUARTERLY" value="<?= e((string) $current['REWARD_GOAL_BASE_XP_QUARTERLY']) ?>">
                        </label>
                        <label>Goal anual
                            <input type="number" min="1" max="700" name="REWARD_GOAL_BASE_XP_YEARLY" value="<?= e((string) $current['REWARD_GOAL_BASE_XP_YEARLY']) ?>">
                        </label>
                    </div>

                    <div class="lq-form-row">
                        <label>Goal futuro
                            <input type="number" min="1" max="900" name="REWARD_GOAL_BASE_XP_FUTURE" value="<?= e((string) $current['REWARD_GOAL_BASE_XP_FUTURE']) ?>">
                        </label>
                        <label>Multiplicador indulgencia repetida
                            <input type="number" step="0.01" min="1" max="3" name="INDULGENCE_REPEAT_COST_MULTIPLIER" value="<?= e((string) $current['INDULGENCE_REPEAT_COST_MULTIPLIER']) ?>">
                        </label>
                    </div>

                    <div class="lq-form-row">
                        <label>Multiplicador precio cosmético
                            <input type="number" step="0.01" min="0.1" max="3" name="COSMETIC_PRICE_MULTIPLIER" value="<?= e((string) $current['COSMETIC_PRICE_MULTIPLIER']) ?>">
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary full">Guardar balance</button>
                </form>

                <form method="POST" style="margin-top:10px;">
                    <input type="hidden" name="action" value="reset">
                    <button type="submit" class="btn btn-secondary full">Resetear a defaults de config</button>
                </form>

                <hr style="margin: 24px 0; border: 0; border-top: 1px solid rgba(255,255,255,0.15);">

                <div class="lq-panel-header">
                    <h2>Seguridad del admin</h2>
                </div>
                <form method="POST" class="lq-form" autocomplete="off">
                    <input type="hidden" name="action" value="change_password">

                    <div class="lq-form-row">
                        <label>Contraseña actual
                            <input type="password" name="current_password" required autocomplete="current-password">
                        </label>
                        <label>Nueva contraseña
                            <input type="password" name="new_password" minlength="<?= (int) (defined('ADMIN_PORTAL_PASSWORD_MIN_LENGTH') ? ADMIN_PORTAL_PASSWORD_MIN_LENGTH : 12) ?>" required autocomplete="new-password">
                        </label>
                    </div>

                    <div class="lq-form-row">
                        <label>Confirmar nueva contraseña
                            <input type="password" name="new_password_confirm" minlength="<?= (int) (defined('ADMIN_PORTAL_PASSWORD_MIN_LENGTH') ? ADMIN_PORTAL_PASSWORD_MIN_LENGTH : 12) ?>" required autocomplete="new-password">
                        </label>
                    </div>

                    <p style="margin:0 0 12px; font-size:.9rem; opacity:.85;">Requisitos: minimo <?= (int) (defined('ADMIN_PORTAL_PASSWORD_MIN_LENGTH') ? ADMIN_PORTAL_PASSWORD_MIN_LENGTH : 12) ?> caracteres, con mayuscula, minuscula, numero y simbolo.</p>

                    <button type="submit" class="btn btn-primary full">Actualizar contraseña admin</button>
                </form>
            </article>
        </section>
    </main>
</body>
</html>
