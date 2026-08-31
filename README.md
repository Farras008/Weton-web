# Weton Online

## Pembayaran pembacaan lengkap (DOKU Checkout)

Kalkulator Weton serta data Primbon tetap berbasis file PHP; MySQL dipakai untuk transaksi DOKU Checkout.

1. Buat database MySQL kosong di hosting, lalu impor [database/schema.sql](database/schema.sql).
   Jika Anda sudah mengimpor schema versi sebelumnya, jalankan sekali [database/migrations/002_replace_louvin_with_doku_checkout.sql](database/migrations/002_replace_louvin_with_doku_checkout.sql), bukan schema penuh lagi.
2. Satu-satunya konfigurasi aplikasi adalah environment variables yang dibaca oleh [config/config.php](config/config.php). Untuk local, salin `.env.example` menjadi `.env` lalu isi nilainya; loader kecil di `config/config.php` hanya mengisi environment variable yang belum ada. Untuk deployment, gunakan key yang sama pada panel hosting atau `.env` yang diunggah terpisah dari source code. File `.env` tidak di-commit.
3. DOKU Checkout memakai `https://api-sandbox.doku.com` otomatis saat `DOKU_ENV=sandbox`. Pastikan `APP_URL` adalah URL HTTPS publik yang tepat. Setelah deployment, set QRIS Notify URL di DOKU ke `https://domain-anda/api/doku/notification`.

Status frontend dipoll dari database; status tidak pernah ditentukan oleh modal/redirect. Endpoint notification memverifikasi signature HMAC-SHA256 DOKU dari raw body dan secara idempoten menandai transaksi sebagai `PAID`.
