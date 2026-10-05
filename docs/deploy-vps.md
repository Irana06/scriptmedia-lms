# Deploy ke VPS / server sendiri (Nginx + php-fpm)

Untuk server Ubuntu yang dikelola sendiri, termasuk server rumah di belakang Cloudflare Tunnel
(tanpa IP publik/port forwarding). Untuk hosting cPanel, lihat [../DEPLOY.md](../DEPLOY.md).

Contoh di bawah memakai domain `lms.sekolah.sch.id`, folder `/var/www/lms-engine`, dan user deploy
`deploy`. Ganti sesuai server.

## 1. Paket (sekali, perlu sudo)

```bash
sudo apt update
sudo apt install -y nginx git unzip php8.4-fpm php8.4-cli php8.4-mysql php8.4-sqlite3 php8.4-gd \
  php8.4-zip php8.4-intl php8.4-mbstring php8.4-xml php8.4-bcmath php8.4-curl
# Composer: https://getcomposer.org/download/   Node.js 20+: https://nodejs.org (atau nvm)
sudo apt install -y mysql-server     # produksi; lewati bila memakai SQLite
sudo usermod -aG www-data deploy     # user deploy satu grup dengan web server
```

## 2. Database

MySQL (disarankan untuk produksi):

```sql
CREATE DATABASE ruangkelas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ruangkelas'@'localhost' IDENTIFIED BY '<password-kuat>';
GRANT ALL PRIVILEGES ON ruangkelas.* TO 'ruangkelas'@'localhost';
```

SQLite cukup untuk demo atau sekolah kecil: tidak perlu server database, cadangan cukup satu berkas.

## 3. Folder aplikasi

```bash
sudo mkdir -p /var/www/lms-engine
sudo chown deploy:www-data /var/www/lms-engine
sudo chmod 2775 /var/www/lms-engine          # setgid: berkas baru ikut grup www-data
```

Sebagai `deploy`:

```bash
cd /var/www/lms-engine
git clone <url-repo> .                         # atau salin kode tanpa .git (git archive)
umask 002
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build                        # build untuk root domain (BUKAN build:subfolder)
cp .env.example .env && php artisan key:generate
```

`.env` produksi:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lms.sekolah.sch.id
APP_DEMO_MODE=false                 # true hanya untuk situs demo publik
TRUSTED_PROXIES=127.0.0.1           # wajib bila di belakang Cloudflare Tunnel / reverse proxy lokal
DB_CONNECTION=mysql                 # atau sqlite
DB_HOST=127.0.0.1
DB_DATABASE=ruangkelas
DB_USERNAME=ruangkelas
DB_PASSWORD=<password-kuat>
# Untuk SQLite: DB_CONNECTION=sqlite dan DB_DATABASE=/var/www/lms-engine/database/database.sqlite
QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

Lalu:

```bash
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/public storage/app/private/backups bootstrap/cache
touch database/database.sqlite                 # hanya untuk SQLite
php artisan migrate --force
php artisan storage:link
php artisan sekolah:setup                      # profil sekolah, admin pertama, tahun ajaran
# atau untuk situs demo: php artisan db:seed --class=DemoSeeder --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
chmod -R g+w storage bootstrap/cache database
find storage bootstrap/cache database -type d -exec chmod g+s {} +
```

`route:cache` aman di root domain; yang bermasalah hanya instalasi subfolder (lihat DEPLOY.md).

## 4. php-fpm pool khusus (batas upload)

Materi bisa sampai 50 MB, kiriman tugas 20 MB. Naikkan batas **hanya untuk aplikasi ini** dengan
pool tersendiri. Jangan pakai `fastcgi_param PHP_VALUE`, karena nilainya bisa terbawa ke situs lain
yang memakai worker yang sama.

`/etc/php/8.4/fpm/pool.d/lms.conf`:

```ini
[lms]
user = www-data
group = www-data
listen = /run/php/php8.4-fpm-lms.sock
listen.owner = www-data
listen.group = www-data
pm = ondemand
pm.max_children = 6
pm.process_idle_timeout = 30s
php_admin_value[upload_max_filesize] = 64M
php_admin_value[post_max_size] = 64M
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 120
```

```bash
sudo php-fpm8.4 -t && sudo systemctl restart php8.4-fpm
```

## 5. Nginx

`/etc/nginx/sites-available/lms-engine`:

```nginx
server {
    listen 80;
    server_name lms.sekolah.sch.id;
    root /var/www/lms-engine/public;
    index index.php;
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm-lms.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 120;
    }

    location = /sw.js {
        add_header Cache-Control "no-cache";
        try_files $uri =404;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/lms-engine /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

HTTPS tanpa tunnel: `sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d lms.sekolah.sch.id`.

## 6. Cloudflare Tunnel (opsional, server tanpa IP publik)

Bila `cloudflared` sudah berjalan dengan tunnel yang dikelola lewat berkas
(`/etc/cloudflared/config.yml`), tambahkan host **sebelum** aturan penutup `http_status:404`:

```yaml
ingress:
  - hostname: lms.sekolah.sch.id
    service: http://localhost:80
  - service: http_status:404
```

```bash
sudo cloudflared tunnel --config /etc/cloudflared/config.yml ingress validate
sudo systemctl restart cloudflared
cloudflared tunnel route dns <tunnel-id> lms.sekolah.sch.id   # membuat CNAME di Cloudflare
```

Tunnel baru dari nol: `cloudflared tunnel login`, `cloudflared tunnel create <nama>`, lalu ikuti
[dokumentasi Cloudflare](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/).
Ingat `TRUSTED_PROXIES=127.0.0.1` di `.env`; tanpa itu CSS/JS akan dimuat lewat `http` dan diblokir
browser (*mixed content*).

## 7. Cron (cadangan otomatis)

Sebagai user `deploy` (`crontab -e`):

```cron
* * * * * cd /var/www/lms-engine && umask 002 && php artisan schedule:run >> /dev/null 2>&1
```

`umask 002` membuat berkas cadangan dan log tetap bisa ditulis oleh web server (grup www-data).

## 8. Memperbarui aplikasi

```bash
cd /var/www/lms-engine && umask 002
php artisan down --retry=15
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
chmod -R g+w storage bootstrap/cache database
php artisan up
```

Jika server tidak punya akses ke repo (mis. repo privat tanpa deploy key), kirim kode dari komputer
pengembang. Berkas lokal (`.env`, database, `storage/`) tidak ikut tertimpa:

```bash
git archive --format=tar HEAD | ssh deploy@server 'mkdir -p ~/lms-staging && tar -x -C ~/lms-staging'
# di server: composer install + npm run build di ~/lms-staging, lalu salin ke /var/www/lms-engine
# dengan mengecualikan node_modules, .env, database/database.sqlite, dan storage
```

## Pemeriksaan setelah deploy

```bash
curl -I https://lms.sekolah.sch.id/              # 200
curl -s https://lms.sekolah.sch.id/ | grep -o 'https://[^"]*app-[^"]*\.css'   # aset harus https
tail -n 50 storage/logs/laravel.log              # tidak ada ERROR baru
php artisan schedule:list                        # jadwal cadangan terdaftar
```
