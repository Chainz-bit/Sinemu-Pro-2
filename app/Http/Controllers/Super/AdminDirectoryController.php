<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Services\Super\Admins\AdminVerificationQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminDirectoryController extends Controller
{
    public function __construct(
        private readonly AdminVerificationQueryService $adminVerificationQueryService
    ) {
    }

    public function index(Request $request): View
    {
        $superAdmin = Auth::guard('super_admin')->user();
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', 'semua'));

        $data = $this->adminVerificationQueryService->buildIndexData(
            search: $search,
            status: $statusFilter,
            page: (int) $request->query('page', 1),
            perPage: 12,
            superAdminId: $superAdmin?->id
        );

        return view('super.admins.index', [
            'superAdmin' => $superAdmin,
            'search' => $search,
            'statusFilter' => $statusFilter,
            ...$data,
        ]);
    }
}
