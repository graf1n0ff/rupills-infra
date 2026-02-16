#!/bin/bash
# Полный бэкап: БД + плагины + uploads + wp-config

set -e

BACKUP_DIR="/var/www/backups"
DATE=$(date +%Y%m%d-%H%M%S)
LOG_FILE="$BACKUP_DIR/backup-$DATE.log"

# Создаём директорию если её нет
mkdir -p "$BACKUP_DIR"

echo "🚀 Начало полного бэкапа..." | tee "$LOG_FILE"

# 1. Бэкап базы данных
echo "" | tee -a "$LOG_FILE"
echo "=== Бэкап базы данных ===" | tee -a "$LOG_FILE"
/var/www/scripts/backup-db.sh 2>&1 | tee -a "$LOG_FILE"

# 2. Бэкап плагинов
echo "" | tee -a "$LOG_FILE"
echo "=== Бэкап плагинов ===" | tee -a "$LOG_FILE"
/var/www/scripts/backup-plugins.sh 2>&1 | tee -a "$LOG_FILE"

# 3. Бэкап uploads (если нужно)
if [ -d "/var/www/html/wp-content/uploads" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап uploads ===" | tee -a "$LOG_FILE"
    cd /var/www/html/wp-content
    tar -czf "$BACKUP_DIR/uploads-$DATE.tar.gz" uploads/ 2>&1 | tee -a "$LOG_FILE"
    ln -sf "uploads-$DATE.tar.gz" "$BACKUP_DIR/uploads-latest.tar.gz"
    SIZE=$(du -h "$BACKUP_DIR/uploads-$DATE.tar.gz" | cut -f1)
    echo "✅ Бэкап uploads создан: uploads-$DATE.tar.gz ($SIZE)" | tee -a "$LOG_FILE"
    
    # Удаляем старые бэкапы uploads (старше 14 дней)
    find "$BACKUP_DIR" -name "uploads-*.tar.gz" -mtime +14 -delete
fi

# 4. Бэкап wp-config.php (зашифрованный)
if [ -f "/var/www/html/wp-config.php" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап wp-config.php ===" | tee -a "$LOG_FILE"
    
    # Запрашиваем пароль для шифрования (если не задан)
    ENCRYPT_PASSWORD="${BACKUP_ENCRYPT_PASSWORD:-backup123}"
    
    openssl enc -aes-256-cbc -salt -pbkdf2 \
        -in /var/www/html/wp-config.php \
        -out "$BACKUP_DIR/wp-config-$DATE.enc" \
        -pass pass:"$ENCRYPT_PASSWORD" 2>&1 | tee -a "$LOG_FILE"
    
    ln -sf "wp-config-$DATE.enc" "$BACKUP_DIR/wp-config-latest.enc"
    echo "✅ Бэкап wp-config.php создан (зашифрован)" | tee -a "$LOG_FILE"
    echo "⚠️  Пароль для расшифровки: $ENCRYPT_PASSWORD" | tee -a "$LOG_FILE"
fi

# 5. Бэкап темы flatsome-child
if [ -d "/var/www/html/wp-content/themes/flatsome-child" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап темы flatsome-child ===" | tee -a "$LOG_FILE"
    cd /var/www/html/wp-content/themes
    tar -czf "$BACKUP_DIR/flatsome-child-$DATE.tar.gz" flatsome-child/ 2>&1 | tee -a "$LOG_FILE"
    ln -sf "flatsome-child-$DATE.tar.gz" "$BACKUP_DIR/flatsome-child-latest.tar.gz"
    SIZE=$(du -h "$BACKUP_DIR/flatsome-child-$DATE.tar.gz" | cut -f1)
    echo "✅ Бэкап темы создан: flatsome-child-$DATE.tar.gz ($SIZE)" | tee -a "$LOG_FILE"
fi

echo "" | tee -a "$LOG_FILE"
echo "✅ Полный бэкап завершён!" | tee -a "$LOG_FILE"
echo "📁 Все файлы в: $BACKUP_DIR" | tee -a "$LOG_FILE"

# Показываем размер всех бэкапов
echo "" | tee -a "$LOG_FILE"
echo "=== Размеры бэкапов ===" | tee -a "$LOG_FILE"
du -h "$BACKUP_DIR"/*.gz "$BACKUP_DIR"/*.enc 2>/dev/null | tee -a "$LOG_FILE"
