#!/bin/bash
# Полный бэкап: БД + плагины + uploads + wp-config

set -e

BACKUP_DIR="/var/www/backups"
DATE=$(date +%Y%m%d-%H%M%S)
LOG_FILE="$BACKUP_DIR/backup-$DATE.log"

# Создаём директорию если её нет
mkdir -p "$BACKUP_DIR"

echo "🚀 Начало полного бэкапа..." | tee "$LOG_FILE"

# 0. Сохранение версии WordPress
if [ -f "/var/www/html/wp-includes/version.php" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Сохранение версии WordPress ===" | tee -a "$LOG_FILE"
    WP_VERSION=$(grep "wp_version = " /var/www/html/wp-includes/version.php | cut -d"'" -f2)
    if [ -n "$WP_VERSION" ]; then
        echo "$WP_VERSION" > "$BACKUP_DIR/wp-version.txt"
        ln -sf "wp-version.txt" "$BACKUP_DIR/wp-version-latest.txt"
        echo "✅ Версия WordPress сохранена: $WP_VERSION" | tee -a "$LOG_FILE"
    fi
fi

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
    echo "✅ Бэкап дочерней темы создан: flatsome-child-$DATE.tar.gz ($SIZE)" | tee -a "$LOG_FILE"
fi

# 6. Бэкап родительской темы Flatsome
if [ -d "/var/www/html/wp-content/themes/flatsome" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап родительской темы Flatsome ===" | tee -a "$LOG_FILE"
    cd /var/www/html/wp-content/themes
    tar -czf "$BACKUP_DIR/flatsome-$DATE.tar.gz" flatsome/ 2>&1 | tee -a "$LOG_FILE"
    ln -sf "flatsome-$DATE.tar.gz" "$BACKUP_DIR/flatsome-latest.tar.gz"
    SIZE=$(du -h "$BACKUP_DIR/flatsome-$DATE.tar.gz" | cut -f1)
    echo "✅ Бэкап родительской темы создан: flatsome-$DATE.tar.gz ($SIZE)" | tee -a "$LOG_FILE"
fi

# 7. Бэкап mu-plugins
if [ -d "/var/www/html/wp-content/mu-plugins" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап mu-plugins ===" | tee -a "$LOG_FILE"
    cd /var/www/html/wp-content
    tar -czf "$BACKUP_DIR/mu-plugins-$DATE.tar.gz" mu-plugins/ 2>&1 | tee -a "$LOG_FILE"
    ln -sf "mu-plugins-$DATE.tar.gz" "$BACKUP_DIR/mu-plugins-latest.tar.gz"
    SIZE=$(du -h "$BACKUP_DIR/mu-plugins-$DATE.tar.gz" | cut -f1)
    echo "✅ Бэкап mu-plugins создан: mu-plugins-$DATE.tar.gz ($SIZE)" | tee -a "$LOG_FILE"
fi

# 8. Бэкап шрифтов
if [ -d "/var/www/html/wp-content/fonts" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап шрифтов ===" | tee -a "$LOG_FILE"
    cd /var/www/html/wp-content
    tar -czf "$BACKUP_DIR/fonts-$DATE.tar.gz" fonts/ 2>&1 | tee -a "$LOG_FILE"
    ln -sf "fonts-$DATE.tar.gz" "$BACKUP_DIR/fonts-latest.tar.gz"
    SIZE=$(du -h "$BACKUP_DIR/fonts-$DATE.tar.gz" | cut -f1)
    echo "✅ Бэкап шрифтов создан: fonts-$DATE.tar.gz ($SIZE)" | tee -a "$LOG_FILE"
fi

# 9. Бэкап переводов
if [ -d "/var/www/html/wp-content/languages" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап переводов ===" | tee -a "$LOG_FILE"
    cd /var/www/html/wp-content
    tar -czf "$BACKUP_DIR/languages-$DATE.tar.gz" languages/ 2>&1 | tee -a "$LOG_FILE"
    ln -sf "languages-$DATE.tar.gz" "$BACKUP_DIR/languages-latest.tar.gz"
    SIZE=$(du -h "$BACKUP_DIR/languages-$DATE.tar.gz" | cut -f1)
    echo "✅ Бэкап переводов создан: languages-$DATE.tar.gz ($SIZE)" | tee -a "$LOG_FILE"
fi

# 10. Бэкап .htaccess
if [ -f "/var/www/html/.htaccess" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап .htaccess ===" | tee -a "$LOG_FILE"
    cp /var/www/html/.htaccess "$BACKUP_DIR/htaccess-$DATE.txt"
    ln -sf "htaccess-$DATE.txt" "$BACKUP_DIR/htaccess-latest.txt"
    SIZE=$(du -h "$BACKUP_DIR/htaccess-$DATE.txt" | cut -f1)
    echo "✅ Бэкап .htaccess создан: htaccess-$DATE.txt ($SIZE)" | tee -a "$LOG_FILE"
fi

# 11. Бэкап .litespeed_conf.dat
if [ -f "/var/www/html/wp-content/.litespeed_conf.dat" ]; then
    echo "" | tee -a "$LOG_FILE"
    echo "=== Бэкап конфигурации LiteSpeed Cache ===" | tee -a "$LOG_FILE"
    cp /var/www/html/wp-content/.litespeed_conf.dat "$BACKUP_DIR/litespeed_conf-$DATE.dat"
    ln -sf "litespeed_conf-$DATE.dat" "$BACKUP_DIR/litespeed_conf-latest.dat"
    SIZE=$(du -h "$BACKUP_DIR/litespeed_conf-$DATE.dat" | cut -f1)
    echo "✅ Бэкап LiteSpeed конфига создан: litespeed_conf-$DATE.dat ($SIZE)" | tee -a "$LOG_FILE"
fi

echo "" | tee -a "$LOG_FILE"
echo "✅ Полный бэкап завершён!" | tee -a "$LOG_FILE"
echo "📁 Все файлы в: $BACKUP_DIR" | tee -a "$LOG_FILE"

# Показываем размер всех бэкапов
echo "" | tee -a "$LOG_FILE"
echo "=== Размеры бэкапов ===" | tee -a "$LOG_FILE"
du -h "$BACKUP_DIR"/*-latest.* 2>/dev/null | tee -a "$LOG_FILE"
