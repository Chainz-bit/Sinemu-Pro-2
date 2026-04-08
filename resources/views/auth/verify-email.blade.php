@extends('layouts.auth')

@section('content')
    <main style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#eef1f5;padding:1rem;">
        <div style="width:min(520px,100%);background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:1.25rem;">
            <h1 style="margin:0 0 .5rem;font-size:1.25rem;">Verifikasi Email</h1>
            <p style="margin:0 0 1rem;color:#6b7280;">Silakan verifikasi email Anda melalui link yang dikirimkan.</p>

            @if (session('status') == 'verification-link-sent')
                <div style="margin-bottom:1rem;padding:.75rem;border-radius:10px;background:#ecfeff;border:1px solid #a5f3fc;color:#0f766e;">
                    Link verifikasi baru sudah dikirim ke email Anda.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" style="margin-bottom:.75rem;">
                @csrf
                <button type="submit" style="width:100%;height:44px;border:0;border-radius:10px;background:#5a3ff6;color:#fff;font-weight:700;">
                    Kirim Ulang Link Verifikasi
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="width:100%;height:44px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font-weight:600;">
                    Keluar
                </button>
            </form>
        </div>
    </main>
@endsection
