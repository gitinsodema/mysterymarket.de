<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

function mmEliteMemberCode(): string
{
    return 'ES-' . strtoupper(bin2hex(random_bytes(4)));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $name = trim((string)($_POST['display_name'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $regions = trim((string)($_POST['preferred_regions'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '') {
            $error = 'Name und gültige E-Mail sind erforderlich.';
        } else {
            $pdo = mmDb();
            try {
                $pdo->beginTransaction();

                $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                $insertUser = $pdo->prepare(
                    'INSERT INTO backoffice_users (email, password_hash, role, account_status, created_at, updated_at)
                     VALUES (:email, :password_hash, \'elite\', \'disabled\', NOW(), NOW())'
                );
                $insertUser->execute(['email'=>$email,'password_hash'=>$passwordHash]);
                $userId = (int)$pdo->lastInsertId();

                $code = mmEliteMemberCode();
                $insertMember = $pdo->prepare(
                    'INSERT INTO elite_members
                     (user_id, member_code, membership_status, display_name, city, preferred_regions, created_at, updated_at)
                     VALUES (:user_id, :code, \'invited\', :display_name, :city, :regions, NOW(), NOW())'
                );
                $insertMember->execute([
                    'user_id'=>$userId,
                    'code'=>$code,
                    'display_name'=>$name,
                    'city'=>$city !== '' ? $city : null,
                    'regions'=>$regions !== '' ? $regions : null,
                ]);
                $memberId = (int)$pdo->lastInsertId();
                $pdo->commit();

                mmBackofficeAudit((int)$user['id'], 'elite_member.created', 'elite_member', $memberId, ['member_code'=>$code]);
                header('Location: /backoffice/members.php?created=1', true, 303);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Mitglied konnte nicht angelegt werden. E-Mail eventuell bereits vorhanden.';
            }
        }
    }
}

mmHeader('Elite Shopper anlegen', 'Neuen Elite-Shopper-Datensatz anlegen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div><p class="eyebrow">Admin · Elite Shopper</p><h1>Mitglied anlegen.</h1><p class="lead">Zunächst als Einladung ohne aktiven Login.</p></div>
</section>
<section class="section">
  <div class="form-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/member-new.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <label>Name<input name="display_name" required maxlength="150"></label>
      <label>E-Mail<input type="email" name="email" required maxlength="254"></label>
      <div class="form-grid">
        <label>Ort<input name="city" maxlength="120"></label>
        <label>Regionen<input name="preferred_regions" maxlength="500" placeholder="z. B. NRW, Rheinland"></label>
      </div>
      <div class="actions">
        <button type="submit">Als Einladung anlegen</button>
        <a class="button secondary" href="/backoffice/members.php">Abbrechen</a>
      </div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
