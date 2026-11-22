# Garage Rental

Web app for renting garages (bicycle, car, electric car), built with Laravel 13 + Blade and a relational database.

## Setup

```bash
composer install
cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed      # creates the tables + the admin account
php artisan storage:link        # makes uploaded payment proofs viewable
php artisan serve
```

Open http://127.0.0.1:8000

Admin login: `admin@garage.test` / `password`

### Database

The default is SQLite (the file `database/database.sqlite`, created by `migrate`).
To use MySQL instead, change these lines in `.env`, create the database, and run `php artisan migrate --seed` again:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=garage_rental
DB_USERNAME=root
DB_PASSWORD=
```

## Tests

```bash
php artisan test
```

The tests use an in-memory SQLite database, so they never touch your real data.
They need the PHP `gd` extension (used to make fake images); enable `extension=gd` in `php.ini` if a test says GD is missing.

## Structure (MVC)

| Layer       | Folder                          | Contents                                                        |
|-------------|---------------------------------|-----------------------------------------------------------------|
| Model       | `app/Models`                    | User, Garage, Rental, Extension                                 |
| View        | `resources/views`               | Blade pages                                                     |
| Controller  | `app/Http/Controllers`          | AuthController, RentalController, Admin/* controllers           |
| Service     | `app/Services`                  | RentalCalculator (all price and date rules)                     |
| Middleware  | `app/Http/Middleware`           | EnsureRole (admin / tenant pages)                               |
| Database    | `database/migrations`           | users, garages, rentals, extensions tables                      |
| Tests       | `tests/Unit`, `tests/Feature`   | White box (calculator) and black/grey box (pages + database)    |
