#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Skripta za scrapanje profesorjev in komentarjev s strani profesorji.net
"""

import requests
from bs4 import BeautifulSoup
import pandas as pd
import time
import re
from urllib.parse import urljoin, urlparse
import logging

# Nastavitev logiranja
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

class ProfesorjiScraper:
    def __init__(self):
        self.base_url = "https://profesorji.net"
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        })
        self.profesorji_data = []
        
    def get_page(self, url):
        """Pridobi stran z error handling"""
        try:
            response = self.session.get(url, timeout=10)
            response.raise_for_status()
            response.encoding = 'utf-8'
            return response.text
        except requests.RequestException as e:
            logger.error(f"Napaka pri pridobivanju strani {url}: {e}")
            return None
    
    def extract_profesor_links(self, html_content):
        """Izloči vse povezave do profesorjev iz glavne strani"""
        soup = BeautifulSoup(html_content, 'html.parser')
        profesorji_links = []
        
        # Iščemo povezave v seznamu profesorjev
        # Poiščemo vse povezave, ki vsebujejo '/profesor/feri/uni/'
        links = soup.find_all('a', href=True)
        
        for link in links:
            href = link.get('href')
            if '/profesor/feri/uni/' in href:
                full_url = urljoin(self.base_url, href)
                profesor_name = link.get_text(strip=True)
                if profesor_name and full_url not in [p['url'] for p in profesorji_links]:
                    profesorji_links.append({
                        'ime': profesor_name,
                        'url': full_url
                    })
        
        logger.info(f"Najdenih {len(profesorji_links)} profesorjev")
        return profesorji_links
    
    def scrape_profesor_page(self, profesor_url, profesor_ime):
        """Scrapa stran posameznega profesorja in pridobi komentarje"""
        logger.info(f"Scrapam profesorja: {profesor_ime}")
        
        html_content = self.get_page(profesor_url)
        if not html_content:
            return None
        
        soup = BeautifulSoup(html_content, 'html.parser')
        
        # Pridobi osnovne informacije o profesorju
        profesor_info = {
            'ime': profesor_ime,
            'url': profesor_url,
            'komentarji': []
        }
        
        # Pridobi komentarje - poskusi različne metode
        komentarji = []
        
        # Metoda 1: Išči po klasi 'comment'
        comment_divs = soup.find_all('div', class_=lambda x: x and 'comment' in x.lower())
        if comment_divs:
            for div in comment_divs:
                text = div.get_text(strip=True)
                if text and len(text) > 20:
                    komentarji.append(text)
        
        # Metoda 2: Išči po stilu (margin)
        if not komentarji:
            style_divs = soup.find_all('div', style=lambda x: x and 'margin' in x)
            for div in style_divs:
                text = div.get_text(strip=True)
                if text and len(text) > 20:
                    komentarji.append(text)
        
        # Metoda 3: Išči po strukturi strani - komentarji so običajno v div-ih z dolgim besedilom
        if not komentarji:
            all_divs = soup.find_all('div')
            for div in all_divs:
                text = div.get_text(strip=True)
                # Filtriranje: dolgo besedilo, ne vsebuje navigacijskih elementov
                if (len(text) > 50 and 
                    not text.startswith('profesorji.net') and
                    not text.startswith('admin') and
                    '©' not in text and
                    'piškotki' not in text.lower() and
                    'splošni pogoji' not in text.lower()):
                    
                    # Preveri, če je to verjetno komentar
                    if any(keyword in text.lower() for keyword in ['ocena', 'komentar', 'mnenje', 'predavanje', 'strinjam', 'ne strinjam']):
                        komentarji.append(text)
        
        # Metoda 4: Išči po besedilu, ki vsebuje ocene
        if not komentarji:
            # Išči besedilo, ki vsebuje "Ocena X.XX od 5"
            ocena_pattern = re.compile(r'Ocena\s+\d+\.\d+\s+od\s+5')
            for element in soup.find_all(text=True):
                if ocena_pattern.search(element):
                    parent = element.parent
                    if parent:
                        text = parent.get_text(strip=True)
                        if text and len(text) > 30:
                            komentarji.append(text)
        
        # Odstrani duplikate in prekratke komentarje
        unique_komentarji = []
        for komentar in komentarji:
            komentar_clean = komentar.strip()
            if (len(komentar_clean) > 30 and 
                komentar_clean not in unique_komentarji):
                unique_komentarji.append(komentar_clean)
        
        profesor_info['komentarji'] = unique_komentarji
        
        logger.info(f"Najdenih {len(profesor_info['komentarji'])} komentarjev za {profesor_ime}")
        return profesor_info
    
    def scrape_all_profesorji(self, max_profesorji=None):
        """Glavna funkcija za scrapanje vseh profesorjev"""
        logger.info("Začenjam scrapanje glavne strani...")
        
        # Pridobi glavno stran
        main_page_html = self.get_page(f"{self.base_url}/fakulteta/feri/uni")
        if not main_page_html:
            logger.error("Ni mogoče pridobiti glavne strani")
            return
        
        # Izloči povezave do profesorjev
        profesorji_links = self.extract_profesor_links(main_page_html)
        
        if not profesorji_links:
            logger.error("Ni bilo mogoče najti povezav do profesorjev")
            return
        
        # Omeji število profesorjev za testiranje
        if max_profesorji:
            profesorji_links = profesorji_links[:max_profesorji]
            logger.info(f"TEST MODE: Scrapam samo prvih {max_profesorji} profesorjev")
        
        # Scrapaj vsakega profesorja
        for i, profesor in enumerate(profesorji_links, 1):
            logger.info(f"Scrapam {i}/{len(profesorji_links)}: {profesor['ime']}")
            
            profesor_data = self.scrape_profesor_page(profesor['url'], profesor['ime'])
            if profesor_data:
                self.profesorji_data.append(profesor_data)
            
            # Pause med zahtevki
            time.sleep(1)
        
        logger.info(f"Scrapanje končano. Skupaj {len(self.profesorji_data)} profesorjev.")
    
    def save_to_csv(self, filename="profesorji_komentarji.csv"):
        """Shrani podatke v CSV datoteko"""
        if not self.profesorji_data:
            logger.warning("Ni podatkov za shranjevanje")
            return
        
        # Pripravi podatke za CSV
        csv_data = []
        for profesor in self.profesorji_data:
            if profesor['komentarji']:
                for komentar in profesor['komentarji']:
                    csv_data.append({
                        'profesor': profesor['ime'],
                        'url': profesor['url'],
                        'komentar': komentar
                    })
            else:
                # Če ni komentarjev, dodaj vrstico brez komentarja
                csv_data.append({
                    'profesor': profesor['ime'],
                    'url': profesor['url'],
                    'komentar': ''
                })
        
        # Ustvari DataFrame in shrani
        df = pd.DataFrame(csv_data)
        df.to_csv(filename, index=False, encoding='utf-8')
        logger.info(f"Podatki shranjeni v {filename}")
        
        # Prikaži statistiko
        logger.info(f"Skupaj vrstic: {len(df)}")
        logger.info(f"Profesorjev s komentarji: {len(df[df['komentar'] != ''])}")
        
        return df

def main():
    """Glavna funkcija"""
    scraper = ProfesorjiScraper()
    
    # Za testiranje: scrapaj samo prvih 5 profesorjev
    # Za produkcijo: odstrani max_profesorji parameter
    TEST_MODE = False
    MAX_PROFESORJI = 5 if TEST_MODE else None
    
    try:
        # Scrapaj vse profesorje
        scraper.scrape_all_profesorji(max_profesorji=MAX_PROFESORJI)
        
        # Shrani v CSV
        filename = "profesorji_komentarji_test.csv" if TEST_MODE else "profesorji_komentarji.csv"
        df = scraper.save_to_csv(filename)
        
        if df is not None:
            print("\n" + "="*50)
            print("SCRAPANJE KONČANO!")
            print("="*50)
            print(f"Skupaj profesorjev: {len(scraper.profesorji_data)}")
            print(f"Skupaj vrstic v CSV: {len(df)}")
            print(f"Profesorjev s komentarji: {len(df[df['komentar'] != ''])}")
            print(f"Datoteka: {filename}")
            print("="*50)
        
    except KeyboardInterrupt:
        logger.info("Scrapanje prekinjeno s strani uporabnika")
        if scraper.profesorji_data:
            filename = "profesorji_komentarji_test.csv" if TEST_MODE else "profesorji_komentarji.csv"
            scraper.save_to_csv(filename)
    except Exception as e:
        logger.error(f"Napaka med scrapanjem: {e}")
        if scraper.profesorji_data:
            filename = "profesorji_komentarji_test.csv" if TEST_MODE else "profesorji_komentarji.csv"
            scraper.save_to_csv(filename)

if __name__ == "__main__":
    main()
