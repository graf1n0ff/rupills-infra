#!/bin/bash
# Скрипт бэкапа базы данных WordPress

set -e

BACKUP_DIR="/var/www/backups"
DATE=$(date +%Y%m%d-%H%M%S)
DB_NAME="${DB_NAME:-wordpress}"
DB_USER="${DB_USER:-wordpress}"
DB_PASSWORD="${DB_PASSWORD}"
DB_HOST="${DB_HOST:-localhost}"

# Создаём директорию если её нет
mkdir -p "$BACKUP_DIR"

echo "📦 Создание бэкапа базы данных..."

# Создаём дамп базы данных
if [ -n "$DB_PASSWORD" ]; then
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" | gzip > "$BACKUP_DIR/db-$DATE.sql.gz"
else
    mysqldump -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" | gzip > "$BACKUP_DIR/db-$DATE.sql.gz"
fi

# Проверяем что файл создан
if [ -f "$BACKUP_DIR/db-$DATE.sql.gz" ]; then
    SIZE=$(du -h "$BACKUP_DIR/db-$DATE.sql.gz" | cut -f1)
    echo "✅ Бэкап БД создан: db-$DATE.sql.gz ($SIZE)"
    
    # Создаём симлинк на latest
    ln -sf "db-$DATE.sql.gz" "$BACKUP_DIR/db-latest.sql.gz"
    
    # Удаляем старые бэкапы (старше 7 дней)
    find "$BACKUP_DIR" -name "db-*.sql.gz" -mtime +7 -delete
    
    echo "✅ Готово!"
else
    echo "❌ Ошибка создания бэкапа!"
    exit 1
fi
