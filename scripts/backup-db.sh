#!/bin/bash
# Скрипт бэкапа базы данных WordPress

set -e

BACKUP_DIR="/var/www/backups"
DATE=$(date +%Y%m%d-%H%M%S)
WP_CONFIG="/var/www/html/wp-config.php"

# Создаём директорию если её нет
mkdir -p "$BACKUP_DIR"

echo "📦 Создание бэкапа базы данных..."

# Извлекаем данные из wp-config.php, если переменные окружения не заданы
if [ -z "$DB_NAME" ] && [ -f "$WP_CONFIG" ]; then
    DB_NAME=$(grep "DB_NAME" "$WP_CONFIG" | cut -d "'" -f 4)
    DB_USER=$(grep "DB_USER" "$WP_CONFIG" | cut -d "'" -f 4)
    DB_PASSWORD=$(grep "DB_PASSWORD" "$WP_CONFIG" | cut -d "'" -f 4)
    DB_HOST=$(grep "DB_HOST" "$WP_CONFIG" | cut -d "'" -f 4)
    echo "📝 Получены данные из wp-config.php"
fi

# Используем значения по умолчанию, если не найдены
DB_NAME="${DB_NAME:-wordpress}"
DB_USER="${DB_USER:-wordpress}"
DB_HOST="${DB_HOST:-localhost}"

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
