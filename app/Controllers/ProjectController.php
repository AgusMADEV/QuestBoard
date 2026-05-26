<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Project.php';

final class ProjectController
{
    private Project $projectModel;

    public function __construct()
    {
        $this->projectModel = new Project();
    }

    public function index(int $userId): array
    {
        return $this->projectModel->getAllByUser($userId);
    }

    public function store(int $userId, array $data): array
    {
        $clean = $this->validate($userId, $data);
        if (!$clean['success']) return $clean;

        $ok = $this->projectModel->create($userId, $clean['data']);
        return [
            'success' => $ok,
            'message' => $ok ? 'Proyecto creado correctamente.' : 'No se pudo crear el proyecto.'
        ];
    }

    public function update(int $userId, array $data): array
    {
        $id = (int)($data['id'] ?? 0);

        if ($id <= 0 || !$this->projectModel->findByIdAndUser($id, $userId)) {
            return [
                'success' => false,
                'message' => 'Proyecto no válido.'
            ];
        }

        $clean = $this->validate($userId, $data);
        if (!$clean['success']) return $clean;

        $ok = $this->projectModel->update($id, $userId, $clean['data']);
        return [
            'success' => $ok,
            'message' => $ok ? 'Proyecto actualizado correctamente.' : 'No se pudo actualizar el proyecto.'
        ];
    }

    public function destroy(int $userId, int $id): array
    {
        if ($id <= 0 || !$this->projectModel->findByIdAndUser($id, $userId)) {
            return [
                'success' => false,
                'message' => 'Proyecto no válido.'
            ];
        }

        $ok = $this->projectModel->delete($id, $userId);
        return [
            'success' => $ok,
            'message' => $ok ? 'Proyecto eliminado correctamente.' : 'No se pudo eliminar el proyecto.'
        ];
    }

    private function validate(int $userId, array $data): array
    {
        $title = trim($data['title'] ?? '');
        if ($title === '') {
            return [
                'success' => false,
                'message' => 'El título del proyecto es obligatorio.',
                'errors' => ['title' => 'El título es obligatorio.'],
            ];
        }

        if (mb_strlen($title) > 150) {
            return [
                'success' => false,
                'message' => 'El título no puede superar los 150 caracteres.',
                'errors' => ['title' => 'Máximo 150 caracteres.'],
            ];
        }

        $goalId = ((int)($data['goal_id'] ?? 0)) > 0 ? (int)$data['goal_id'] : null;
        $areaId = ((int)($data['area_id'] ?? 0)) > 0 ? (int)$data['area_id'] : null;

        // Validar que la meta pertenece al usuario si se especificó
        if ($goalId !== null) {
            require_once __DIR__ . '/../Models/Goal.php';
            $goalModel = new Goal();
            if (!$goalModel->findByIdAndUser($goalId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'La meta seleccionada no existe o no pertenece a tu usuario.',
                    'errors' => ['goal_id' => 'Meta no válida para tu usuario.'],
                ];
            }
        }

        // Validar que el área pertenece al usuario si se especificó
        if ($areaId !== null) {
            require_once __DIR__ . '/../Models/LifeArea.php';
            $lifeAreaModel = new LifeArea();
            if (!$lifeAreaModel->findByIdAndUser($areaId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'El área seleccionada no existe o no pertenece a tu usuario.',
                    'errors' => ['area_id' => 'Área no válida para tu usuario.'],
                ];
            }
        }

        $allowedStatuses = ['active', 'completed', 'paused', 'cancelled'];
        $status = in_array(($data['status'] ?? ''), $allowedStatuses, true) ? $data['status'] : 'active';

        return [
            'success' => true,
            'data' => [
                'goal_id' => $goalId,
                'area_id' => $areaId,
                'title' => $title,
                'description' => trim($data['description'] ?? '') ?: null,
                'status' => $status,
                'progress' => max(0, min(100, (int)($data['progress'] ?? 0))),
                'start_date' => trim($data['start_date'] ?? '') ?: null,
                'due_date' => trim($data['due_date'] ?? '') ?: null,
            ]
        ];
    }
}
