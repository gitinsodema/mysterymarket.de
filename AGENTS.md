# MysteryMarket Development Rules

## Scope
mysterymarket.de is the public presence for Robert Breuss' operational audit and field services.

It is not a shopper marketplace and not a replacement for ShopperMatch.

## Runtime
- PHP 8.5
- MariaDB
- Production domain: mysterymarket.de
- Production root: /var/www/vhosts/mysterymarket.de
- Plesk user: mysterymarket.de

## Deployment
Production deployment is performed separately from Git publication.
After deployment, ownership and permissions must be set for the Plesk user.

## Security
- Never commit production database credentials.
- Never expose confidential client or audit-program information.
- Verification references must reveal only explicitly public information.
- Public customer/partner names require explicit approval.
