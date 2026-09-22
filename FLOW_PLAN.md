# Eightfinity Flow Plan

Dokumen ini jadi acuan flow aplikasi sebelum lanjut merapikan test, UI detail, dan validasi akhir.

## User Flow

### 1. Register

Status: sudah ada.

Route:
- `GET /register`
- `POST /register`

Alur:
1. User membuka halaman register.
2. User mengisi nama, email, nomor telepon, nomor alternatif opsional, dan password.
3. Sistem membuat akun dengan role `user`.
4. User otomatis login.
5. User diarahkan ke `/home`.

Catatan:
- Form register sekarang wajib punya `phone`.
- Test bawaan lama belum mengikuti requirement ini.

### 2. Login

Status: sudah ada.

Route:
- `GET /login`
- `POST /login`

Alur:
1. User membuka login.
2. User memasukkan email dan password.
3. Sistem hanya menerima akun non-admin di portal user.
4. Jika berhasil, user masuk ke `/home`.
5. Jika akun admin mencoba login dari portal user, sistem menolak dan mengarahkan untuk login lewat `/admin/login`.

### 3. Home dan Package Selection

Status: sudah ada.

Route:
- `GET /home`
- `GET /packages/{slug}`
- `GET /book`

Alur:
1. User melihat halaman home.
2. User memilih package:
   - Wedding Package
   - Reservation Package
   - Unlimited Package
3. User masuk ke halaman booking.
4. User memilih opsi durasi, tanggal, jam, ukuran booth, alamat customer, dan lokasi booking.
5. Jika data lengkap, user bisa lanjut ke payment.

Yang perlu dicek manual:
- Tombol dari home/package detail ke booking.
- Query parameter package dan option terbawa dengan benar.
- Validasi tanggal minimal besok dan tanggal yang sudah memiliki booking aktif tidak dapat dipilih untuk lanjut.

### 4. Payment

Status: terintegrasi Midtrans.

Route:
- `GET /payment`
- `POST /payment`
- `GET /payment/finish/{booking}`
- `POST /midtrans/notification`

Alur:
1. User melihat ringkasan booking dan sistem mengecek ulang ketersediaan tanggal.
2. Saat `Bayar Sekarang`, booking dibuat sebagai `pending` lalu user diarahkan ke Midtrans.
3. Sebelum metode pembayaran dipilih, batas awal pembayaran adalah 24 jam.
4. Setelah Midtrans mengembalikan tipe pembayaran, countdown mengikuti expiry provider (contoh fallback QRIS 5 menit).
5. Pembayaran sukses kembali ke halaman receipt booking; pembayaran expired/cancelled kembali tercermin di Booking History.

Catatan:
- Availability tanggal divalidasi di UI dan divalidasi ulang di backend dengan lock agar booking tanggal yang sama tidak lolos bersamaan.
- Callback/status Midtrans tidak menurunkan booking yang sudah `confirmed/completed` kembali menjadi `pending/expired`, kecuali refund.

### 5. Payment Success

Status: sudah ada.

Route:
- `GET /payment/success/{booking}`

Alur:
1. User melihat receipt booking.
2. User bisa download receipt PDF.
3. Booking hanya bisa dilihat oleh owner booking.

Yang perlu dicek manual:
- User lain tidak bisa membuka receipt booking milik user berbeda.
- Semua field booking tampil benar.

### 6. Profile dan Booking History

Status: sudah ada.

Route:
- `GET /profile`
- `PUT /profile`
- `PATCH /bookings/{booking}/cancel`
- `PATCH /bookings/{booking}/reschedule`

Alur:
1. User membuka profile.
2. User melihat personal information dan booking history.
3. User bisa edit profile.
4. User bisa view receipt.
5. User bisa reschedule booking hanya sebelum memasuki H-3.
6. Reschedule tidak membuat booking baru dan tidak meminta pembayaran ulang.
7. Booking expired/cancelled/completed tetap dapat ditampilkan sebagai riwayat sesuai status.

Yang perlu dicek manual:
- Edit profile berhasil dan redirect tetap enak.
- Reschedule ditolak mulai H-3.
- Reschedule hanya mengubah jadwal/venue yang diizinkan dan tidak membuat pembayaran baru.

### 7. Logout

Status: sudah ada.

Route:
- `POST /logout`
- `GET /logout`

Alur:
1. User klik sign out dari profile.
2. Sistem logout guard `web`.
3. User diarahkan ke `/login`.

## Admin Flow

### 1. Admin Login

Status: sudah ada.

Route:
- `GET /admin/login`
- `POST /admin/login`

Alur:
1. Admin membuka `/admin/login`.
2. Admin memasukkan email dan password.
3. Sistem hanya menerima user dengan role `admin`.
4. Jika berhasil, admin masuk ke `/dashboard`.
5. Jika akun user biasa login lewat admin portal, sistem menolak.

### 2. Dashboard

Status: sudah ada.

Route:
- `GET /dashboard`

Alur:
1. Admin melihat metrik:
   - bookings today
   - active sessions
   - queue length
   - revenue today
2. Admin melihat recent bookings.
3. Admin melihat package stats.

Yang perlu dicek manual:
- Metrik berubah setelah ada booking baru.
- Revenue hanya menghitung status `confirmed` dan `completed`.

### 3. Manage Bookings

Status: sudah ada.

Route:
- `GET /admin/bookings`
- `PATCH /admin/bookings/{booking}/status`

Alur:
1. Admin melihat semua booking customer.
2. Admin bisa search booking.
3. Admin bisa filter by status.
4. Admin bisa update status:
   - pending
   - confirmed
   - completed
   - cancelled

Prioritas tambahan:
1. Tambahkan detail payment proof jika upload bukti pembayaran dipakai.
2. Tambahkan konfirmasi sebelum status `cancelled` atau `completed` jika dibutuhkan.

### 4. Manage Queue

Status: sudah ada.

Route:
- `GET /admin/queue`
- `PATCH /admin/queue/{booking}`

Alur:
1. Hanya booking `confirmed` yang dapat menjadi event queue.
2. Admin dapat menambah tamu antrean dengan jam check-in yang dibatasi ke jam sewa booking.
3. Admin dapat Start Session, Complete Session, Pause/Resume booth, dan menghapus antrean.
4. Jumlah foto otomatis mengikuti jumlah sesi tamu yang sudah `completed`.
5. Session Progress dan tombol tambah foto manual tidak digunakan lagi.
6. Admin bisa masuk ke layout booking terkait.

Catatan:
- Jam check-in default mengikuti waktu saat ini jika masih di dalam jam sewa; jika belum mulai, default ke jam mulai booking.
- Duplicate submit antrean dilindungi client-side dan server-side.

### 5. Customer Data

Status: sudah ada.

Route:
- `GET /admin/customers`
- `PATCH /admin/customers/{user}`
- `DELETE /admin/customers/{user}`

Alur:
1. Admin melihat daftar customer.
2. Admin bisa search customer.
3. Admin bisa melihat detail customer.
4. Admin bisa edit nama, email, phone, alternate phone.
5. Admin bisa melihat booking history customer.
6. Admin bisa delete customer beserta booking history.

Prioritas tambahan:
1. Pastikan delete customer memang sesuai kebutuhan skripsi, karena ini destructive.
2. Tambahkan handling error jika email customer sudah dipakai.

### 6. Layout Editor

Status: sudah ada.

Route:
- `GET /admin/layout`
- `PATCH /admin/layout/{booking}`

Alur:
1. Admin memilih booking dari queue.
2. Admin memilih layout name.
3. Admin memilih booth size.
4. Admin memilih printer position.
5. Admin memilih entrance direction.
6. Admin drag posisi camera, props, dan printer.
7. Admin menyimpan layout ke booking.
8. Admin bisa kembali ke queue.

Yang perlu dicek manual:
- Drag item berjalan di desktop dan mobile.
- Posisi tersimpan dan muncul lagi setelah refresh.
- Booking yang sudah `completed` tidak muncul lagi di layout queue.

### 7. Admin Logout

Status: sudah ada.

Route:
- `GET /admin/logout`
- `POST /admin/logout`

Alur:
1. Admin klik sign out.
2. Sistem logout guard `admin`.
3. Admin diarahkan ke `/admin/login`.

## Prioritas Pengerjaan Berikutnya

1. Lengkapi payment flow: tentukan upload bukti pembayaran dipakai atau tidak.
2. Tes manual full user flow dari register sampai booking muncul di profile.
3. Tes manual admin flow dari booking masuk dashboard sampai complete session.
4. Rapikan test suite agar sesuai flow baru, bukan default Breeze lama.
5. Tambahkan README project Eightfinity.
6. Aktifkan Git repo agar progress bisa dilacak.

## Checklist Demo

User:
- [ ] Register user baru.
- [ ] Login user.
- [ ] Pilih package.
- [ ] Buat booking.
- [ ] Submit payment.
- [ ] Lihat receipt.
- [ ] Lihat booking history di profile.
- [ ] Edit profile.
- [ ] Reschedule booking.
- [ ] Cancel booking.
- [ ] Logout.

Admin:
- [ ] Login admin.
- [ ] Cek dashboard metrics.
- [ ] Buka manage bookings.
- [ ] Confirm booking.
- [ ] Buka queue.
- [ ] Start session.
- [ ] Edit layout booking.
- [ ] Complete session.
- [ ] Buka customer detail.
- [ ] Edit customer.
- [ ] Logout.
