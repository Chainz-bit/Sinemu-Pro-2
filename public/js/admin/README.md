# Struktur JS Admin

JavaScript admin dipisah berbasis fitur agar mudah dilacak.

## Peta File
- `app.js`: entrypoint inisialisasi semua modul.
- `modules/row-menu.js`: dropdown aksi per baris tabel.
- `modules/profile-menu.js`: dropdown profil di sidebar.
- `modules/notification-modal.js`: panel notifikasi topbar.
- `modules/sidebar.js`: toggle sidebar di layar kecil.

## Alur Utama
1. `app.js` mencari elemen DOM.
2. `app.js` membuat instance modul.
3. Modul saling dihubungkan (ketika satu terbuka, yang lain ditutup).
4. Event global (`click` luar, `Escape`) menutup semua panel.
