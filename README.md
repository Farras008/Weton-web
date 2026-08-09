# Weton Online

## Pembayaran pembacaan lengkap

Fitur pembayaran memakai Duitku Create Invoice dan SMTP. Kalkulator Weton serta data Primbon tetap berbasis file PHP; MySQL hanya dipakai untuk transaksi.

1. Buat database MySQL kosong di hosting, lalu impor [database/schema.sql](database/schema.sql).
   Jika Anda sudah mengimpor schema versi sebelumnya, jalankan sekali [database/migrations/001_add_email_sending_at.sql](database/migrations/001_add_email_sending_at.sql), bukan schema penuh lagi.
2. Untuk shared hosting, copy [config/local.example.php](config/local.example.php) menjadi `config/local.php`, lalu isi password database Hostinger pada `db.password`. Template telah berisi host `srv1981.hstgr.io`, database `u468044357_weton_db`, user `u468044357_weton`, dan port `3306`. Isi juga Duitku, SMTP, dan URL aplikasi. `config/local.php` sudah diabaikan Git sehingga harus dibuat/upload sendiri di hosting. Atau set key yang sama sebagai environment variables melalui panel hosting (`DB_PASSWORD`, bukan `DB_PASS`). File `.env` tidak dibaca otomatis oleh PHP pada proyek ini; `.env.example` hanya referensi key.
3. Jalankan `composer install --no-dev --optimize-autoloader` saat deploy untuk memasang PHPMailer. Upload folder `vendor/` bila hosting tidak menjalankan Composer.
4. Setel callback project Duitku ke URL publik aktual untuk `payment/callback.php`, dan return URL ke `payment/return.php`. Keduanya dibentuk dari `APP_URL`; pastikan `APP_URL` adalah URL HTTPS publik yang tepat. Gunakan `DUITKU_ENV=sandbox` sebelum berpindah ke kredensial production.

`payment/return.php` hanya menampilkan status yang tersimpan di database; status tidak pernah ditentukan dari parameter redirect. Callback memeriksa signature HMAC-SHA256 dan melakukan Check Transaction sebelum mengubah transaksi menjadi sukses.

Untuk mengirim ulang email yang gagal, jalankan dari command line (misalnya cron yang terlindungi):

```sh
php payment/retry_emails.php
```

Script retry tidak dapat diakses via web dan hanya memproses transaksi `SUCCESS` yang belum memiliki `email_sent_at`.
