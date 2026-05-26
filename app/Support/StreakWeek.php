<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Habit.php';
require_once __DIR__ . '/../Models/Task.php';

if (!function_exists('buildWeeklyActivityByUser')) {
    function buildWeeklyActivityByUser(int $userId, ?DateTimeImmutable $weekStart = null): array
    {
        $weekStart = ($weekStart ?? new DateTimeImmutable('monday this week'))->setTime(0, 0);
        $weekEnd = $weekStart->modify('+6 days');
        $weekLabels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

        $habitModel = new Habit();
        $taskModel = new Task();

        $habitLogs = $habitModel->getWeekLogs($userId, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
        $taskCompletionDates = $taskModel->getCompletedDatesByRange($userId, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
        $habitCompletionDates = [];

        foreach ($habitLogs as $habitLogByDate) {
            foreach ($habitLogByDate as $date => $done) {
                if ($done) {
                    $habitCompletionDates[$date] = true;
                }
            }
        }

        $weekActivity = [];

        for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
            $dayDate = $weekStart->modify('+' . $dayIndex . ' days')->format('Y-m-d');
            $weekActivity[] = [
                'label' => $weekLabels[$dayIndex],
                'date' => $dayDate,
                'done' => isset($habitCompletionDates[$dayDate]) || isset($taskCompletionDates[$dayDate]),
            ];
        }

        return $weekActivity;
    }
}
