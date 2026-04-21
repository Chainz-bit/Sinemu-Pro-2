<?php

namespace App\Services\Super\Dashboard;

use App\Models\Admin;
use App\Services\Super\Admins\AdminVerificationQueryService;
use Illuminate\Support\Collection;

class SuperDashboardQueryService
{
    public function __construct(
        private readonly AdminVerificationQueryService $adminVerificationQueryService
    ) {
    }

    /**
     * @return array{
     *   summary: array<string,int>,
     *   priorityAdmins: Collection<int,Admin>,
     *   newestAdmins: Collection<int,Admin>,
     *   latestActivities: Collection<int,Admin>
     * }
     */
    public function buildDashboardData(?int $superAdminId = null): array
    {
        return [
            'summary' => $this->adminVerificationQueryService->buildSummary($superAdminId),
            'priorityAdmins' => $this->adminVerificationQueryService->buildPendingPreview(4, $superAdminId),
            'newestAdmins' => Admin::query()
                ->when($superAdminId !== null, function ($query) use ($superAdminId) {
                    $query->where(function ($builder) use ($superAdminId) {
                        $builder
                            ->where('super_admin_id', $superAdminId)
                            ->orWhereNull('super_admin_id');
                    });
                })
                ->latest()
                ->limit(5)
                ->get(),
            'latestActivities' => $this->adminVerificationQueryService->buildLatestActivities(5, $superAdminId),
        ];
    }
}
