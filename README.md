# BedahSaham

Aplikasi web untuk menganalisis saham Indonesia dengan data real-time dari API Yahoo Finance dan fundamental Modelling Prep.

## Deskripsi

Bedah Saham adalah aplikasi berbasis web yang memungkinkan pengguna untuk:

- Melihat data real-time saham Indonesia
- Menganalisis tren harga saham
- Mendapatkan informasi fundamental perusahaan
- Memantau kinerja saham dalam periode tertentu
- Menganalisis saham menggunakan AI (kecerdasan buatan)

Aplikasi ini dibangun menggunakan framework CodeIgniter 4 dengan PHP 8.2+ dan menggunakan database MySQL.

## Fitur Utama

### 1. Market Explorer (Real-Time Table)

- Menampilkan daftar emiten pilihan dengan notasi papan bursa (Utama, Pengembangan, Akselerasi).
- Pembaruan harga otomatis tanpa penyegaran halaman menggunakan Live UI Sync.
- Filter pencarian cepat berdasarkan kode emiten atau nama perusahaan.
- Visualisasi sektor menggunakan ikon tematik untuk kemudahan navigasi.

### 2. Advanced Charting System

- Grafik interaktif bertenaga ApexCharts.js.
- Mendukung multi-range: 1D (Intraday), 1W, 1M, 6M, hingga 1Y.
- Logika Smart-Holiday: Grafik tetap menampilkan data hari bursa terakhir meskipun diakses saat hari libur atau akhir pekan.
- Sinkronisasi data antara titik dasar grafik dengan rekapan harga di dashboard.

### 3. AI Stock Analyst (DeepSeek Integration)

- Analisis fundamental dan teknikal otomatis menggunakan model DeepSeek V3 melalui Ollama.
- Menghasilkan ringkasan kondisi perusahaan, interpretasi rasio keuangan (ROE, PBV, DER), dan rekomendasi investasi (Buy/Hold/Sell) yang terstruktur.

### 4. Robust Background Sync

- Perintah CLI `php spark stock:sync` untuk memperbarui harga saham secara berkala.
- Menggunakan strategi Human-Like Requesting (Randomized Jitter dan User-Agent Rotation) untuk menghindari pembatasan frekuensi akses (rate limit).

## Instalasi

### Prasyarat

- PHP 8.2 atau lebih tinggi
- Composer
- MariaDB / MySQL
- Ollama (Sudah terinstal di mesin lokal untuk fitur AI)

### Langkah Instalasi

1. Clone repository ini:

   ```bash
   git clone https://github.com/your-username/bedah-saham.git
   cd bedah-saham
   ```

2. Install dependensi menggunakan Composer:

   ```bash
   composer install
   ```

3. Salin file konfigurasi:

   ```bash
   cp env .env
   ```

4. Konfigurasi database di file `.env`:

   ```env
   database.default.hostname = localhost
   database.default.database = bedah_saham
   database.default.username = root
   database.default.password =
   database.default.DBDriver = MySQLi
   FMP_API_KEY = 'your_api_key_here'
   FMP_BASE_URL = 'https://financialmodelingprep.com/stable/'

   ```

5. Buat database dan jalankan migrasi:

   ```bash
   php spark migrate
   ```

6. Jalankan server development:

   ```bash
   php spark serve
   ```

7. Akses aplikasi di browser:
   ```
   http://localhost:8080
   ```

## Penggunaan

### Menjalankan Sinkronisasi Data

Untuk menjalankan sinkronisasi data saham secara berkala:

```bash
php spark stock:sync
```

Perintah ini akan menjalankan proses pengambilan data saham secara otomatis dan terus-menerus.

### Struktur Database

Aplikasi ini menggunakan 3 tabel utama:

1. **emiten** - Menyimpan informasi dasar emiten saham
2. **stock_data** - Menyimpan data harga saham real-time
3. **stock_histories** - Menyimpan data historis kinerja perusahaan

## Teknologi yang Digunakan

- **Backend**: PHP 8.2+, CodeIgniter 4 Framework
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Database**: MySQL
- **API**: Yahoo Finance API, Financial Modeling Prep API
- **Chart**: ApexCharts.js
- **LLM**: Ollama dengan model : Deepseek v3.1:671b-cloud

## Kontribusi

Kontribusi sangat diallowed untuk pengembangan aplikasi ini. Untuk berkontribusi:

1. Fork repository ini
2. Buat branch fitur baru (`git checkout -b feature/NamaFitur`)
3. Commit perubahan (`git commit -am 'Tambahkan fitur baru'`)
4. Push ke branch (`git push origin feature/NamaFitur`)
5. Buat Pull Request

## Lisensi

Proyek ini dilisensikan di bawah MIT License - lihat file [LICENSE](LICENSE) untuk detail lebih lanjut.

## Kontak

Jika Anda memiliki pertanyaan atau saran, silakan hubungi kami di [diyaz.hal22@gmail.com](mailto:diyaz.hal22@gmail.com) atau buat issue di repository ini.
