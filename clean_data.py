#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Skripta za čiščenje podatkov iz CSV datoteke
"""

import pandas as pd
import re

def clean_komentarji(csv_file, output_file):
    """Očisti komentarje v CSV datoteki"""
    
    # Preberi CSV
    df = pd.read_csv(csv_file)
    
    def clean_text(text):
        if pd.isna(text) or text == '':
            return text
        
        # Odstrani nepotrebne besede in znake
        text = re.sub(r'moč komentarja:-\d+\+\d+', '', text)
        text = re.sub(r'strinjam sene strinjam se', '', text)
        text = re.sub(r'Napisal:', '', text)
        text = re.sub(r'\s+', ' ', text)  # Več presledkov v enega
        text = text.strip()
        
        return text
    
    # Očisti komentarje
    df['komentar_clean'] = df['komentar'].apply(clean_text)
    
    # Odstrani prazne komentarje
    df = df[df['komentar_clean'] != '']
    
    # Shrani očiščene podatke
    df.to_csv(output_file, index=False, encoding='utf-8')
    
    print(f"Podatki očiščeni in shranjeni v {output_file}")
    print(f"Skupaj vrstic: {len(df)}")
    
    return df

if __name__ == "__main__":
    clean_komentarji("profesorji_komentarji.csv", "profesorji_komentarji_clean.csv")
