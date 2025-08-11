#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Ročna skripta za čiščenje komentarjev v CSV datoteki.
Odstrani vse nepotrebno besedilo in pusti samo dejanske komentarje.
"""

import csv
import os
import sys
import re
from pathlib import Path

def clean_comments(input_file, output_file=None):
    """
    Ročno očisti komentarje v CSV datoteki z odstranitvijo vsega nepotrebnega besedila.
    
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
    removed_rows = 0
    
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
                            
                            # Ročno odstrani nepotrebno besedilo
                            # Odstrani "moč komentarja:X+Y" vzorce
                            comment = re.sub(r'moč komentarja:-\d+\+\d+', '', comment)
                            comment = re.sub(r'moč komentarja:\d+', '', comment)
                            
                            # Odstrani "Napisal:ime" vzorce
                            comment = re.sub(r'Napisal:[^\s]+', '', comment)
                            
                            # Odstrani HTML in spletno besedilo
                            comment = re.sub(r'Fakulteta za elektrotehniko, računalništvo in informatiko / UNI', '', comment)
                            comment = re.sub(r'Število ocen:\d+', '', comment)
                            comment = re.sub(r'Predmeti:.*?dodaj predmet', '', comment)
                            comment = re.sub(r'Komentiraj predmete! Klikni na ime predmeta zgoraj!', '', comment)
                            comment = re.sub(r'Spremeni podatke profesorja', '', comment)
                            comment = re.sub(r'Dodaj komentar', '', comment)
                            comment = re.sub(r'Sponzorji', '', comment)
                            comment = re.sub(r'O tem profesorju še nihče ni napisal mnenja\.', '', comment)
                            
                            # Odstrani ASCII art in nepotrebne simbole
                            comment = re.sub(r'[⠁-⣿]+', '', comment)  # Braille vzorci
                            comment = re.sub(r'[▄▀█░]+', '', comment)  # ASCII art vzorci
                            
                            # Odstrani večkratne presledke
                            comment = re.sub(r'\s+', ' ', comment)
                            
                            # Odstrani presledke na začetku in koncu
                            comment = comment.strip()
                            
                            # Preveri, ali je komentar po čiščenju smiseln
                            if len(comment) > 5 and not comment.isspace() and comment != "":
                                # Posodobi vrstico z očiščenim komentarjem
                                row['komentar'] = comment
                                cleaned_rows += 1
                                writer.writerow(row)
                            else:
                                # Komentar je preveč kratek ali ne vsebuje smiselnega besedila
                                removed_rows += 1
                                continue
                        else:
                            # Vrstica nima komentarja, vseeno jo napiši
                            writer.writerow(row)
                        
                    except Exception as e:
                        print(f"Napaka pri obdelavi vrstice {row_num}: {e}")
                        print(f"Vrstica: {row}")
                        error_rows += 1
                        # Vseeno poskusi napisati vrstico
                        writer.writerow(row)
        
        print(f"Uspešno očiščeno {cleaned_rows} komentarjev od skupno {total_rows} vrstic.")
        print(f"Odstranjenih {removed_rows} vrstic z nepotrebnimi komentarji.")
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
    
    print("Ročno čiščenje komentarjev v CSV datoteki...")
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
