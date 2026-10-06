# Panduan Instalasi Nizarctyl Panel di VPS

> Panel ini berbasis **Pterodactyl Panel 1.15.1** yang telah dimodifikasi dengan fitur tambahan:
> - Registrasi user
> - Billing & Shop (Midtrans)
> - Auto-provisioning server setelah pembayaran

---

## Instalasi Otomatis (Rekomendasi)

Jalankan satu perintah ini di VPS kamu:

```bash
bash <(curl -s https://raw.githubusercontent.com/nizarrahman/panel/main/install.sh)
```



Script akan menanyakan:
- Domain panel
- Email & password admin
- Konfigurasi database
- SMTP mail
- API key Midtrans (opsional)

Setelah selesai, panel langsung bisa diakses.

---

## Instalasi Manual (Step by Step)

| Komponen | Versi Minimum |
|---|---|
| OS | Ubuntu 20.04 / 22.04 / Debian 11 |
| PHP | 8.2 atau 8.3 |
| MySQL | 8.0+ |
| Redis | 6.0+ |
| Nginx | 1.18+ |
| Composer | 2.x |
| Node.js | 18+ (hanya untuk build frontend) |
| RAM | 1 GB (minimal), 2 GB (rekomendasi) |

---

## 1. Persiapan Server

### Update sistem
```bash
apt update && apt upgrade -y
```

### Install dependensi dasar
```bash
apt install -y curl wget git unzip software-properties-common
```

---

## 2. Install PHP 8.2

```bash
apt install -y lsb-release ca-certificates apt-transport-https
add-apt-repository ppa:ondrej/php -y
apt update

apt install -y php8.2 php8.2-fpm php8.2-cli \
  php8.2-mysql php8.2-mbstring php8.2-xml \
  php8.2-curl php8.2-zip php8.2-bcmath \
  php8.2-gd php8.2-redis php8.2-posix
```

Verifikasi:
```bash
php -v
```

---

## 3. Install MySQL

```bash
apt install -y mysql-server
mysql_secure_installation
```

Buat database dan user:
```bash
mysql -u root -p
```

```sql
CREATE DATABASE nizarctyl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nizarctyl'@'127.0.0.1' IDENTIFIED BY 'PASSWORD_KUAT_DISINI';
GRANT ALL PRIVILEGES ON nizarctyl.* TO 'nizarctyl'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

> ⚠️ Ganti `PASSWORD_KUAT_DISINI` dengan password yang kuat.

---

## 4. Install Redis

```bash
apt install -y redis-server
systemctl enable --now redis-server
```

---

## 5. Install Nginx

```bash
apt install -y nginx
systemctl enable --now nginx
```

---

## 6. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
composer --version
```

---

## 7. Upload & Setup Panel

### Buat direktori
```bash
mkdir -p /var/www/nizarctyl
cd /var/www/nizarctyl
```

### Upload file panel
Zip seluruh folder panel dari komputer kamu, lalu upload ke VPS:
```bash
# Di komputer kamu (PowerShell)
Compress-Archive -Path "D:\sites\panel-1.15.1\*" -DestinationPath "panel.zip"

# Upload via SCP
scp panel.zip root@IP_VPS:/var/www/nizarctyl/

# Di VPS
cd /var/www/nizarctyl
unzip panel.zip
```

Atau clone dari Git jika sudah dipush:
```bash
git clone https://github.com/nizarrahman/panel.git /var/www/nizarctyl
cd /var/www/nizarctyl
```

### Set permission
```bash
chmod -R 755 /var/www/nizarctyl
chown -R www-data:www-data /var/www/nizarctyl
```

---

## 8. Konfigurasi Environment

```bash
cd /var/www/nizarctyl
cp .env.example .env
nano .env
```

Isi nilai berikut di `.env`:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=                          # akan diisi otomatis di langkah berikutnya
APP_URL=https://panel.domain.kamu.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nizarctyl
DB_USERNAME=nizarctyl
DB_PASSWORD=PASSWORD_KUAT_DISINI

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=file
QUEUE_CONNECTION=redis
SESSION_DRIVER=file

MAIL_MAILER=smtp
MAIL_HOST=smtp.domain.kamu.com
MAIL_PORT=465
MAIL_USERNAME=email@domain.kamu.com
MAIL_PASSWORD=password_email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@domain.kamu.com
MAIL_FROM_NAME="Nizarctyl Panel"

# Aktifkan registrasi user
ALLOW_REGISTRATION=true
```

---

## 9. Install Dependencies PHP

```bash
cd /var/www/nizarctyl
composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-posix
```

---

## 10. Generate App Key & Migrasi Database

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
```

> Jika muncul error foreign key, lihat bagian **Troubleshooting** di bawah.

---

## 11. Buat Admin User

```bash
php artisan p:user:make
```

Ikuti promptnya:
- Is this user an administrator? → `yes`
- Email, Username, First Name, Last Name, Password

---

## 12. Konfigurasi Nginx

```bash
nano /etc/nginx/sites-available/nizarctyl
```

Isi dengan konfigurasi berikut (ganti `panel.domain.kamu.com`):

```nginx
server {
    listen 80;
    server_name panel.domain.kamu.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name panel.domain.kamu.com;

    root /var/www/nizarctyl/public;
    index index.php;

    access_log /var/log/nginx/nizarctyl.access.log;
    error_log  /var/log/nginx/nizarctyl.error.log error;

    client_max_body_size 100m;
    client_body_timeout 120s;

    ssl_certificate     /etc/letsencrypt/live/panel.domain.kamu.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/panel.domain.kamu.com/privkey.pem;
    ssl_session_cache   shared:SSL:10m;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         "ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384";
    ssl_prefer_server_ciphers on;

    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Robots-Tag none;
    add_header Content-Security-Policy "frame-ancestors 'self'";
    add_header X-Frame-Options DENY;
    add_header Referrer-Policy same-origin;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size = 100M";
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTP_PROXY "";
        fastcgi_intercept_errors off;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Aktifkan site:
```bash
ln -s /etc/nginx/sites-available/nizarctyl /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

---

## 13. Install SSL (Let's Encrypt)

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d panel.domain.kamu.com
```

---

## 14. Setup Queue Worker (Supervisor)

```bash
apt install -y supervisor
nano /etc/supervisor/conf.d/nizarctyl-worker.conf
```

Isi:
```ini
[program:nizarctyl-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/nizarctyl/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/nizarctyl-worker.log
stopwaitsecs=3600
```

Aktifkan:
```bash
supervisorctl reread
supervisorctl update
supervisorctl start nizarctyl-worker:*
supervisorctl status
```

---

## 15. Setup Cron Job

```bash
crontab -e
```

Tambahkan baris ini:
```
* * * * * php /var/www/nizarctyl/artisan schedule:run >> /dev/null 2>&1
```

---

## 16. Konfigurasi Billing (Midtrans)

1. Login ke panel sebagai admin
2. Buka **Admin → Billing Settings**
3. Centang **Aktifkan Billing**
4. Isi **Server Key** dan **Client Key** dari [Midtrans Dashboard](https://dashboard.midtrans.com)
5. Daftarkan Webhook URL di Midtrans Dashboard:
   - Menu: **Settings → Configuration → Payment Notification URL**
   - Isi: `https://panel.domain.kamu.com/billing/webhook/midtrans`
6. Buat plan di **Admin → Billing → Plans**
   - Pilih egg, set resource, dan environment variables

---

## 17. Optimasi Production

```bash
cd /var/www/nizarctyl
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> ⚠️ Setiap kali update file `.env` atau kode, jalankan ulang perintah di atas.

---

## Troubleshooting

### Error: `external_id cannot be null`
```bash
php artisan tinker --execute="DB::statement('ALTER TABLE users MODIFY external_id VARCHAR(191) NULL DEFAULT NULL');"
```

### Error: `mysql is not recognized`
Migration memakai schema squash. Rename dulu file schema:
```bash
mv database/schema/mysql-schema.sql database/schema/mysql-schema.sql.bak
php artisan migrate --force
```

### Error: Foreign key incompatible
Pastikan kolom `user_id` di tabel billing menggunakan tipe yang sama dengan `users.id` (unsigned int).

### Queue tidak berjalan
```bash
supervisorctl restart nizarctyl-worker:*
```

### Permission error
```bash
chown -R www-data:www-data /var/www/nizarctyl
chmod -R 755 /var/www/nizarctyl/storage
chmod -R 755 /var/www/nizarctyl/bootstrap/cache
```

---

## Catatan Penting

- **Wings (daemon)** perlu diinstall terpisah di node server. Lihat dokumentasi Pterodactyl Wings: https://pterodactyl.io/wings/1.0/installing.html
- Pastikan minimal **1 Node** sudah dikonfigurasi dan punya **free allocation** agar auto-provisioning server bisa bekerja
- Untuk environment production Midtrans, ganti ke mode **Production** di Billing Settings
- Backup database secara rutin: `mysqldump nizarctyl > backup.sql`
