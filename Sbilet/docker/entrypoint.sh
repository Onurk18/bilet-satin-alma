#!/usr/bin/env bash
set -e

DB_PATH="/var/www/html/storage/bilet.db"
SCHEMA="/var/www/html/db/schema.sql"
SEED="/var/www/html/db/seed.sql"

# storage izinleri
chown -R www-data:www-data /var/www/html/storage

if [ ! -f "$DB_PATH" ]; then
  echo "[entrypoint] bilet.db yok, oluşturuluyor…"
  su -s /bin/sh -c "touch '$DB_PATH'" www-data

  if [ -f "$SCHEMA" ]; then
    echo "[entrypoint] schema.sql uygulanıyor…"
    su -s /bin/sh -c "sqlite3 '$DB_PATH' < '$SCHEMA'" www-data
  fi

  # WAL modu + FK güvence
  su -s /bin/sh -c "sqlite3 '$DB_PATH' \"PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;\"" www-data

  if [ -f "$SEED" ]; then
    echo "[entrypoint] seed.sql uygulanıyor…"
    su -s /bin/sh -c "sqlite3 '$DB_PATH' < '$SEED'" www-data
  fi
else
  echo "[entrypoint] bilet.db mevcut, şema uygulanmayacak."
fi

exec "$@"
