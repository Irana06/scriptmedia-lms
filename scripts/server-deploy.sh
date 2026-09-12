#!/usr/bin/env bash
#
# Deploy di server: ambil kode terbaru dengan git pull, lalu jalankan semua
# langkah Laravel dengan urutan yang benar.
#
#   PHP84=$PHP84 bash ~/school/lms-engine/scripts/server-deploy.sh
#   PHP84=$PHP84 bash ~/school/lms-engine/scripts/server-deploy.sh --demo
#
# --demo     sekalian menjalankan DemoSeeder (aman diulang)
# --no-pull  lewati git pull, misalnya bila kode sudah diperbarui manual
#
# Aman dijalankan berulang. Alasan setiap langkah ada di DEPLOY.md.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP84:-php}"
EXPECT_DB="${EXPECT_DB:-mysql}"
SKIP_HEALTHCHECK="${SKIP_HEALTHCHECK:-0}"
COMPOSER_CHANGED="${COMPOSER_CHANGED:-0}"
SEED_DEMO=0
PULL=1
FORWARD_ARGS=()

for arg in "$@"; do
    case "$arg" in
        --demo) SEED_DEMO=1; FORWARD_ARGS+=("$arg") ;;
        --no-pull) PULL=0 ;;
        *) echo "Argumen tidak dikenal: $arg" >&2; exit 2 ;;
    esac
done

cd "$APP_DIR"

step() { printf '\n== %s ==\n' "$1"; }
artisan() { "$PHP_BIN" artisan "$@"; }
env_value() { grep -E "^$1=" .env | tail -n 1 | cut -d= -f2- | tr -d "\"' " || true; }

if [ "$PULL" -eq 1 ]; then
    step "Mengambil kode terbaru"

    if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
        echo "Ada file yang diubah langsung di server:" >&2
        git status --short --untracked-files=no >&2
        echo "Kembalikan perubahan itu dulu (git checkout -- <file>) supaya git pull tidak bentrok." >&2
        exit 1
    fi

    before="$(git rev-parse HEAD)"
    git pull --ff-only
    after="$(git rev-parse HEAD)"

    if [ "$before" = "$after" ]; then
        echo "Sudah versi terbaru."
    else
        echo "Diperbarui ${before:0:7} -> ${after:0:7}:"
        git --no-pager log --oneline "$before..$after"

        if git diff --name-only "$before" "$after" | grep -qx 'composer.lock'; then
            COMPOSER_CHANGED=1
        fi

        # Skrip ini sendiri mungkin ikut berubah. Bash membaca skrip sambil
        # berjalan, jadi jalankan ulang versi terbaru dari awal.
        exec env COMPOSER_CHANGED="$COMPOSER_CHANGED" bash "$APP_DIR/scripts/server-deploy.sh" --no-pull ${FORWARD_ARGS[@]+"${FORWARD_ARGS[@]}"}
    fi
fi

step "Memeriksa lingkungan"

if [ ! -f .env ]; then
    echo "File .env tidak ditemukan di $APP_DIR." >&2
    exit 1
fi

db_connection="$(env_value DB_CONNECTION)"
echo "Folder        : $APP_DIR"
echo "Commit        : $(git rev-parse --short HEAD 2>/dev/null || echo '-')"
echo "PHP           : $("$PHP_BIN" -r 'echo PHP_VERSION;')"
echo "DB_CONNECTION : ${db_connection:-(kosong)}"
echo "Composer      : $([ "$COMPOSER_CHANGED" = "1" ] && echo 'perlu install (composer.lock berubah)' || echo 'tidak berubah')"

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
    echo "            Di PC lokal jalankan 'npm run build:subfolder', commit, lalu push." >&2
fi

step "Mode perawatan"
artisan down --retry=15 || true
trap 'artisan up >/dev/null 2>&1 || true' EXIT

if [ "$COMPOSER_CHANGED" = "1" ]; then
    step "Memasang dependensi PHP"

    if ! command -v composer >/dev/null 2>&1; then
        echo "composer.lock berubah, tetapi perintah composer tidak ditemukan di server." >&2
        echo "Jalankan 'composer install --no-dev --optimize-autoloader' secara manual, lalu ulangi dengan --no-pull." >&2
        exit 1
    fi

    composer install --no-dev --optimize-autoloader --no-interaction
fi

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
