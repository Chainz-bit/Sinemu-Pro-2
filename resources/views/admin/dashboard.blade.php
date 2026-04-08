<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - SiNemu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <header class="bg-white border-bottom">
        <div class="container py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('img/logo.png') }}" alt="Sinemu" style="height:34px">
                <strong>Dashboard Admin</strong>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
            </form>
        </div>
    </header>

    <main class="container py-4">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="text-secondary small">Total Laporan Hilang</div>
                        <div class="display-6 fw-bold">{{ $totalHilang }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="text-secondary small">Total Barang Temuan</div>
                        <div class="display-6 fw-bold">{{ $totalTemuan }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="text-secondary small">Menunggu Verifikasi</div>
                        <div class="display-6 fw-bold">{{ $menungguVerifikasi }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h2 class="h5 fw-bold mb-3">Laporan Terbaru</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>Barang</th>
                                <th>Lokasi</th>
                                <th>Tanggal Kejadian</th>
                                <th>Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestReports as $report)
                                <tr>
                                    <td class="text-uppercase">{{ $report->type }}</td>
                                    <td>{{ $report->item_name }}</td>
                                    <td>{{ $report->location }}</td>
                                    <td>{{ $report->incident_date }}</td>
                                    <td>{{ $report->created_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary">Belum ada data laporan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
