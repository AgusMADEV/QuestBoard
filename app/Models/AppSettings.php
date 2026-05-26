<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/connection.php';

final class AppSettings
{
    private PDO $db;
    private array $tableCache = [];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getConnection();
    }

    public function hasSettingsTable(): bool
    {
        if (array_key_exists('app_settings', $this->tableCache)) {
            return $this->tableCache['app_settings'];
        }

        $stmt = $this->db->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
             LIMIT 1'
        );
        $stmt->execute(['table' => 'app_settings']);

        $exists = (bool) $stmt->fetchColumn();
        $this->tableCache['app_settings'] = $exists;

        return $exists;
    }

    public function getMany(array $keys): array
    {
        if (!$this->hasSettingsTable() || empty($keys)) {
            return [];
        }

        $keys = array_values(array_unique(array_filter(array_map('strval', $keys))));
        if (empty($keys)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $stmt = $this->db->prepare(
            "SELECT setting_key, setting_value
             FROM app_settings
             WHERE setting_key IN ({$placeholders})"
        );
        $stmt->execute($keys);

        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        return $settings;
    }

    public function upsertMany(array $settings): void
    {
        if (!$this->hasSettingsTable() || empty($settings)) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO app_settings (setting_key, setting_value)
             VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP'
        );

        foreach ($settings as $key => $value) {
            $stmt->execute([
                'setting_key' => (string) $key,
                'setting_value' => (string) $value,
            ]);
        }
    }

    public function deleteMany(array $keys): void
    {
        if (!$this->hasSettingsTable() || empty($keys)) {
            return;
        }

        $keys = array_values(array_unique(array_filter(array_map('strval', $keys))));
        if (empty($keys)) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $stmt = $this->db->prepare("DELETE FROM app_settings WHERE setting_key IN ({$placeholders})");
        $stmt->execute($keys);
    }
}
