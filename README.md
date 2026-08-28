# mysterymarket.de

Public website for MysteryMarket operational audit and field services.

## Positioning
MysteryMarket represents active audit and field-service work. It is not a shopper marketplace.

Public areas:
- Services
- Current Audits
- Verify
- INSODEMA operational tools
- About
- Contact
- Legal Notice
- Privacy

## Runtime
- PHP 8.5
- MariaDB
- Production: mysterymarket.de
- Root: /var/www/vhosts/mysterymarket.de
- Plesk user: mysterymarket.de

## First server setup

```bash
cd /var/www/vhosts/mysterymarket.de
cp config/local.example.php config/local.php
# edit verified legal + DB values
mysql -u <user> -p <database> < database/schema.sql
/opt/plesk/php/8.5/bin/php scripts/preflight.php
```

After deployment from a root shell, restore Plesk ownership:

```bash
chown -R mysterymarket.de:psacln /var/www/vhosts/mysterymarket.de
find /var/www/vhosts/mysterymarket.de -type d -exec chmod 755 {} \;
find /var/www/vhosts/mysterymarket.de -type f -exec chmod 644 {} \;
```

Keep `config/local.php` server-local and out of Git.
