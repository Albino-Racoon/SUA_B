#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Popravljena skripta za čiščenje komentarjev v CSV datoteki.
Odstrani besedilo "strinjam sene strinjam se" na koncu vsakega komentarja.
"""

import csv
import os
import sys
from pathlib import Path

def clean_comments(input_file, output_file=None):
    """
    Očisti komentarje v CSV datoteki z odstranitvijo besedila "strinjam sene strinjam se".
    
    Args:
        input_file (str): Pot do vhodne CSV datoteke
        output_file (str, optional): Pot do izhodne CSV datoteke. Če ni podana, 
                                   se ustvari backup in prepiše original.
    """
    
    # Preveri, ali vhodna datoteka obstaja
    if not os.path.exists(input_file):
        print(f"Napaka: Datoteka {input_file} ne obstaja!")
        return False
    
    # Če izhodna datoteka ni podana, ustvari backup in prepiši original
    if output_file is None:
        # Ustvari backup ime
        input_path = Path(input_file)
        backup_file = input_path.with_suffix('.backup.csv')
        output_file = input_file
        
        # Ustvari backup, če še ne obstaja
        if not os.path.exists(backup_file):
            print(f"Ustvarjam backup datoteko: {backup_file}")
            import shutil
            shutil.copy2(input_file, backup_file)
    
    cleaned_rows = 0
    total_rows = 0
    error_rows = 0
    
    try:
        # Preberi vhodno datoteko
        with open(input_file, 'r', encoding='utf-8', newline='') as infile:
            # Uporabi DictReader za boljše rokovanje z vrsticami
            reader = csv.DictReader(infile)
            fieldnames = reader.fieldnames
            
            # Pripravi izhodno datoteko
            with open(output_file, 'w', encoding='utf-8', newline='') as outfile:
                writer = csv.DictWriter(outfile, fieldnames=fieldnames)
                writer.writeheader()  # Napiši glavo
                
                # Obdelaj vsako vrstico
                for row_num, row in enumerate(reader, start=2):  # start=2 ker je prva vrstica glava
                    total_rows += 1
                    
                    try:
                        # Preveri, ali ima vrstica komentar stolpec
                        if 'komentar' in row and row['komentar']:
                            comment = row['komentar']
                            
                            # Odstrani "strinjam sene strinjam se" na koncu
                            if comment.endswith("strinjam sene strinjam se"):
                                comment = comment[:-len("strinjam sene strinjam se")]
                                cleaned_rows += 1
                            
                            # Posodobi vrstico z očiščenim komentarjem
                            row['komentar'] = comment
                        
                        # Napiši vrstico (tudi če ni bilo sprememb)
                        writer.writerow(row)
                        
                    except Exception as e:
                        print(f"Napaka pri obdelavi vrstice {row_num}: {e}")
                        print(f"Vrstica: {row}")
                        error_rows += 1
                        # Vseeno poskusi napisati vrstico
                        writer.writerow(row)
        
        print(f"Uspešno očiščeno {cleaned_rows} komentarjev od skupno {total_rows} vrstic.")
        if error_rows > 0:
            print(f"Opozorilo: {error_rows} vrstic je imelo napake pri obdelavi.")
        print(f"Rezultat shranjen v: {output_file}")
        
        if output_file == input_file:
            backup_file = input_path.with_suffix('.backup.csv')
            print(f"Originalna datoteka je bila prepisana. Backup je na voljo v: {backup_file}")
        
        return True
        
    except Exception as e:
        print(f"Napaka pri obdelavi datoteke: {e}")
        return False

def main():
    """Glavna funkcija."""
    
    # Pot do CSV datoteke
    csv_file = "/Users/jasa/Desktop/SUA_Asist/profesorji_komentarji_incremental.csv"
    
    print("Čiščenje komentarjev v CSV datoteki (popravljena verzija)...")
    print(f"Vhodna datoteka: {csv_file}")
    
    # Očisti komentarje
    success = clean_comments(csv_file)
    
    if success:
        print("\nČiščenje je bilo uspešno zaključeno!")
    else:
        print("\nČiščenje ni bilo uspešno!")
        sys.exit(1)

if __name__ == "__main__":
    main()
