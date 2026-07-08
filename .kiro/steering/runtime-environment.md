# Runtime Environment

## Container Runtime

- **Container engine**: Podman (not Docker). Use `podman` commands, not `docker`.
- **Compose**: Use `podman compose` or `podman-compose` for multi-container operations.

## Development Deployment (podman)

- **Joomla container**: `ttclub-joomla` — Joomla 6.1 with PHP 8.4 + Apache
- **Database container**: `ttclub-db` — MariaDB 11.4
- **phpMyAdmin container**: `ttclub-pma` — accessible on port 8081
- **Joomla port**: 8080
- **MariaDB port**: 3306

## Database Credentials

- DB name: `joomla`
- DB user: `joomla`
- DB password: `joomla_password`
- Root password: `root_password`
- **Table prefix**: `j6ttc_`

## Applying Schema Changes

To run SQL against the running database:
```bash
podman exec ttclub-db mariadb -u joomla -pjoomla_password joomla -e "SQL_STATEMENT_HERE"
```

All component tables use the prefix `j6ttc_ttclub_` (e.g., `j6ttc_ttclub_players`, `j6ttc_ttclub_teams`).
