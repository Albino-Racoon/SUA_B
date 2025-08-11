#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Preprosta skripta za čiščenje komentarjev v CSV datoteki.
Uporablja regex za iskanje in zamenjavo besedila "strinjam sene strinjam se".
"""

import re
import os
import sys
from pathlib import Path

def clean_comments_simple(input_file, output_file=None):
    """
    Očisti komentarje v CSV datoteki z odstranitvijo besedila "strinjam sene strinjam se".
    Uporablja preprosto regex zamenjavo.
    
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
    
    try:
        # Preberi celotno datoteko
        with open(input_file, 'r', encoding='utf-8') as infile:
            content = infile.read()
        
        # Preštej, koliko pojavov besedila je bilo
        pattern = r'strinjam sene strinjam se'
        matches = re.findall(pattern, content)
        num_matches = len(matches)
        
        if num_matches == 0:
            print("Ni bilo najdenih pojavov besedila 'strinjam sene strinjam se'.")
            return True
        
        # Odstrani besedilo
        cleaned_content = re.sub(pattern, '', content)
        
        # Napiši očiščeno vsebino
        with open(output_file, 'w', encoding='utf-8') as outfile:
            outfile.write(cleaned_content)
        
        print(f"Uspešno odstranjenih {num_matches} pojavov besedila 'strinjam sene strinjam se'.")
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
    
    print("Čiščenje komentarjev v CSV datoteki (preprosta verzija)...")
    print(f"Vhodna datoteka: {csv_file}")
    
    # Očisti komentarje
    success = clean_comments_simple(csv_file)
    
    if success:
        print("\nČiščenje je bilo uspešno zaključeno!")
    else:
        print("\nČiščenje ni bilo uspešno!")
        sys.exit(1)

if __name__ == "__main__":
    main()
