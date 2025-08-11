# SUA Asistent - Docker Projekt

Aplikacija za upravljanje profesorjev in komentarjev z Docker Compose.

## 🚀 Hitri zagon

### Predpogoji
- Docker in Docker Compose nameščeni
- Port 80 in 5432 prosti

### Zagon projekta
```bash
# Zagon vseh storitev
docker-compose up --build -d

# Preverjanje statusa
docker-compose ps

# Pregled log-ov
docker-compose logs php
docker-compose logs db
docker-compose logs nginx

# Ustavitev
docker-compose down
```

## 📊 Podatki

Projekt vsebuje:
- **310 profesorjev** iz CSV datoteke
- **1554 komentarjev** o profesorjih
- PostgreSQL baza podatkov
- PHP 8.1 aplikacija
- Nginx web strežnik

## 🌐 Dostop

- **Web aplikacija**: http://localhost
- **PostgreSQL baza**: localhost:5432
  - Baza: `profesorji_db`
  - Uporabnik: `postgres`
  - Geslo: `postgres`

## 🏗️ Arhitektura

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Nginx     │    │     PHP     │    │ PostgreSQL  │
│   Port 80   │◄──►│   Port 9000 │◄──►│   Port 5432 │
└─────────────┘    └─────────────┘    └─────────────┘
```

## 📁 Datoteke

- `docker-compose.yml` - Glavna konfiguracija
- `Dockerfile` - PHP kontejner
- `nginx.conf` - Nginx konfiguracija
- `init.sql` - Inicializacija baze
- `docker-entrypoint.sh` - Zagon skripta

## 🔧 Vzdrževanje

### Čiščenje
```bash
# Počisti vse kontejnerje in volumne
docker-compose down --volumes --remove-orphans

# Počisti Docker cache
docker system prune -f
```

### Posodobitve
```bash
# Ponovno zgradi in zaženi
docker-compose up --build -d
```

## 📝 Opombe

- Aplikacija se samodejno požene ob zagonu
- Podatki se uvažajo iz `profesorji_komentarji_incremental.backup.csv`
- Baza se inicializira z `init.sql`
- Vsi kontejnerji imajo health check-e in restart politike
