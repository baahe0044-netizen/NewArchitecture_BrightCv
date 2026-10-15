<?php

declare(strict_types=1);

final class DashboardRepository
{
    public function dataForUser(int $userId): array
    {
        $resumes = new ResumeRepository();
        return [
            'stats' => $resumes->dashboardStats($userId),
            'resumes' => array_slice($resumes->listByUser($userId), 0, 6),
            'activity' => (new ActivityRepository())->recent($userId),
        ];
    }
}
