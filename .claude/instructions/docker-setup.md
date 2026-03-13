# Docker Local Development Setup

## Prerequisites

### Install Docker Desktop
- **Windows**: Download from https://www.docker.com/products/docker-desktop/ — requires WSL2 enabled
- **macOS**: Download from the same URL — works on both Intel and Apple Silicon

After installation, verify:
```bash
docker --version
docker compose version
```

## Architecture

The Docker setup provides three services:

| Service | Purpose | Local URL |
|---|---|---|
| **web** | Apache + PHP 8.2 (ProcessWire) | http://localhost:8080 |
| **db** | MariaDB 10.6 (database) | localhost:3306 |
| **adminer** | Database management UI | http://localhost:8081 |

## Configuration

### docker/.env
Copy `docker/.env.example` to `docker/.env` and adjust:

```env
# Project
PROJECT_NAME=my-client-site
COMPOSE_PROJECT_NAME=my-client-site

# Web server
WEB_PORT=8080
ADMINER_PORT=8081

# Database
DB_NAME=pw_dev
DB_USER=pw_user
DB_PASS=pw_password
DB_ROOT_PASS=root_password
DB_PORT=3306

# ProcessWire admin (for initial setup reference)
PW_ADMIN_USER=admin
PW_ADMIN_PASS=change-this-password
```

## Getting Started

### First Time Setup

```bash
# 1. Clone the repo
git clone https://github.com/yourusername/project-name.git my-client-site
cd my-client-site

# 2. Install PW core via Composer
composer install

# 3. Install frontend dependencies
npm install

# 4. Configure Docker environment
cp docker/.env.example docker/.env
# Edit docker/.env with your project name and credentials

# 5. Start Docker
cd docker
docker compose up -d --build

# 6. Wait for services to start (10-15 seconds)
docker compose logs -f  # Watch logs, Ctrl+C to exit

# 7. Visit http://localhost:8080 to run the PW installer
```

### ProcessWire Installation (First Run)

When you visit http://localhost:8080 for the first time, the PW installer will run:

1. **Database settings:**
   - Host: `db` (the Docker service name, not localhost)
   - Name: value from `DB_NAME` in `.env`
   - User: value from `DB_USER` in `.env`
   - Password: value from `DB_PASS` in `.env`
   - Port: `3306`

2. **Admin account:**
   - Choose a username and password
   - Set admin email

3. **Time zone:** Europe/London

4. **Profile:** Blank (we use our own templates)

5. After installation, import fields and templates from the exports:
   - Navigate to Setup > Fields > Import
   - Paste contents of `site/install/fields.json`
   - Navigate to Setup > Templates > Import
   - Paste contents of `site/install/templates.json`
   - Or run: `php scripts/install-fields.php`

### Daily Development

```bash
# Start environment
cd docker && docker compose up -d

# Watch frontend changes (in project root, separate terminal)
npm run dev

# View logs
cd docker && docker compose logs -f

# Stop environment
cd docker && docker compose down
```

## File Watching

The Docker setup mounts the project directory into the container, so file changes are reflected immediately — no restart needed for PHP changes.

For Tailwind CSS, run the watcher in a separate terminal:
```bash
npm run dev
# This runs: tailwindcss -i ./site/assets/src/app.css -o ./site/assets/dist/app.css --watch
```

## Database Management

### Adminer (Web UI)
Visit http://localhost:8081
- System: MySQL
- Server: `db`
- Username: value from `DB_USER`
- Password: value from `DB_PASS`
- Database: value from `DB_NAME`

### Command Line
```bash
# Connect to MySQL CLI
docker compose exec db mysql -u pw_user -p pw_dev

# Export database
docker compose exec db mysqldump -u pw_user -p pw_dev > ../backup.sql

# Import database
docker compose exec -T db mysql -u pw_user -p pw_dev < ../backup.sql
```

## Troubleshooting

### Port already in use
Change `WEB_PORT` or `DB_PORT` in `docker/.env`, then restart:
```bash
docker compose down && docker compose up -d
```

### Permission issues
```bash
# Fix file ownership (run from project root)
docker compose exec web chown -R www-data:www-data /var/www/html/site/assets/
```

### Container won't start
```bash
# Check logs
docker compose logs web
docker compose logs db

# Rebuild from scratch
docker compose down -v  # Removes volumes (database data!)
docker compose up -d --build
```

### Composer/PHP issues inside container
```bash
# Run commands inside the PHP container
docker compose exec web bash
# Now you're inside the container
composer install
php -v
```

### Slow file system on macOS
Docker on macOS can be slow with mounted volumes. If performance is poor:
1. Open Docker Desktop > Settings > Resources > File Sharing
2. Ensure your project directory is in the list
3. Consider using Docker's `cached` mount option (already configured in docker-compose.yml)

### Windows-specific: line endings
Ensure Git is configured to use LF line endings:
```bash
git config core.autocrlf input
```

## Multi-Site Project Overlay

When using the `sites/<project>/` directory pattern, a `docker/docker-compose.override.yml` overlays project-specific files onto PW's `site/` directory inside the container:

```yaml
# docker/docker-compose.override.yml
services:
  web:
    volumes:
      - ../sites/<project>/templates:/var/www/html/site/templates:cached
      - ../sites/<project>/config.php:/var/www/html/site/config.php:cached
      - ../sites/<project>/install:/var/www/html/site/install:cached
```

**Important notes:**
- `site/assets/` is NOT overlaid — PW manages `files/`, `logs/`, `cache/` there
- Vite output must go to `../../site/assets/dist/` (relative to the project dir)
- PW install files (`install.sql`, `info.php`, `files/`) must be copied into the project's install dir
- When switching projects: update the override file, update `docker/.env`, then restart containers
- Must stop old project's containers by name first: `docker compose -p <old-project> down`

### Switching Between Projects

```bash
# 1. Stop current project
cd docker && docker compose down

# 2. Update docker/.env with new project credentials
cp ../sites/<new-project>/docker.env .env

# 3. Update override to point at new project
# Edit docker-compose.override.yml paths

# 4. Start new project
docker compose up -d --build
```

## Resetting Everything

```bash
# Nuclear option — removes all containers, volumes, and images
cd docker
docker compose down -v --rmi all

# Then rebuild
docker compose up -d --build
```
