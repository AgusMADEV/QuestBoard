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
        return $stmt->execute(['user_id' => $userId] + $data);
    }

    public function update(int $id, int $userId, array $data): bool
    {
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
        return $stmt->execute(['id' => $id, 'user_id' => $userId] + $data);
    }

    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM projects 
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'user_id' => $userId
        ]);
    }
}
