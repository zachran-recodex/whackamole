# Game Whack-A-Mole dengan Sistem Otentikasi PHP

Game klasik Whack-A-Mole yang telah ditingkatkan dengan sistem otentikasi PHP MySQL untuk melacak skor pemain dan menyediakan papan peringkat.

![Whack-A-Mole Game](css/hammer.png)

## Fitur

- **Gameplay Whack-A-Mole Klasik**: Pukul tikus tanah saat muncul dari lubang untuk mendapatkan poin
- **Beberapa Tingkat Kesulitan**: Mode mudah, sedang, dan sulit
- **Pengaturan yang Dapat Disesuaikan**: Kontrol kecepatan tikus, durasi permainan, dan efek suara
- **Sistem Otentikasi Pengguna**:
  - Pendaftaran dan login pengguna
  - Fitur reset kata sandi
  - Pengelolaan profil pengguna
- **Pelacakan Skor**:
  - Riwayat skor pribadi
  - Papan peringkat global
  - Penyaringan skor berdasarkan tingkat kesulitan
- **Desain Responsif**: Dapat dimainkan di desktop dan perangkat mobile

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- XAMPP atau lingkungan pengembangan lokal serupa
- Browser web dengan JavaScript diaktifkan

## Instalasi dengan XAMPP

1. **Instal XAMPP**:
   - Unduh dan instal [XAMPP](https://www.apachefriends.org/download.html) jika Anda belum memilikinya
   - Pastikan Anda menginstal versi yang menyertakan PHP dan MySQL

2. **Unduh atau Clone Project**:
   - Unduh file ZIP atau clone repositori ini
   - Ekstrak file ke folder htdocs XAMPP Anda (biasanya di `C:\xampp\htdocs\whackamole`)

3. **Mulai Layanan XAMPP**:
   - Buka XAMPP Control Panel
   - Klik tombol "Start" untuk Apache dan MySQL
   - Pastikan kedua layanan berjalan (indikator akan menjadi hijau)

4. **Akses Game**:
   - Buka browser web Anda
   - Navigasikan ke `http://localhost/whackamole`
   - Database dan tabel akan otomatis dibuat pada akses pertama

## Pengaturan Manual Database (Opsional)

Jika Anda lebih suka menyiapkan database secara manual daripada membiarkan aplikasi melakukannya secara otomatis:

1. **Buat Database**:
   - Buka phpMyAdmin (biasanya di `http://localhost/phpmyadmin`)
   - Buat database baru bernama `whackamole_game`

2. **Import Skema SQL**:
   - Gunakan SQL berikut untuk membuat tabel yang diperlukan:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL,
    difficulty VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Konfigurasi

### Pengaturan Database

Jika Anda perlu mengubah pengaturan koneksi database (port berbeda, username, atau password):

1. Buka file `config/database.php`
2. Perbarui konstanta berikut:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'whackamole_game');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Password default XAMPP biasanya kosong
   define('DB_PORT', '3306'); // Port default MySQL di XAMPP
   ```

### Pengaturan Game

Game ini memiliki beberapa pengaturan yang dapat disesuaikan di `js/script.js`:

- Kecepatan munculnya tikus tanah
- Durasi permainan
- Efek suara

## Cara Penggunaan

### Bermain sebagai Tamu

1. Klik "Play as Guest" pada layar login
2. Pilih tingkat kesulitan
3. Klik "START" untuk mulai bermain
4. Pukul tikus tanah saat muncul untuk mendapatkan poin

### Bermain sebagai Pengguna Terdaftar

1. Daftar akun baru atau masuk dengan kredensial yang ada
2. Pilih tingkat kesulitan
3. Mainkan game
4. Skor Anda akan otomatis disimpan ke profil Anda
5. Lihat skor Anda dan peringkat global di dashboard

## Struktur File

```
whackamole/
├── assets/
│   ├── css/
│   │   └── auth.css
│   └── js/
│       └── auth.js
├── config/
│   └── database.php
├── css/
│   └── [Stylesheet dan aset game]
├── includes/
│   ├── auth.php
│   ├── functions.php
│   └── session.php
├── js/
│   └── script.js
├── dashboard.php
├── index.php
├── login.php
├── logout.php
├── profile.php
├── register.php
├── reset-password.php
└── README.md
```

## Pemecahan Masalah

### Masalah Koneksi Database

- Periksa apakah layanan XAMPP berjalan
- Pastikan MySQL berjalan dan dapat diakses
- Pastikan kredensial database di `config/database.php` sesuai dengan pengaturan XAMPP Anda

### Game Tidak Bisa Dimulai

- Periksa konsol browser untuk error JavaScript
- Pastikan semua aset game dimuat dengan benar
- Coba bersihkan cache dan cookie browser

### Masalah Otentikasi

- Jika pendaftaran gagal, periksa apakah username atau email sudah digunakan
- Untuk masalah login, gunakan fungsi reset password
- Masalah sesi sering dapat diatasi dengan membersihkan cookie browser

## Catatan Keamanan

- Aplikasi ini menggunakan hashing password untuk penyimpanan yang aman
- Perlindungan CSRF diimplementasikan untuk formulir
- Sanitasi input membantu mencegah serangan XSS
- Manajemen sesi mencakup perlindungan terhadap fiksasi sesi

## Informasi Tambahan

Jika Anda mengalami masalah port yang berbeda di XAMPP:
- Secara default, Apache XAMPP berjalan di port 80
- Jika port 80 sudah digunakan, XAMPP mungkin menggunakan port alternatif (misalnya 8080)
- Jika Apache berjalan di port alternatif, akses game melalui: `http://localhost:8080/whackamole`