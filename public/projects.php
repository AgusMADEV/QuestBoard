<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/ProjectController.php';
require_once __DIR__ . '/../app/Models/Project.php';
require_once __DIR__ . '/../app/Models/Goal.php';
require_once __DIR__ . '/../app/Models/LifeArea.php';

AuthController::requireAuth();

$userId = (int) $_SESSION['user_id'];
$controller = new ProjectController();
$projectModel = new Project();
$goalModel = new Goal();
$lifeAreaModel = new LifeArea();
$message = null;
$messageType = null;
$editingProject = null;

if (isset($_GET['edit'])) {
    $editingProject = $projectModel->findByIdAndUser((int) $_GET['edit'], $userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') $result = $controller->store($userId, $_POST);
    elseif ($action === 'update') $result = $controller->update($userId, $_POST);
    elseif ($action === 'delete') $result = $controller->destroy($userId, (int)($_POST['id'] ?? 0));
    else $result = ['success' => false, 'message' => 'Acción no válida.'];

    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        header('Location: projects.php?message=' . urlencode($message) . '&type=' . $messageType);
        exit;
    }
}

if (isset($_GET['message'], $_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}

$projects = $controller->index($userId);
$goals = $goalModel->getAllByUser($userId);
$areas = $lifeAreaModel->getAllByUser($userId);

function e(string|null $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function selected(mixed $a, mixed $b): string { return (string)$a === (string)$b ? 'selected' : ''; }
function activeNav(string $page): string { return basename($_SERVER['PHP_SELF']) === $page ? 'active' : ''; }
function statusLabel(string $status): string { return ['active'=>'Activo','completed'=>'Completado','paused'=>'Pausado','cancelled'=>'Cancelado'][$status] ?? $status; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proyectos | <?= APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <div class="sidebar-brand">QuestBoard</div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= activeNav('dashboard.php') ?>">Inicio</a>
            <a href="areas.php" class="<?= activeNav('areas.php') ?>">Áreas</a>
            <a href="goals.php" class="<?= activeNav('goals.php') ?>">Metas</a>
            <a href="projects.php" class="<?= activeNav('projects.php') ?>">Proyectos</a>
            <a href="#">Tareas</a>
            <a href="#">Hábitos</a>
            <a href="#">Stats</a>
        </nav>
        <a href="logout.php" class="logout">Cerrar sesión</a>
    </aside>

    <main class="dashboard">
        <header class="dashboard-header">
            <div>
                <p class="eyebrow">Organización y ejecución</p>
                <h1>Proyectos</h1>
                <p class="muted">Divide tus metas en proyectos ejecutables. Cada proyecto contiene tareas específicas.</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Volver al dashboard</a>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="areas-layout">
            <article class="card">
                <div class="card-header">
                    <h2><?= $editingProject ? 'Editar proyecto' : 'Crear nuevo proyecto' ?></h2>
                    <?php if ($editingProject): ?><a href="projects.php">Cancelar</a><?php endif; ?>
                </div>

                <form method="POST" class="form compact-form">
                    <input type="hidden" name="action" value="<?= $editingProject ? 'update' : 'create' ?>">
                    <?php if ($editingProject): ?><input type="hidden" name="id" value="<?= (int)$editingProject['id'] ?>"><?php endif; ?>

                    <label>Título
                        <input type="text" name="title" placeholder="Ej: Desarrollar módulo de usuarios" value="<?= e($editingProject['title'] ?? '') ?>" required>
                    </label>

                    <label>Descripción
                        <textarea name="description" rows="3" placeholder="Describe el alcance y entregables del proyecto."><?= e($editingProject['description'] ?? '') ?></textarea>
                    </label>

                    <label>Meta relacionada
                        <select name="goal_id">
                            <option value="">Sin meta</option>
                            <?php foreach ($goals as $goal): ?>
                                <option value="<?= (int)$goal['id'] ?>" <?= selected($editingProject['goal_id'] ?? '', $goal['id']) ?>>
                                    <?= e($goal['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Área de vida
                        <select name="area_id">
                            <option value="">Sin área</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= (int)$area['id'] ?>" <?= selected($editingProject['area_id'] ?? '', $area['id']) ?>>
                                    <?= e(($area['icon'] ? $area['icon'] . ' ' : '') . $area['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="form-row">
                        <label>Estado
                            <select name="status">
                                <option value="active" <?= selected($editingProject['status'] ?? 'active', 'active') ?>>Activo</option>
                                <option value="paused" <?= selected($editingProject['status'] ?? 'active', 'paused') ?>>Pausado</option>
                                <option value="completed" <?= selected($editingProject['status'] ?? 'active', 'completed') ?>>Completado</option>
                                <option value="cancelled" <?= selected($editingProject['status'] ?? 'active', 'cancelled') ?>>Cancelado</option>
                            </select>
                        </label>
                        <label>Progreso %
                            <input type="number" name="progress" min="0" max="100" value="<?= e((string)($editingProject['progress'] ?? 0)) ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>Fecha inicio <input type="date" name="start_date" value="<?= e($editingProject['start_date'] ?? '') ?>"></label>
                        <label>Fecha límite <input type="date" name="due_date" value="<?= e($editingProject['due_date'] ?? '') ?>"></label>
                    </div>

                    <button type="submit" class="btn btn-primary full"><?= $editingProject ? 'Guardar cambios' : 'Crear proyecto' ?></button>
                </form>
            </article>

            <section class="areas-list">
                <?php if (empty($projects)): ?>
                    <article class="card empty-state">
                        <h2>No hay proyectos todavía</h2>
                        <p>Crea tu primer proyecto y conecta tus metas con acciones concretas.</p>
                    </article>
                <?php endif; ?>

                <?php foreach ($projects as $project): ?>
                    <article class="goal-card">
                        <div class="goal-top">
                            <div class="goal-title-group">
                                <h2><?= e($project['title']) ?></h2>
                                <p><?= e($project['description'] ?: 'Sin descripción.') ?></p>
                            </div>
                            <div class="goal-badges">
                                <span class="badge badge-status"><?= statusLabel($project['status']) ?></span>
                            </div>
                        </div>

                        <div class="goal-progress-row">
                            <div class="goal-progress-info"><span>Progreso</span><span><?= (int)$project['progress'] ?>%</span></div>
                            <div class="progress"><div style="width: <?= (int)$project['progress'] ?>%"></div></div>
                        </div>

                        <div class="goal-footer">
                            <div class="goal-meta">
                                <?php if (!empty($project['goal_title'])): ?>
                                    <span class="badge">🎯 <?= e($project['goal_title']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($project['area_name'])): ?>
                                    <span class="badge"><?= e(($project['area_icon'] ? $project['area_icon'] . ' ' : '') . $project['area_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($project['due_date'])): ?>
                                    <span class="badge">📅 <?= e(date('d/m/Y', strtotime($project['due_date']))) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="goal-actions">
                                <a href="projects.php?edit=<?= (int)$project['id'] ?>" class="btn btn-secondary">Editar</a>
                                <form method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este proyecto?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$project['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        </section>
    </main>
</body>
</html>
