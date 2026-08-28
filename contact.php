<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/db.php';

$types = [
    'client' => 'Kunde / Auftraggeber',
    'agency' => 'Agentur / Audit Partner',
    'shopper' => 'Shopper / Auditor',
    'other' => 'Andere Anfrage',
];

$errors = [];
$success = false;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['mm_csrf'] ??= bin2hex(random_bytes(24));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim((string)($_POST['type'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));
    $organisation = trim((string)($_POST['organisation'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $privacy = ($_POST['privacy'] ?? '') === '1';
    $csrf = (string)($_POST['csrf'] ?? '');
    $honeypot = trim((string)($_POST['company_url'] ?? ''));

    if (!hash_equals((string)$_SESSION['mm_csrf'], $csrf)) $errors[] = 'Die Formularsitzung ist abgelaufen.';
    if ($honeypot !== '') $errors[] = 'Die Anfrage konnte nicht verarbeitet werden.';
    if (!isset($types[$type])) $errors[] = 'Bitte wählen Sie die Art Ihrer Anfrage.';
    if ($name === '') $errors[] = 'Bitte geben Sie Ihren Namen ein.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    if ($subject === '') $errors[] = 'Bitte geben Sie einen Betreff ein.';
    if (mb_strlen($message) < 10) $errors[] = 'Bitte beschreiben Sie Ihre Anfrage.';
    if (!$privacy) $errors[] = 'Bitte bestätigen Sie die Datenschutzhinweise.';

    if (!$errors) {
        try {
            $stmt = mmDb()->prepare(
                'INSERT INTO contact_requests
                (request_type, name, organisation, email, phone, subject, message, privacy_acknowledged_at, created_at)
                VALUES (:request_type,:name,:organisation,:email,:phone,:subject,:message,NOW(),NOW())'
            );
            $stmt->execute([
                'request_type' => $type,
                'name' => $name,
                'organisation' => $organisation,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message,
            ]);
            $success = true;
            $_SESSION['mm_csrf'] = bin2hex(random_bytes(24));
        } catch (Throwable $e) {
            $errors[] = 'Die Anfrage konnte derzeit nicht gespeichert werden. Bitte schreiben Sie alternativ an hello@mysterymarket.de.';
        }
    }
}

mmHeader('Kontakt', 'Kontakt zu MysteryMarket für Kunden, Agenturen, Shopper und andere Anfragen.');
?>
<section class="hero">
  <div>
    <p class="eyebrow">Kontakt</p>
    <h1>Worum geht es?</h1>
    <p class="lead">Kunden, Agenturen, Shopper und andere Anfragen werden getrennt erfasst und zweckgebunden bearbeitet.</p>
    <p><strong>Direkter Kontakt:</strong> <a href="mailto:hello@mysterymarket.de">hello@mysterymarket.de</a><br>
    <strong>Business & Partnerschaften:</strong> <a href="mailto:partners@mysterymarket.de">partners@mysterymarket.de</a></p>
  </div>
</section>
<section class="section">
<div class="form-card">
<?php if ($success): ?><div class="alert success"><strong>Anfrage gespeichert.</strong><p>Ihre Anfrage wurde zur Bearbeitung aufgenommen.</p></div><?php endif; ?>
<?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $error): ?><li><?= mmEscape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= mmEscape((string)$_SESSION['mm_csrf']) ?>">
<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Company URL<input name="company_url" tabindex="-1" autocomplete="off"></label></div>
<div class="form-grid">
<label>Wer sind Sie? *<select name="type" required><option value="">Bitte wählen</option><?php foreach ($types as $value => $label): ?><option value="<?= mmEscape($value) ?>"><?= mmEscape($label) ?></option><?php endforeach; ?></select></label>
<label>Name *<input name="name" maxlength="150" required></label>
<label>Organisation<input name="organisation" maxlength="200"></label>
<label>E-Mail *<input type="email" name="email" maxlength="254" required></label>
<label>Telefon<input type="tel" name="phone" maxlength="60"></label>
<label>Betreff *<input name="subject" maxlength="200" required></label>
<label class="wide">Nachricht *<textarea name="message" maxlength="10000" required></textarea></label>
<label class="wide"><input type="checkbox" name="privacy" value="1" required style="width:auto;margin-right:8px">Ich habe die <a href="/privacy.php">Datenschutzhinweise</a> gelesen und bin mit der zweckgebundenen Verarbeitung meiner Anfrage einverstanden. *</label>
</div>
<button type="submit">Anfrage an MysteryMarket senden</button>
</form>
</div>
</section>
<?php mmFooter(); ?>
