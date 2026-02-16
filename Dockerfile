FROM litespeedtech/openlitespeed:latest

# Установка необходимых PHP расширений для WordPress
RUN apt-get update && apt-get install -y \
    php-mysql \
    php-redis \
    php-imagick \
    php-zip \
    php-xml \
    php-curl \
    php-mbstring \
    php-gd \
    php-intl \
    php-bcmath \
    php-soap \
    && rm -rf /var/lib/apt/lists/*

# Настройка PHP для WordPress
RUN echo "memory_limit = 2048M" >> /usr/local/lsws/lsphp81/etc/php/8.1/litespeed/php.ini && \
    echo "upload_max_filesize = 256M" >> /usr/local/lsws/lsphp81/etc/php/8.1/litespeed/php.ini && \
    echo "post_max_size = 256M" >> /usr/local/lsws/lsphp81/etc/php/8.1/litespeed/php.ini && \
    echo "max_execution_time = 300" >> /usr/local/lsws/lsphp81/etc/php/8.1/litespeed/php.ini && \
    echo "max_input_time = 300" >> /usr/local/lsws/lsphp81/etc/php/8.1/litespeed/php.ini

# Создание директории для бэкапов
RUN mkdir -p /var/www/backups && chown -R lsadm:lsadm /var/www/backups

# Копирование конфигурации OpenLiteSpeed (если есть)
# COPY lsws-config/ /usr/local/lsws/conf/

EXPOSE 80 443 7080

CMD ["/usr/local/lsws/bin/lswsctrl", "start", "&&", "tail", "-f", "/usr/local/lsws/logs/error.log"]
