# Struktur JS Pengelola Barang

Folder ini bernama `manager` untuk memudahkan pencarian file frontend pengelola barang.
Nama route, guard, model, relasi database, dan namespace backend masih tetap memakai `admin` sampai tahap rename teknis dilakukan.

JavaScript pengelola barang dipisah berbasis fitur agar mudah dilacak.

## Peta File
- `app.js`: entrypoint inisialisasi semua modul.
- `modules/row-menu.js`: dropdown aksi per baris tabel.
- `modules/profile-menu.js`: dropdown profil di sidebar.
- `modules/notification-modal.js`: panel notifikasi topbar.
- `modules/sidebar.js`: toggle sidebar di layar kecil.

## Batas Rename
- Aman diedit/ditambah di sini untuk perilaku UI pengelola barang.
- Jangan ubah route `admin.*`, guard `admin`, model `Admin`, atau kolom seperti `admin_id` dari folder frontend ini.
- Jika perlu memakai modul shell yang sama di area lain, import dari `apps/manager/modules/`.

## Alur Utama
1. `app.js` mencari elemen DOM.
2. `app.js` membuat instance modul.
3. Modul saling dihubungkan (ketika satu terbuka, yang lain ditutup).
4. Event global (`click` luar, `Escape`) menutup semua panel.
