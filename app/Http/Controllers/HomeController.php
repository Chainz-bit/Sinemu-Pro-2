<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\LaporanBarangHilang;
use App\Services\Home\HomePageViewService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(private readonly HomePageViewService $homePageService)
    {
    }

    public function index(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $databaseResponsive = $this->homePageService->isDatabaseResponsive();
        if ($databaseResponsive && Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->homePageService->buildHomeViewData(
            currentUser: $databaseResponsive ? Auth::user() : null,
            includeClaimableReports: false
        );

        return view('home', $viewData);
    }

    public function showLostDetail(LaporanBarangHilang $laporanBarangHilang)
    {
        return view('home.detail', $this->homePageService->buildLostDetailViewData($laporanBarangHilang));
    }

    public function showFoundDetail(Barang $barang)
    {
        return view('home.detail', $this->homePageService->buildFoundDetailViewData($barang));
    }
}
