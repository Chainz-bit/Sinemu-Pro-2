<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FoundReportController extends Controller
{
    public function create()
    {
        $categories = collect();
        if (Schema::hasTable('kategoris')) {
            $categories = Kategori::query()
                ->forForm()
                ->get(['id', 'nama_kategori']);
        }

        return view('user.pages.found-report', [
            'user' => Auth::user(),
            'categories' => $categories,
        ]);
    }
}