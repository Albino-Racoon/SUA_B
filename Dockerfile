FROM php:8.1-apache

# Namestitev potrebnih paketov
RUN apt-get update && apt-get install -y \
    libpq-dev \
    python3 \
    python3-pip \
    python3-dev \
    python3-venv \
    postgresql-client \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Ustvarjanje virtualnega okolja za Python
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Namestitev Python odvisnosti v virtualnem okolju
COPY requirements.txt /tmp/
RUN pip3 install --no-cache-dir -r /tmp/requirements.txt

# Nastavitev PHP konfiguracije
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "max_execution_time = 300" > /usr/local/etc/php/conf.d/execution-time.ini \
    && echo "post_max_size = 100M" > /usr/local/etc/php/conf.d/upload-limits.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/upload-limits.ini

# Nastavitev Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo "DocumentRoot /var/www/html" >> /etc/apache2/apache2.conf

# Kopiranje aplikacije
COPY src/ /var/www/html/
COPY init.sql /docker-entrypoint-initdb.d/
COPY profesorji_komentarji_incremental.backup.csv /var/www/html/
COPY import_data_docker.py /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/

# Nastavitev dovoljenj
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod +x /var/www/html/import_data_docker.py \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
