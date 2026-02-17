#!/bin/bash
# Скрипт восстановления сайта из бэкапов
# Использование: ./restore.sh [db|plugins|uploads|all]

set -e

BACKUP_DIR="/var/www/backups"
HTML_DIR="/var/www/html"
RESTORE_TYPE="${1:-all}"

echo "🚀 Восстановление ru-pills.com..."
echo "Тип восстановления: $RESTORE_TYPE"
echo ""

# Проверка наличия директории бэкапов
if [ ! -d "$BACKUP_DIR" ]; then
    echo "❌ Директория бэкапов не найдена: $BACKUP_DIR"
    exit 1
fi

# Восстановление базы данных
restore_db() {
    echo "=== Восстановление базы данных ==="
    
    DB_FILE="$BACKUP_DIR/db-latest.sql.gz"
    if [ ! -f "$DB_FILE" ]; then
        echo "❌ Файл бэкапа БД не найден: $DB_FILE"
        return 1
    fi
    
    echo "📦 Распаковка дампа..."
    gunzip -c "$DB_FILE" > /tmp/restore-db.sql
    
    echo "📥 Импорт в базу данных..."
    DB_NAME="${DB_NAME:-wordpress}"
    DB_USER="${DB_USER:-wordpress}"
    DB_PASSWORD="${DB_PASSWORD}"
    DB_HOST="${DB_HOST:-localhost}"
    
    if [ -n "$DB_PASSWORD" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /tmp/restore-db.sql
    else
        mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < /tmp/restore-db.sql
    fi
    
    rm /tmp/restore-db.sql
    echo "✅ База данных восстановлена"
}

# Восстановление плагинов
restore_plugins() {
    echo "=== Восстановление плагинов ==="
    
    PLUGINS_FILE="$BACKUP_DIR/plugins-latest.tar.gz"
    if [ ! -f "$PLUGINS_FILE" ]; then
        echo "❌ Файл бэкапа плагинов не найден: $PLUGINS_FILE"
        return 1
    fi
    
    echo "📦 Распаковка плагинов..."
    cd "$HTML_DIR/wp-content"
    
    # Бэкап текущих плагинов на всякий случай
    if [ -d "plugins" ]; then
        mv plugins plugins.backup-$(date +%Y%m%d-%H%M%S)
    fi
    
    tar -xzf "$PLUGINS_FILE"
    echo "✅ Плагины восстановлены"
}

# Восстановление uploads
restore_uploads() {
    echo "=== Восстановление uploads ==="
    
    UPLOADS_FILE="$BACKUP_DIR/uploads-latest.tar.gz"
    if [ ! -f "$UPLOADS_FILE" ]; then
        echo "⚠️  Файл бэкапа uploads не найден: $UPLOADS_FILE"
        echo "Пропускаем восстановление uploads"
        return 0
    fi
    
    echo "📦 Распаковка uploads..."
    cd "$HTML_DIR/wp-content"
    
    # Бэкап текущих uploads на всякий случай
    if [ -d "uploads" ]; then
        mv uploads uploads.backup-$(date +%Y%m%d-%H%M%S)
    fi
    
    tar -xzf "$UPLOADS_FILE"
    echo "✅ Uploads восстановлены"
}

# Восстановление wp-config.php
restore_wpconfig() {
    echo "=== Восстановление wp-config.php ==="
    
    WPCONFIG_FILE="$BACKUP_DIR/wp-config-latest.enc"
    if [ ! -f "$WPCONFIG_FILE" ]; then
        echo "⚠️  Зашифрованный wp-config.php не найден"
        return 0
    fi
    
    echo "🔓 Расшифровка wp-config.php..."
    echo "Введите пароль для расшифровки (или нажмите Enter для пропуска):"
    read -s ENCRYPT_PASSWORD
    
    if [ -z "$ENCRYPT_PASSWORD" ]; then
        echo "Пропускаем восстановление wp-config.php"
        return 0
    fi
    
    # Бэкап текущего wp-config.php
    if [ -f "$HTML_DIR/wp-config.php" ]; then
        cp "$HTML_DIR/wp-config.php" "$HTML_DIR/wp-config.php.backup-$(date +%Y%m%d-%H%M%S)"
    fi
    
    openssl enc -aes-256-cbc -d -pbkdf2 \
        -in "$WPCONFIG_FILE" \
        -out "$HTML_DIR/wp-config.php" \
        -pass pass:"$ENCRYPT_PASSWORD"
    
    echo "✅ wp-config.php восстановлен"
}

# Восстановление WordPress core
restore_wpcore() {
    echo "=== Восстановление WordPress core ==="
    
    WP_VERSION_FILE="$BACKUP_DIR/wp-version-latest.txt"
    if [ ! -f "$WP_VERSION_FILE" ]; then
        echo "⚠️  Файл версии WordPress не найден: $WP_VERSION_FILE"
        echo "Используем версию по умолчанию: 6.1.1"
        WP_VERSION="6.1.1"
    else
        WP_VERSION=$(cat "$WP_VERSION_FILE" | tr -d '[:space:]')
        echo "📌 Восстанавливаем WordPress версии: $WP_VERSION"
    fi
    
    # Проверяем наличие WP-CLI
    if ! command -v wp &> /dev/null; then
        echo "⚠️  WP-CLI не найден. Установите WP-CLI или восстановите WordPress core вручную."
        echo "   Команда: wp core download --version=$WP_VERSION --locale=ru_RU --path=$HTML_DIR"
        return 1
    fi
    
    echo "📥 Скачивание WordPress $WP_VERSION..."
    cd "$HTML_DIR"
    
    # Бэкап текущего WordPress core на всякий случай
    if [ -d "wp-admin" ] || [ -d "wp-includes" ]; then
        mkdir -p "$HTML_DIR/wp-core-backup-$(date +%Y%m%d-%H%M%S)"
        [ -d "wp-admin" ] && mv wp-admin "$HTML_DIR/wp-core-backup-$(date +%Y%m%d-%H%M%S)/" 2>/dev/null || true
        [ -d "wp-includes" ] && mv wp-includes "$HTML_DIR/wp-core-backup-$(date +%Y%m%d-%H%M%S)/" 2>/dev/null || true
        [ -f "wp-*.php" ] && mv wp-*.php "$HTML_DIR/wp-core-backup-$(date +%Y%m%d-%H%M%S)/" 2>/dev/null || true
    fi
    
    wp core download --version="$WP_VERSION" --locale=ru_RU --path="$HTML_DIR" --force
    
    echo "✅ WordPress core восстановлен (версия $WP_VERSION)"
}

# Восстановление дочерней темы
restore_theme() {
    echo "=== Восстановление темы flatsome-child ==="
    
    THEME_FILE="$BACKUP_DIR/flatsome-child-latest.tar.gz"
    if [ ! -f "$THEME_FILE" ]; then
        echo "⚠️  Файл бэкапа дочерней темы не найден: $THEME_FILE"
        return 0
    fi
    
    echo "📦 Распаковка дочерней темы..."
    cd "$HTML_DIR/wp-content/themes"
    
    # Бэкап текущей темы на всякий случай
    if [ -d "flatsome-child" ]; then
        mv flatsome-child flatsome-child.backup-$(date +%Y%m%d-%H%M%S)
    fi
    
    tar -xzf "$THEME_FILE"
    echo "✅ Дочерняя тема восстановлена"
}

# Восстановление родительской темы Flatsome
restore_flatsome() {
    echo "=== Восстановление родительской темы Flatsome ==="
    
    FLATSOME_FILE="$BACKUP_DIR/flatsome-latest.tar.gz"
    if [ ! -f "$FLATSOME_FILE" ]; then
        echo "⚠️  Файл бэкапа Flatsome не найден: $FLATSOME_FILE"
        return 0
    fi
    
    echo "📦 Распаковка родительской темы..."
    cd "$HTML_DIR/wp-content/themes"
    
    # Бэкап текущей темы на всякий случай
    if [ -d "flatsome" ]; then
        mv flatsome flatsome.backup-$(date +%Y%m%d-%H%M%S)
    fi
    
    tar -xzf "$FLATSOME_FILE"
    echo "✅ Родительская тема Flatsome восстановлена"
}

# Восстановление mu-plugins
restore_muplugins() {
    echo "=== Восстановление mu-plugins ==="
    
    MUPLUGINS_FILE="$BACKUP_DIR/mu-plugins-latest.tar.gz"
    if [ ! -f "$MUPLUGINS_FILE" ]; then
        echo "⚠️  Файл бэкапа mu-plugins не найден: $MUPLUGINS_FILE"
        return 0
    fi
    
    echo "📦 Распаковка mu-plugins..."
    cd "$HTML_DIR/wp-content"
    
    # Бэкап текущих mu-plugins на всякий случай
    if [ -d "mu-plugins" ]; then
        mv mu-plugins mu-plugins.backup-$(date +%Y%m%d-%H%M%S)
    fi
    
    tar -xzf "$MUPLUGINS_FILE"
    echo "✅ mu-plugins восстановлены"
}

# Восстановление шрифтов
restore_fonts() {
    echo "=== Восстановление шрифтов ==="
    
    FONTS_FILE="$BACKUP_DIR/fonts-latest.tar.gz"
    if [ ! -f "$FONTS_FILE" ]; then
        echo "⚠️  Файл бэкапа шрифтов не найден: $FONTS_FILE"
        return 0
    fi
    
    echo "📦 Распаковка шрифтов..."
    cd "$HTML_DIR/wp-content"
    
    # Бэкап текущих шрифтов на всякий случай
    if [ -d "fonts" ]; then
        mv fonts fonts.backup-$(date +%Y%m%d-%H%M%S)
    fi
    
    tar -xzf "$FONTS_FILE"
    echo "✅ Шрифты восстановлены"
}

# Восстановление переводов
restore_languages() {
    echo "=== Восстановление переводов ==="
    
    LANGUAGES_FILE="$BACKUP_DIR/languages-latest.tar.gz"
    if [ ! -f "$LANGUAGES_FILE" ]; then
        echo "⚠️  Файл бэкапа переводов не найден: $LANGUAGES_FILE"
        return 0
    fi
    
    echo "📦 Распаковка переводов..."
    cd "$HTML_DIR/wp-content"
    
    # Бэкап текущих переводов на всякий случай
    if [ -d "languages" ]; then
        mv languages languages.backup-$(date +%Y%m%d-%H%M%S)
    fi
    
    tar -xzf "$LANGUAGES_FILE"
    echo "✅ Переводы восстановлены"
}

# Восстановление .htaccess
restore_htaccess() {
    echo "=== Восстановление .htaccess ==="
    
    HTACCESS_FILE="$BACKUP_DIR/htaccess-latest.txt"
    if [ ! -f "$HTACCESS_FILE" ]; then
        echo "⚠️  Файл бэкапа .htaccess не найден: $HTACCESS_FILE"
        return 0
    fi
    
    echo "📦 Восстановление .htaccess..."
    
    # Бэкап текущего .htaccess на всякий случай
    if [ -f "$HTML_DIR/.htaccess" ]; then
        cp "$HTML_DIR/.htaccess" "$HTML_DIR/.htaccess.backup-$(date +%Y%m%d-%H%M%S)"
    fi
    
    cp "$HTACCESS_FILE" "$HTML_DIR/.htaccess"
    echo "✅ .htaccess восстановлен"
}

# Восстановление конфигурации LiteSpeed
restore_litespeed_conf() {
    echo "=== Восстановление конфигурации LiteSpeed Cache ==="
    
    LITESPEED_FILE="$BACKUP_DIR/litespeed_conf-latest.dat"
    if [ ! -f "$LITESPEED_FILE" ]; then
        echo "⚠️  Файл бэкапа LiteSpeed конфига не найден: $LITESPEED_FILE"
        return 0
    fi
    
    echo "📦 Восстановление конфига..."
    
    # Бэкап текущего конфига на всякий случай
    if [ -f "$HTML_DIR/wp-content/.litespeed_conf.dat" ]; then
        cp "$HTML_DIR/wp-content/.litespeed_conf.dat" "$HTML_DIR/wp-content/.litespeed_conf.dat.backup-$(date +%Y%m%d-%H%M%S)"
    fi
    
    cp "$LITESPEED_FILE" "$HTML_DIR/wp-content/.litespeed_conf.dat"
    echo "✅ Конфигурация LiteSpeed восстановлена"
}

# Основная логика
case "$RESTORE_TYPE" in
    db)
        restore_db
        ;;
    plugins)
        restore_plugins
        ;;
    uploads)
        restore_uploads
        ;;
    theme)
        restore_theme
        ;;
    flatsome)
        restore_flatsome
        ;;
    muplugins)
        restore_muplugins
        ;;
    fonts)
        restore_fonts
        ;;
    languages)
        restore_languages
        ;;
    htaccess)
        restore_htaccess
        ;;
    litespeed)
        restore_litespeed_conf
        ;;
    wp)
        restore_wpcore
        ;;
    all)
        restore_wpcore
        restore_db
        restore_plugins
        restore_uploads
        restore_wpconfig
        restore_theme
        restore_flatsome
        restore_muplugins
        restore_fonts
        restore_languages
        restore_htaccess
        restore_litespeed_conf
        ;;
    *)
        echo "❌ Неизвестный тип восстановления: $RESTORE_TYPE"
        echo "Использование: $0 [wp|db|plugins|uploads|theme|flatsome|muplugins|fonts|languages|htaccess|litespeed|all]"
        exit 1
        ;;
esac

echo ""
echo "✅ Восстановление завершено!"
echo "⚠️  Не забудьте проверить права доступа к файлам:"
echo "   chown -R www-data:www-data $HTML_DIR"
