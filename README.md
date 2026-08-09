# Weton Online

## Pembayaran pembacaan lengkap

Fitur pembayaran memakai Duitku Create Invoice dan SMTP. Kalkulator Weton serta data Primbon tetap berbasis file PHP; MySQL hanya dipakai untuk transaksi.

1. Buat database MySQL kosong di hosting, lalu impor [database/schema.sql](database/schema.sql).
   Jika Anda sudah mengimpor schema versi sebelumnya, jalankan sekali [database/migrations/001_add_email_sending_at.sql](database/migrations/001_add_email_sending_at.sql), bukan schema penuh lagi.
2. Untuk shared hosting, copy [config/local.example.php](config/local.example.php) menjadi `config/local.php`, lalu isi database (host, nama, user, password), Duitku (Merchant Code, API Key, `environment: sandbox`), SMTP (host, port, username, password, encryption, from address), dan URL aplikasi. `config/local.php` sudah diabaikan Git. Atau set key yang sama sebagai environment variables melalui panel hosting. File `.env` tidak dibaca otomatis oleh PHP pada proyek ini; `.env.example` hanya referensi key.
3. Jalankan `composer install --no-dev --optimize-autoloader` saat deploy untuk memasang PHPMailer. Upload folder `vendor/` bila hosting tidak menjalankan Composer.
4. Setel callback project Duitku ke URL publik aktual untuk `payment/callback.php`, dan return URL ke `payment/return.php`. Keduanya dibentuk dari `APP_URL`; pastikan `APP_URL` adalah URL HTTPS publik yang tepat. Gunakan `DUITKU_ENV=sandbox` sebelum berpindah ke kredensial production.

`payment/return.php` hanya menampilkan status yang tersimpan di database; status tidak pernah ditentukan dari parameter redirect. Callback memeriksa signature HMAC-SHA256 dan melakukan Check Transaction sebelum mengubah transaksi menjadi sukses.

Untuk mengirim ulang email yang gagal, jalankan dari command line (misalnya cron yang terlindungi):

```sh
php payment/retry_emails.php
```

Script retry tidak dapat diakses via web dan hanya memproses transaksi `SUCCESS` yang belum memiliki `email_sent_at`.
