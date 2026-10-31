# Kostly — catatan perbaikan final

## Perbaikan yang dilakukan
- Login memvalidasi peran yang dipilih. Jika akun terdaftar sebagai Penyewa, akun tidak akan diam-diam masuk sebagai Pemilik Kost; pesan error menjelaskan peran yang benar.
- Form daftar/login mempertahankan pilihan dan input saat validasi gagal serta menampilkan pesan validasi.
- Form tambah kost sekarang menerima deskripsi, latitude, dan longitude; koordinat valid akan digunakan oleh peta pada halaman pencarian dan detail.
- Listing publik dan halaman detail hanya menampilkan kost berstatus `approved`.
- Booking hanya dapat diajukan oleh Penyewa untuk kost yang sudah disetujui dan masih memiliki kamar. Tanggal mulai tidak boleh di masa lalu.
- Saat pemilik menerima booking, jumlah kamar tersedia berkurang. Jika booking yang diterima kemudian ditolak, ketersediaan kamar dikembalikan.
- Statistik pengguna admin menggunakan jumlah akun dari database, bukan angka contoh tetap.
- CSS final menyamakan grid kartu menjadi tiga kolom di semua lebar layar dan memaksa panel peta berada setelah grid.

## Cara menjalankan di Windows/XAMPP
1. Backup folder proyek dan database terlebih dahulu.
2. Ekstrak folder `kost-laravel` ke `C:\xampp\htdocs\kost-laravel` (atau lokasi proyek kamu).
3. Buka terminal pada folder tersebut.
4. Jalankan `C:\xampp\php\php.exe artisan optimize:clear`.
5. Jika database belum memiliki tabel aplikasi, jalankan `C:\xampp\php\php.exe artisan migrate`. Perintah ini menambahkan tabel sesuai migration yang belum berjalan; jangan gunakan `migrate:fresh` pada database berisi data.
6. Jalankan `C:\xampp\php\php.exe artisan serve` lalu buka `http://127.0.0.1:8000`.

Konfigurasi `.env` pada paket ini memakai SQLite dan file `database/database.sqlite`. Jika ingin memakai MySQL XAMPP, buat database bernama `kostly`, lalu atur `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=kostly`, `DB_USERNAME=root`, dan `DB_PASSWORD=` pada `.env`, kemudian jalankan migration yang belum diterapkan.

## Alur data
1. Daftar akun melalui menu Daftar dan pilih Penyewa atau Pemilik Kost.
2. Pemilik masuk, buka **Tambah Kost**, isi data, dan ajukan.
3. Pengajuan berstatus `pending`; admin perlu masuk memakai akun ber-role `admin` dan menyetujui pengajuan.
4. Setelah disetujui, kost tampil untuk penyewa. Agar penanda muncul di peta, pemilik perlu mengisi latitude dan longitude yang benar saat mengajukan kost.
5. Penyewa mengajukan booking. Pemilik menerima/menolak dari menu Booking Masuk.

## Catatan verifikasi
- Pemeriksaan sintaks PHP berhasil dan daftar route Laravel berhasil dimuat.
- Tes PHPUnit dan pemeriksaan database tidak dapat dijalankan di lingkungan pembuat ZIP ini karena ekstensi PHP seperti `DOM`, `mbstring`, `xmlwriter`, dan driver PDO SQLite tidak tersedia di lingkungan tersebut. Jalankan pemeriksaan akhir di instalasi PHP/XAMPP kamu.
