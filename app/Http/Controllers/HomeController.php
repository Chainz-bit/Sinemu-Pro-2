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
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->homePageService->buildHomeViewData(
            currentUser: Auth::user(),
            includeClaimableReports: Auth::check()
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
