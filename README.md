# ShocoStore

## Prasības

- PHP 8.3 vai jaunāka versija ar ieslēgtiem `pdo_sqlite`, `openssl`, `mbstring`, `fileinfo` un `zip` paplašinājumiem.
- Composer 2.
- Node.js un npm.

MySQL serveris vai Docker lokālai izstrādei nav nepieciešams. Backend pēc noklusējuma izmanto SQLite datubāzi failā `Backend/api/database/database.sqlite`.

## Uzstādīšana

No repozitorija saknes mapes palaid:

```sh
npm run backend:setup
```

Komanda uzinstalē PHP atkarības, izveido `Backend/api/.env` no piemēra, sagatavo lokālo SQLite datubāzi, ģenerē Laravel atslēgu, izveido tabulas un ielādē sākuma preču katalogu. Esošais `.env` netiek aizstāts ar paraugu, bet datubāzei tiek piemērotas migrācijas.

Ja `.env` jau ir izveidots ar MySQL iestatījumiem, pirms uzstādīšanas nomaini tajā `DB_CONNECTION=sqlite` un `DB_DATABASE=database/database.sqlite`. Tas atstāj MySQL datus neskartus, bet šī lokālā lietotnes kopija turpmāk izmantos SQLite failu.

Pēc tam atver divus termināļus repozitorija saknes mapē:

```sh
npm run backend
```

```sh
npm run dev
```

Laravel API būs pieejams `http://127.0.0.1:8000`, bet Vite priekšskatījums — tā norādītajā lokālajā adresē. Vite pārsūta `/api` pieprasījumus Laravel serverim.

Shēmu un testa/preču datus pārvalda Laravel migrācijas un sējējs — SQL failus manuāli importēt nevajag. Lai pilnībā atiestatītu lokālo datubāzi un no jauna ielādētu katalogu, no `Backend/api` mapes palaid `php artisan migrate:fresh --seed`; šī komanda dzēš visus pašreizējos datubāzes datus.

Ja vajadzīgs MySQL, izveido datubāzi `ShocoStore` un nomaini `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` un `DB_PASSWORD` vērtības failā `Backend/api/.env`.

## API

- `GET /api/products` — preču katalogs.
- `POST /api/products` — saglabā preci ar `name`, `price` un neobligātajiem `stock`, `description`, `tone`, `tag` laukiem.
- `GET /api/orders` — pieslēgtā lietotāja pasūtījumi.
- `POST /api/orders` — saglabā pasūtījumu ar `productId`, `quantity` un neobligāto `customerName`; krājumu pārbauda un samazina datubāzes transakcijā.
- `GET /api/health` — pārbauda API pieejamību.
