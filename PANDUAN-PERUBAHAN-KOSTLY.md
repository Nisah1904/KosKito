# Kostly — pembaruan tampilan

## Perubahan
- Layout aplikasi konsisten dengan sidebar, topbar pencarian, navigasi akun berdasarkan role, dan footer.
- Sidebar responsif untuk layar laptop dan ponsel.
- Penataan ulang komponen halaman, kartu kost, form, dashboard, tabel, detail, booking, dan kalkulator biaya.
- Data demo tiga kost lama dihapus dari sumber data awal. Halaman menampilkan empty state sampai data kost terverifikasi dimasukkan.
- Tidak lagi memakai foto stok sebagai foto pengganti untuk properti baru; jika foto belum ada, tampil placeholder.

## File penting yang diperbarui
- `resources/views/layouts/app.blade.php`
- `public/css/kostly.css`
- `app/Http/Controllers/KostlyController.php`
- `resources/views/welcome.blade.php`
- `resources/views/recommendations.blade.php`
- `resources/views/compare.blade.php`
- `resources/views/cost-planner.blade.php`
- beberapa view gambar properti untuk placeholder kosong

## Menjalankan di komputer lokal
1. Salin perubahan ke folder project lokal atau gunakan project ini sebagai dasar.
2. Jangan mengganti file `.env` milik project lokal.
3. Pastikan PHP dan Composer tersedia serta dependensi Laravel sudah terpasang.
4. Jalankan `php artisan view:clear` lalu `php artisan serve`.

## Catatan integrasi data
Versi ini tidak mengisi contoh kost palsu. Saat data tiga kost Bukit Palembang tersedia, data tersebut perlu dimasukkan ke sumber data yang sesuai. Saat ini sebagian proses login, properti, dan booking pada project awal masih memakai session; agar data permanen dan dapat dikelola banyak akun, tahap berikutnya perlu menghubungkannya ke tabel database dan autentikasi yang persisten.
