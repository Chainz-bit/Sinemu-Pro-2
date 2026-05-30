# View Pengelola Barang

Folder ini bernama `manager` untuk tampilan Pengelola Barang. Beberapa kontrak teknis Laravel masih memakai nama `admin` dan belum direname:

- route name `admin.*`
- guard `admin`
- model dan relasi `Admin`
- controller namespace `App\Http\Controllers\Admin`
- alias kompatibilitas view `admin::`

Secara UI, area ini sudah memakai label **Pengelola Barang**. Untuk mengubah tampilan dashboard, profil, laporan, klaim, atau pengaturan pengelola barang, edit file di folder ini.

Namespace utama view adalah `manager::`, contohnya `view('manager::pages.dashboard.index')`. Namespace `admin::` tetap disediakan sementara sebagai alias kompatibilitas selama route, guard, model, dan controller masih teknis `admin`.

Jangan rename route, guard, controller, model, atau database bersamaan dengan perubahan view kecil. Bagian itu masuk tahap teknis terpisah.
