# /docker-up — Start Local Docker Development Environment

## Purpose
Start (or restart) the local Docker development environment for ProcessWire.

## Workflow

### Step 1: Check Prerequisites
Verify the following exist:
- `docker/docker-compose.yml`
- `docker/Dockerfile`
- `docker/.env` (if missing, copy from `docker/.env.example` and prompt for values)

### Step 2: Check Docker Status
```bash
docker --version
docker compose version
```
If Docker isn't running, instruct the operator to start Docker Desktop.

### Step 3: Start Containers
```bash
cd docker
docker compose up -d --build
```

### Step 4: Verify Services
Check that all services are running:
- **PHP/Apache**: http://localhost:8080
- **MariaDB**: port 3306
- **Adminer** (DB admin): http://localhost:8081

### Step 4b: Multi-Site Overlay Check
If the current branch uses the `sites/<project>/` pattern (check if `sites/` directory exists):
1. Verify `docker/docker-compose.override.yml` exists and points at the correct project
2. Verify the project's install dir has PW installer files (`install.sql`, `info.php`, `files/`)
3. If switching projects, stop old containers first: `docker compose -p <old-project> down`

### Step 5: First Run Setup
If this is the first run (no `wire/` directory exists or fresh database):

1. Run `composer install` inside the container: `docker compose exec web composer install`
2. Copy PW installer to root: `docker compose exec web cp /var/www/html/vendor/processwire/processwire/install.php /var/www/html/install.php`
3. **Important**: If `config.php` already has `$config->dbName` set, temporarily comment it out — PW's index.php skips the installer if dbName is set
4. Navigate to http://localhost:8080/install.php (NOT just `/` — the .htaccess rewrites to index.php)
5. Guide through the web installer:
   - Database host: `db` (Docker service name, not localhost)
   - Database name/user/pass: values from `docker/.env`
   - Time zone: Europe/London
   - Select "Blank" site profile
6. After installation:
   - Delete `install.php` from root (PW blocks admin while it exists)
   - Restore `$config->dbName` if it was commented out
   - Clean up duplicate config entries appended by installer (keep tableSalt, installed, sessionName)
   - Run the field/template import script

### Step 6: Output Status
Display:
- Container status (running/stopped)
- Access URLs
- Database credentials (from .env)
- Log tail command: `docker compose logs -f`

## Common Issues

### Port conflicts
If port 8080 or 3306 is in use, update `docker/.env` with alternative ports and restart.

### File permission issues (macOS)
ProcessWire needs write access to `site/assets/`. The Docker config handles this, but if issues occur:
```bash
chmod -R 777 site/assets/
```

### Database connection refused
Wait 10–15 seconds after `docker compose up` for MariaDB to initialise. Check logs:
```bash
docker compose logs db
```

## Stopping the Environment
```bash
cd docker
docker compose down        # Stop containers
docker compose down -v     # Stop and remove database volume (fresh start)
```
