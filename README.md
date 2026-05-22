# Bedah Saham - Aplikasi Analisis Saham Indonesia

Bedah Saham adalah aplikasi web modern untuk analisis saham Indonesia yang memanfaatkan teknologi AI dan data real-time dari API keuangan internasional. Aplikasi ini memungkinkan pengguna untuk menganalisis, memantau, dan memahami pergerakan saham dengan lebih mendalam.

## 📋 Daftar Isi

- [Fitur](#-fitur)
- [Tech Stack](#-tech-stack)
- [Prasyarat](#-prasyarat)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Database](#-database)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Struktur Project](#-struktur-project)
- [Integrasi API](#-integrasi-api)
- [Penggunaan Fitur](#-penggunaan-fitur)
- [Testing](#-testing)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)

## ✨ Fitur

### Fitur Utama

- **Dashboard Interaktif**: Tampilan ringkasan pasar saham dengan chart dan statistik real-time
- **Detail Saham**: Informasi lengkap tentang setiap emiten (perusahaan saham)
- **Analisis AI**: Analisis prediktif menggunakan AI (Ollama) untuk memberikan insight tentang pergerakan saham
- **Grafik Interaktif**: Visualisasi data saham dengan berbagai format chart
- **Sistem Autentikasi**: Login dan registrasi pengguna yang aman
- **Sistem Token/Paket**: Paket berlangganan untuk akses fitur premium
- **Sinkronisasi Data**: Perintah CLI untuk sinkronisasi data saham otomatis

### Fitur Tambahan

- Riwayat transaksi token
- Manajemen paket berlangganan
- Responsif dan mobile-friendly (Tailwind CSS v4)
- Error handling yang komprehensif

## 🛠️ Tech Stack

### Backend

- **Framework**: CodeIgniter 4 (PHP 8.2+)
- **Database**: MySQL/MySQLi
- **API Integration**:
  - Financial Modeling Prep API (FMP) - Data pasar saham
  - Ollama API - AI Analysis
- **Authentication**: JWT-based token system

### Frontend

- **CSS Framework**: Tailwind CSS v4
- **Architecture**: Server-side rendering (MVC)
- **Responsif Design**: Mobile-first approach

### DevOps & Tools

- **Docker**: Containerization
- **Nginx**: Web server
- **Composer**: PHP dependency management
- **NPM**: Node package management
- **PHPUnit**: Unit testing
- **CLI**: Spark commands untuk admin tasks

## 📦 Prasyarat

- **PHP**: 8.2 atau lebih tinggi
- **MySQL**: 5.7 atau lebih tinggi
- **Composer**: Untuk dependency PHP
- **Node.js & NPM**: Untuk Tailwind CSS
- **Docker** (opsional): Untuk development environment
- **API Keys**:
  - Financial Modeling Prep API key
  - Ollama API key

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/your-username/bedah-saham.git
cd bedah-saham
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Setup Environment File

```bash
cp .env.example .env
```

Atau buat file `.env` baru dengan konfigurasi berikut.

### 5. Generate Encryption Key (Opsional)

```bash
php spark key:generate
```

## 🌐 Setup Virtual Host XAMPP

### Untuk Akses via `bedah-saham.test`

#### Windows

##### 1. Edit File `hosts`

1. Buka `C:\Windows\System32\drivers\etc\hosts` dengan administrator privileges
2. Gunakan Notepad atau text editor favorit
3. Tambahkan baris berikut di akhir file:

```
127.0.0.1  bedah-saham.test
```

4. Simpan file

##### 2. Konfigurasi Virtual Host Apache

1. Buka file `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. Scroll ke akhir file dan tambahkan konfigurasi berikut:

```apache
<VirtualHost *:80>
    ServerName bedah-saham.test
    ServerAlias www.bedah-saham.test
    DocumentRoot "C:/xampp/htdocs/bedah-saham/public"

    <Directory "C:/xampp/htdocs/bedah-saham/public">
        Options +FollowSymLinks +Indexes +MultiViews
        AllowOverride All
        Require all granted

        # Rewrite rules untuk CodeIgniter
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /

            # Jika file atau folder exist, gunakan langsung
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d

            # Redirect ke index.php
            RewriteRule ^(.*)$ index.php/$1 [L]
        </IfModule>
    </Directory>

    # Access logs
    CustomLog "C:/xampp/apache/logs/bedah-saham-access.log" combined
    ErrorLog "C:/xampp/apache/logs/bedah-saham-error.log"
</VirtualHost>
```

3. Simpan file

##### 3. Aktifkan mod_rewrite

1. Buka `C:\xampp\apache\conf\httpd.conf`
2. Cari baris:

```
#LoadModule rewrite_module modules/mod_rewrite.so
```

3. Hapus `#` di depannya sehingga menjadi:

```
LoadModule rewrite_module modules/mod_rewrite.so
```

4. Simpan file

##### 4. Restart Apache

1. Buka XAMPP Control Panel
2. Stop Apache (jika sedang berjalan)
3. Click "Start" untuk memulai Apache kembali
4. Tunggu sampai status menunjukkan running (hijau)

##### 5. Verifikasi Setup

1. Buka browser dan akses: `http://bedah-saham.test`
2. Aplikasi seharusnya sudah berjalan
3. Jika ada error, cek:
   - Log file di `C:\xampp\apache\logs\bedah-saham-error.log`
   - File `hosts` sudah tersimpan dengan benar
   - `.htaccess` ada di folder `public`

#### macOS/Linux

##### 1. Edit File `hosts`

```bash
sudo nano /etc/hosts
```

Tambahkan:

```
127.0.0.1  bedah-saham.test
```

Simpan dengan `Ctrl+X`, `Y`, `Enter`

##### 2. Konfigurasi Virtual Host

Edit file Apache vhosts (lokasi bisa berbeda):

```bash
sudo nano /usr/local/etc/apache2/extra/httpd-vhosts.conf
# atau
sudo nano /etc/apache2/sites-available/bedah-saham.test.conf
```

Tambahkan konfigurasi yang sama seperti Windows (sesuaikan path):

```apache
<VirtualHost *:80>
    ServerName bedah-saham.test
    ServerAlias www.bedah-saham.test
    DocumentRoot "/path/to/xampp/htdocs/bedah-saham/public"

    <Directory "/path/to/xampp/htdocs/bedah-saham/public">
        Options +FollowSymLinks +Indexes +MultiViews
        AllowOverride All
        Require all granted

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php/$1 [L]
        </IfModule>
    </Directory>
</VirtualHost>
```

##### 3. Enable Virtual Host (Linux)

```bash
sudo a2ensite bedah-saham.test
sudo systemctl restart apache2
```

##### 4. Test Configuration

```bash
sudo apachectl -t
```

Harus menampilkan `Syntax OK`

## ⚙️ Konfigurasi

### File `.env` Configuration

```env
# Environment
CI_ENVIRONMENT = development

# App Configuration
app.baseURL = 'http://bedah-saham.test/'

# Financial Modeling Prep API
FMP_API_KEY = "YOUR_FMP_API_KEY"
FMP_BASE_URL = "https://financialmodelingprep.com/stable"

# Ollama AI Configuration
OLLAMA_URL = "https://ollama.com/api/chat"
OLLAMA_API_KEY = "YOUR_OLLAMA_API_KEY"

# Database Configuration
database.default.hostname = localhost
database.default.database = bedah_saham
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306

# Session & Logging
session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
logger.threshold = 4
```

### Dapatkan API Keys

#### Financial Modeling Prep API

1. Kunjungi [https://financialmodelingprep.com](https://financialmodelingprep.com)
2. Daftar akun gratis
3. Copy API key dari dashboard
4. Paste ke `.env` pada `FMP_API_KEY`

#### Ollama API

1. Kunjungi [https://ollama.com](https://ollama.com)
2. Setup Ollama di local atau gunakan cloud service
3. Dapatkan API key
4. Konfigurasi di `.env` pada `OLLAMA_API_KEY` dan `OLLAMA_URL`

## 🗄️ Database

### 1. Buat Database

```bash
mysql -u root
mysql> CREATE DATABASE bedah_saham CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
mysql> EXIT;
```

### 2. Jalankan Migrations

```bash
php spark migrate
```

### 3. Seed Database (Opsional)

```bash
php spark db:seed TokenPackageSeeder
```

### Struktur Database

#### Tabel Utama:

- **users**: Menyimpan data pengguna
- **emitens**: Daftar perusahaan saham
- **stock_histories**: Riwayat harga saham
- **stock_analysis**: Hasil analisis AI
- **token_packages**: Paket berlangganan
- **token_transactions**: Transaksi token pengguna

## 🎯 Menjalankan Aplikasi

### Development Mode

#### 1. Start Web Server

```bash
php spark serve
```

Aplikasi akan berjalan di `http://localhost:8080`

#### 2. Start Tailwind Watch (Terminal baru)

```bash
npm run dev
```

### Production Mode

#### Build Tailwind CSS

```bash
npm run build
```

#### Konfigurasi untuk Production

```env
CI_ENVIRONMENT = production
app.baseURL = 'https://your-domain.com/'
```

### Menggunakan Docker

#### Build dan Run

```bash
docker build -t bedah-saham .
docker run -p 80:80 bedah-saham
```

## 📁 Struktur Project

```
bedah-saham/
├── app/
│   ├── Commands/              # CLI commands
│   │   └── StockSync.php      # Command sinkronisasi saham
│   ├── Config/                # Konfigurasi aplikasi
│   ├── Controllers/           # HTTP Controllers
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── StockDetailController.php
│   │   ├── AIAnalysisController.php
│   │   ├── TokenController.php
│   │   └── ChartController.php
│   ├── Database/              # Migrations & Seeds
│   ├── Filters/               # HTTP Filters (Authentication)
│   ├── Models/                # Database Models
│   │   ├── UserModel.php
│   │   ├── EmitenModel.php
│   │   ├── StockHistoryModel.php
│   │   ├── TokenPackageModel.php
│   │   └── TokenTransactionModel.php
│   ├── Libraries/             # Custom Libraries
│   │   └── StockFetcher.php   # Library fetch data saham
│   └── Views/                 # Template HTML
│
├── public/                    # Document root
│   ├── index.php
│   ├── assets/
│   └── css/
│       └── style.css          # Compiled Tailwind CSS
│
├── tests/                     # Unit & Integration Tests
│   ├── unit/
│   ├── database/
│   └── session/
│
├── writable/                  # Writable directories
│   ├── cache/
│   ├── logs/
│   ├── uploads/
│   └── session/
│
├── vendor/                    # Composer dependencies
├── .env                       # Environment configuration
├── composer.json              # PHP dependencies
├── package.json               # Node dependencies
├── phpunit.xml.dist           # PHPUnit configuration
└── spark                      # CodeIgniter CLI
```

## 🔌 Integrasi API

### Financial Modeling Prep API

Digunakan untuk mengambil data saham real-time:

```php
// Dari StockFetcher.php
$stockData = $this->fetchStockData('BBCA'); // Ambil data saham BBCA
```

**Endpoints yang digunakan:**

- Profile perusahaan
- Historical price data
- Financial ratios
- Market data

### Ollama AI Integration

Digunakan untuk analisis prediktif dan insights:

```php
// Dari AIAnalysisController.php
$analysis = $this->analyzeStock($stockCode, $historicalData);
```

**Capabilities:**

- Natural language processing
- Pattern recognition
- Predictive analysis
- Insight generation

## 📖 Penggunaan Fitur

### 1. Registrasi & Login

- Kunjungi halaman `/auth/register` untuk membuat akun baru
- Login dengan username/email dan password
- Session akan di-simpan secara secure

### 2. Dashboard

- Halaman utama menampilkan overview pasar saham
- Grafik pergerakan indeks utama
- Top gainers dan top losers
- Quick links ke fitur lainnya

### 3. Mencari & Menganalisis Saham

- Gunakan search bar untuk mencari ticker saham
- Klik pada saham untuk melihat detail lengkap
- Lihat grafik historis dan analisis AI

### 4. Token & Paket Premium

- Lihat paket berlangganan yang tersedia di `/token`
- Beli paket untuk unlock fitur premium
- Kelola transaksi dan riwayat token

### 5. Admin Commands

#### Sinkronisasi Data Saham

```bash
php spark stock:sync
```

Perintah ini akan:

- Fetch data saham terbaru dari FMP API
- Update database dengan data terkini
- Log semua aktivitas sinkronisasi

## 🧪 Testing

### Menjalankan Unit Tests

```bash
composer test
# atau
php vendor/bin/phpunit
```

### Test Coverage

```bash
php vendor/bin/phpunit --coverage-html=coverage
```

Test files terletak di direktori `tests/`:

- `unit/` - Unit tests untuk business logic
- `database/` - Database tests
- `session/` - Session management tests

## 🤝 Kontribusi

Kontribusi sangat diterima! Untuk berkontribusi:

1. Fork repository
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📝 Lisensi

Project ini berlisensi MIT. Lihat file [LICENSE](LICENSE) untuk detail lebih lanjut.

## 📞 Support & Kontak

Jika ada pertanyaan atau bug report, silakan buka issue di repository ini atau hubungi:

- **Email**: your-email@example.com
- **GitHub Issues**: [https://github.com/your-username/bedah-saham/issues](https://github.com/your-username/bedah-saham/issues)

## 🎓 Resources & Dokumentasi

- [CodeIgniter 4 Documentation](https://codeigniter.com/docs/4.7)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Financial Modeling Prep API Docs](https://site.financialmodelingprep.com)
- [Ollama Documentation](https://github.com/ollama/ollama)
- [MySQL Documentation](https://dev.mysql.com/doc)

---

**Dibuat dengan ❤️ untuk investor dan enthusiast saham Indonesia**

Last Updated: May 22, 2026
