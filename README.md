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

## Atlaižu kodu administrēšana

Izveido lietotāja kontu veikalā, tad iestati tā e-pastu `ADMIN_EMAIL` vērtībā failā `Backend/api/.env` un no `Backend/api` mapes palaid `php artisan config:clear`. Pēc pārlādes pieslēdzies šim kontam un atver `http://localhost:5173/#/admin` (vai Vite terminālī parādīto adresi). Admina e-pastu iestati tikai pēc sava konta izveides, lai publiskā reģistrācija nevarētu iepriekš rezervēt šo adresi.

Admina lapā var izveidot manuālu vai automātiski ģenerētu atlaižu kodu ar 0.01–100% atlaidi, vienu/neierobežotu lietošanas reižu skaitu un derīguma termiņu. Kodi tiek saglabāti datubāzē; neaktīvu, izlietotu vai beigušos kodu grozā vairs nepieņem.

Tajā pašā lapā administrators var pievienot jaunas šokolādes un rediģēt esošo nosaukumu, cenu, atlikumu, aprakstu, birku, iepakojuma stilu un produkta attēlu (JPG, PNG vai WebP līdz 5 MB). Attēli tiek glabāti Laravel publiskajā diskā. Šokolādi var dzēst, ja tai nav pasūtījumu; pasūtījumu vēsture tiek saglabāta. Veidojot kuponu, izvēlies konkrētās šokolādes, uz kurām atlaide attiecas; ja neviena nav izvēlēta, kupons attiecas uz visām.

Pasūtījuma noformēšanas lapa ir pieejama maršrutā `#/pasutijums`; to var atvērt arī no groza. Lapā pārskati grozu, piemēro atlaižu kodu un apstiprini pasūtījumu. Piegāde un tiešsaistes apmaksa pašlaik nav pieejama.

Pēc tam atver divus termināļus repozitorija saknes mapē:

```sh
npm run backend
```

```sh
npm run dev
```

Laravel API būs pieejams `http://127.0.0.1:8000`, bet Vite priekšskatījums — tā norādītajā lokālajā adresē. Vite pārsūta `/api` pieprasījumus Laravel serverim. Backend startēšanas komanda iestata PHP augšupielādes limitu tā, lai var pievienot attēlus līdz lietotnē atļautajiem 5 MB.

Shēmu un testa/preču datus pārvalda Laravel migrācijas un sējējs — SQL failus manuāli importēt nevajag. Lai pilnībā atiestatītu lokālo datubāzi un no jauna ielādētu katalogu, no `Backend/api` mapes palaid `php artisan migrate:fresh --seed`; šī komanda dzēš visus pašreizējos datubāzes datus.

Ja vajadzīgs MySQL, izveido datubāzi `ShocoStore` un nomaini `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` un `DB_PASSWORD` vērtības failā `Backend/api/.env`.

## API

- `GET /api/products` — preču katalogs.
- `POST /api/products` — saglabā preci ar `name`, `price` un neobligātajiem `stock`, `description`, `tone`, `tag` laukiem.
- `POST /api/products` — administrators var papildus augšupielādēt produkta `image`; `POST /api/products/{id}` ar `_method=PUT` atjaunina preci un, ja norādīts, aizstāj tās attēlu. Palaid `php artisan storage:link`, lai publiski attēli būtu pieejami.
- `GET /api/orders` — pieslēgtā lietotāja pasūtījumi.
- `POST /api/orders` — saglabā pasūtījumu ar `productId`, `quantity` un neobligāto `customerName`; krājumu pārbauda un samazina datubāzes transakcijā.
- `GET /api/health` — pārbauda API pieejamību.
