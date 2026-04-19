# Database Setup

This project uses MySQL and can be viewed in `phpMyAdmin`.

## Option 1: Docker Compose

From the project root:

```powershell
docker compose up -d
```

Services:

- MySQL: `localhost:3306`
- phpMyAdmin: `http://localhost:8080`

Default credentials created by `docker-compose.yml`:

- MySQL root user: `root`
- MySQL root password: `root`
- App user: `app_user`
- App password: `app_password`
- Database: `lobbyjava`

`phpMyAdmin` login:

- Server: `mysql` if you log in from inside the container defaults, or `localhost` in some local setups
- Username: `root`
- Password: `root`

The schema is auto-created from [db/init/01-schema.sql](/C:/Users/Asus/Desktop/wetransfer_java_2026-04-16_1929/java/db/init/01-schema.sql).

## Java app connection

The Java app now reads DB settings from environment variables or Java system properties.
It also auto-loads a local `.env` file from the project root and creates the required tables on first successful connection.

Environment variables:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `JDBC_URL` (optional full override)

Defaults if nothing is set:

- host: `localhost`
- port: `3306`
- database: `lobbyjava`
- user: `root`
- password: empty string

For the Docker setup, launch the app with:

```powershell
mvn javafx:run
```

Because `.env` is now supported, you can usually just keep the generated `.env` file as-is and run the app.

## Tables created

- `user`
- `gold`
- `gold_operation`
- `reservation`
- `article`
