<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

$existing = mmDb()->prepare('SELECT id FROM elite_members WHERE user_id = :user_id LIMIT 1');
$existing->execute(['user_id'=>(int)$user['id']]);
$existingId = (int)$existing->fetchColumn();
if ($existingId > 0) {
    header('Location: /backoffice/profile.php', true, 303);
    exit;
}

function mmAdminSelfEliteCode(): string
{
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $code = 'ES-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = mmDb()->prepare('SELECT 1 FROM elite_members WHERE member_code = :code LIMIT 1');
        $stmt->execute(['code'=>$code]);
        if (!$stmt->fetchColumn()) {
            return $code;
        }
    }

    throw new RuntimeException('Keine eindeutige Elite-Mitgliedsnummer konnte erzeugt werden.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $displayName = trim((string)($_POST['display_name'] ?? ''));

        if ($displayName === '' || mb_strlen($displayName) > 150) {
            $error = 'Name ist erforderlich.';
        } else {
            try {
                $code = mmAdminSelfEliteCode();

                $stmt = mmDb()->prepare(
                    "INSERT INTO elite_members
                     (user_id, member_code, membership_status, display_name, joined_at, created_at, updated_at)
                     VALUES (:user_id, :member_code, 'active', :display_name, NOW(), NOW(), NOW())"
                );
                $stmt->execute([
                    'user_id'=>(int)$user['id'],
                    'member_code'=>$code,
                    'display_name'=>$displayName,
                ]);
                $memberId = (int)mmDb()->lastInsertId();

                mmBackofficeAudit(
                    (int)$user['id'],
                    'elite_member.admin_self_created',
                    'elite_member',
                    $memberId,
                    ['member_code'=>$code]
                );

                header('Location: /backoffice/profile.php?created=1', true, 303);
                exit;
            } catch (Throwable $e) {
                $error = 'Eigenes Elite-Profil konnte nicht angelegt werden.';
            }
        }
    }
}

mmHeader('Mein Elite-Profil anlegen', 'Elite-Mitgliedschaft mit dem vorhandenen Admin-Account verknüpfen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Persönlich</p>
    <h1>Mein Elite-Profil anlegen.</h1>
    <p class="lead">Dein bestehender Admin-Login bleibt unverändert. Zusätzlich wird eine aktive Elite-Mitgliedschaft an denselben Account gebunden.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/">Zurück zum Dashboard</a></div>
  </div>
</section>

<section class="section">
  <div class="form-card compact-admin-form">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/profile-bootstrap.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <label>Name
        <input name="display_name" maxlength="150" required>
        <small class="field-hint">Dieser Name ist später die verbindliche Personenidentität für deine Verify-Ausweise.</small>
      </label>
      <button type="submit">Eigenes Elite-Profil anlegen</button>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
