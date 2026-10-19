# 7. PROGRAMMATŪRAS LIETOJAMĪBAS TESTĒŠANA

Testēšanas mērķis ir pārliecināties, ka ShocoStore galvenās funkcijas darbojas korekti un ir ērti lietojamas gan datorā, gan mobilajā ierīcē. Pārbaudes aptver lietotāja kontu izveidi un pieteikšanos, katalogu, vēlmju sarakstu, grozu, pasūtījumu noformēšanu, atlaižu kodus un administratora funkcijas. Atsevišķi jāpārbauda tumšais režīms, tostarp logotipa un kājenes attēlojums.

Backend funkcionālajiem testiem izmanto Laravel/PHPUnit. Saskarnes izkārtojums, mobilā versija un vizuālās izmaiņas jāpārbauda pārlūkā. Tabulās norādīts paredzamais rezultāts un pārbaudes veids; manuālās pārbaudes rezultātu reģistrē pēc to izpildes, nevis pieņem par veiksmīgu iepriekš.

## 7.1. Testēšanas vides sagatavošana

1. Sagatavo projektu saskaņā ar [README.md](README.md), izmantojot `npm run backend:setup`.
2. Palaid backend ar `npm run backend` un frontend ar `npm run dev`.
3. Administratora pārbaudēm izveido lietotāja kontu, norādi tā e-pastu `ADMIN_EMAIL` iestatījumā failā `Backend/api/.env` un izpildi `php artisan config:clear` mapē `Backend/api`.
4. Datu bāzes sākotnējo katalogu var atjaunot ar `php artisan migrate:fresh --seed` mapē `Backend/api`. Šī komanda dzēš esošos lokālos datus.
5. Pārbaudes ar administratoru, kuponiem un pasūtījumiem veic ar testa datiem, nevis produkcijas kontiem.

Tiešsaistes apmaksa un piegāde lietotnē pašlaik nav pieejama, tāpēc pasūtījuma pārbaudē sagaidāms, ka tas tiek saglabāts ar statusu “gaida” un apmaksa netiek veikta.

## 7.2. Reģistrācijas un pieteikšanās testēšanas dati

| Nr. | Testa gadījums | Ievaddati / darbība | Sagaidāmais rezultāts | Pārbaudes veids |
|---|---|---|---|---|
| 1. | Veiksmīga reģistrācija | Ievada vārdu, unikālu e-pastu, vismaz 8 rakstzīmju paroli un tās apstiprinājumu | Konts tiek izveidots, lietotājs ir pieslēdzies un tiek atvērts veikals | PHPUnit un pārlūks |
| 2. | Nederīga vai jau izmantota e-pasta adrese | Ievada nederīgu e-pastu vai reģistrēta konta e-pastu | Reģistrācija nenotiek un tiek parādīts kļūdas paziņojums | PHPUnit un pārlūks |
| 3. | Parole neatbilst prasībām | Ievada paroli, kas ir īsāka par 8 rakstzīmēm | Forma neļauj to iesniegt | Pārlūks |
| 4. | Paroles nesakrīt | Paroles apstiprinājumā ievada citu vērtību | Forma norāda, ka paroles nesakrīt, un konts netiek izveidots | Pārlūks |
| 5. | Veiksmīga un neveiksmīga pieteikšanās | Ievada pareizu paroli, pēc tam mēģina pieteikties ar nepareizu paroli | Ar pareizajiem datiem lietotājs pieslēdzas; ar nepareizajiem pieteikšanās tiek noraidīta | PHPUnit un pārlūks |
| 6. | Izrakstīšanās | Pieslēdzas un izvēlas izrakstīties | Sesija tiek pārtraukta, un aizsargātām darbībām atkārtoti jāpieprasa pieteikšanās | PHPUnit un pārlūks |

## 7.3. Kataloga, vēlmju saraksta un groza testēšanas dati

| Nr. | Testa gadījums | Ievaddati / darbība | Sagaidāmais rezultāts | Pārbaudes veids |
|---|---|---|---|---|
| 1. | Kataloga atvēršana un produktu detaļas | Atver veikalu un produkta detaļas | Redzams katalogs ar produkta nosaukumu, cenu, aprakstu, attēlu un pieejamību | PHPUnit un pārlūks |
| 2. | Meklēšana un filtrēšana | Meklē pēc nosaukuma vai apraksta un izvēlas piena, tumšo vai balto šokolādi | Tiek rādīti tikai meklēšanas kritērijiem un izvēlētajai kategorijai atbilstošie produkti; tukša rezultāta gadījumā ir pieejama visu produktu atjaunošana | Pārlūks |
| 3. | Produktu kārtošana | Izvēlas cenu augošā un dilstošā secībā | Produkti tiek sakārtoti atbilstoši izvēlētajam virzienam | Pārlūks |
| 4. | Vēlmju saraksts | Pievieno un noņem produktu no vēlmju saraksta, pēc tam pārlādē lapu | Saraksta skaits atjaunojas, izvēle tiek saglabāta šajā pārlūkā un sarakstā redzamas katalogā esošās preces | Pārlūks |
| 5. | Produkta pievienošana grozam | Pievieno pieejamu produktu un atver grozu | Produkts un tā cena ir redzami grozā, groza skaits ir atjaunināts | Pārlūks |
| 6. | Daudzuma maiņa un izņemšana | Palielina un samazina daudzumu, pēc tam izņem produktu | Daudzums nepārsniedz noliktavas atlikumu vai 99 gabalus; summa pārrēķinās un izņemtais produkts vairs nav grozā | Pārlūks |
| 7. | Groza saglabāšana | Pievieno preci, pārlādē lapu | Pieejamais groza saturs tiek atjaunots no pārlūka krātuves; neatbilstoši vai vairs nepieejami produkti netiek atjaunoti | Pārlūks |

## 7.4. Pasūtījuma un atlaižu koda testēšanas dati

| Nr. | Testa gadījums | Ievaddati / darbība | Sagaidāmais rezultāts | Pārbaudes veids |
|---|---|---|---|---|
| 1. | Pasūtījums bez pieslēgšanās | Grozā ir preces; mēģina apstiprināt pasūtījumu | Tiek pieprasīts pieslēgties, pasūtījums netiek izveidots | PHPUnit un pārlūks |
| 2. | Veiksmīgs pasūtījums | Pieslēdzas, grozā ievieto pieejamas preces un apstiprina pasūtījumu | Pasūtījums tiek saglabāts, krājums samazinās, grozs tiek iztukšots un tiek parādīts gaidošs statuss; apmaksa netiek veikta | PHPUnit un pārlūks |
| 3. | Nepietiekams krājums | Pasūta vairāk preču, nekā ir atlikumā | Pasūtījums tiek noraidīts, un krājums/pārējās pasūtījuma pozīcijas netiek daļēji saglabātas | PHPUnit |
| 4. | Atlaižu koda piemērošana | Ievada aktīvu kodu, kas attiecas uz grozā esošu preci | Kods tiek pieņemts, atlaide un gala summa tiek aprēķināta korekti | PHPUnit un pārlūks |
| 5. | Nederīgs, beidzies vai atsaukts kods | Ievada neesošu kodu vai kodu, kura termiņš beidzies vai kas atsaukts | Kods netiek piemērots un tiek parādīts kļūdas paziņojums | PHPUnit un pārlūks |
| 6. | Ierobežots kupons | Izlieto vienreiz lietojamu kodu un mēģina to piemērot atkārtoti | Pirmā piemērošana izdodas, bet nākamā tiek noraidīta | PHPUnit |
| 7. | Produktiem piesaistīts kupons | Grozā ir prece, kurai kods ir un nav paredzēts | Kods der tikai izvēlētajām precēm; tukša produktu izvēle nozīmē, ka tas attiecas uz visām precēm | PHPUnit un pārlūks |

## 7.5. Administratora funkciju testēšanas dati

Administratora piekļuve tiek piešķirta kontam, kura e-pasts atbilst servera `ADMIN_EMAIL` iestatījumam. Pārbaudes jāveic gan ar administratoru, gan ar parastu lietotāju.

| Nr. | Testa gadījums | Ievaddati / darbība | Sagaidāmais rezultāts | Pārbaudes veids |
|---|---|---|---|---|
| 1. | Piekļuves kontrole | Atver administrēšanas lapu un API bez pieslēgšanās, ar parastu kontu un ar administratora kontu | Nepieslēgtam lietotājam nepieciešama autentifikācija, parastam lietotājam piekļuve liegta, administratoram piekļuve atļauta | PHPUnit un pārlūks |
| 2. | Atlaižu koda izveide | Izveido manuālu vai ģenerētu kodu ar 0,01–100% atlaidi, derīguma termiņu un vienu vai neierobežotu lietojumu | Kods tiek saglabāts, parādās kodu sarakstā, un tas ir izmantojams atbilstoši norādītajiem nosacījumiem | PHPUnit un pārlūks |
| 3. | Atlaižu koda atsaukšana | Atsauc aktīvu kodu | Kods tiek atzīmēts kā atsaukts un vairs nav izmantojams | PHPUnit un pārlūks |
| 4. | Produkta pievienošana un rediģēšana | Ievada produkta nosaukumu, cenu, atlikumu un citus laukus; maina jau esošu produktu | Produkts un izmaiņas tiek saglabātas un atspoguļotas katalogā | PHPUnit un pārlūks |
| 5. | Produkta attēla augšupielāde un nomaiņa | Pievieno vai nomaina JPG, PNG vai WebP attēlu līdz 5 MB | Priekšskatījums un saglabātais produkta attēls tiek atjaunināts | PHPUnit un pārlūks |
| 6. | Produkta dzēšana | Dzēš produktu bez pasūtījumiem, pēc tam mēģina dzēst produktu ar pasūtījumu vēsturi | Preci bez pasūtījumiem var dzēst; preci ar piesaistītiem pasūtījumiem nevar dzēst, un vēsture tiek saglabāta | PHPUnit un pārlūks |

## 7.6. Mobilās versijas un tumšā režīma pārbaudes

Pārbaudes jāveic vismaz šaurā mobilās ierīces skatā un darbvirsmas skatā. Pārlūkā jāizmēģina gan gaišais, gan tumšais režīms.

| Nr. | Testa gadījums | Ievaddati / darbība | Sagaidāmais rezultāts | Pārbaudes veids |
|---|---|---|---|---|
| 1. | Mobilā sākumlapa un navigācija | Atver sākumlapu mobilajā skatā un izmanto navigāciju, galvenes darbības un kājenes saites | Saturs un vadīklas ir sasniedzamas, teksts un pogas nepārklājas un lapa nerada nevajadzīgu horizontālu ritināšanu | Manuāla vizuāla pārbaude |
| 2. | Mobilais veikals, grozs un pasūtījums | Meklē un filtrē produktus, pievieno preci, maina tās daudzumu un atver pasūtījuma lapu | Produktu kartītes, groza dialogs un pasūtījuma kopsavilkums ir salasāmi un lietojami šaurā ekrānā | Manuāla vizuāla pārbaude |
| 3. | Mobilā administratora lapa | Administratora kontā atver produktu un kuponu formas un sarakstus | Formas un saraksti pielāgojas ekrāna platumam, laukus un darbības var izmantot bez satura pārklāšanās | Manuāla vizuāla pārbaude |
| 4. | Tumšā režīma logotips | Pārslēdz tumšo režīmu un apskata galvenes logotipu | Logotips ir skaidri saskatāms pret tumšo galvenes fonu | Manuāla vizuāla pārbaude |
| 5. | Tumšā režīma kājene | Tumšajā režīmā apskata lapas apakšu | Kājenes fons, teksts, saites un logotips ir salasāmi un vizuāli atšķirami | Manuāla vizuāla pārbaude |
| 6. | Režīma saglabāšana un atjaunošana | Pārslēdz režīmu, pārlādē lapu un atver lietotni jaunā sesijā | Izvēlētais režīms tiek saglabāts un atjaunots; ja izvēle nav saglabāta, tiek ņemta vērā ierīces krāsu shēma | Pārlūks |

## 7.7. Automatizēto testu izpilde un regresijas pārbaude

Backend automatizētos testus palaiž mapē `Backend/api`:

```sh
composer test
```

Testu komplekts ietver kontu izveidi un autentifikāciju, pasūtījumu un krājumu pārbaudes, produktu API, administratora piekļuvi un atlaižu kodu darbību. Frontend mobilā izkārtojuma un tumšā režīma pārbaudes šobrīd veic manuāli pārlūkā.

Pēc kļūdu labojumiem vai apvienotām izmaiņām atkārtoti izpilda automatizētos testus un pārbauda attiecīgo lietotāja plūsmu. Īpaši jāpārbauda, ka labojumi tumšajam logotipam un kājenei darbojas abās krāsu shēmās, ka mobilajā skatā funkcijas joprojām ir sasniedzamas un ka atlaižu koda, attēla un krājumu izmaiņas neietekmē pasūtījuma saglabāšanu.
