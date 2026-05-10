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
        if ($databaseResponsive && \App\Support\ManagerPortal::check()) {
            return redirect()->route(\App\Support\ManagerPortal::dashboardRoute());
        }

        $viewData = $this->homePageService->buildHomeViewData(
            currentUser: $databaseResponsive ? Auth::user() : null,
            includeClaimableReports: false
        );

        return view('home.pages.index', $viewData);
    }

    public function showLostDetail(LaporanBarangHilang $laporanBarangHilang)
    {
        return view('home.pages.detail', $this->homePageService->buildLostDetailViewData($laporanBarangHilang));
    }

    public function showFoundDetail(Barang $barang)
    {
        return view('home.pages.detail', $this->homePageService->buildFoundDetailViewData($barang));
    }
}
