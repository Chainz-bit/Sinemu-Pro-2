<?php

namespace App\Services\Super\Admins;

use App\Models\Admin;
use App\Support\AdminVerificationStatusPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminVerificationQueryService
{
    /**
     * @return array{
     *   summary: array<string,int>,
     *   pendingAdmins: Collection<int,Admin>,
     *   latestActivities: Collection<int,Admin>,
     *   admins: LengthAwarePaginator
     * }
     */
    public function buildIndexData(string $search = '', string $status = 'semua', int $page = 1, int $perPage = 10, ?int $superAdminId = null): array
    {
        return [
            'summary' => $this->buildSummary($superAdminId),
            'pendingAdmins' => $this->buildPendingPreview(5, $superAdminId),
            'latestActivities' => $this->buildLatestActivities(6, $superAdminId),
            'admins' => $this->buildListingQuery($search, $status, $superAdminId)
                ->paginate($perPage, ['*'], 'page', max($page, 1))
                ->withQueryString(),
        ];
    }

    /**
     * @return array{
     *   total:int,
     *   pending:int,
     *   active:int,
     *   rejected:int,
     *   newThisWeek:int
     * }
     */
    public function buildSummary(?int $superAdminId = null): array
    {
        $baseQuery = $this->baseQuery($superAdminId);

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where(function (Builder $query) {
                $query->whereNull('status_verifikasi')->orWhere('status_verifikasi', 'pending');
            })->count(),
            'active' => (clone $baseQuery)->where('status_verifikasi', 'active')->count(),
            'rejected' => (clone $baseQuery)->where('status_verifikasi', 'rejected')->count(),
            'newThisWeek' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * @return Collection<int,Admin>
     */
    public function buildPendingPreview(int $limit = 5, ?int $superAdminId = null): Collection
    {
        return $this->baseQuery($superAdminId)
            ->where(function (Builder $query) {
                $query->whereNull('status_verifikasi')->orWhere('status_verifikasi', 'pending');
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int,Admin>
     */
    public function buildLatestActivities(int $limit = 6, ?int $superAdminId = null): Collection
    {
        return $this->baseQuery($superAdminId)
            ->orderByDesc('verified_at')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    public function buildListingQuery(string $search = '', string $status = 'semua', ?int $superAdminId = null): Builder
    {
        $query = $this->baseQuery($superAdminId)->latest();

        if ($search !== '') {
            $keyword = trim($search);
            $query->where(function (Builder $builder) use ($keyword) {
                $builder
                    ->where('nama', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('username', 'like', '%' . $keyword . '%')
                    ->orWhere('instansi', 'like', '%' . $keyword . '%')
                    ->orWhere('kecamatan', 'like', '%' . $keyword . '%');
            });
        }

        if ($status !== '' && $status !== 'semua') {
            $normalizedStatus = AdminVerificationStatusPresenter::key($status);

            if ($normalizedStatus === 'pending') {
                $query->where(function (Builder $builder) {
                    $builder->whereNull('status_verifikasi')->orWhere('status_verifikasi', 'pending');
                });
            } else {
                $query->where('status_verifikasi', $normalizedStatus);
            }
        }

        return $query;
    }

    private function baseQuery(?int $superAdminId = null): Builder
    {
        $query = Admin::query();

        if ($superAdminId !== null) {
            $query->where(function (Builder $builder) use ($superAdminId) {
                $builder
                    ->where('super_admin_id', $superAdminId)
                    ->orWhereNull('super_admin_id');
            });
        }

        return $query;
    }
}
