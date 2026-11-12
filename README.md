# Sistem Presensi dan Laporan Presensi

Aplikasi web berbasis **Laravel 12** untuk mencatat presensi pegawai, meliputi
check-in, check-out, izin, dan sakit. Aplikasi juga menyediakan dashboard,
riwayat presensi, pencarian, filter, sorting, pagination, ringkasan data, serta
export laporan presensi ke PDF.

## Fitur

- Autentikasi login, logout, dan registrasi pegawai.
- Dashboard pegawai dengan status presensi hari ini, ringkasan presensi bulan
  berjalan, persentase ketepatan waktu, dan presensi terbaru.
- Check-in dan check-out satu kali per hari.
- Pencatatan status **hadir**, **terlambat**, **izin**, atau **sakit**.
- Laporan presensi personal untuk pegawai.
- Dashboard admin untuk memantau jumlah pegawai, ringkasan presensi, tren
  mingguan, dan pegawai yang terlambat.
- Daftar pegawai dan laporan presensi seluruh pegawai untuk admin.
- Filter berdasarkan nama, tanggal tepat, rentang tanggal, bulan/tahun, dan
  status.
- Sorting berdasarkan tanggal atau nama, pagination, serta pilihan 10, 25, 50,
  atau 100 baris per halaman.
- Export laporan yang sedang difilter ke PDF menggunakan Dompdf.

## Teknologi dan kebutuhan

- PHP **8.2 atau lebih baru**
- Laravel **12**
- Composer
- Node.js dan npm
- MariaDB/MySQL
- Ekstensi PHP yang dibutuhkan Laravel, termasuk PDO MySQL

Jika menggunakan XAMPP, aktifkan PHP/CLI yang sesuai dan pastikan perintah
`php`, `composer`, `node`, dan `npm` dapat dijalankan dari terminal.

## Instalasi

Jalankan perintah berikut dari direktori project:

```bash
git clone <url-repository>
cd farras_bappeda_presensi-app
composer install
cp .env.example .env
php artisan key:generate
```

Pada Windows, salin `.env.example` sebagai `.env` secara manual jika perintah
`cp` tidak tersedia.

### Konfigurasi environment

Project menggunakan MariaDB/MySQL. Buat database terlebih dahulu, misalnya:

```sql
CREATE DATABASE presensi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Kemudian sesuaikan konfigurasi database pada `.env`:

```dotenv
APP_NAME="Sistem Presensi"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=presensi
DB_USERNAME=root
DB_PASSWORD=
```

Untuk MariaDB, gunakan `DB_CONNECTION=mysql` dan isi host, port, nama database,
username, serta password sesuai konfigurasi MariaDB lokal Anda. Pastikan
database dan layanan MariaDB/MySQL sudah aktif sebelum menjalankan migrasi.

Jalankan migrasi:

```bash
php artisan migrate
```

### Data demo (opsional)

Untuk membuat akun demo dan data presensi contoh:

```bash
php artisan db:seed
```

Seeder membuat:

- Admin: `admin@mail.com`
- Password akun demo: `password`
- Sembilan akun pegawai acak dengan password `password`
- Hingga 100 data presensi contoh pada hari kerja terakhir

Kredensial tersebut hanya untuk pengembangan/demo. Ganti atau hapus akun demo
sebelum aplikasi digunakan di lingkungan produksi.

### Instalasi aset frontend

```bash
npm install
npm run build
```

## Menjalankan aplikasi

Untuk menjalankan server Laravel:

```bash
php artisan serve
```

Buka [http://localhost:8000](http://localhost:8000) di browser.

Jika perlu mengembangkan atau membangun aset frontend, gunakan Node.js dan npm:

```bash
npm install
npm run dev
```

Untuk build aset produksi:

```bash
npm run build
```

`npm run dev` hanya diperlukan saat mengembangkan aset frontend. Aplikasi Laravel
tetap dapat dijalankan dengan `php artisan serve`.

## Panduan pengguna (pegawai)

### 1. Registrasi dan login

1. Buka halaman aplikasi dan pilih **Registrasi** jika belum memiliki akun.
2. Isi nama, email, password minimal 8 karakter, dan konfirmasi password.
3. Login menggunakan email dan password yang telah didaftarkan.
4. Akun yang dibuat melalui registrasi otomatis memiliki role `pegawai`.

### 2. Mencatat presensi

Pada dashboard pegawai:

- Pilih **Check-in** saat mulai bekerja.
- Pilih **Check-out** saat selesai bekerja.
- Pilih **Izin** atau **Sakit** jika tidak masuk dan pilih status yang sesuai.

Aturan presensi:

- Setiap pegawai hanya dapat memiliki satu catatan untuk satu tanggal.
- Check-in paling lambat pukul **07:30** berstatus `hadir`; setelah itu
  berstatus `terlambat`.
- Check-out hanya dapat dilakukan setelah check-in dan tidak dapat diulang.
- Check-out sebelum pukul **16:00** (Senin-Kamis) atau **16:30** (Jumat)
  ditandai sebagai pulang lebih awal.
- Waktu aplikasi menggunakan zona waktu **Asia/Jakarta**.

### 3. Melihat dan mengunduh riwayat

Menu **Riwayat Presensi** menampilkan presensi milik akun yang sedang login.
Gunakan filter nama/status/tanggal, sorting, dan pagination untuk menemukan
data. Tombol export mengunduh hasil sesuai filter sebagai file PDF.

## Panduan admin

Login menggunakan akun dengan role `admin` untuk membuka dashboard admin.
Admin dapat:

- Melihat ringkasan presensi pegawai pada bulan berjalan.
- Melihat ringkasan presensi hari ini dan tren tujuh hari terakhir.
- Melihat jumlah pegawai dan daftar pegawai yang terlambat.
- Membuka daftar pegawai.
- Membuka laporan presensi seluruh pegawai.
- Mencari, memfilter, mengurutkan, melakukan pagination, dan mengunduh laporan
  presensi pegawai dalam format PDF.

Laporan admin hanya mencakup akun dengan role `pegawai`; presensi akun admin
tidak termasuk dalam laporan tersebut. Pada versi ini admin tidak mengelola
data pegawai melalui form CRUD. Akun dapat dibuat melalui registrasi atau
melalui seeder/database sesuai kebutuhan deployment.

## Pengujian

Jalankan seluruh test Laravel dengan:

```bash
php artisan test
```

Atau gunakan script Composer:

```bash
composer run test
```

Test menggunakan konfigurasi database testing yang ditentukan oleh Laravel.
Jangan menjalankan `migrate:fresh` pada database development/produksi karena
perintah tersebut menghapus seluruh tabel dan data.

## Struktur penting

```text
app/Http/Controllers/   Controller autentikasi, presensi, laporan, dan admin
app/Models/              Model User dan Attendance
database/migrations/     Struktur tabel users, sessions, dan attendances
database/seeders/        Data demo admin, pegawai, dan presensi
resources/views/         Tampilan login, dashboard, laporan, dan admin
routes/web.php           Daftar route aplikasi
```

## Catatan deployment dan keamanan

- Jangan commit file `.env` atau membagikan `APP_KEY`.
- Gunakan `APP_DEBUG=false` di produksi.
- Gunakan password unik dan kuat; jangan memakai password demo `password`.
- Atur `APP_URL` sesuai alamat deployment.
- Jalankan `php artisan config:cache` dan `php artisan route:cache` setelah
  konfigurasi produksi final.
- Pastikan folder `storage` dan `bootstrap/cache` dapat ditulis oleh web
  server.
- PDF dibuat dari data yang sedang difilter dan tidak membutuhkan koneksi
  internet.

## Lisensi

Project ini menggunakan Laravel Framework yang berlisensi MIT. Detail lisensi
aplikasi dapat ditambahkan sesuai kebijakan pemilik project.
