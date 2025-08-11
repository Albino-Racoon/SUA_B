#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Test skripta za preverjanje osnovne funkcionalnosti scraper-ja
"""

import sys
import os
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from scrape_profesorji import ProfesorjiScraper

def test_basic_functionality():
    """Test osnovne funkcionalnosti"""
    print("Testiram osnovno funkcionalnost scraper-ja...")
    
    scraper = ProfesorjiScraper()
    
    # Test pridobivanja glavne strani
    print("1. Test pridobivanja glavne strani...")
    main_page_html = scraper.get_page(f"{scraper.base_url}/fakulteta/feri/uni")
    
    if main_page_html:
        print("✓ Glavna stran uspešno pridobljena")
        print(f"   Dolžina HTML-ja: {len(main_page_html)} znakov")
        
        # Test izločevanja povezav
        print("\n2. Test izločevanja povezav do profesorjev...")
        profesorji_links = scraper.extract_profesor_links(main_page_html)
        
        if profesorji_links:
            print(f"✓ Najdenih {len(profesorji_links)} profesorjev")
            print("   Prvih 5 profesorjev:")
            for i, profesor in enumerate(profesorji_links[:5]):
                print(f"   {i+1}. {profesor['ime']}")
            
            # Test scrapanja prvega profesorja
            if len(profesorji_links) > 0:
                print(f"\n3. Test scrapanja prvega profesorja ({profesorji_links[0]['ime']})...")
                profesor_data = scraper.scrape_profesor_page(
                    profesorji_links[0]['url'], 
                    profesorji_links[0]['ime']
                )
                
                if profesor_data:
                    print(f"✓ Profesor uspešno scrapan")
                    print(f"   Število komentarjev: {len(profesor_data['komentarji'])}")
                    if profesor_data['komentarji']:
                        print(f"   Prvi komentar: {profesor_data['komentarji'][0][:100]}...")
                else:
                    print("✗ Napaka pri scrapanju profesorja")
        else:
            print("✗ Ni bilo mogoče najti povezav do profesorjev")
    else:
        print("✗ Napaka pri pridobivanju glavne strani")
    
    print("\nTest končan!")

if __name__ == "__main__":
    test_basic_functionality()
