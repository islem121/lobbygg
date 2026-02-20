# Lobby.gg

Symfony 6.4 web application for a gaming social platform with:
- Frontoffice (community, marketplace, tournaments, sponsoring)
- Backoffice admin dashboard (CRUD modules)
- MySQL database with Doctrine ORM

## Tech Stack
- PHP `>=8.1`
- Symfony `6.4`
- Doctrine ORM + Doctrine Migrations
- Twig
- MySQL / MariaDB (XAMPP compatible)
- Composer

## Project Structure
- `src/Controller/` application controllers
- `src/Controller/Admin/` admin controllers
- `src/Entity/` Doctrine entities
- `src/Form/` Symfony forms
- `src/Repository/` repositories
- `templates/front/` frontoffice templates
- `templates/admin/` admin templates
- `public/` web root (Symfony entrypoint)

## Main Features
- Authentication (login/register/logout)
- Role-based access (`ROLE_ADMIN`, `ROLE_SPONSOR`, `ROLE_CLIENT`)
- Community/blog posts + comments + reactions
- Marketplace (products, categories, orders)
- Sponsoring flow:
  - Offers (sponsors)
  - Client requests/documents
  - Contracts
- Tournaments:
  - Frontoffice list/details/join/leave
  - Admin dashboard CRUD
  - Participation management

## Requirements
- PHP 8.1+ (8.2 recommended)
- MySQL/MariaDB running locally
- Composer installed
- (Optional) Symfony CLI

## Installation
1. Clone or open the project:
   ```powershell
   cd C:\Users\Asus\Desktop\lobbygg
   ```
2. Install PHP dependencies:
   ```powershell
   composer install
   ```
3. Check environment in `.env`:
   - `APP_ENV=dev`
   - `DATABASE_URL="mysql://root:@127.0.0.1:3306/first_project?serverVersion=8.0&charset=utf8mb4"`

## Database Setup

### Option A: Restore existing dump (recommended)
If you have the dump used in this project:
`C:\Users\Asus\Desktop\first_project.sql`

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS first_project; CREATE DATABASE first_project CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
Get-Content C:\Users\Asus\Desktop\first_project.sql | C:\xampp\mysql\bin\mysql.exe -u root first_project
```

### Option B: Fresh schema via Doctrine (if you don’t have a dump)
```powershell
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Run the App

### Symfony local server
```powershell
symfony serve -d
```
Open: `http://127.0.0.1:8000`

### Or Apache/XAMPP
Point Apache virtual host/root to:
`C:\Users\Asus\Desktop\lobbygg\public`

## Useful Commands
- Clear cache:
  ```powershell
  php bin/console cache:clear --no-warmup
  ```
- List routes:
  ```powershell
  php bin/console debug:router
  ```
- Validate container:
  ```powershell
  php bin/console lint:container
  ```
- Lint Twig:
  ```powershell
  php bin/console lint:twig templates
  ```

## Admin Dashboard
- Admin entry: `/admin`
- Tournament admin module: `/admin/tournament/`

## Security / Roles
- Access control is defined in:
  `config/packages/security.yaml`
- Main provider loads users by email from `App\Entity\User`.

## Notes About Current State
- Front and admin tournament modules are both present:
  - Front routes start with `front_tournaments*`
  - Admin routes start with `app_admin_tournament*`
- Admin users navigating to `/tournaments` are redirected to admin tournaments page.

## Troubleshooting
- `vendor/autoload.php missing`:
  - Run `composer install`
- DB errors like “table/column not found”:
  - Confirm correct DB in `.env`
  - Import `first_project.sql` or run migrations
  - Clear cache
- Login “invalid credentials”:
  - Verify user exists in `user` table
  - Ensure hashed password in DB is full length (bcrypt ~60 chars)

## License
Project is marked as `proprietary` in `composer.json`.

