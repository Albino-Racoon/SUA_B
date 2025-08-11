# Navodila za namestitev GraphQL API-ja

## Korak 1: Namestitev Composer odvisnosti

V korenski mapi projekta izvedite:

```bash
composer install
```

To bo namestilo potrebne GraphQL knjižnice (webonyx/graphql-php).

## Korak 2: Preverite konfiguracijo baze podatkov

Preverite, da je `src/config/database.php` pravilno nastavljen in da se lahko povežete z bazo podatkov.

## Korak 3: Testiranje GraphQL API-ja

### 3.1 Odprite GraphQL test stran
Pojdite na: `src/graphql_test.php`

### 3.2 Testirajte osnovno poizvedbo
V GraphQL playground vnesite:

```graphql
query {
  profesorji(limit: 3) {
    ime
    komentarji {
      komentar
    }
  }
}
```

### 3.3 Preverite rezultate
Kliknite "Izvedi poizvedbo" in preverite, ali se prikažejo podatki.

## Korak 4: Integracija v obstoječo aplikacijo

### 4.1 Odprite integracijsko stran
Pojdite na: `src/graphql_integration_example.php`

### 4.2 Testirajte različne funkcionalnosti
- Iskanje profesorjev
- Nalaganje statistike
- Kompleksne poizvedbe

## Korak 5: Uporaba v JavaScript kodi

### 5.1 Osnovna GraphQL poizvedba
```javascript
async function getProfessors() {
    const query = `
        query {
            profesorji {
                ime
                komentarji {
                    komentar
                }
            }
        }
    `;
    
    const response = await fetch('/src/graphql.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ query })
    });
    
    return await response.json();
}
```

### 5.2 Poizvedba s parametri
```javascript
async function searchProfessors(searchTerm) {
    const query = `
        query {
            profesorji(search: "${searchTerm}") {
                ime
                url
            }
        }
    `;
    
    // ... implementacija
}
```

## Korak 6: Preverjanje delovanja

### 6.1 Preverite GraphQL endpoint
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"query": "query { statistika { skupno_kvizov } }"}' \
  http://localhost/src/graphql.php
```

### 6.2 Preverite CORS nastavitve
GraphQL endpoint ima nastavljene CORS glave za razvoj.

## Korak 7: Razširitve

### 7.1 Dodajanje novih tipov
V `src/graphql/schema.php` dodajte nove tipe podatkov.

### 7.2 Dodajanje novih poizvedb
V `queryType` dodajte nove polja za poizvedbe.

### 7.3 Implementacija resolucij
Za vsako novo poizvedbo implementirajte resolver funkcijo.

## Težave in rešitve

### Problem: "Class not found" napaka
**Rešitev**: Preverite, da je `composer install` uspešno izveden.

### Problem: "Database connection failed"
**Rešitev**: Preverite konfiguracijo v `src/config/database.php`.

### Problem: "GraphQL schema error"
**Rešitev**: Preverite sintakso v `src/graphql/schema.php`.

### Problem: CORS napake
**Rešitev**: GraphQL endpoint ima že nastavljene CORS glave.

## Podpora

Za dodatno podporo glejte:
- `README_GraphQL.md` - Podrobna dokumentacija
- `src/graphql_test.php` - Interaktivno testiranje
- `src/graphql_integration_example.php` - Primeri integracije

## Preverjanje zahtev

✅ **GraphQL API implementiran** - `src/graphql.php`  
✅ **Vsaj 3 tipi podatkov** - Profesor, Komentar, KvizRezultat, Statistika  
✅ **Vsaj 2 poizvedbi** - profesorji, statistika, komentarji, kvizRezultati  
✅ **Skalar tipi** - String, Int, Float, vključno z datumom (created_at)  
✅ **Polja, ki so drugi tipi** - profesorji.komentarji, komentarji.profesor  
✅ **Resolucije implementirane** - Vse poizvedbe imajo resolver funkcije  
✅ **Integracija v obstoječo aplikacijo** - Dodana nova navigacija in test strani  
✅ **REST API ohranjen** - Vsa obstoječa funkcionalnost deluje  
✅ **Dokumentacija** - README in navodila za namestitev  
✅ **Testiranje** - GraphQL playground in primerjava REST vs GraphQL
