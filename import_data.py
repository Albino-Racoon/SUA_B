#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Skripta za uvoz CSV podatkov o profesorjih v PostgreSQL podatkovno bazo.
"""

import csv
import psycopg2
from psycopg2.extras import RealDictCursor
import re

# Konfiguracija baze
DB_CONFIG = {
    'host': 'localhost',
    'database': 'profesorji_db',
    'user': 'postgres',
    'password': 'postgres',
    'port': '5432'
}

def clean_comment(comment):
    """Očisti komentar od HTML in nepotrebnih elementov"""
    if not comment:
        return ""
    
    # Odstrani HTML elemente
    comment = re.sub(r'<[^>]+>', '', comment)
    
    # Odstrani nepotrebne besede
    comment = re.sub(r'strinjam sene strinjam se', '', comment)
    comment = re.sub(r'moč komentarja:-\d+\+\d+', '', comment)
    comment = re.sub(r'Napisal:[^,]*', '', comment)
    
    # Očisti vejice in presledke
    comment = re.sub(r',+', ',', comment)
    comment = comment.strip(' ,')
    
    return comment if comment else "Brez komentarja"

def import_data():
    """Uvozi podatke iz CSV datoteke"""
    try:
        # Poveži se z bazo
        conn = psycopg2.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        print("Povezava z bazo uspešna!")
        
        # Počisti obstoječe podatke
        cursor.execute("DELETE FROM komentarji")
        cursor.execute("DELETE FROM profesorji")
        cursor.execute("ALTER SEQUENCE profesorji_id_seq RESTART WITH 1")
        cursor.execute("ALTER SEQUENCE komentarji_id_seq RESTART WITH 1")
        
        print("Obstoječi podatki so bili počiščeni.")
        
        # Slovar za shranjevanje profesorjev
        profesorji_dict = {}
        
        # Preberi CSV datoteko
        with open('profesorji_komentarji_incremental.backup.csv', 'r', encoding='utf-8') as file:
            csv_reader = csv.DictReader(file)
            
            for row in csv_reader:
                profesor_ime = row['profesor'].strip()
                url = row['url'].strip()
                komentar = row['komentar'].strip()
                
                # Očisti komentar
                clean_komentar = clean_comment(komentar)
                
                # Preskoči prazne komentarje
                if not clean_komentar or clean_komentar == "Brez komentarja":
                    continue
                
                # Dodaj profesorja, če še ne obstaja
                if profesor_ime not in profesorji_dict:
                    cursor.execute(
                        "INSERT INTO profesorji (ime, url) VALUES (%s, %s) RETURNING id",
                        (profesor_ime, url)
                    )
                    profesor_id = cursor.fetchone()[0]
                    profesorji_dict[profesor_ime] = profesor_id
                    print(f"Dodan profesor: {profesor_ime} (ID: {profesor_id})")
                
                # Dodaj komentar
                cursor.execute(
                    "INSERT INTO komentarji (profesor_id, komentar) VALUES (%s, %s)",
                    (profesorji_dict[profesor_ime], clean_komentar)
                )
        
        # Potrdi spremembe
        conn.commit()
        
        # Izpiši statistiko
        cursor.execute("SELECT COUNT(*) FROM profesorji")
        stevilo_profesorjev = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM komentarji")
        stevilo_komentarjev = cursor.fetchone()[0]
        
        print(f"\n✅ Uvoz končan!")
        print(f"📊 Statistika:")
        print(f"   - Profesorji: {stevilo_profesorjev}")
        print(f"   - Komentarji: {stevilo_komentarjev}")
        
        # Izpiši nekaj primerov
        cursor.execute("""
            SELECT p.ime, COUNT(k.id) as stevilo_komentarjev
            FROM profesorji p
            LEFT JOIN komentarji k ON p.id = k.profesor_id
            GROUP BY p.id, p.ime
            ORDER BY stevilo_komentarjev DESC
            LIMIT 5
        """)
        
        print(f"\n🏆 Top 5 profesorjev po številu komentarjev:")
        for row in cursor.fetchall():
            print(f"   - {row[0]}: {row[1]} komentarjev")
        
    except Exception as e:
        print(f"❌ Napaka: {e}")
        if conn:
            conn.rollback()
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

if __name__ == "__main__":
    import_data()
