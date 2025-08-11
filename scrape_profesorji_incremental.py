#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Inkrementalna skripta za scrapanje profesorjev z možnostjo nadaljevanja
"""

import requests
from bs4 import BeautifulSoup
import pandas as pd
import time
import re
from urllib.parse import urljoin
import logging
import os
import json

# Nastavitev logiranja
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

class IncrementalProfesorjiScraper:
    def __init__(self, checkpoint_file="scraping_checkpoint.json"):
        self.base_url = "https://profesorji.net"
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        })
        self.profesorji_data = []
        self.checkpoint_file = checkpoint_file
        self.load_checkpoint()
        
    def load_checkpoint(self):
        """Naloži checkpoint, če obstaja"""
        if os.path.exists(self.checkpoint_file):
            try:
                with open(self.checkpoint_file, 'r', encoding='utf-8') as f:
                    checkpoint = json.load(f)
                    self.profesorji_data = checkpoint.get('profesorji_data', [])
                    logger.info(f"Naložen checkpoint z {len(self.profesorji_data)} profesorji")
            except Exception as e:
                logger.error(f"Napaka pri nalaganju checkpoint-a: {e}")
                self.profesorji_data = []
    
    def save_checkpoint(self):
        """Shrani checkpoint"""
        try:
            checkpoint = {
                'profesorji_data': self.profesorji_data,
                'timestamp': time.time()
            }
            with open(self.checkpoint_file, 'w', encoding='utf-8') as f:
                json.dump(checkpoint, f, ensure_ascii=False, indent=2)
            logger.info("Checkpoint shranjen")
        except Exception as e:
            logger.error(f"Napaka pri shranjevanju checkpoint-a: {e}")
    
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
        
        # Metoda 3: Išči po strukturi strani
        if not komentarji:
            all_divs = soup.find_all('div')
            for div in all_divs:
                text = div.get_text(strip=True)
                if (len(text) > 50 and 
                    not text.startswith('profesorji.net') and
                    not text.startswith('admin') and
                    '©' not in text and
                    'piškotki' not in text.lower() and
                    'splošni pogoji' not in text.lower()):
                    
                    if any(keyword in text.lower() for keyword in ['ocena', 'komentar', 'mnenje', 'predavanje', 'strinjam', 'ne strinjam']):
                        komentarji.append(text)
        
        # Metoda 4: Išči po besedilu, ki vsebuje ocene
        if not komentarji:
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
    
    def scrape_all_profesorji(self, start_from=0, batch_size=10):
        """Glavna funkcija za scrapanje vseh profesorjev z možnostjo nadaljevanja"""
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
        
        # Preveri, kateri profesorji so že scrapani
        scraped_urls = {p['url'] for p in self.profesorji_data}
        remaining_profesorji = [p for p in profesorji_links if p['url'] not in scraped_urls]
        
        logger.info(f"Skupaj profesorjev: {len(profesorji_links)}")
        logger.info(f"Že scrapanih: {len(scraped_urls)}")
        logger.info(f"Ostane za scrapanje: {len(remaining_profesorji)}")
        
        if start_from > 0:
            remaining_profesorji = remaining_profesorji[start_from:]
        
        # Scrapaj profesorje v batch-ih
        for i, profesor in enumerate(remaining_profesorji):
            logger.info(f"Scrapam {i+1}/{len(remaining_profesorji)}: {profesor['ime']}")
            
            profesor_data = self.scrape_profesor_page(profesor['url'], profesor['ime'])
            if profesor_data:
                self.profesorji_data.append(profesor_data)
                
                # Shrani checkpoint vsakih batch_size profesorjev
                if (i + 1) % batch_size == 0:
                    self.save_checkpoint()
                    logger.info(f"Checkpoint shranjen po {i+1} profesorjih")
            
            # Pause med zahtevki
            time.sleep(1)
        
        # Končni checkpoint
        self.save_checkpoint()
        logger.info(f"Scrapanje končano. Skupaj {len(self.profesorji_data)} profesorjev.")
    
    def save_to_csv(self, filename="profesorji_komentarji_incremental.csv"):
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
    scraper = IncrementalProfesorjiScraper()
    
    try:
        # Scrapaj vse profesorje
        scraper.scrape_all_profesorji(batch_size=5)
        
        # Shrani v CSV
        df = scraper.save_to_csv()
        
        if df is not None:
            print("\n" + "="*50)
            print("SCRAPANJE KONČANO!")
            print("="*50)
            print(f"Skupaj profesorjev: {len(scraper.profesorji_data)}")
            print(f"Skupaj vrstic v CSV: {len(df)}")
            print(f"Profesorjev s komentarji: {len(df[df['komentar'] != ''])}")
            print("="*50)
        
    except KeyboardInterrupt:
        logger.info("Scrapanje prekinjeno s strani uporabnika")
        scraper.save_checkpoint()
        if scraper.profesorji_data:
            scraper.save_to_csv()
    except Exception as e:
        logger.error(f"Napaka med scrapanjem: {e}")
        scraper.save_checkpoint()
        if scraper.profesorji_data:
            scraper.save_to_csv()

if __name__ == "__main__":
    main()
