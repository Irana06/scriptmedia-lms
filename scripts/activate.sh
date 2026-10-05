# Lingkungan terminal untuk server cPanel (CloudLinux alt-php). Jalankan di setiap terminal baru:
#
#   source ~/school/lms-engine/scripts/activate.sh
#
# Menyediakan $PHP84 (PHP 8.4) dan $COMPOSER84 (Composer yang dijalankan dengan PHP 8.4), karena
# perintah `php` bawaan server ini bukan PHP 8.4. Server lain (VPS, lokal) tidak memerlukan berkas ini.
if [ -x /opt/alt/php84/usr/bin/php ]; then
    export PHP84=/opt/alt/php84/usr/bin/php
    export COMPOSER84="$PHP84 /usr/local/bin/composer"
else
    echo "PHP 8.4 alt-php tidak ditemukan di /opt/alt/php84; \$PHP84 tidak diisi." >&2
fi
