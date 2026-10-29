# ShocoStore

Šokolādes veikals ar Vue 3 saskarni un Laravel API. Klienti var reģistrēties, apskatīt katalogu, norādīt piegādes adresi un iesniegt pasūtījumus. Administrators pārvalda produktus, atlaižu kodus un pasūtījumu izpildi.

## Kā projekts darbojas

```text
Vue komponente → Frontend/src/api/store.js → /api maršruts
→ Laravel kontrolieris → modelis → datubāze → JSON atbilde pārlūkam
```

Konti, preces, adreses, kuponi un pasūtījumi ir datubāzē. Foto faili ir failu glabātuvē; datubāzē saglabā to ceļus. Vēlmju saraksts un tēmas izvēle tiek glabāti pārlūkā.

## Projekta struktūra: kas par ko atbild

### Kopīgie faili

| Fails / mape | Atbildība |
| --- | --- |
| `Frontend/src/` | Vue saskarne un API pieprasījumi. |
| `Backend/api/` | Laravel serveris, datubāze, autentifikācija un validācija. |
| [package.json](package.json) | npm atkarības un izstrādes/būvēšanas komandas. |
| [vite.config.js](vite.config.js) | Vue būvēšana un lokālā `/api` pārsūtīšana uz Laravel portā 8000. |
| `package-lock.json` | Precīzas npm atkarību versijas; tās instalē `npm ci`. |
| `Backend/api/composer.json`, `composer.lock` | PHP atkarības, to versijas un backend uzstādīšanas komandas. |

### Frontend loģika

Ceļi ir attiecībā pret `Frontend/src/`.

| Fails | Atbildība |
| --- | --- |
| `main.js` | Ielādē CSS un pievieno Vue lietotni HTML elementam `#app`. |
| `App.vue` | Sākumlapa, galvene, kājene, navigācija pēc `#/...`, grozs un kopīgie dialogi. |
| `style.css` | Kopīgās krāsas, fonti, tumšā tēma un responsīvie izkārtojumi. Komponentēm var būt arī savs `<style scoped>`. |
| `api/store.js` | Axios pieprasījumi, CSRF tokeni, API kļūdas un kontu/preču/adrešu/pasūtījumu/kuponu darbības. |
| `api/state.js` | Kopīgais katalogs, pieslēgtais lietotājs, ielādes kļūdas un sesijas atjaunošana. |
| `api/product-image.js` | Pārbauda foto, samazina garāko malu līdz 1600 px un pārveido JPEG failā līdz 1 MB. |
| `theme.js` | Gaišās/tumšās tēmas pārslēgšana un saglabāšana. |
| `wishlist.js` | Vēlmju saraksta produktu ID glabāšana `localStorage`. |
| `data/products.js` | Eiro summu formatēšana; šis fails nav preču datubāze. |
| `assets/images/` | Logotips, sākumlapas bildes un noklusējuma produktu fotogrāfiju atlants. |

### Frontend komponentes

Mape: `Frontend/src/components/`.

| Komponente | Atbildība |
| --- | --- |
| `ShopPage.vue` | Katalogs, meklēšana, kategorijas un kārtošana. |
| `ShopCard.vue` | Viena produkta kartīte un pievienošana grozam. |
| `ProductImage.vue` | Augšupielādētais foto vai noklusējuma attēls no atlanta. |
| `ProductDetailDialog.vue` | Apraksts, kakao saturs, sastāvdaļas, alergēni un uzturvērtība uz 100 g. |
| `CartDrawer.vue` | Groza sānu logs un preču daudzumi. |
| `CheckoutPage.vue` | Groza iesniegšana, piegādes adrese, kupons un kopsavilkums. |
| `LoginPage.vue`, `RegisterPage.vue` | Pieslēgšanās un reģistrācija. |
| `EmailVerificationNotice.vue` | E-pasta verificēšanas statuss un atkārtota saites nosūtīšana. |
| `ProfilePage.vue` | Profila rediģēšana, adreses/pasūtījumu sadaļas un konta dzēšana. |
| `AddressFields.vue` | Kopīgi saņēmēja, tālruņa un adreses lauki profilam, noformēšanai un pasūtījuma maiņai. |
| `DeliveryAddress.vue` | Noklusējuma piegādes adreses saglabāšana lietotāja profilā. |
| `OrdersPanel.vue` | Klienta/admin pasūtījumi, statusa izvēlne, vēsture, adreses/daudzumu maiņa un atcelšana. |
| `AdminPage.vue` | Produktu pievienošana/rediģēšana, receptūra, uzturvērtība, atlaižu kodi un pasūtījumu sadaļa. |
| `WishlistPage.vue`, `WishButton.vue` | Vēlmju saraksta lapa un pievienošanas/noņemšanas poga. |
| `AboutPage.vue` | Veikala stāsta lapa. |

### Backend maršruti un konfigurācija

Ceļi ir attiecībā pret `Backend/api/`.

| Fails | Atbildība |
| --- | --- |
| `routes/api.php` | `/api` galapunkti un autentifikācijas, verificēta e-pasta, admin un pieprasījumu biežuma ierobežojumi. |
| `routes/web.php` | Atver frontend būvējumu `public/shop/index.html`. |
| `bootstrap/app.php` | Laravel palaišana, maršruti, `admin` middleware un JSON kļūdas API. |
| `app/Http/Middleware/EnsureAdmin.php` | Admin piekļuve verificētam kontam, kura e-pasts sakrīt ar `ADMIN_EMAIL`. |
| `app/Providers/AppServiceProvider.php` | Verificēšanas vēstules teksts un poga. |
| `config/app.php` | Lietotnes adreses, nosaukums un administratora e-pasts. |
| `config/auth.php`, `config/session.php` | Autentifikācija un sesiju glabāšana. |
| `config/database.php` | Datubāzes savienojumi. |
| `config/filesystems.php` | Failu diski, publiskie URL un glabātuves saite. |
| `config/mail.php` | Vēstuļu transports un SMTP iestatījumi. |
| `config/queue.php`, `config/cache.php`, `config/logging.php` | Fona darbi, kešs un žurnāli. |

### Backend kontrolieri

Mape: `Backend/api/app/Http/Controllers/`.

| Kontrolieris | Atbildība |
| --- | --- |
| `AuthController.php` | Reģistrācija, pieslēgšanās/izrakstīšanās, profila maiņa, e-pasta verificēšana un konta dzēšana. Atgriež arī lietotāja adresi un `isAdmin`. |
| `StoreController.php` | Katalogs, preču validācija/saglabāšana/dzēšana, attēli, uzturvērtība, receptūras saturs un agrākais vienas preces pasūtījuma API. |
| `AdminCouponController.php` | Kuponu izveide, pārbaude, saraksts, atsaukšana un piesaiste precēm. |
| `CouponController.php` | Visa groza iesniegšana: cenas, atlaides, atlikumu pārbaude un viena sūtījuma rindu izveide datubāzes transakcijā. |
| `ShipmentController.php` | Profila adrese, klienta/admin pasūtījumi, statusu pārejas, daudzumu/adreses maiņa, izsekošanas numurs un atlikumu atjaunošana pēc atcelšanas. |

### Modeļi un dati

Mape: `Backend/api/app/Models/`.

| Modelis / tabula | Saturs |
| --- | --- |
| `Customer.php` / `Lietotajs` | Lietotājs, paroles jaucējvērtība, verificēts e-pasts un noklusējuma adrese. |
| `Prece.php` / `Prece` | Šokolādes cena, atlikums, apraksts, attēla ceļš, uzturvērtība un receptūra. |
| `Pasutijums.php` / `Pasutijums` | Vienas preces pasūtījuma rinda. Kopīgs `shipment_id` apvieno groza rindas vienā sūtījumā; saglabā cenu, adreses kopiju, statusu un vēsturi. |
| `DiscountCoupon.php` / `discount_coupons` | Atlaides procents, derīguma termiņš, limits un izmantošanas skaits. |
| `discount_coupon_products` | Kuponu un preču saites; bez piesaistītām precēm kupons attiecas uz visām. |

`database/seeders/catalog.json` satur sākotnējo katalogu un receptūras datus. `DatabaseSeeder.php` pievieno trūkstošās preces pēc nosaukuma ar atlikumu 25; jau esošās preces nepārraksta. Admin ievadīto saturu un uzturvērtību glabā datubāzē; sākotnējā kataloga receptūra ir rezerves dati pēc preces nosaukuma.

### Migrācijas

Mape: `Backend/api/database/migrations/`. Migrācijas maina datubāzes struktūru, nevis lapas izskatu.

| Migrācija | Ko izveido / papildina |
| --- | --- |
| `0001_01_01_*` | Laravel sākotnējās tabulas, sesijas, kešs un fona darbi. |
| `2026_10_05_000000_create_store_tables.php` | Veikala lietotāji, preces un pasūtījumu rindas. |
| `2026_10_16_000000_create_discount_coupons_table.php` | Atlaižu kodi. |
| `2026_10_16_000001_create_discount_coupon_products_table.php` | Kuponu piesaiste precēm. |
| `2026_10_16_000002_add_product_image_path.php` | Produkta attēla ceļš. |
| `2026_10_26_000000_add_email_verified_at_to_lietotajs_table.php` | E-pasta verificēšanas laiks. |
| `2026_10_29_000001_add_nutrition_to_products.php` | Uzturvērtība JSON laukā. |
| `2026_10_29_000002_add_details_to_products.php` | Kakao saturs, sastāvdaļas, alergēni un receptūras apraksts JSON laukā. |
| `2026_10_29_000003_add_delivery_and_shipment_fields.php` | Profila/pasūtījuma adrese, sūtījuma ID, vienības cena, statusu vēsture un izsekošanas numurs. |

Jau izpildītas migrācijas rediģēšana serverī izmaiņas nepiemēro. Ja vajag mainīt esošas datubāzes struktūru, pievieno jaunu migrāciju.

## Lokālā uzstādīšana

- Projekta izstrādes PHP versija ir 8.5. Pašreiz instalētās atkarības prasa vismaz PHP 8.4.1; ar PHP 8.3 nepietiek visām atkarībām/testiem.
- Composer 2; Node.js 20.19+ vai 22.12+ atbilstoši Vite prasībām; npm.
- PHP paplašinājumi: `pdo_sqlite` lokālai datubāzei vai `pdo_mysql` MySQL, kā arī `openssl`, `mbstring`, `fileinfo`, `dom`, `xml` un `zip`.

Sākotnējai uzstādīšanai no repozitorija saknes:

```sh
npm ci
npm run backend:setup
```

Setup instalē PHP atkarības, izveido trūkstošo `Backend/api/.env`, sagatavo SQLite failu, ģenerē lietotnes atslēgu, izveido lokālo publiskās glabātuves saiti un palaiž migrācijas ar katalogu. Esoša servera atjaunināšanai izmanto migrācijas, nevis atkārtotu atslēgas ģenerēšanu.

Lokālā `.env` datubāze:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

SQLite izstrādei MySQL vai Docker nav vajadzīgs. MySQL gadījumā iestati savus `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` un `DB_PASSWORD`.

Atsevišķos termināļos repozitorija saknē palaid:

```sh
npm run backend
```

```sh
npm run dev
```

API darbojas `http://127.0.0.1:8000`. Veikalu atver Vite parādītajā adresē, parasti `http://localhost:5173`; Vite pārsūta `/api` pieprasījumus backend. Ja PowerShell bloķē `npm.ps1`, izmanto `npm.cmd`.

| Komanda | Mape | Atbildība |
| --- | --- | --- |
| `npm run dev` | Sakne | Frontend izstrādes serveris. |
| `npm run backend` | Sakne | Lokāls PHP serveris ar upload limitiem. |
| `npm run build` | Sakne | Frontend būvējums mapē `dist/`. |
| `npm run build:cloud` | Sakne | Servera būvējums `Backend/api/public/shop/` ar `/shop/` resursu ceļu. |
| `php artisan migrate` | `Backend/api` | Jaunās datubāzes migrācijas. |
| `php artisan db:seed` | `Backend/api` | Sākotnējā kataloga trūkstošās preces. |
| `php artisan test --compact` | `Backend/api` | Backend testi ar testa datubāzi. |

`php artisan migrate:fresh --seed` dzēš tabulas un izveido tās no jauna. Izmanto tikai apzinātai datubāzes atiestatīšanai.

## Vides mainīgie un e-pasti

Lokālās vērtības ir `Backend/api/.env`, paraugs — `.env.example`. Serverī izmanto hostinga vides mainīgos. Papildinot iestatījumus, saglabā esošo lietotnes atslēgu un datubāzes savienojumu.

| Mainīgais | Atbildība |
| --- | --- |
| `APP_KEY` | Laravel šifrēšana. Esošā vidē saglabā tās pašreizējo atslēgu. |
| `APP_URL`, `FRONTEND_URL` | Publiskās adreses, e-pasta saites un atgriešanās profilā. |
| `ADMIN_EMAIL` | Admin konta e-pasts; kontam jābūt reģistrētam un verificētam. |
| `DB_*` | Datubāzes savienojums. |
| `SESSION_DRIVER` | Sesiju glabāšana. |
| `MAIL_MAILER` | `log` raksta vēstules žurnālā; `smtp` sūta caur e-pasta pakalpojumu. |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME` | SMTP savienojums. |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Pakalpojuma SMTP lietotājs un SMTP atslēga/parole. |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Atļautā/verificētā nosūtītāja adrese un nosaukums. |
| `QUEUE_CONNECTION` | Fona darbu konfigurācija; pašreizējā verificēšanas vēstule tiek sūtīta sinhroni. |

Verificēšanas saite ir parakstīta un derīga 60 minūtes. E-pasta maiņa profilā atceļ iepriekšējo verificēšanu. Ja SMTP pakalpojums ierobežo IP adreses, atļauj lietotnes servera izejošo IP. Atslēgas un paroles neievieto Git.

## Funkciju darbība

### Administrācija un foto

Pēc konta izveides iestati `ADMIN_EMAIL`, verificē e-pastu un atver `#/admin`. Lokāli pēc konfigurācijas maiņas palaid `php artisan config:clear`.

Admin rediģē cenas, atlikumus, aprakstus, bildes, kakao saturu, sastāvdaļas, alergēnus, garšu, tekstūru, saderības ieteikumus un uzturvērtību uz 100 g. Preci ar pasūtījumu vēsturi nevar dzēst.

Foto plūsma: pārlūks pieņem JPG/PNG/WebP līdz 20 MB, samazina līdz JPEG zem 1 MB, saglabā produkta datus un tad nosūta foto atsevišķi. Servera validācijas limits attēlam ir 5 MB. Ja upload neizdodas, produkts paliek saglabāts; foto var atkārtot bez dublikāta izveides.

Faili pašreiz ir `storage/app/public/chocolates`; publiskie URL ir `/storage/...`. Lokāli `php artisan storage:link` savieno `public/storage` ar glabātuvi. Serverī jānodrošina failu pastāvīga glabāšana un publiska piekļuve: saite pati nenodrošina failu saglabāšanos pēc pārizvietošanas. Object Storage izmantošanai jāpielāgo arī kontrolierī tieši izmantotais `public` disks.

### Piegādes adrese un pasūtījumi

Profilā (`#/profils`) saglabā saņēmēju, tālruni, ielu/mājas/dzīvokļa numuru, pilsētu, pasta indeksu un valsti. Noformējot pasūtījumu (`#/pasutijums`), adrese aizpildās no profila. Pasūtījumā saglabā atsevišķu adreses kopiju; profila adreses maiņa nepārraksta iepriekšējos pasūtījumus.

Pasūtījumam vajag verificētu e-pastu. Serveris aprēķina cenas/atlaides un rezervē atlikumu. Groza rindas apvieno kopīgs sūtījuma ID. Tiešsaistes apmaksa un automātiska kurjerpakalpojuma integrācija nav ieviesta; administratoram statuss un izsekošanas numurs jāatjaunina manuāli.

| Statuss datubāzē | Nosaukums saskarnē | Atļautais nākamais statuss |
| --- | --- | --- |
| `gaida` | Gaida apstiprinājumu | `apstiprinats`, `atcelts` |
| `apstiprinats` | Apstiprināts | `sagatavo`, `atcelts` |
| `sagatavo` | Tiek sagatavots | `nosutits`, `atcelts` |
| `nosutits` | Nosūtīts | `piegadats` |
| `piegadats` | Piegādāts | Noslēguma statuss. |
| `atcelts` | Atcelts | Noslēguma statuss. |

Klients redz savus pasūtījumus. Adresi/daudzumus var mainīt statusā `gaida`; atcelt var līdz nosūtīšanai. Admin rediģē līdz nosūtīšanai un maina statusu ar izvēlni. Atcelšana atgriež preces noliktavā vienu reizi; daudzumu palielināšana pārbauda atlikumu. Izmaiņas saglabā vēsturē.

### Atlaižu kodi

Admin izveido manuālu vai ģenerētu kodu ar 0.01–100% atlaidi, vienu/neierobežotu lietošanas reižu skaitu un derīguma termiņu. Kodu var piesaistīt precēm. Serveris nepieņem atsauktus, izlietotus vai beigušos kodus. Pasūtījuma atcelšana pašreiz neatjauno kupona izmantošanas limitu.

## API pārskats

Visiem ceļiem priekšā ir `/api`. Frontend mutācijas izmanto CSRF tokenu no `GET /auth/csrf`.

| Metode un ceļš | Atbildība / piekļuve |
| --- | --- |
| `GET /health`, `GET /products` | Publiska pieejamības pārbaude un katalogs. |
| `POST /auth/register`, `POST /auth/login` | Reģistrācija un pieslēgšanās. |
| `GET /auth/user`, `POST /auth/logout` | Pašreizējais konts un izrakstīšanās. |
| `POST /auth/profile`, `DELETE /auth/account` | Sava profila maiņa un konta dzēšana. |
| `POST /auth/email/resend` | Atkārtota verificēšanas vēstule. |
| `GET /auth/email/verify/{id}/{hash}` | Verificēšana ar parakstītu saiti. |
| `PUT /auth/address` | Sava profila adrese. |
| `GET /shipments`, `PATCH /shipments/{id}` | Savi sūtījumi, atļautās izmaiņas un atcelšana. |
| `POST /checkout` | Verificēta klienta grozs: `items`, `coupon`, `address`, `saveAddress`. |
| `GET /orders`, `POST /orders` | Agrākais pasūtījumu rindu API; jaunā saskarne izmanto `/checkout` un `/shipments`. POST arī pieprasa adresi/verificētu e-pastu. |
| `POST /products`, `PUT /products/{id}`, `DELETE /products/{id}` | Admin preču pārvaldība. Foto izmanto multipart POST uz `/products/{id}` ar `_method=PUT`. |
| `GET /coupons/validate` | Publiska kupona pārbaude. |
| `GET /admin/coupons`, `POST /admin/coupons`, `DELETE /admin/coupons/{id}` | Admin kuponi; DELETE atsauc kodu. |
| `GET /admin/shipments`, `PATCH /admin/shipments/{id}` | Admin pasūtījumu pārvaldība. |

## Izvietošana

`npm run build:cloud` palaiž no repozitorija saknes. Laravel jābūt instalētām Composer atkarībām; servera publiskā mape ir `Backend/api/public`. `routes/web.php` sagaida būvējumu `public/shop/index.html`.

Pēc jaunu migrāciju izvietošanas no `Backend/api`:

```sh
php artisan migrate --force
```

Ja vajag ielādēt sākotnējā kataloga trūkstošās preces:

```sh
php artisan db:seed --force
```

Saglabā esošo `APP_KEY` un hostinga datubāzes mainīgos. Lokālās izmaiņas nonāk vietnē pēc nosūtīšanas uz servera izmantoto Git zaru un veiksmīgas izvietošanas.

## Testi un problēmu meklēšana

`Backend/api/tests/Feature/`:

| Tests | Atbildība |
| --- | --- |
| `AccountCheckoutTest.php` | Konti, verificēšana, profils, dzēšana un pasūtījumu piekļuve. |
| `StoreApiTest.php` | Preces, foto, uzturvērtība, receptūra un atlikumi. |
| `AdminCouponTest.php` | Admin piekļuve un kuponi. |
| `ShipmentTest.php` | Adreses kopija, sūtījumu grupēšana, īpašumtiesības, statusi un atcelšana/atlikumi. |

Testu konfigurācija ir `Backend/api/phpunit.xml`; testu datubāze ir atmiņas SQLite. PHP versijai jāatbilst Composer atkarību prasībām.

| Simptoms | Ko pārbaudīt |
| --- | --- |
| `500` | Laravel/servera žurnāla izņēmumu; nederīgs `APP_KEY` var izraisīt šifrēšanas kļūdu. |
| `401` / `419` | Pieslēgšanos, sesiju un CSRF. |
| `403` admin darbībā | `ADMIN_EMAIL`, pieslēgto kontu un verificētu e-pastu. |
| `422` | Atbildes validācijas kļūdas, obligātos laukus un preču atlikumu. |
| Foto `404` | Faila esamību, publisko disku/saiti un pastāvīgu glabāšanu. |
| Upload `413` | Nginx/PHP izmēra limitus. Nginx brīdinājums par buferēšanu pagaidu failā pats par sevi nav kļūda. |
| E-pasts nepienāk | SMTP konfigurāciju, verificētu nosūtītāju, IP ierobežojumus, pakalpojuma žurnālus un mēstules. |
| Jauns lauks nedarbojas | Vai kods izvietots un migrācijas izpildītas. |
