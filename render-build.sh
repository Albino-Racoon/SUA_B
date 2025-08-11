#!/bin/bash
set -e

echo "🚀 Starting build process for Profesorji Komentarji API..."

# Namestitev potrebnih paketov
echo "📦 Installing system packages..."
apt-get update && apt-get install -y \
    libpq-dev \
    python3 \
    python3-pip \
    python3-dev \
    python3-venv \
    postgresql-client \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Ustvarjanje virtualnega okolja za Python
echo "🐍 Setting up Python virtual environment..."
python3 -m venv /opt/venv
export PATH="/opt/venv/bin:$PATH"

# Namestitev Python odvisnosti
echo "📚 Installing Python dependencies..."
pip3 install --no-cache-dir -r requirements.txt

# Nastavitev PHP konfiguracije
echo "⚙️ Configuring PHP..."
echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini
echo "max_execution_time = 300" > /usr/local/etc/php/conf.d/execution-time.ini
echo "post_max_size = 100M" > /usr/local/etc/php/conf.d/upload-limits.ini
echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/upload-limits.ini

# Nastavitev Apache
echo "🌐 Configuring Apache..."
echo "ServerName localhost" >> /etc/apache2/apache2.conf
echo "DocumentRoot /var/www/html" >> /etc/apache2/apache2.conf

# Nastavitev dovoljenj
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod +x /var/www/html/import_data_docker.py
chmod +x /usr/local/bin/docker-entrypoint.sh

echo "✅ Build process completed successfully!"
echo "🎯 Application is ready to start!"
