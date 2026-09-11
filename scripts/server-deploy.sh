#!/usr/bin/env bash
#
# Langkah di server setelah kode baru diunggah ke ~/school/lms-engine.
#
#   PHP84=$PHP84 bash ~/school/lms-engine/scripts/server-deploy.sh
#   PHP84=$PHP84 bash ~/school/lms-engine/scripts/server-deploy.sh --demo
#
# --demo  sekalian menjalankan DemoSeeder (aman diulang).
#
# Aman dijalankan berulang. Alasan setiap langkah ada di DEPLOY.md.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP84:-php}"
EXPECT_DB="${EXPECT_DB:-mysql}"
SKIP_HEALTHCHECK="${SKIP_HEALTHCHECK:-0}"
SEED_DEMO=0

for arg in "$@"; do
    case "$arg" in
        --demo) SEED_DEMO=1 ;;
        *) echo "Argumen tidak dikenal: $arg" >&2; exit 2 ;;
    esac
done

cd "$APP_DIR"

step() { printf '\n== %s ==\n' "$1"; }
artisan() { "$PHP_BIN" artisan "$@"; }
env_value() { grep -E "^$1=" .env | tail -n 1 | cut -d= -f2- | tr -d "\"' " || true; }

step "Memeriksa lingkungan"

if [ ! -f .env ]; then
    echo "File .env tidak ditemukan di $APP_DIR." >&2
    exit 1
fi

db_connection="$(env_value DB_CONNECTION)"
echo "Folder        : $APP_DIR"
echo "PHP           : $("$PHP_BIN" -r 'echo PHP_VERSION;')"
echo "DB_CONNECTION : ${db_connection:-(kosong)}"

# Pengingat yang diminta: pastikan server memakai database yang benar sebelum migrasi.
if [ "$db_connection" != "$EXPECT_DB" ]; then
    echo "Berhenti: DB_CONNECTION seharusnya '$EXPECT_DB'. Periksa .env sebelum menjalankan migrasi." >&2
    exit 1
fi

# Cache route membuat halaman depan menjawab 405 pada instalasi subfolder.
if ls bootstrap/cache/routes-*.php >/dev/null 2>&1; then
    echo "Menghapus cache route yang tertinggal."
    rm -f bootstrap/cache/routes-*.php
fi

# URL font tertanam di CSS saat build; build biasa membuat font 404 di /lms.
if ! grep -qs '/lms/build/assets/' public/build/assets/*.css; then
    echo "PERINGATAN: aset tidak di-build untuk subfolder /lms, font akan 404." >&2
    echo "            Di PC lokal jalankan 'npm run build:subfolder', lalu unggah ulang public/build." >&2
fi

step "Mode perawatan"
artisan down --retry=15 || true
trap 'artisan up >/dev/null 2>&1 || true' EXIT

step "Migrasi database"
artisan migrate --force

if [ "$SEED_DEMO" -eq 1 ]; then
    step "Mengisi data demo"
    artisan db:seed --class=DemoSeeder --force
fi

step "Membersihkan dan membangun cache"
artisan optimize:clear
artisan config:cache
artisan view:cache
artisan event:cache
# Sengaja tanpa route:cache dan optimize — lihat DEPLOY.md.

if [ ! -e public/storage ]; then
    step "Menautkan storage"
    artisan storage:link
fi

step "Mengaktifkan aplikasi"
artisan up
trap - EXIT

if [ "$SKIP_HEALTHCHECK" != "1" ] && command -v curl >/dev/null 2>&1; then
    step "Memeriksa halaman depan"
    app_url="$(env_value APP_URL)"
    status="$(curl -s -o /dev/null -w '%{http_code}' "${app_url%/}/" || true)"
    echo "GET ${app_url%/}/ -> $status"

    if [ "$status" != "200" ]; then
        echo "PERINGATAN: halaman depan tidak menjawab 200. Cek storage/logs/laravel.log." >&2
        exit 1
    fi
fi

printf '\nSelesai.\n'
