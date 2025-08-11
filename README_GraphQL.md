# GraphQL API za aplikacijo Profesorji Komentarji

## Opis

Implementiran je GraphQL API, ki omogoča dostop do podatkov o profesorjih, komentarjih in kvizih preko enotne končne točke.

## Zahteve

- PHP 8.0+
- Composer
- MySQL/MariaDB baza podatkov

## Namestitev

### 1. Namestitev odvisnosti

```bash
composer install
```

### 2. Konfiguracija baze podatkov

Preverite, da je `src/config/database.php` pravilno nastavljen.

### 3. Dostop do API-ja

GraphQL endpoint je dostopen na: `src/graphql.php`

## GraphQL Shema

### Tipi podatkov

#### Profesor
```graphql
type Profesor {
  id: Int!
  ime: String!
  url: String!
  created_at: String!
  updated_at: String!
  komentarji: [Komentar!]!
}
```

#### Komentar
```graphql
type Komentar {
  id: Int!
  komentar: String!
  created_at: String!
  profesor: Profesor!
}
```

#### KvizRezultat
```graphql
type KvizRezultat {
  id: Int!
  uporabnik_id: String!
  stevilo_pravilnih: Int!
  stevilo_vprasanj: Int!
  rezultat: Float!
  created_at: String!
}
```

#### Statistika
```graphql
type Statistika {
  skupno_kvizov: Int!
  povprecje: Float!
  najboljsi_rezultat: Float!
  najslabsi_rezultat: Float!
  skupno_pravilnih: Int!
  skupno_vprasanj: Int!
}
```

### Poizvedbe (Queries)

#### 1. Pridobi vse profesorje
```graphql
query {
  profesorji {
    id
    ime
    url
    komentarji {
      komentar
      created_at
    }
  }
}
```

#### 2. Pridobi določenega profesorja
```graphql
query {
  profesor(id: 1) {
    ime
    komentarji {
      komentar
      created_at
    }
  }
}
```

#### 3. Pridobi komentarje
```graphql
query {
  komentarji(profesor_id: 1, limit: 10) {
    komentar
    created_at
    profesor {
      ime
    }
  }
}
```

#### 4. Pridobi statistiko kvizov
```graphql
query {
  statistika {
    skupno_kvizov
    povprecje
    najboljsi_rezultat
  }
}
```

#### 5. Pridobi rezultate kvizov
```graphql
query {
  kvizRezultati(limit: 5) {
    uporabnik_id
    stevilo_pravilnih
    rezultat
    created_at
  }
}
```

### Parametri

- **limit**: Omeji število rezultatov
- **offset**: Začni od določenega indeksa
- **search**: Išči po imenu profesorja
- **profesor_id**: Filtriraj komentarje po profesorju

## Testiranje

### 1. GraphQL Playground

Odprite `src/graphql_test.php` v brskalniku za interaktivno testiranje API-ja.

### 2. cURL primer

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"query": "query { profesorji { ime } }"}' \
  http://localhost/src/graphql.php
```

### 3. JavaScript primer

```javascript
const query = `
  query {
    profesorji(limit: 5) {
      ime
      komentarji {
        komentar
      }
    }
  }
`;

fetch('/src/graphql.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ query })
})
.then(response => response.json())
.then(data => console.log(data));
```

## Prednosti GraphQL

1. **Natančno določanje podatkov**: Stranka lahko določi, katere podatke potrebuje
2. **Ena poizvedba za več podatkov**: Lahko pridobite profesorja in njegove komentarje v eni poizvedbi
3. **Stroga tipizacija**: Shema jasno definira, katere podatke lahko zahtevate
4. **Introspekcija**: API lahko sam opisuje svojo strukturo

## Primeri uporabe

### Kompleksna poizvedba
```graphql
query {
  profesorji(search: "Tina") {
    ime
    url
    komentarji {
      komentar
      created_at
    }
  }
  statistika {
    skupno_kvizov
    povprecje
  }
}
```

### Filtriranje in omejevanje
```graphql
query {
  profesorji(limit: 3, offset: 0) {
    ime
    komentarji(limit: 2) {
      komentar
    }
  }
}
```

## Varnost

- API podpira CORS za razvoj
- Vse poizvedbe so samo za branje (GET operacije)
- Ni implementiranih mutacij (spreminjanje podatkov)

## Razširitve

Za prihodnje razširitve lahko dodate:

1. **Mutations**: Za dodajanje/urejanje profesorjev in komentarjev
2. **Subscriptions**: Za real-time posodobitve
3. **Autentifikacija**: Zaščiten dostop do API-ja
4. **Rate limiting**: Omejitev števila zahtev
5. **Caching**: Predpomnjenje pogostih poizvedb

## Podpora

Za vprašanja in podporo glejte dokumentacijo GraphQL specifikacije ali se obrnite na razvijalce aplikacije.
