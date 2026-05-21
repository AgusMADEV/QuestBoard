<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/GoalController.php';
require_once __DIR__ . '/../app/Controllers/ProjectController.php';
require_once __DIR__ . '/../app/Controllers/TaskController.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/Task.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';
require_once __DIR__ . '/../app/Models/User.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$userModel = new User();
$user = $userModel->findById($userId);

if (!$user) {
    AuthController::logout();
    header('Location: login.php');
    exit;
}

$goalController = new GoalController();
$projectController = new ProjectController();
$taskController = new TaskController();

$goalModel = new Goal();
$projectModel = new Project();
$taskModel = new Task();
$lifeAreaModel = new LifeArea();

$validSections = ['goals', 'projects', 'tasks'];
$sectionInput = $_GET['section'] ?? 'goals';
$section = in_array($sectionInput, $validSections, true) ? $sectionInput : 'goals';

$message = null;
$messageType = null;

$editingGoal = null;
$editingProject = null;
$editingTask = null;

if ($section === 'goals' && isset($_GET['edit_goal'])) {
    $editingGoal = $goalModel->findByIdAndUser((int) $_GET['edit_goal'], $userId);
}
if ($section === 'projects' && isset($_GET['edit_project'])) {
    $editingProject = $projectModel->findByIdAndUser((int) $_GET['edit_project'], $userId);
}
if ($section === 'tasks' && isset($_GET['edit_task'])) {
    $editingTask = $taskModel->findByIdAndUser((int) $_GET['edit_task'], $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entityInput = $_POST['entity'] ?? $section;
    $entity = in_array($entityInput, $validSections, true) ? $entityInput : 'goals';
    $action = $_POST['action'] ?? '';

    if ($entity === 'goals') {
        if ($action === 'create') {
            $result = $goalController->store($userId, $_POST);
        } elseif ($action === 'update') {
            $result = $goalController->update($userId, $_POST);
        } elseif ($action === 'delete') {
            $result = $goalController->destroy($userId, (int) ($_POST['id'] ?? 0));
        } else {
            $result = ['success' => false, 'message' => 'Accion no valida.'];
        }
    } elseif ($entity === 'projects') {
        if ($action === 'create') {
            $result = $projectController->store($userId, $_POST);
        } elseif ($action === 'update') {
            $result = $projectController->update($userId, $_POST);
        } elseif ($action === 'delete') {
            $result = $projectController->destroy($userId, (int) ($_POST['id'] ?? 0));
        } else {
            $result = ['success' => false, 'message' => 'Accion no valida.'];
        }
    } else {
        if ($action === 'create') {
            $result = $taskController->store($userId, $_POST);
        } elseif ($action === 'update') {
            $result = $taskController->update($userId, $_POST);
        } elseif ($action === 'delete') {
            $result = $taskController->destroy($userId, (int) ($_POST['id'] ?? 0));
        } elseif ($action === 'complete') {
            $result = $taskController->complete($userId, (int) ($_POST['id'] ?? 0));
        } else {
            $result = ['success' => false, 'message' => 'Accion no valida.'];
        }
    }

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        header('Location: goals.php?section=' . $entity . '&message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }

    $section = $entity;
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$goals = $goalController->index($userId);
$projects = $projectController->index($userId);
$tasks = $taskController->index($userId);
$areas = $lifeAreaModel->getAllByUser($userId);

$completedCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'completed'));
$pendingCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'pending'));
$progressCount = count(array_filter($tasks, static fn($task) => $task['status'] === 'in_progress'));

$searchPlaceholder = [
    'goals' => 'Buscar metas...',
    'projects' => 'Buscar retos...',
    'tasks' => 'Buscar misiones...',
][$section];

$heroTitle = [
    'goals' => 'Metas',
    'projects' => 'Retos',
    'tasks' => 'Misiones',
][$section];

$heroEyebrow = [
    'goals' => 'Direccion y progreso',
    'projects' => 'Bloques de avance',
    'tasks' => 'Accion diaria',
][$section];

$heroDescription = [
    'goals' => 'Define lo que quieres conseguir y mide tu avance con progreso, prioridad y recompensas.',
    'projects' => 'Convierte tus metas en retos accionables para mantener enfoque y claridad.',
    'tasks' => 'Completa misiones diarias para sumar XP, LifeCoins y progreso real.',
][$section];

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? 'selected' : '';
}

function shortText(string|null $value, int $limit = 42): string
{
    $value = trim((string) $value);
    return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit - 1) . '...';
}

function goalTypeLabel(string $type): string
{
    return ['daily' => 'Diaria', 'weekly' => 'Semanal', 'monthly' => 'Mensual', 'quarterly' => 'Trimestral', 'yearly' => 'Anual', 'future' => 'Futuro'][$type] ?? $type;
}

function priorityLabel(string $priority): string
{
    return ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Critica'][$priority] ?? $priority;
}

function goalStatusLabel(string $status): string
{
    return ['not_started' => 'No iniciada', 'in_progress' => 'En progreso', 'paused' => 'Pausada', 'completed' => 'Completada', 'cancelled' => 'Cancelada'][$status] ?? $status;
}

function projectStatusLabel(string $status): string
{
    return ['active' => 'Activa', 'completed' => 'Completada', 'paused' => 'Pausada', 'cancelled' => 'Cancelada'][$status] ?? $status;
}

function taskStatusLabel(string $status): string
{
    return ['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'][$status] ?? $status;
}

function statusClass(string $status): string
{
    return [
        'not_started' => 'blue',
        'in_progress' => 'green',
        'paused' => 'orange',
        'completed' => 'purple',
        'cancelled' => 'red',
        'active' => 'green',
        'pending' => 'blue',
    ][$status] ?? 'blue';
}

function priorityClass(string $priority): string
{
    return ['low' => 'green', 'medium' => 'orange', 'high' => 'red', 'critical' => 'red'][$priority] ?? 'blue';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Metas | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/modules/crud.css">
    <link rel="stylesheet" href="../assets/css/modules/tasks.css">
    <link rel="stylesheet" href="../assets/css/modules/metas.css">
</head>
<body class="lifequest-app">
    <aside class="lq-sidebar">
        <a href="dashboard.php" class="lq-logo"><span>Life<span>Quest</span><i>✦</i></span></a>
        <nav class="lq-nav">
            <a href="dashboard.php"><span>🏠</span>Inicio</a>
            <a href="goals.php" class="active"><span>🎯</span>Metas</a>
            <a href="areas.php"><span>🧩</span>Áreas</a>
            <a href="habits.php"><span>💚</span>Hábitos</a>
            <a href="#"><span>🛍️</span>Tienda</a>
            <a href="#"><span>📊</span>Progreso</a>
        </nav>

        <section class="lq-sidebar-card unlock">
            <div>
                <strong>Tu centro de avance</strong>
                <p>Todo tu sistema en un solo modulo: metas, retos y misiones.</p>
                <a href="goals.php?section=tasks" class="mini-btn">Ir a misiones</a>
            </div>
            <span class="bag">🧠</span>
        </section>

        <section class="lq-user-mini">
            <div class="mini-avatar"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
            <div>
                <strong><?= e(shortText($user['name'] ?? 'Usuario', 18)) ?></strong>
                <small>Ver perfil</small>
            </div>
            <span>⌄</span>
        </section>

        <div class="lq-sidebar-bottom">
            <a href="#">⚙️</a>
            <a href="#">?</a>
            <a href="logout.php">↪</a>
        </div>
    </aside>

    <main class="lq-main">
        <header class="lq-topbar">
            <button class="icon-btn">☰</button>
            <div class="search-box">
                <span>🔎</span>
                <input type="search" placeholder="<?= e($searchPlaceholder) ?>" disabled>
                <kbd>⌘ K</kbd>
            </div>
            <div class="top-stats">
                <div class="xp-pill">
                    <span>✦</span>
                    <strong><?= number_format((int) ($user['xp'] ?? 0), 0, ',', '.') ?> XP</strong>
                    <div class="mini-progress"><i style="width: 35%"></i></div>
                    <small>Nivel <?= (int) ($user['level'] ?? 1) ?></small>
                </div>
                <div class="currency-pill coin"><span>🪙</span><strong><?= number_format((int) ($user['points'] ?? 0), 0, ',', '.') ?></strong></div>
                <div class="profile-pill">
                    <div class="mini-avatar image-like"><?= mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1)) ?></div>
                    <strong>¡Hola, <?= e(shortText($user['name'] ?? 'Usuario', 12)) ?>! 👋</strong>
                </div>
            </div>
        </header>

        <section class="lq-page-shell metas-shell">
            <header class="lq-page-hero metas-hero">
                <div>
                    <p class="eyebrow"><?= e($heroEyebrow) ?></p>
                    <h1><?= e($heroTitle) ?></h1>
                    <p><?= e($heroDescription) ?></p>
                </div>
                <div class="lq-page-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Volver al inicio</a>
                    <a href="goals.php?section=tasks" class="btn btn-primary">Abrir misiones</a>
                </div>
            </header>

            <nav class="metas-tabs" aria-label="Navegacion metas">
                <a href="goals.php?section=goals" class="<?= $section === 'goals' ? 'active' : '' ?>">Metas</a>
                <a href="goals.php?section=projects" class="<?= $section === 'projects' ? 'active' : '' ?>">Retos</a>
                <a href="goals.php?section=tasks" class="<?= $section === 'tasks' ? 'active' : '' ?>">Misiones</a>
            </nav>

            <?php if ($section === 'tasks'): ?>
                <section class="task-today-strip">
                    <div class="lq-mini-stat"><strong><?= count($tasks) ?></strong><small>Total misiones</small></div>
                    <div class="lq-mini-stat"><strong><?= $pendingCount ?></strong><small>Pendientes</small></div>
                    <div class="lq-mini-stat"><strong><?= $progressCount ?></strong><small>En progreso</small></div>
                    <div class="lq-mini-stat"><strong><?= $completedCount ?></strong><small>Completadas</small></div>
                </section>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="lq-alert <?= e($messageType) ?>"><?= e($message) ?></div>
            <?php endif; ?>

            <section class="hub-layout">
                <section>
                    <?php if ($section === 'goals'): ?>
                        <section class="lq-crud-layout">
                            <article class="lq-form-panel">
                                <div class="lq-panel-header">
                                    <div>
                                        <h2><?= $editingGoal ? 'Editar meta' : 'Nueva meta' ?></h2>
                                        <p><?= $editingGoal ? 'Ajusta el progreso y la prioridad.' : 'Crea una meta clara y medible.' ?></p>
                                    </div>
                                    <?php if ($editingGoal): ?><a href="goals.php?section=goals">Cancelar</a><?php endif; ?>
                                </div>

                                <form method="POST" class="lq-form">
                                    <input type="hidden" name="entity" value="goals">
                                    <input type="hidden" name="action" value="<?= $editingGoal ? 'update' : 'create' ?>">
                                    <?php if ($editingGoal): ?><input type="hidden" name="id" value="<?= (int) $editingGoal['id'] ?>"><?php endif; ?>

                                    <label>Titulo
                                        <input type="text" name="title" placeholder="Ej: Terminar el TFG" value="<?= e($editingGoal['title'] ?? '') ?>" required>
                                    </label>

                                    <label>Descripcion
                                        <textarea name="description" rows="3" placeholder="Describe que quieres conseguir."><?= e($editingGoal['description'] ?? '') ?></textarea>
                                    </label>

                                    <label>Area de vida
                                        <select name="area_id">
                                            <option value="">Sin area</option>
                                            <?php foreach ($areas as $area): ?>
                                                <option value="<?= (int) $area['id'] ?>" <?= selected($editingGoal['area_id'] ?? '', $area['id']) ?>>
                                                    <?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <div class="lq-form-row">
                                        <label>Tipo
                                            <select name="type">
                                                <option value="daily" <?= selected($editingGoal['type'] ?? 'monthly', 'daily') ?>>Diaria</option>
                                                <option value="weekly" <?= selected($editingGoal['type'] ?? 'monthly', 'weekly') ?>>Semanal</option>
                                                <option value="monthly" <?= selected($editingGoal['type'] ?? 'monthly', 'monthly') ?>>Mensual</option>
                                                <option value="quarterly" <?= selected($editingGoal['type'] ?? 'monthly', 'quarterly') ?>>Trimestral</option>
                                                <option value="yearly" <?= selected($editingGoal['type'] ?? 'monthly', 'yearly') ?>>Anual</option>
                                                <option value="future" <?= selected($editingGoal['type'] ?? 'monthly', 'future') ?>>Futuro</option>
                                            </select>
                                        </label>

                                        <label>Prioridad
                                            <select name="priority">
                                                <option value="low" <?= selected($editingGoal['priority'] ?? 'medium', 'low') ?>>Baja</option>
                                                <option value="medium" <?= selected($editingGoal['priority'] ?? 'medium', 'medium') ?>>Media</option>
                                                <option value="high" <?= selected($editingGoal['priority'] ?? 'medium', 'high') ?>>Alta</option>
                                                <option value="critical" <?= selected($editingGoal['priority'] ?? 'medium', 'critical') ?>>Critica</option>
                                            </select>
                                        </label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Estado
                                            <select name="status">
                                                <option value="not_started" <?= selected($editingGoal['status'] ?? 'not_started', 'not_started') ?>>No iniciada</option>
                                                <option value="in_progress" <?= selected($editingGoal['status'] ?? 'not_started', 'in_progress') ?>>En progreso</option>
                                                <option value="paused" <?= selected($editingGoal['status'] ?? 'not_started', 'paused') ?>>Pausada</option>
                                                <option value="completed" <?= selected($editingGoal['status'] ?? 'not_started', 'completed') ?>>Completada</option>
                                                <option value="cancelled" <?= selected($editingGoal['status'] ?? 'not_started', 'cancelled') ?>>Cancelada</option>
                                            </select>
                                        </label>

                                        <label>Progreso %
                                            <input type="number" name="progress" min="0" max="100" value="<?= e((string) ($editingGoal['progress'] ?? 0)) ?>">
                                        </label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Fecha inicio <input type="date" name="start_date" value="<?= e($editingGoal['start_date'] ?? '') ?>"></label>
                                        <label>Fecha limite <input type="date" name="due_date" value="<?= e($editingGoal['due_date'] ?? '') ?>"></label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Recompensa XP <input type="number" name="xp_reward" min="0" value="<?= e((string) ($editingGoal['xp_reward'] ?? 50)) ?>"></label>
                                        <label>LifeCoins <input type="number" name="points_reward" min="0" value="<?= e((string) ($editingGoal['points_reward'] ?? 25)) ?>"></label>
                                    </div>

                                    <button type="submit" class="btn btn-primary full"><?= $editingGoal ? 'Guardar cambios' : 'Crear meta' ?></button>
                                </form>
                            </article>

                            <section class="lq-list-panel">
                                <div class="lq-panel-header">
                                    <div>
                                        <h2>Tus metas</h2>
                                        <p><?= count($goals) ?> metas creadas</p>
                                    </div>
                                </div>

                                <div class="lq-list-grid">
                                    <?php if (empty($goals)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay metas todavia</h2>
                                            <p>Crea tu primera meta y luego desglosala en retos y misiones.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($goals as $goal): ?>
                                        <article class="lq-object-card">
                                            <div class="lq-object-top">
                                                <div class="lq-object-icon" style="background: <?= e($goal['area_color'] ?: '#16C79A') ?>;"><?= e($goal['area_icon'] ?: '🎯') ?></div>
                                                <div class="lq-object-title">
                                                    <h2><?= e($goal['title']) ?></h2>
                                                    <p><?= e($goal['description'] ?: 'Sin descripcion.') ?></p>
                                                </div>
                                                <div class="lq-object-badges">
                                                    <span class="lq-badge <?= priorityClass($goal['priority']) ?>"><?= priorityLabel($goal['priority']) ?></span>
                                                    <span class="lq-badge <?= statusClass($goal['status']) ?>"><?= goalStatusLabel($goal['status']) ?></span>
                                                </div>
                                            </div>

                                            <div class="lq-progress-block">
                                                <div class="lq-progress-info"><span>Progreso</span><span><?= (int) $goal['progress'] ?>%</span></div>
                                                <div class="lq-progress"><span style="width: <?= (int) $goal['progress'] ?>%"></span></div>
                                            </div>

                                            <div class="lq-object-footer">
                                                <div class="lq-object-meta">
                                                    <span class="lq-badge"><?= goalTypeLabel($goal['type']) ?></span>
                                                    <?php if (!empty($goal['area_name'])): ?>
                                                        <span class="lq-badge green"><?= e(($goal['area_icon'] ? $goal['area_icon'] . ' ' : '') . $goal['area_name']) ?></span>
                                                    <?php endif; ?>
                                                    <span class="lq-badge purple">XP +<?= (int) $goal['xp_reward'] ?></span>
                                                    <span class="lq-badge orange">LC +<?= (int) $goal['points_reward'] ?></span>
                                                </div>

                                                <div class="lq-object-actions">
                                                    <a href="goals.php?section=goals&edit_goal=<?= (int) $goal['id'] ?>" class="btn btn-secondary">Editar</a>
                                                    <form method="POST" onsubmit="return confirm('Seguro que quieres eliminar esta meta?');">
                                                        <input type="hidden" name="entity" value="goals">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= (int) $goal['id'] ?>">
                                                        <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </section>
                    <?php elseif ($section === 'projects'): ?>
                        <section class="lq-crud-layout">
                            <article class="lq-form-panel">
                                <div class="lq-panel-header">
                                    <div>
                                        <h2><?= $editingProject ? 'Editar reto' : 'Nuevo reto' ?></h2>
                                        <p><?= $editingProject ? 'Actualiza este reto.' : 'Crea un bloque conectado a una meta.' ?></p>
                                    </div>
                                    <?php if ($editingProject): ?><a href="goals.php?section=projects">Cancelar</a><?php endif; ?>
                                </div>

                                <form method="POST" class="lq-form">
                                    <input type="hidden" name="entity" value="projects">
                                    <input type="hidden" name="action" value="<?= $editingProject ? 'update' : 'create' ?>">
                                    <?php if ($editingProject): ?><input type="hidden" name="id" value="<?= (int) $editingProject['id'] ?>"><?php endif; ?>

                                    <label>Titulo
                                        <input type="text" name="title" placeholder="Ej: Lanzar modulo de habitos" value="<?= e($editingProject['title'] ?? '') ?>" required>
                                    </label>

                                    <label>Descripcion
                                        <textarea name="description" rows="3" placeholder="Describe el alcance del reto."><?= e($editingProject['description'] ?? '') ?></textarea>
                                    </label>

                                    <label>Meta relacionada
                                        <select name="goal_id">
                                            <option value="">Sin meta</option>
                                            <?php foreach ($goals as $goal): ?>
                                                <option value="<?= (int) $goal['id'] ?>" <?= selected($editingProject['goal_id'] ?? '', $goal['id']) ?>><?= e($goal['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <label>Area de vida
                                        <select name="area_id">
                                            <option value="">Sin area</option>
                                            <?php foreach ($areas as $area): ?>
                                                <option value="<?= (int) $area['id'] ?>" <?= selected($editingProject['area_id'] ?? '', $area['id']) ?>><?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <div class="lq-form-row">
                                        <label>Estado
                                            <select name="status">
                                                <option value="active" <?= selected($editingProject['status'] ?? 'active', 'active') ?>>Activa</option>
                                                <option value="paused" <?= selected($editingProject['status'] ?? 'active', 'paused') ?>>Pausada</option>
                                                <option value="completed" <?= selected($editingProject['status'] ?? 'active', 'completed') ?>>Completada</option>
                                                <option value="cancelled" <?= selected($editingProject['status'] ?? 'active', 'cancelled') ?>>Cancelada</option>
                                            </select>
                                        </label>

                                        <label>Progreso %
                                            <input type="number" name="progress" min="0" max="100" value="<?= e((string) ($editingProject['progress'] ?? 0)) ?>">
                                        </label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Fecha inicio <input type="date" name="start_date" value="<?= e($editingProject['start_date'] ?? '') ?>"></label>
                                        <label>Fecha limite <input type="date" name="due_date" value="<?= e($editingProject['due_date'] ?? '') ?>"></label>
                                    </div>

                                    <button type="submit" class="btn btn-primary full"><?= $editingProject ? 'Guardar cambios' : 'Crear reto' ?></button>
                                </form>
                            </article>

                            <section class="lq-list-panel">
                                <div class="lq-panel-header">
                                    <div>
                                        <h2>Tus retos</h2>
                                        <p><?= count($projects) ?> retos creados</p>
                                    </div>
                                </div>

                                <div class="lq-list-grid">
                                    <?php if (empty($projects)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay retos todavia</h2>
                                            <p>Crea tu primer reto para dividir metas complejas en pasos.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($projects as $project): ?>
                                        <article class="lq-object-card">
                                            <div class="lq-object-top">
                                                <div class="lq-object-icon" style="background: <?= e($project['area_color'] ?: '#16C79A') ?>;"><?= e($project['area_icon'] ?: '🚀') ?></div>
                                                <div class="lq-object-title">
                                                    <h2><?= e($project['title']) ?></h2>
                                                    <p><?= e($project['description'] ?: 'Sin descripcion.') ?></p>
                                                </div>
                                                <div class="lq-object-badges"><span class="lq-badge <?= statusClass($project['status']) ?>"><?= projectStatusLabel($project['status']) ?></span></div>
                                            </div>

                                            <div class="lq-progress-block">
                                                <div class="lq-progress-info"><span>Progreso</span><span><?= (int) $project['progress'] ?>%</span></div>
                                                <div class="lq-progress"><span style="width: <?= (int) $project['progress'] ?>%"></span></div>
                                            </div>

                                            <div class="lq-object-footer">
                                                <div class="lq-object-meta">
                                                    <?php if (!empty($project['goal_title'])): ?><span class="lq-badge blue">M <?= e(shortText($project['goal_title'], 32)) ?></span><?php endif; ?>
                                                    <?php if (!empty($project['area_name'])): ?><span class="lq-badge green"><?= e(($project['area_icon'] ? $project['area_icon'] . ' ' : '') . $project['area_name']) ?></span><?php endif; ?>
                                                    <?php if (!empty($project['due_date'])): ?><span class="lq-badge orange">F <?= e(date('d/m/Y', strtotime($project['due_date']))) ?></span><?php endif; ?>
                                                </div>

                                                <div class="lq-object-actions">
                                                    <a href="goals.php?section=projects&edit_project=<?= (int) $project['id'] ?>" class="btn btn-secondary">Editar</a>
                                                    <form method="POST" onsubmit="return confirm('Seguro que quieres eliminar este reto?');">
                                                        <input type="hidden" name="entity" value="projects">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= (int) $project['id'] ?>">
                                                        <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </section>
                    <?php else: ?>
                        <section class="lq-crud-layout">
                            <article class="lq-form-panel">
                                <div class="lq-panel-header">
                                    <div>
                                        <h2><?= $editingTask ? 'Editar mision' : 'Nueva mision' ?></h2>
                                        <p><?= $editingTask ? 'Ajusta esta tarea diaria.' : 'Define una accion concreta para hoy.' ?></p>
                                    </div>
                                    <?php if ($editingTask): ?><a href="goals.php?section=tasks">Cancelar</a><?php endif; ?>
                                </div>

                                <form method="POST" class="lq-form">
                                    <input type="hidden" name="entity" value="tasks">
                                    <input type="hidden" name="action" value="<?= $editingTask ? 'update' : 'create' ?>">
                                    <?php if ($editingTask): ?><input type="hidden" name="id" value="<?= (int) $editingTask['id'] ?>"><?php endif; ?>

                                    <label>Titulo
                                        <input type="text" name="title" placeholder="Ej: Entrenar 30 minutos" value="<?= e($editingTask['title'] ?? '') ?>" required>
                                    </label>

                                    <label>Descripcion
                                        <textarea name="description" rows="3" placeholder="Define exactamente que hay que hacer."><?= e($editingTask['description'] ?? '') ?></textarea>
                                    </label>

                                    <label>Reto
                                        <select name="project_id">
                                            <option value="">Sin reto</option>
                                            <?php foreach ($projects as $project): ?>
                                                <option value="<?= (int) $project['id'] ?>" <?= selected($editingTask['project_id'] ?? '', $project['id']) ?>><?= e($project['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <label>Meta
                                        <select name="goal_id">
                                            <option value="">Sin meta</option>
                                            <?php foreach ($goals as $goal): ?>
                                                <option value="<?= (int) $goal['id'] ?>" <?= selected($editingTask['goal_id'] ?? '', $goal['id']) ?>><?= e($goal['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <label>Area
                                        <select name="area_id">
                                            <option value="">Sin area</option>
                                            <?php foreach ($areas as $area): ?>
                                                <option value="<?= (int) $area['id'] ?>" <?= selected($editingTask['area_id'] ?? '', $area['id']) ?>><?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <div class="lq-form-row">
                                        <label>Prioridad
                                            <select name="priority">
                                                <option value="low" <?= selected($editingTask['priority'] ?? 'medium', 'low') ?>>Baja</option>
                                                <option value="medium" <?= selected($editingTask['priority'] ?? 'medium', 'medium') ?>>Media</option>
                                                <option value="high" <?= selected($editingTask['priority'] ?? 'medium', 'high') ?>>Alta</option>
                                                <option value="critical" <?= selected($editingTask['priority'] ?? 'medium', 'critical') ?>>Critica</option>
                                            </select>
                                        </label>

                                        <label>Estado
                                            <select name="status">
                                                <option value="pending" <?= selected($editingTask['status'] ?? 'pending', 'pending') ?>>Pendiente</option>
                                                <option value="in_progress" <?= selected($editingTask['status'] ?? 'pending', 'in_progress') ?>>En progreso</option>
                                                <option value="completed" <?= selected($editingTask['status'] ?? 'pending', 'completed') ?>>Completada</option>
                                                <option value="cancelled" <?= selected($editingTask['status'] ?? 'pending', 'cancelled') ?>>Cancelada</option>
                                            </select>
                                        </label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Tiempo estimado
                                            <input type="number" name="estimated_minutes" min="0" value="<?= e((string) ($editingTask['estimated_minutes'] ?? 25)) ?>">
                                        </label>
                                        <label>Fecha limite
                                            <input type="date" name="due_date" value="<?= e($editingTask['due_date'] ?? date('Y-m-d')) ?>">
                                        </label>
                                    </div>

                                    <div class="lq-form-row">
                                        <label>Recompensa XP <input type="number" name="xp_reward" min="0" value="<?= e((string) ($editingTask['xp_reward'] ?? 10)) ?>"></label>
                                        <label>LifeCoins <input type="number" name="points_reward" min="0" value="<?= e((string) ($editingTask['points_reward'] ?? 5)) ?>"></label>
                                    </div>

                                    <button type="submit" class="btn btn-primary full"><?= $editingTask ? 'Guardar cambios' : 'Crear mision' ?></button>
                                </form>
                            </article>

                            <section class="lq-list-panel">
                                <div class="lq-panel-header">
                                    <div>
                                        <h2>Tus misiones</h2>
                                        <p><?= count($tasks) ?> misiones creadas</p>
                                    </div>
                                </div>

                                <div class="lq-list-grid mission-list-like">
                                    <?php if (empty($tasks)): ?>
                                        <article class="lq-empty">
                                            <h2>No hay misiones todavia</h2>
                                            <p>Crea tu primera mision para empezar a sumar progreso diario.</p>
                                        </article>
                                    <?php endif; ?>

                                    <?php foreach ($tasks as $task): ?>
                                        <article class="mission-row-card">
                                            <div class="mission-row-main">
                                                <div class="mission-row-title">
                                                    <strong><?= e($task['title']) ?></strong>
                                                    <small><?= e($task['description'] ?: 'Sin descripcion.') ?></small>
                                                </div>
                                                <div class="mission-row-progress">
                                                    <div class="lq-progress"><span style="width: <?= $task['status'] === 'completed' ? '100' : ($task['status'] === 'in_progress' ? '55' : '20') ?>%"></span></div>
                                                </div>
                                                <div class="mission-row-meta">
                                                    <span class="lq-badge <?= priorityClass($task['priority']) ?>"><?= priorityLabel($task['priority']) ?></span>
                                                    <span class="lq-badge <?= statusClass($task['status']) ?>"><?= taskStatusLabel($task['status']) ?></span>
                                                </div>
                                            </div>
                                            <div class="mission-row-actions">
                                                <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="entity" value="tasks">
                                                        <input type="hidden" name="action" value="complete">
                                                        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                                        <button type="submit" class="btn lq-task-complete">Completar</button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="goals.php?section=tasks&edit_task=<?= (int) $task['id'] ?>" class="btn btn-secondary">Editar</a>
                                                <form method="POST" onsubmit="return confirm('Seguro que quieres eliminar esta mision?');">
                                                    <input type="hidden" name="entity" value="tasks">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                                    <button type="submit" class="btn lq-btn-danger">Eliminar</button>
                                                </form>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </section>
                    <?php endif; ?>
                </section>

                <aside class="metas-side-summary">
                    <article>
                        <small>Nivel</small>
                        <strong><?= (int) ($user['level'] ?? 1) ?></strong>
                        <span><?= number_format((int) ($user['xp'] ?? 0), 0, ',', '.') ?> XP</span>
                    </article>
                    <article>
                        <small>Racha actual</small>
                        <strong><?= (int) ($user['current_streak'] ?? 0) ?> dias</strong>
                        <span>Constancia diaria</span>
                    </article>
                    <article>
                        <small>Resumen rapido</small>
                        <strong><?= count($goals) ?> metas</strong>
                        <span><?= count($projects) ?> retos, <?= count($tasks) ?> misiones</span>
                    </article>
                </aside>
            </section>
        </section>
    </main>
</body>
</html>
