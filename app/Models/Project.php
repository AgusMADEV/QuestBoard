<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class Project
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getConnection();
    }

    public function getAllByUser(int $userId): array
    {
        $sql = "SELECT projects.*, 
                       goals.title AS goal_title,
                       life_areas.name AS area_name, 
                       life_areas.color AS area_color, 
                       life_areas.icon AS area_icon
                FROM projects
                LEFT JOIN goals ON projects.goal_id = goals.id
                LEFT JOIN life_areas ON projects.area_id = life_areas.id
                WHERE projects.user_id = :user_id
                ORDER BY projects.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getActiveByUser(int $userId, int $limit = 5): array
    {
        $sql = "SELECT projects.*, 
                       goals.title AS goal_title,
                       life_areas.name AS area_name, 
                       life_areas.color AS area_color, 
                       life_areas.icon AS area_icon
                FROM projects
                LEFT JOIN goals ON projects.goal_id = goals.id
                LEFT JOIN life_areas ON projects.area_id = life_areas.id
                WHERE projects.user_id = :user_id AND projects.status = 'active'
                ORDER BY projects.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT * FROM projects 
                WHERE id = :id AND user_id = :user_id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId
        ]);

        $project = $stmt->fetch();
        return $project ?: null;
    }

    public function create(int $userId, array $data): bool
    {
        $sql = "INSERT INTO projects (user_id, goal_id, area_id, title, description, status, progress, start_date, due_date)
                VALUES (:user_id, :goal_id, :area_id, :title, :description, :status, :progress, :start_date, :due_date)";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute(['user_id' => $userId] + $data);

        if ($ok && $data['goal_id'] !== null) {
            $this->refreshGoalProgress((int) $data['goal_id']);
        }

        return $ok;
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $previous = $this->findByIdAndUser($id, $userId);

        $sql = "UPDATE projects 
                SET goal_id = :goal_id,
                    area_id = :area_id,
                    title = :title,
                    description = :description,
                    status = :status,
                    progress = :progress,
                    start_date = :start_date,
                    due_date = :due_date
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute(['id' => $id, 'user_id' => $userId] + $data);

        if ($ok) {
            $previousGoalId = isset($previous['goal_id']) ? (int) $previous['goal_id'] : null;
            $newGoalId = $data['goal_id'] !== null ? (int) $data['goal_id'] : null;

            if ($previousGoalId !== null) {
                $this->refreshGoalProgress($previousGoalId);
            }

            if ($newGoalId !== null && $newGoalId !== $previousGoalId) {
                $this->refreshGoalProgress($newGoalId);
            }
        }

        return $ok;
    }

    public function delete(int $id, int $userId): bool
    {
        $project = $this->findByIdAndUser($id, $userId);

        $sql = "DELETE FROM projects 
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            'id' => $id,
            'user_id' => $userId
        ]);

        if ($ok && isset($project['goal_id']) && $project['goal_id'] !== null) {
            $this->refreshGoalProgress((int) $project['goal_id']);
        }

        return $ok;
    }

    private function refreshGoalProgress(int $goalId): void
    {
        $projectStatsStmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(AVG(progress), 0) AS avg_progress
             FROM projects
             WHERE goal_id = :goal_id
               AND status <> 'cancelled'"
        );
        $projectStatsStmt->execute(['goal_id' => $goalId]);
        $projectStats = $projectStatsStmt->fetch();

        $projectTotal = (int) ($projectStats['total'] ?? 0);

        if ($projectTotal > 0) {
            $progress = (int) round((float) ($projectStats['avg_progress'] ?? 0));
        } else {
            $taskStatsStmt = $this->db->prepare(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
                 FROM tasks
                 WHERE goal_id = :goal_id
                   AND status <> 'cancelled'"
            );
            $taskStatsStmt->execute(['goal_id' => $goalId]);
            $taskStats = $taskStatsStmt->fetch();

            $taskTotal = (int) ($taskStats['total'] ?? 0);
            $taskCompleted = (int) ($taskStats['completed'] ?? 0);
            $progress = $taskTotal > 0 ? (int) round(($taskCompleted / $taskTotal) * 100) : 0;
        }

        $update = $this->db->prepare(
            "UPDATE goals
             SET progress = :progress,
                 status = CASE
                    WHEN :progress_completed = 100 THEN 'completed'
                    WHEN status = 'completed' AND :progress_not_completed < 100 THEN 'in_progress'
                    WHEN status = 'not_started' AND :progress_started > 0 THEN 'in_progress'
                    ELSE status
                 END
             WHERE id = :goal_id"
        );

        $update->execute([
            'progress' => $progress,
            'progress_completed' => $progress,
            'progress_not_completed' => $progress,
            'progress_started' => $progress,
            'goal_id' => $goalId,
        ]);
    }
}
