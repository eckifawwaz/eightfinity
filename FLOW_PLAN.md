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
4. User memilih opsi durasi, tanggal, jam, jumlah pax, alamat customer, dan lokasi booking.
5. Jika data lengkap, user bisa lanjut ke payment.

Yang perlu dicek manual:
- Tombol dari home/package detail ke booking.
- Query parameter package dan option terbawa dengan benar.
- Validasi tanggal minimal hari ini.

### 4. Payment

Status: sudah ada, tapi masih simulasi QR.

Route:
- `GET /payment`
- `POST /payment`

Alur:
1. User melihat ringkasan booking.
2. Sistem menampilkan QR simulasi.
3. User klik `Cek pembayaran`.
4. Sistem membuat data booking dengan status `pending`.
5. User diarahkan ke `/payment/success/{booking}`.

Catatan:
- Backend menerima `payment_proof`, tapi UI payment sekarang belum menampilkan upload bukti pembayaran.
- QR masih generated dummy, belum payment gateway asli.

Prioritas tambahan:
1. Tambahkan upload bukti pembayaran di UI payment, atau hapus requirement bukti jika memang tidak dipakai.
2. Tampilkan instruksi pembayaran yang final.
3. Pastikan data booking masuk database setelah submit.

### 5. Payment Success

Status: sudah ada.

Route:
- `GET /payment/success/{booking}`

Alur:
1. User melihat receipt booking.
2. User bisa download receipt text.
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
5. User bisa reschedule booking yang belum `completed` atau `cancelled`.
6. User bisa cancel booking yang belum `completed` atau `cancelled`.
7. Booking yang selesai atau batal bisa di-rebook.

Yang perlu dicek manual:
- Edit profile berhasil dan redirect tetap enak.
- Cancel booking mengubah status menjadi `cancelled`.
- Reschedule mengubah tanggal, jam, pax, package, alamat, lokasi, dan status kembali `pending`.

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
1. Booking dengan status `pending` dan `confirmed` masuk queue.
2. Booking `confirmed` tampil sebagai `In Session`.
3. Booking `pending` tampil sebagai waiting queue.
4. Admin bisa start session, pause session, complete session.
5. Admin bisa masuk ke layout booking terkait.

Catatan:
- Session progress masih statis 60%.
- Estimasi wait time masih hitungan sederhana.

Prioritas tambahan:
1. Putuskan apakah boleh lebih dari satu booking berstatus `confirmed`.
2. Jika hanya satu active session, tambahkan validasi backend.
3. Buat progress session dinamis jika diperlukan.

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
