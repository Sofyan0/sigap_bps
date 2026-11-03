<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


# SIGAP Situbondo — Dashboard Harga Komoditas (Laravel + MySQL)

Paket ini adalah **source overlay** untuk proyek Laravel 12 baru (bukan folder `vendor/` atau instalasi Laravel penuh). Fitur:
- Dashboard perbandingan harga pusat vs Kabupaten Situbondo
- Filter rentang tanggal dan komoditas
- Grafik Chart.js
- Tabel data dengan selisih harga dan persentase
- Input data manual sederhana
- Sinkronisasi pusat yang dapat dikonfigurasi lewat `.env`
- Perintah sinkronisasi manual dan Laravel Scheduler
- Log hasil sinkronisasi

> Penting: endpoint API SP2KP yang dapat dipakai publik belum diasumsikan/ditetapkan dalam paket ini. Sinkronisasi otomatis hanya bekerja setelah Anda memiliki URL/API resmi yang mengizinkan akses dan menyesuaikan pemetaan JSON di `app/Services/CentralPriceSyncService.php`. Jangan mengandalkan scraping situs tanpa izin atau API yang stabil. Data contoh harus diganti dengan data aktual.

## 1. Buat proyek Laravel 12

Pastikan PHP memenuhi kebutuhan Laravel 12 (PHP 8.2+), Composer, dan MySQL tersedia.

```bash
cd D:\
composer create-project laravel/laravel sigap-situbondo
cd sigap-situbondo
```

Ekstrak isi ZIP ini ke folder proyek `sigap-situbondo`, lalu izinkan penimpaan file bila diminta. Struktur `app/`, `config/`, `database/`, `resources/`, `routes/` dan `README.md` akan ditambahkan.

## 2. Siapkan database

Buat database MySQL, misalnya `sigap_situbondo`, lalu salin `.env.example.sigap` ke referensi konfigurasi Anda atau salin nilai-nilainya ke `.env` proyek Laravel:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigap_situbondo
DB_USERNAME=root
DB_PASSWORD=

SIGAP_CENTRAL_API_URL=
SIGAP_CENTRAL_API_TOKEN=
SIGAP_CENTRAL_TIMEOUT=20
SIGAP_CENTRAL_JSON_PATH=data
SIGAP_SYNC_ENABLED=false
```

`SIGAP_CENTRAL_JSON_PATH` adalah jalur sederhana menuju array data pada respons JSON. Contoh `data`, `results`, atau kosong jika respons JSON langsung berupa array.

## 3. Pasang source dan jalankan migrasi

```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Opsional, masukkan data contoh untuk menguji tampilan:

```bash
php artisan db:seed --class=SigapDemoSeeder
```

Data seeder hanyalah data demo dan bukan harga resmi.

## 4. Cara format data API pusat

Service mengharapkan tiap item berupa objek dengan properti yang dapat dipetakan, contoh:

```json
{
  "data": [
    {
      "commodity": "Beras Medium",
      "price": 13500,
      "date": "2026-10-14",
      "market": "Pasar Contoh"
    }
  ]
}
```

Jika API resmi menggunakan nama properti berbeda, edit `mapItem()` di:
`app/Services/CentralPriceSyncService.php`

Variabel `SIGAP_CENTRAL_API_URL` harus berupa endpoint yang memang tersedia dan Anda berhak mengaksesnya. Token opsional dikirim sebagai `Authorization: Bearer ...`.

## 5. Jalankan sinkronisasi

Sinkronisasi manual:

```bash
php artisan sigap:sync-central
```

Aktifkan dengan mengubah:

```env
SIGAP_SYNC_ENABLED=true
SIGAP_CENTRAL_API_URL=https://alamat-api-resmi-anda
```

Scheduler dijadwalkan sekali setiap hari. Untuk menjalankan scheduler di lingkungan lokal Windows, jalankan perintah berikut berulang melalui Task Scheduler Windows (misalnya setiap menit):

```bash
php artisan schedule:run
```

Di server Linux, tambahkan cron Laravel standar:

```cron
* * * * * cd /path/ke/sigap-situbondo && php artisan schedule:run >> /dev/null 2>&1
```

Jika API belum tersedia, sinkronisasi tidak akan mengarang data: perintah akan berhenti dengan pesan konfigurasi yang jelas.

## 6. Menambahkan data

Gunakan formulir “Tambah data” pada dashboard. Pilih sumber **Pusat** atau **Kabupaten Situbondo**. Untuk data kabupaten, input harga hasil pemantauan lokal. Pastikan komoditas, satuan, tanggal, dan pasar konsisten saat dibandingkan.

## 7. Catatan produksi

- Tambahkan autentikasi dan otorisasi admin sebelum aplikasi dipublikasikan.
- Tambahkan validasi dan audit sesuai kebijakan instansi.
- Jangan menaruh token API di source code atau Git; gunakan `.env`.
- Konfirmasi lisensi, izin, dokumentasi, batas akses, dan definisi data dari penyedia sumber.
- Data pusat dan kabupaten bisa berbeda tanggal/pasar/satuan. Perbandingan hanya bermakna jika kriterianya sebanding.
