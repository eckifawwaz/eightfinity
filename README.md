# Eightfinity

Eightfinity adalah aplikasi booking photo booth berbasis Laravel dan React. Aplikasi ini memiliki portal customer untuk registrasi, booking, payment, dan riwayat booking, serta portal admin untuk mengelola booking, queue, customer, dan layout booth.

## Tech Stack

- Laravel 10
- React 19
- Vite
- Tailwind CSS
- MySQL atau SQLite untuk local development

## Fitur User

- Register dan login user.
- Melihat home dan detail package.
- Membuat booking berdasarkan package, tanggal, jam, jumlah pax, alamat, dan lokasi.
- Melakukan payment dengan QR simulasi.
- Melihat receipt booking.
- Melihat booking history di profile.
- Edit profile.
- Cancel booking.
- Reschedule booking.
- Logout.

## Fitur Admin

- Login admin dengan guard terpisah.
- Dashboard metrik booking, queue, dan revenue.
- Manage bookings dan update status booking.
- Manage queue untuk start, pause, dan complete session.
- Manage customer data.
- Melihat dan mengedit detail customer.
- Layout editor 2D per booking.
- Logout admin.

## Port Lokal

Portal user:

```bash
http://127.0.0.1:8000
```

Portal admin:

```bash
http://127.0.0.1:8001
```

Konfigurasi portal ada di `.env`:

```env
USER_APP_URL=http://127.0.0.1:8000
ADMIN_APP_URL=http://127.0.0.1:8001
```

## Install Local

Clone repository:

```bash
git clone https://github.com/eckifawwaz/eightfinity.git
cd eightfinity
```

Install dependency PHP:

```bash
composer install
```

Install dependency JavaScript:

```bash
npm install
```

Copy environment:

```bash
cp .env.example .env
```

Generate app key:

```bash
php artisan key:generate
```

Untuk local development, sesuaikan `.env`. Contoh SQLite:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
USER_APP_URL=http://127.0.0.1:8000
ADMIN_APP_URL=http://127.0.0.1:8001

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/eightfinity/database/database.sqlite
```

Buat file SQLite jika memakai SQLite:

```bash
touch database/database.sqlite
```

Jalankan migration:

```bash
php artisan migrate
```

Jalankan seeder untuk membuat akun admin:

```bash
php artisan db:seed
```

Credential admin mengikuti `.env`:

```env
ADMIN_SEED_NAME="EightFinity Admin"
ADMIN_SEED_EMAIL=admin@your-domain.com
ADMIN_SEED_PASSWORD=change-this-admin-password
ADMIN_SEED_PHONE=+6280000000000
```

Seeder akan dilewati jika `ADMIN_SEED_EMAIL` belum diisi atau password masih memakai placeholder. Isi dengan email dan password admin yang benar sebelum menjalankan `php artisan db:seed`.

## Run Local

Jalankan Vite:

```bash
npm run dev
```

Jalankan server user:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Jika ingin menjalankan portal admin di port terpisah:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Buka:

- User: `http://127.0.0.1:8000/register`
- Admin: `http://127.0.0.1:8001/admin/login`

## Build Frontend

Untuk production build:

```bash
npm run build
```

Output build akan dibuat di `public/build`.

## Test

Jalankan test:

```bash
php artisan test
```

Catatan: sebagian test bawaan Laravel Breeze lama mungkin perlu disesuaikan dengan flow React dan portal user/admin yang sekarang.

## Deployment Checklist

Di server production:

```bash
cp .env.example .env
php artisan key:generate
```

Jangan upload atau menyalin `.env` lokal ke server production. File `.env` lokal berisi kredensial development dan sudah masuk `.gitignore`, jadi production harus dibuat dari `.env.example`, lalu diisi ulang di server.

Sesuaikan `.env` production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
USER_APP_URL=https://your-domain.com
ADMIN_APP_URL=https://admin.your-domain.com

ADMIN_SEED_NAME="EightFinity Admin"
ADMIN_SEED_EMAIL=admin@your-domain.com
ADMIN_SEED_PASSWORD=your-secure-admin-password
ADMIN_SEED_PHONE=+6280000000000

MIDTRANS_WEDDING_4_HOURS_PAYMENT_LINK=https://app.midtrans.com/payment-links/...
MIDTRANS_WEDDING_8_HOURS_PAYMENT_LINK=https://app.midtrans.com/payment-links/...
MIDTRANS_RESERVATION_4_HOURS_PAYMENT_LINK=https://app.midtrans.com/payment-links/...
MIDTRANS_RESERVATION_4_PLUS_1_HOURS_PAYMENT_LINK=https://app.midtrans.com/payment-links/...
MIDTRANS_UNLIMITED_2_HOURS_PAYMENT_LINK=https://app.midtrans.com/payment-links/...
MIDTRANS_UNLIMITED_3_HOURS_PAYMENT_LINK=https://app.midtrans.com/payment-links/...
MIDTRANS_UNLIMITED_4_HOURS_PAYMENT_LINK=https://app.midtrans.com/payment-links/...

DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=eightfinity
DB_USERNAME=eightfinity_user
DB_PASSWORD=your-secure-password

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

VITE_WHATSAPP_URL=https://wa.me/6285860581496
VITE_INSTAGRAM_URL=https://www.instagram.com/___eightfinity
```

Sebelum deploy, rotate dan isi ulang secret berikut di server production:

- `APP_KEY`: generate baru dengan `php artisan key:generate`.
- `ADMIN_SEED_PASSWORD`: gunakan password kuat, jangan pakai password lokal.
- `DB_PASSWORD`: gunakan user database production dengan akses terbatas ke database Eightfinity.
- `MAIL_PASSWORD`: buat app password baru khusus production.
- `MIDTRANS_*_PAYMENT_LINK`: gunakan payment link production, bukan sandbox/local test link.
- `VITE_WHATSAPP_URL` dan `VITE_INSTAGRAM_URL`: isi dengan akun bisnis yang akan dibuka dari footer website.

Jangan gunakan password placeholder di production. Isi `ADMIN_SEED_EMAIL` dan `ADMIN_SEED_PASSWORD` sebelum menjalankan `php artisan db:seed --force`.

Setelah `.env` production sudah benar, jalankan deploy script:

```bash
./scripts/deploy-production.sh
```

Script ini menjalankan dependency install, frontend build, `migrate --force`, `db:seed --force`, `storage:link`, dan cache Laravel. Script akan berhenti jika `.env` belum ada atau masih memakai `APP_ENV=local/testing`.

Pastikan folder berikut writable oleh web server:

- `storage`
- `bootstrap/cache`

## Struktur Penting

- `resources/js/react` - React application.
- `resources/views/react.blade.php` - Blade shell untuk mounting React.
- `routes/web.php` - Route user dan admin.
- `app/Http/Controllers` - Controller backend.
- `app/Models/Booking.php` - Model booking.
- `config/portals.php` - Konfigurasi URL portal user/admin.
- `database/migrations` - Struktur database.

## Catatan Flow

Detail flow user dan admin tersedia di:

```bash
FLOW_PLAN.md
```
