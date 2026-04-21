<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Klaim;
use App\Services\User\Claims\ClaimHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiwayatKlaimController extends Controller
{
    public function __construct(private readonly ClaimHistoryService $claimHistoryService)
    {
    }

    public function index(Request $request)
    {
        abort_unless(Auth::check(), 403);
        $user = Auth::user();
        $historyData = $this->claimHistoryService->buildHistoryData((int) $user->id, (array) $request->query());

        return view('user.pages.claim-history', [
            'user' => $user,
            'search' => $historyData['search'],
            'statusFilter' => $historyData['statusFilter'],
            'typeFilter' => $historyData['typeFilter'],
            'claims' => $historyData['claims'],
        ]);
    }

    public function destroy(Klaim $klaim)
    {
        abort_unless(Auth::check(), 403);
        $user = Auth::user();
        abort_unless((int) $klaim->user_id === (int) $user->id, 403);
        $this->claimHistoryService->deleteClaimProofs($klaim);

        $klaim->delete();

        return redirect()
            ->route('user.claim-history', request()->query())
            ->with('status', 'Riwayat klaim berhasil dihapus.');
    }
}
