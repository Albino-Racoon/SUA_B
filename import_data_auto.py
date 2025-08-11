#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Avtomatska skripta za uvoz CSV podatkov o profesorjih v PostgreSQL podatkovno bazo.
Ta skripta se izvaja avtomatsko ob zagonu Docker kontejnerja.
"""

import csv
import psycopg2
import os
import sys
import time
from pathlib import Path
import re

def connect_to_db():
    """Poveže se z PostgreSQL podatkovno bazo."""
    max_retries = 30
    retry_count = 0
    
    while retry_count < max_retries:
        try:
            connection = psycopg2.connect(
                host="db",  # Uporabi Docker service ime
                port="5432",
                database="profesorji_db",
                user="profesorji_user",
                password="profesorji_password"
            )
            print("✅ Uspešna povezava z bazo!")
            return connection
        except psycopg2.Error as e:
            retry_count += 1
            print(f"⏳ Poskus {retry_count}/{max_retries} - čakam na bazo... ({e})")
            time.sleep(2)
    
    print("❌ Ni bilo mogoče vzpostaviti povezave z bazo po 30 poskusih!")
    return None

def create_tables(connection):
    """Ustvari tabele, če ne obstajajo."""
    try:
        cursor = connection.cursor()
        
        # Tabela za profesorje
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS profesorji (
                id SERIAL PRIMARY KEY,
                ime VARCHAR(255) NOT NULL UNIQUE,
                url TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        
        # Tabela za komentarje
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS komentarji (
                id SERIAL PRIMARY KEY,
                profesor_id INTEGER REFERENCES profesorji(id) ON DELETE CASCADE,
                komentar TEXT NOT NULL,
                avtor VARCHAR(255),
                moc_komentarja VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        
        # Tabela za kviz rezultate
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS kviz_rezultati (
                id SERIAL PRIMARY KEY,
                uporabnik_id VARCHAR(255),
                stevilo_pravilnih INTEGER NOT NULL,
                stevilo_vprasanj INTEGER NOT NULL,
                rezultat DECIMAL(5,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        
        # Indeksi
        cursor.execute("""
            CREATE INDEX IF NOT EXISTS idx_profesorji_ime ON profesorji(ime)
        """)
        cursor.execute("""
            CREATE INDEX IF NOT EXISTS idx_komentarji_profesor_id ON komentarji(profesor_id)
        """)
        cursor.execute("""
            CREATE INDEX IF NOT EXISTS idx_komentarji_komentar ON komentarji(komentar)
        """)
        
        connection.commit()
        print("✅ Tabele so bile uspešno ustvarjene")
        
    except psycopg2.Error as e:
        print(f"❌ Napaka pri ustvarjanju tabel: {e}")
        connection.rollback()

def clean_komentar(komentar):
    """Očisti komentar od HTML in nepotrebnih elementov."""
    # Odstrani HTML elemente
    komentar = re.sub(r'<[^>]+>', '', komentar)
    
    # Odstrani "strinjam sene strinjam se" na koncu
    komentar = re.sub(r'strinjam sene strinjam se$', '', komentar)
    
    # Odstrani "Napisal:" prefix
    komentar = re.sub(r'^Napisal:[^:]*:', '', komentar)
    
    # Odstrani "moč komentarja:" in oceno
    komentar = re.sub(r'moč komentarja:-\d+\+\d+', '', komentar)
    
    # Očisti whitespace
    komentar = komentar.strip()
    
    return komentar

def extract_avtor(komentar):
    """Izloči avtorja komentarja."""
    avtor_match = re.search(r'^Napisal:([^:]+):', komentar)
    if avtor_match:
        return avtor_match.group(1).strip()
    return None

def extract_moc_komentarja(komentar):
    """Izloči moč komentarja."""
    moc_match = re.search(r'moč komentarja:(-\d+\+\d+)', komentar)
    if moc_match:
        return moc_match.group(1)
    return None

def import_csv_data(connection, csv_file):
    """Uvozi podatke iz CSV datoteke v podatkovno bazo."""
    try:
        cursor = connection.cursor()
        
        # Preveri, ali so podatki že v bazi
        cursor.execute("SELECT COUNT(*) FROM profesorji")
        count_profesorji = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM komentarji")
        count_komentarji = cursor.fetchone()[0]
        
        if count_profesorji > 0 or count_komentarji > 0:
            print(f"ℹ️  V bazi je že {count_profesorji} profesorjev in {count_komentarji} komentarjev. Podatki se ne bodo ponovno uvozi.")
            return
        
        # Branje CSV datoteke
        with open(csv_file, 'r', encoding='utf-8') as file:
            csv_reader = csv.DictReader(file)
            
            # Slovar za shranjevanje profesorjev
            profesorji_dict = {}
            komentarji_data = []
            
            for row in csv_reader:
                profesor_ime = row['profesor'].strip()
                url = row['url'].strip()
                komentar_raw = row['komentar'].strip()
                
                # Dodaj profesorja, če še ne obstaja
                if profesor_ime not in profesorji_dict:
                    cursor.execute("""
                        INSERT INTO profesorji (ime, url) 
                        VALUES (%s, %s) 
                        RETURNING id
                    """, (profesor_ime, url))
                    profesor_id = cursor.fetchone()[0]
                    profesorji_dict[profesor_ime] = profesor_id
                else:
                    profesor_id = profesorji_dict[profesor_ime]
                
                # Očisti komentar
                komentar_clean = clean_komentar(komentar_raw)
                avtor = extract_avtor(komentar_raw)
                moc_komentarja = extract_moc_komentarja(komentar_raw)
                
                # Dodaj komentar
                komentarji_data.append((profesor_id, komentar_clean, avtor, moc_komentarja))
            
            # Vstavi vse komentarje
            for komentar_data in komentarji_data:
                cursor.execute("""
                    INSERT INTO komentarji (profesor_id, komentar, avtor, moc_komentarja)
                    VALUES (%s, %s, %s, %s)
                """, komentar_data)
        
        connection.commit()
        print(f"✅ Uspešno uvoženih {len(profesorji_dict)} profesorjev in {len(komentarji_data)} komentarjev")
        
    except psycopg2.Error as e:
        print(f"❌ Napaka pri uvozu podatkov: {e}")
        connection.rollback()
    except FileNotFoundError:
        print(f"❌ Datoteka {csv_file} ne obstaja!")
    except Exception as e:
        print(f"❌ Napaka: {e}")

def main():
    """Glavna funkcija."""
    csv_file = "/var/www/html/profesorji_komentarji_incremental.backup.csv"
    
    if not os.path.exists(csv_file):
        print(f"❌ CSV datoteka {csv_file} ne obstaja!")
        return
    
    print("🔌 Povezovanje s podatkovno bazo...")
    connection = connect_to_db()
    
    if not connection:
        print("❌ Ni bilo mogoče vzpostaviti povezave z bazo!")
        return
    
    # Ustvari tabele
    create_tables(connection)
    
    # Uvozi podatke
    print(f"📥 Uvažanje podatkov iz {csv_file}...")
    import_csv_data(connection, csv_file)
    
    # Zapri povezavo
    connection.close()
    print("🔌 Povezava z bazo je bila zaprta")

if __name__ == "__main__":
    main()
