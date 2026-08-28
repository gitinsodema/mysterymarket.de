# mysterymarket.de

Public website for MysteryMarket operational audit and field services.

## Positioning
MysteryMarket represents active audit and field-service work for agencies and direct clients. The public positioning is operational execution and fieldwork, not full-service agency evaluation/reporting.

Public areas:
- Services
- Current Audits
- Verify
- OPS
- Elite Shopper Partners
- About
- Contact
- Legal Notice
- Privacy

## Runtime
- PHP 8.5
- MariaDB
- Production: mysterymarket.de
- Document root: /var/www/vhosts/mysterymarket.de/httpdocs
- Plesk user: mysterymarket.de

## Production safety

Only operate inside the MysteryMarket document root:

```text
/var/www/vhosts/mysterymarket.de/httpdocs
```

Do not recursively lint, chown, chmod or otherwise modify:

```text
/var/www/vhosts/mysterymarket.de
```

The vhost contains separate subdomains/applications that are outside the MysteryMarket website scope.

## First server setup

```bash
cd /var/www/vhosts/mysterymarket.de/httpdocs
cp config/local.example.php config/local.php
# edit verified legal + DB/mail values
mariadb -u <user> -p <database> < database/schema.sql
/opt/plesk/php/8.5/bin/php scripts/preflight.php
```

For PHP linting, prefer tracked project files only:

```bash
cd /var/www/vhosts/mysterymarket.de/httpdocs
PHP=/opt/plesk/php/8.5/bin/php
git ls-files '*.php' -z | xargs -0 -n1 "$PHP" -l
```

Keep `config/local.php` server-local and out of Git.
