# Asisten AI Kostly (Claude)

## Fitur
- **Asisten chat** (`/asisten`, wajib login): mahasiswa bertanya dalam bahasa sehari-hari. Asisten hanya menjawab dari kost berstatus *approved* di database (maks. 60 listing termurah) dan daftar kampus. Kost demo selalu disebut sebagai data contoh. Nama kost yang disebut menjadi tautan ke halaman detail.
- **Percakapan tersimpan** di tabel `ai_conversations` dan `ai_messages`. Asisten mengingat 12 pesan terakhir. Pengguna hanya bisa membuka percakapannya sendiri dan bisa menghapusnya.
- **Jelaskan dengan AI** di halaman Smart Match (`/rekomendasi`): penjelasan singkat dari skor dan alasan yang dihitung sistem. Pengunjung yang belum login melihat tautan untuk masuk.
- **Batas pemakaian**: `AI_DAILY_LIMIT` pertanyaan per akun per hari (bawaan 40). Kuota dikembalikan jika panggilan AI gagal.

## Cara kerja
Laravel menjalankan `ai/claude_bridge.py` (Symfony Process). Skrip ini memakai library `emergentintegrations` dengan model `claude-sonnet-5-5` dan meneruskan jawaban token demi token sebagai Server-Sent Events.

## Persiapan
```bash
pip install emergentintegrations --extra-index-url https://d33sy5i8bnduwe.cloudfront.net/simple/
php artisan migrate
```

Tambahkan ke `.env` (lihat `.env.example`):
```
EMERGENT_LLM_KEY=...        # Emergent Universal Key, jangan di-commit
AI_MODEL=claude-sonnet-5-5
AI_PYTHON_BIN=python3        # path python yang sudah terpasang emergentintegrations
AI_DAILY_LIMIT=40
```

Saldo key bisa ditambah di Profile → Universal Key → Add Balance.

## Catatan
- Untuk streaming lewat Nginx, header `X-Accel-Buffering: no` sudah dikirim. Jika memakai PHP-FPM, pastikan `output_buffering` tidak menahan respons.
- AI hanya merangkum data yang ada. Jarak tetap berupa perkiraan garis lurus, dan pengguna diarahkan ke halaman detail atau peta untuk mengeceknya.
