<?php

declare(strict_types=1);

final class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard = new DashboardRepository())
    {
    }

    public function forUser(int $userId): array
    {
        return $this->dashboard->dataForUser($userId);
    }
}
