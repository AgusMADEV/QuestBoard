<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class Badge
{
    private PDO $db;
    private ?bool $tableReady = null;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getConnection();
    }

    public function syncAndGetByUser(int $userId, array $metrics): array
    {
        return $this->syncWithMetrics($userId, $metrics)['badges'];
    }

    public function syncAndCollectNewlyUnlocked(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $metrics = $this->getMetricsByUser($userId);
        $result = $this->syncWithMetrics($userId, $metrics);

        return $result['newly_unlocked'];
    }

    private function syncWithMetrics(int $userId, array $metrics): array
    {
        $catalog = $this->getCatalog();
        if ($userId <= 0 || empty($catalog)) {
            return [
                'badges' => [],
                'newly_unlocked' => [],
            ];
        }

        $earnedMap = $this->getEarnedMap($userId);
        $newlyUnlockedCodes = [];

        foreach ($catalog as $badge) {
            $code = (string) ($badge['code'] ?? '');
            $metric = (string) ($badge['metric'] ?? '');
            $target = max(1, (int) ($badge['target'] ?? 1));
            $value = max(0, (int) ($metrics[$metric] ?? 0));

            if ($code === '' || isset($earnedMap[$code])) {
                continue;
            }

            if ($value >= $target) {
                $this->earnBadge($userId, $code, $value);
                $earnedMap[$code] = [
                    'earned_at' => date('Y-m-d H:i:s'),
                    'metric_value' => $value,
                ];
                $newlyUnlockedCodes[$code] = true;
            }
        }

        $result = [];
        $newlyUnlockedBadges = [];

        foreach ($catalog as $badge) {
            $code = (string) ($badge['code'] ?? '');
            $metric = (string) ($badge['metric'] ?? '');
            $target = max(1, (int) ($badge['target'] ?? 1));
            $value = max(0, (int) ($metrics[$metric] ?? 0));
            $earned = $earnedMap[$code] ?? null;
            $progressValue = $earned !== null
                ? max($target, (int) ($earned['metric_value'] ?? $value))
                : min($value, $target);

            $item = [
                'code' => $code,
                'title' => (string) ($badge['title'] ?? ''),
                'icon' => (string) ($badge['icon'] ?? '🏅'),
                'tone' => (string) ($badge['tone'] ?? 'tone-gray'),
                'description' => (string) ($badge['description'] ?? ''),
                'metric_label' => (string) ($badge['metric_label'] ?? ''),
                'target' => $target,
                'progress_value' => $progressValue,
                'progress_percent' => min(100, (int) round(($progressValue / max(1, $target)) * 100)),
                'unlocked' => $earned !== null,
                'earned_at' => $earned['earned_at'] ?? null,
                'just_unlocked' => isset($newlyUnlockedCodes[$code]),
            ];

            $result[] = $item;

            if (!empty($item['just_unlocked'])) {
                $newlyUnlockedBadges[] = $item;
            }
        }

        return [
            'badges' => $result,
            'newly_unlocked' => $newlyUnlockedBadges,
        ];
    }

    private function getMetricsByUser(int $userId): array
    {
        $completedTasks = 0;
        $completedHabitChecks = 0;
        $bestStreak = 0;
        $focusedMinutes = 0;
        $level = 1;

        $taskStatsStmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
                SUM(CASE WHEN status = 'completed' THEN GREATEST(estimated_minutes, 0) ELSE 0 END) AS focused_minutes
             FROM tasks
             WHERE user_id = :user_id"
        );
        $taskStatsStmt->execute(['user_id' => $userId]);
        $taskStats = $taskStatsStmt->fetch() ?: [];
        $completedTasks = max(0, (int) ($taskStats['completed_tasks'] ?? 0));
        $focusedMinutes = max(0, (int) ($taskStats['focused_minutes'] ?? 0));

        $habitChecksStmt = $this->db->prepare(
            'SELECT COUNT(*) AS total
             FROM habit_logs
             WHERE user_id = :user_id'
        );
        $habitChecksStmt->execute(['user_id' => $userId]);
        $habitChecks = $habitChecksStmt->fetch() ?: [];
        $completedHabitChecks = max(0, (int) ($habitChecks['total'] ?? 0));

        $streakStmt = $this->db->prepare(
            'SELECT
                COALESCE(MAX(best_streak), 0) AS best_streak,
                COALESCE(MAX(current_streak), 0) AS current_streak
             FROM habits
             WHERE user_id = :user_id'
        );
        $streakStmt->execute(['user_id' => $userId]);
        $streakData = $streakStmt->fetch() ?: [];
        $bestStreak = max(
            0,
            (int) ($streakData['best_streak'] ?? 0),
            (int) ($streakData['current_streak'] ?? 0)
        );

        $userStmt = $this->db->prepare(
            'SELECT level
             FROM users
             WHERE id = :user_id
             LIMIT 1'
        );
        $userStmt->execute(['user_id' => $userId]);
        $userRow = $userStmt->fetch() ?: [];
        $level = max(1, (int) ($userRow['level'] ?? 1));

        return [
            'completed_tasks' => $completedTasks,
            'completed_habit_checks' => $completedHabitChecks,
            'best_streak' => $bestStreak,
            'focused_minutes' => $focusedMinutes,
            'level' => $level,
        ];
    }

    private function getCatalog(): array
    {
        return [
            [
                'code' => 'first_step',
                'title' => 'Primer paso',
                'icon' => '👟',
                'tone' => 'tone-green',
                'metric' => 'completed_tasks',
                'metric_label' => 'misiones completadas',
                'target' => 1,
                'description' => 'Completa tu primera mision.',
            ],
            [
                'code' => 'habit_rookie',
                'title' => 'Constancia',
                'icon' => '🔥',
                'tone' => 'tone-orange',
                'metric' => 'best_streak',
                'metric_label' => 'dias de racha',
                'target' => 7,
                'description' => 'Alcanza una racha de 7 dias.',
            ],
            [
                'code' => 'habit_scholar',
                'title' => 'Estudioso',
                'icon' => '📘',
                'tone' => 'tone-purple',
                'metric' => 'completed_habit_checks',
                'metric_label' => 'habitos completados',
                'target' => 30,
                'description' => 'Acumula 30 checks de habitos.',
            ],
            [
                'code' => 'focus_master',
                'title' => 'Enfoque total',
                'icon' => '🎯',
                'tone' => 'tone-blue',
                'metric' => 'focused_minutes',
                'metric_label' => 'minutos enfocados',
                'target' => 600,
                'description' => 'Suma 10 horas de enfoque.',
            ],
            [
                'code' => 'unstoppable',
                'title' => 'Imparable',
                'icon' => '🏆',
                'tone' => 'tone-amber',
                'metric' => 'level',
                'metric_label' => 'nivel',
                'target' => 10,
                'description' => 'Alcanza el nivel 10.',
            ],
        ];
    }

    private function getEarnedMap(int $userId): array
    {
        if (!$this->ensureTable()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT badge_code, metric_value, earned_at
             FROM user_badges
             WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $code = (string) ($row['badge_code'] ?? '');
            if ($code === '') {
                continue;
            }

            $map[$code] = [
                'metric_value' => (int) ($row['metric_value'] ?? 0),
                'earned_at' => (string) ($row['earned_at'] ?? ''),
            ];
        }

        return $map;
    }

    private function earnBadge(int $userId, string $badgeCode, int $metricValue): void
    {
        if (!$this->ensureTable()) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO user_badges (user_id, badge_code, metric_value)
             VALUES (:user_id, :badge_code, :metric_value)
             ON DUPLICATE KEY UPDATE
                 metric_value = GREATEST(metric_value, VALUES(metric_value))'
        );

        $stmt->execute([
            'user_id' => $userId,
            'badge_code' => $badgeCode,
            'metric_value' => max(0, $metricValue),
        ]);
    }

    private function ensureTable(): bool
    {
        if ($this->tableReady !== null) {
            return $this->tableReady;
        }

        try {
            $this->db->exec(
                'CREATE TABLE IF NOT EXISTS user_badges (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    badge_code VARCHAR(80) NOT NULL,
                    metric_value INT NOT NULL DEFAULT 0,
                    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_badge (user_id, badge_code),
                    INDEX idx_user_badges_user (user_id),
                    CONSTRAINT fk_user_badges_user
                        FOREIGN KEY (user_id)
                        REFERENCES users(id)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            $this->tableReady = true;
        } catch (Throwable) {
            $this->tableReady = false;
        }

        return $this->tableReady;
    }
}
