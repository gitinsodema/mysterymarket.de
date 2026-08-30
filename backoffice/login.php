<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

if (mmBackofficeUser()) {
    header('Location: /backoffice/', true, 302);
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Die Sitzung ist abgelaufen. Bitte erneut versuchen.';
    } else {
        try {
            if (mmBackofficeAttemptLogin((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
                header('Location: /backoffice/', true, 302);
                exit;
            }
            $error = http_response_code() === 429
                ? 'Zu viele Anmeldeversuche. Bitte später erneut versuchen.'
                : 'Anmeldung nicht möglich.';
        } catch (Throwable $e) {
            $error = 'Anmeldung derzeit nicht verfügbar.';
        }
    }
}

mmHeader('Backoffice Login', 'Geschützter MysteryMarket Partner- und Adminzugang.', 'noindex,nofollow');
?>
<section class="hero backoffice-login-hero">
  <div>
    <p class="eyebrow">Private Area</p>
    <h1>MysteryMarket Backoffice</h1>
    <p class="lead">Geschützter Zugang für Administration und Elite Shopper.</p>
  </div>
  <div class="backoffice-login-mark" aria-hidden="true">
    <span>MM</span><strong>Private</strong>
  </div>
</section>

<section class="section">
  <div class="form-card backoffice-login-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/login.php" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <label>E-Mail
        <input type="email" name="email" autocomplete="username" required maxlength="254">
      </label>
      <label>Passwort
        <input type="password" name="password" autocomplete="current-password" required>
      </label>
      <button type="submit">Anmelden</button>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
