#!/bin/bash
# Скрипт бэкапа плагинов WordPress

set -e

BACKUP_DIR="/var/www/backups"
PLUGINS_DIR="/var/www/html/wp-content/plugins"
DATE=$(date +%Y%m%d-%H%M%S)

# Создаём директорию если её нет
mkdir -p "$BACKUP_DIR"

echo "📦 Создание бэкапа плагинов..."

# Проверяем что директория плагинов существует
if [ ! -d "$PLUGINS_DIR" ]; then
    echo "❌ Директория плагинов не найдена: $PLUGINS_DIR"
    exit 1
fi

# Создаём архив плагинов
cd "$PLUGINS_DIR/.."
tar -czf "$BACKUP_DIR/plugins-$DATE.tar.gz" plugins/

# Проверяем что файл создан
if [ -f "$BACKUP_DIR/plugins-$DATE.tar.gz" ]; then
    SIZE=$(du -h "$BACKUP_DIR/plugins-$DATE.tar.gz" | cut -f1)
    echo "✅ Бэкап плагинов создан: plugins-$DATE.tar.gz ($SIZE)"
    
    # Создаём симлинк на latest
    ln -sf "plugins-$DATE.tar.gz" "$BACKUP_DIR/plugins-latest.tar.gz"
    
    # Удаляем старые бэкапы (старше 30 дней)
    find "$BACKUP_DIR" -name "plugins-*.tar.gz" -mtime +30 -delete
    
    echo "✅ Готово!"
else
    echo "❌ Ошибка создания бэкапа!"
    exit 1
fi
