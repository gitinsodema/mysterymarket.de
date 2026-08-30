<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';
require_once dirname(__DIR__) . '/includes/atlas.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$failures = 0;
$warnings = 0;

function pass(string $message): void { echo "[PASS] {$message}\n"; }
function fail(string $message): void { global $failures; $failures++; fwrite(STDERR, "[FAIL] {$message}\n"); }
function warn(string $message): void { global $warnings; $warnings++; fwrite(STDERR, "[WARN] {$message}\n"); }

$pdo = mmDb();

$orphanMembers = (int)$pdo->query(
    "SELECT COUNT(*)
     FROM elite_members m
     LEFT JOIN backoffice_users u ON u.id = m.user_id
     WHERE u.id IS NULL"
)->fetchColumn();
$orphanMembers === 0 ? pass('all Elite members reference an existing backoffice user') : fail("{$orphanMembers} Elite member(s) reference no user");

$wrongRole = (int)$pdo->query(
    "SELECT COUNT(*)
     FROM elite_members m
     JOIN backoffice_users u ON u.id = m.user_id
     WHERE u.role <> 'elite'"
)->fetchColumn();
$wrongRole === 0 ? pass('all Elite member accounts use role=elite') : fail("{$wrongRole} Elite member account(s) have the wrong role");

$activeMismatch = (int)$pdo->query(
    "SELECT COUNT(*)
     FROM elite_members m
     JOIN backoffice_users u ON u.id = m.user_id
     WHERE (m.membership_status = 'active' AND u.account_status <> 'active')
        OR (m.membership_status <> 'active' AND u.account_status <> 'disabled')"
)->fetchColumn();
$activeMismatch === 0 ? pass('membership and login account states are consistent') : fail("{$activeMismatch} membership/account state mismatch(es)");

$duplicateOpenRequests = (int)$pdo->query(
    "SELECT COUNT(*) FROM (
        SELECT member_id
        FROM elite_membership_requests
        WHERE request_status = 'open'
        GROUP BY member_id
        HAVING COUNT(*) > 1
    ) x"
)->fetchColumn();
$duplicateOpenRequests === 0 ? pass('no member has multiple open membership requests') : warn("{$duplicateOpenRequests} member(s) have multiple open membership requests");

$invalidAgencyLinks = (int)$pdo->query(
    "SELECT
       (SELECT COUNT(*) FROM elite_feed_posts f LEFT JOIN agencies a ON a.id=f.agency_id WHERE f.agency_id IS NOT NULL AND a.id IS NULL)
       +
       (SELECT COUNT(*) FROM agency_approvals p LEFT JOIN agencies a ON a.id=p.agency_id WHERE p.agency_id IS NOT NULL AND a.id IS NULL)"
)->fetchColumn();
$invalidAgencyLinks === 0 ? pass('agency references in Feed and Approvals are valid') : fail("{$invalidAgencyLinks} invalid agency reference(s)");

$atlasFieldsWithoutCountry = (int)$pdo->query(
    "SELECT COUNT(*)
     FROM elite_members
     WHERE country_code IS NULL
       AND (
         administrative_unit_atlas_id IS NOT NULL
         OR postal_area_atlas_id IS NOT NULL
         OR locality_atlas_id IS NOT NULL
         OR street_atlas_id IS NOT NULL
       )"
)->fetchColumn();
$atlasFieldsWithoutCountry === 0 ? pass('ATLAS profile references always have a country snapshot') : warn("{$atlasFieldsWithoutCountry} profile(s) have ATLAS references without country_code");

if (mmAtlasIsConfigured()) {
    pass('ATLAS server-to-server configuration is present');
} else {
    warn('ATLAS is not configured');
}

$activeAgencies = (int)$pdo->query("SELECT COUNT(*) FROM agencies WHERE is_active=1")->fetchColumn();
echo "[INFO] active_agencies={$activeAgencies}\n";

$activeElite = (int)$pdo->query("SELECT COUNT(*) FROM elite_members WHERE membership_status='active'")->fetchColumn();
echo "[INFO] active_elite={$activeElite}\n";

echo "[INFO] warnings={$warnings}\n";

if ($failures === 0) {
    echo "MYSTERYMARKET_BACKOFFICE_REVIEW_OK\n";
    exit(0);
}

fwrite(STDERR, "MYSTERYMARKET_BACKOFFICE_REVIEW_FAILED failures={$failures}\n");
exit(1);
