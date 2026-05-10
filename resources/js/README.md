# Struktur JavaScript

Gunakan folder ini sebagai peta saat ingin mengubah interaksi frontend.

## `entries/`

Entry Vite yang dipanggil dari Blade lewat `@vite(...)`.
File di sini sebaiknya hanya berisi import CSS, library, dan app utama.

## `apps/`

Kode interaksi per area aplikasi.

- `apps/home/` untuk halaman publik/home.
- `apps/manager/` untuk dashboard pengelola barang.
- `apps/super/` untuk dashboard super admin.
- `apps/user/` untuk dashboard user.
- `apps/auth/` untuk halaman login/register.

## `shared/`

Script global yang dipakai banyak area, seperti transisi halaman dan flash popup.

## Pola edit

- Ubah logic halaman publik di `apps/home/`.
- Ubah sidebar/menu/modal pengelola barang di `apps/manager/modules/`.
- Ubah interaksi user di `apps/user/`.
- Tambahkan file baru sebagai module kecil, lalu import dari `app.js` area terkait.

## Catatan Penamaan

Frontend pengelola barang memakai nama `manager` agar mudah dicari saat mengubah CSS/JS.
Backend masih memakai nama teknis `admin` untuk route, guard, model, relasi, dan database.
Jangan rename bagian teknis itu bersamaan dengan perubahan frontend kecil.
