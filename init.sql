-- Ustvarjanje tabele za profesorje
CREATE TABLE IF NOT EXISTS profesorji (
    id SERIAL PRIMARY KEY,
    ime VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ustvarjanje tabele za komentarje
CREATE TABLE IF NOT EXISTS komentarji (
    id SERIAL PRIMARY KEY,
    profesor_id INTEGER NOT NULL,
    komentar TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profesor_id) REFERENCES profesorji(id) ON DELETE CASCADE
);

-- Ustvarjanje indeksov za hitrejše iskanje
CREATE INDEX IF NOT EXISTS idx_profesorji_ime ON profesorji(ime);
CREATE INDEX IF NOT EXISTS idx_komentarji_profesor_id ON komentarji(profesor_id);
CREATE INDEX IF NOT EXISTS idx_komentarji_komentar ON komentarji(komentar);

-- Ustvarjanje tabele za kviz rezultate
CREATE TABLE IF NOT EXISTS kviz_rezultati (
    id SERIAL PRIMARY KEY,
    uporabnik_id VARCHAR(255),
    stevilo_pravilnih INTEGER NOT NULL,
    stevilo_vprasanj INTEGER NOT NULL,
    rezultat DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

