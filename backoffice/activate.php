<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$token = strtolower(trim((string)($_GET['token'] ?? $_POST['token'] ?? '')));
$record = mmBackofficeActivationRecord($token);
$error = '';
$done = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $repeat = (string)($_POST['password_repeat'] ?? '');

    if (!$record) {
        $error = 'Dieser Aktivierungslink ist ungültig oder abgelaufen.';
    } elseif (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Die Sitzung ist abgelaufen. Bitte den Aktivierungslink erneut öffnen.';
    } elseif (strlen($password) < 12) {
        $error = 'Das Passwort muss mindestens 12 Zeichen lang sein.';
    } elseif ($password !== $repeat) {
        $error = 'Die Passwörter stimmen nicht überein.';
    } elseif (!mmBackofficeActivateElite($token, $password)) {
        $error = 'Die Aktivierung konnte nicht abgeschlossen werden.';
    } else {
        $done = true;
        $record = null;
    }
}

mmHeader('Elite Shopper aktivieren', 'Elite-Shopper-Zugang aktivieren.', 'noindex,nofollow');
?>
<section class="hero backoffice-login-hero">
  <div>
    <p class="eyebrow">Elite Shopper</p>
    <h1><?= $done ? 'Zugang aktiviert.' : 'Zugang aktivieren.' ?></h1>
    <p class="lead">
      <?= $done
          ? 'Dein Elite-Shopper-Zugang ist jetzt aktiv.'
          : ($record ? 'Willkommen ' . mmEscape((string)$record['display_name']) . '. Lege jetzt dein persönliches Passwort fest.' : 'Der Aktivierungslink kann nicht verwendet werden.') ?>
    </p>
  </div>
  <div class="backoffice-login-mark" aria-hidden="true"><span>MM</span><strong>Elite</strong></div>
</section>

<section class="section">
  <div class="form-card backoffice-login-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

    <?php if ($done): ?>
      <div class="alert success"><strong>Aktivierung erfolgreich.</strong><p>Du kannst dich jetzt mit deiner E-Mail-Adresse und dem neuen Passwort anmelden.</p></div>
      <a class="button" href="/backoffice/login.php">Zum Login</a>
    <?php elseif ($record): ?>
      <p><strong><?= mmEscape((string)$record['member_code']) ?></strong><br><?= mmEscape((string)$record['email']) ?></p>
      <form method="post" action="/backoffice/activate.php">
        <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
        <input type="hidden" name="token" value="<?= mmEscape($token) ?>">
        <label>Neues Passwort
          <input type="password" name="password" autocomplete="new-password" minlength="12" required>
        </label>
        <label>Passwort wiederholen
          <input type="password" name="password_repeat" autocomplete="new-password" minlength="12" required>
        </label>
        <button type="submit">Zugang aktivieren</button>
      </form>
    <?php else: ?>
      <div class="alert"><strong>Link ungültig oder abgelaufen.</strong><p>Bitte fordere beim MysteryMarket-Administrator einen neuen Aktivierungslink an.</p></div>
    <?php endif; ?>
  </div>
</section>
<?php mmFooter(); ?>
