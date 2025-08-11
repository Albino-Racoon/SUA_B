#!/bin/bash
set -e

# Počakaj, da je PostgreSQL pripravljen (uporabi environment variables)
echo "Čakam, da se PostgreSQL požene..."
while ! pg_isready -h $DB_HOST -U $DB_USER -d $DB_NAME -p $DB_PORT; do
    echo "Čakam na bazo podatkov..."
    sleep 2
done

echo "PostgreSQL je pripravljen!"

# Ustvari tabele iz init.sql
if [ -f /var/www/html/init.sql ]; then
    echo "Ustvarjam tabele iz init.sql..."
    psql -h $DB_HOST -U $DB_USER -d $DB_NAME -p $DB_PORT -f /var/www/html/init.sql
fi

# Uvozi podatke, če je potrebno
if [ -f /var/www/html/import_data_docker.py ]; then
    echo "Uvažam podatke iz CSV datoteke..."
    cd /var/www/html
    python3 import_data_docker.py
fi

# Zaženi Apache
echo "Začenjam Apache..."
exec apache2-foreground
