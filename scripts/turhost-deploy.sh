#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/home/adiyam29/adh}"
TMP_ROOT="${TMP_ROOT:-/home/adiyam29/tmp}"
REPO_ARCHIVE_URL="${REPO_ARCHIVE_URL:-https://github.com/alibaranarslan/adh/archive/refs/heads/main.tar.gz}"
PHP_BIN="${PHP_BIN:-/usr/local/bin/php}"
STAMP="$(date +%Y%m%d%H%M%S)"
WORK_DIR="${TMP_ROOT}/adh-deploy-${STAMP}"
ARCHIVE="${WORK_DIR}/source.tar.gz"
LOG_FILE="${APP_DIR}/storage/logs/turhost-deploy-${STAMP}.log"
STATUS_FILE="${APP_DIR}/storage/logs/turhost-deploy-status.txt"

mkdir -p "${WORK_DIR}" "${APP_DIR}/storage/logs"

{
    echo "start ${STAMP} $(date -Is)"
    cd "${WORK_DIR}"
    curl -fsSL "${REPO_ARCHIVE_URL}" -o "${ARCHIVE}"
    tar -xzf "${ARCHIVE}" --strip-components=1

    cp "${APP_DIR}/.env" "${WORK_DIR}/.env"

    rm -rf "${APP_DIR}/app" \
        "${APP_DIR}/bootstrap" \
        "${APP_DIR}/config" \
        "${APP_DIR}/database" \
        "${APP_DIR}/lang" \
        "${APP_DIR}/resources" \
        "${APP_DIR}/routes" \
        "${APP_DIR}/scripts" \
        "${APP_DIR}/tests"

    cp -R app bootstrap config database lang resources routes scripts tests "${APP_DIR}/"
    cp artisan composer.json composer.lock package.json vite.config.js "${APP_DIR}/"

    mkdir -p "${APP_DIR}/public"
    rm -rf "${APP_DIR}/public/build"
    cp -R public/build "${APP_DIR}/public/"

    cd "${APP_DIR}"
    "${PHP_BIN}" artisan migrate --force
    "${PHP_BIN}" artisan optimize:clear
    "${PHP_BIN}" artisan config:cache
    "${PHP_BIN}" artisan route:cache
    "${PHP_BIN}" artisan view:cache
    "${PHP_BIN}" artisan filament:optimize || true
    "${PHP_BIN}" artisan sitemap:generate || true

    echo "done ${STAMP} $(date -Is)"
    echo "done ${STAMP}" > "${STATUS_FILE}"
} >> "${LOG_FILE}" 2>&1

rm -rf "${WORK_DIR}"
