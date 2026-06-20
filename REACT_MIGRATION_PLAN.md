# React Migration Plan

## Tujuan

Migrasi front-end Laravel Blade ke React.

## Tahap Migrasi

1. Gunakan React sebagai UI utama.
2. Buat satu Blade shell untuk mounting React melalui Vite.
3. Tambahkan entry React terpisah untuk Vite.
4. Pindahkan route utama ke shell React:
   - `/login`
   - `/home`
   - `/book`
   - `/payment`
   - `/dashboard`
5. Amankan route Laravel:
   - `/login` hanya untuk guest.
   - `/home`, `/book`, dan `/payment` hanya untuk user login.
   - `/dashboard` hanya untuk user login dengan role `admin`.
6. Hapus Blade lama setelah halaman React utama tersedia.
7. Sambungkan halaman React ke API/form Laravel secara bertahap setelah struktur UI stabil.

## Status Implementasi Awal

- React sudah dipasang via Vite.
- Shell React tersedia di `resources/views/react.blade.php`.
- Halaman React awal tersedia untuk Login, Home, Book, Payment, dan Admin Dashboard.
- Route utama sudah diarahkan ke React.
- Blade lama sudah dihapus. Shell React `resources/views/react.blade.php` tetap dipertahankan untuk mounting aplikasi React.
