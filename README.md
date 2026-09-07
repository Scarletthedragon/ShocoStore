# ShocoStore

"ShocoStore" ir interneta veikals kuru projekta laikā līdz novembrim īstenos RVT kursa DP4-4 studenti- Roberts Dāvidsons, Sanija Kraukle, Sanita Savicka un Šarlote Tērmane. Interneta veikalā būs iespējams apskatīt un iegādāties visādus šokolādes produktus.

## Funkcionalitāte

* Produktu apskate
* Produktu meklēšana
* Produktu filtrēšana
* Lietotāju reģistrācija un ielogošanās
* Iepirkumu grozs
* Pasūtījumu veikšana
* Pasūtījumu vēsture
* Vēlmju saraksts
* Produktu atsauksmes

## Tehnoloģijas

### Frontend

* Vue 3
* Vuetify 3
* Vite
* Vue Router
* Axios

### Backend

* Laravel 13
* MySQL

## Projekta palaišana

### Frontend

```bash
cd frontend
npm install
npm run dev
```

### Backend

```bash
cd backend
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

## Datubāze

Projektā tiek izmantota MySQL datubāze.

`.env` failā jānorāda datubāzes savienojuma informācija.

## Projekta mērķis

Izveidot vienkāršu un pārskatāmu online šokolādes veikalu ar ērtu produktu meklēšanu, filtrēšanu un pasūtījumu veikšanu.
