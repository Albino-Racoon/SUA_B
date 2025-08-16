# 🔄 Serverless Storitve - Oblačno Računalništvo

## 📋 Opis
Dve novi serverless storitvi implementirani za izpolnitev zahtev oblačnega računalništva:

### 📊 Analytics Service
- **CREATE**: Dodajanje novih log zapisov
- **READ**: Pridobivanje statistike uporabe
- **UPDATE**: Posodobitev log zapisov
- **DELETE**: Čiščenje starih logov

### 🔔 Notification Service
- **CREATE**: Ustvarjanje novih notifikacij
- **READ**: Pridobivanje notifikacij
- **UPDATE**: Označevanje kot prebrano
- **DELETE**: Brisanje notifikacij

## 🚀 Namestitev

### Lokalno testiranje:
```bash
cd serverless
npm install
npm start
```

### Vercel deployment:
```bash
vercel --prod
```

## 🔗 API Endpoints

### Analytics Service
- `GET /api/analytics` - Pridobi statistike
- `POST /api/analytics` - Dodaj log
- `PUT /api/analytics` - Posodobi log
- `DELETE /api/analytics?id=1` - Izbriši log

### Notification Service
- `GET /api/notifications` - Pridobi notifikacije
- `POST /api/notifications` - Dodaj notifikacijo
- `PUT /api/notifications` - Posodobi notifikacijo
- `DELETE /api/notifications?id=1` - Izbriši notifikacijo

### Health Check
- `GET /health` - Preveri stanje storitev

## 🧪 Testiranje
Storitve lahko testirate preko glavne aplikacije ali direktno preko API-jev.

## 📁 Struktura
```
serverless/
├── api/
│   ├── analytics.js      # Vercel serverless funkcija
│   └── notifications.js  # Vercel serverless funkcija
├── server.js             # Lokalni Express strežnik
├── package.json          # NPM konfiguracija
├── vercel.json           # Vercel konfiguracija
└── README.md             # Ta datoteka
```
