<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/db.php';

$c = mmPageCopy('contact');
$lang = mmLanguage();

$typeLabelsByLang = [
    'de'=>['client'=>'Kunde / Auftraggeber','agency'=>'Agentur / Audit Partner','elite_shopper'=>'Elite Shopper / Auditor','other'=>'Andere Anfrage'],
    'en'=>['client'=>'Client / Commissioning organisation','agency'=>'Agency / Audit Partner','elite_shopper'=>'Elite Shopper / Auditor','other'=>'Other enquiry'],
    'nl'=>['client'=>'Klant / Opdrachtgever','agency'=>'Bureau / Audit Partner','elite_shopper'=>'Elite Shopper / Auditor','other'=>'Andere aanvraag'],
];
$typeLabels = $typeLabelsByLang[$lang] ?? $typeLabelsByLang['de'];

$validationByLang = [
    'de'=>[
        'session'=>'Die Formularsitzung ist abgelaufen.','failed'=>'Die Anfrage konnte nicht verarbeitet werden.','type'=>'Bitte wählen Sie die Art Ihrer Anfrage.','name'=>'Bitte geben Sie Ihren Namen ein.','email'=>'Bitte geben Sie eine gültige E-Mail-Adresse ein.','subject'=>'Bitte geben Sie einen Betreff ein.','message'=>'Bitte beschreiben Sie Ihre Anfrage.','privacy'=>'Bitte bestätigen Sie die Datenschutzhinweise.','store'=>'Die Anfrage konnte derzeit nicht gespeichert werden. Bitte schreiben Sie alternativ an hello@mysterymarket.de.'
    ],
    'en'=>[
        'session'=>'The form session has expired.','failed'=>'The enquiry could not be processed.','type'=>'Please select the type of enquiry.','name'=>'Please enter your name.','email'=>'Please enter a valid email address.','subject'=>'Please enter a subject.','message'=>'Please describe your enquiry.','privacy'=>'Please confirm the privacy notice.','store'=>'The enquiry could not be saved at this time. Please email hello@mysterymarket.de instead.'
    ],
    'nl'=>[
        'session'=>'De formuliesessie is verlopen.','failed'=>'De aanvraag kon niet worden verwerkt.','type'=>'Selecteer het type aanvraag.','name'=>'Vul uw naam in.','email'=>'Vul een geldig e-mailadres in.','subject'=>'Vul een onderwerp in.','message'=>'Beschrijf uw aanvraag.','privacy'=>'Bevestig de privacyverklaring.','store'=>'De aanvraag kon momenteel niet worden opgeslagen. Mail eventueel naar hello@mysterymarket.de.'
    ],
];
$validation = $validationByLang[$lang] ?? $validationByLang['de'];

$errors = [];
$success = false;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['mm_csrf'] ??= bin2hex(random_bytes(24));
$_SESSION['mm_contact_attempts'] ??= [];
$contactNow = time();
$_SESSION['mm_contact_attempts'] = array_values(array_filter(
    (array)$_SESSION['mm_contact_attempts'],
    static fn($ts): bool => is_int($ts) && $ts > $contactNow - 1800
));

function mmSendContactNotification(
    int $requestId,
    string $typeLabel,
    string $name,
    string $organisation,
    string $email,
    string $phone,
    string $subject,
    string $message
): bool {
    $mail = mmConfig()['mail'] ?? [];
    $to = trim((string)($mail['notification_email'] ?? ''));
    $from = trim((string)($mail['from_email'] ?? 'hello@mysterymarket.de'));

    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $safeSubject = preg_replace('/[\r\n]+/', ' ', $subject) ?: 'Contact enquiry';
    $mailSubject = sprintf('[MysteryMarket #%d] %s · %s', $requestId, $typeLabel, $safeSubject);

    $body = implode(PHP_EOL, [
        'New enquiry via mysterymarket.de',
        '',
        'DB-ID: ' . $requestId,
        'Type: ' . $typeLabel,
        'Name: ' . $name,
        'Organisation: ' . ($organisation !== '' ? $organisation : '—'),
        'Email: ' . $email,
        'Phone: ' . ($phone !== '' ? $phone : '—'),
        'Subject: ' . $safeSubject,
        '',
        'Message:',
        $message,
        '',
        'The enquiry was stored in MariaDB before this notification was sent.',
    ]);

    $headers = [
        'From: MysteryMarket Website <' . $from . '>',
        'Reply-To: ' . $email,
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: MysteryMarket Website',
    ];

    return mail($to, $mailSubject, $body, implode("\r\n", $headers));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (count($_SESSION['mm_contact_attempts']) >= 8) {
        http_response_code(429);
        $errors[] = $lang === 'en'
            ? 'Too many form submissions. Please try again later or contact us by email.'
            : ($lang === 'nl'
                ? 'Te veel formulierverzendingen. Probeer het later opnieuw of neem per e-mail contact op.'
                : 'Zu viele Formularversuche. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns per E-Mail.');
    } else {
        $_SESSION['mm_contact_attempts'][] = $contactNow;
    }

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

    if (!$errors && !hash_equals((string)$_SESSION['mm_csrf'], $csrf)) $errors[] = $validation['session'];
    if ($honeypot !== '') $errors[] = $validation['failed'];
    if (!isset($typeLabels[$type])) $errors[] = $validation['type'];
    if ($name === '') $errors[] = $validation['name'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $validation['email'];
    if ($subject === '') $errors[] = $validation['subject'];
    if (mb_strlen($message) < 10) $errors[] = $validation['message'];
    if (!$privacy) $errors[] = $validation['privacy'];

    if (!$errors) {
        try {
            $pdo = mmDb();
            $stmt = $pdo->prepare(
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

            $requestId = (int)$pdo->lastInsertId();
            $notificationSent = mmSendContactNotification(
                $requestId,
                $typeLabels[$type],
                $name,
                $organisation,
                $email,
                $phone,
                $subject,
                $message
            );

            $notificationStmt = $pdo->prepare(
                $notificationSent
                    ? 'UPDATE contact_requests SET notification_sent_at = NOW(), notification_failed_at = NULL WHERE id = :id'
                    : 'UPDATE contact_requests SET notification_failed_at = NOW() WHERE id = :id'
            );
            $notificationStmt->execute(['id' => $requestId]);

            $success = true;
            $_SESSION['mm_csrf'] = bin2hex(random_bytes(24));
        } catch (Throwable $e) {
            $errors[] = $validation['store'];
        }
    }
}

mmHeader($c['title'], $c['lead']);
?>
<section class="hero">
  <div><p class="eyebrow"><?= mmEscape($c['title']) ?></p><h1><?= mmEscape($c['hero']) ?></h1><p class="lead"><?= mmEscape($c['lead']) ?></p></div>
  <div class="contact-channels">
    <div><span><?= mmEscape($c['general']) ?></span><a href="mailto:hello@mysterymarket.de">hello@mysterymarket.de</a></div>
    <div><span><?= mmEscape($c['agency']) ?></span><a href="mailto:agency@mysterymarket.de">agency@mysterymarket.de</a></div>
    <div><span><?= mmEscape($c['elite']) ?></span><a href="mailto:eliteshopper@mysterymarket.de">eliteshopper@mysterymarket.de</a><small><?= mmEscape($c['elite_note']) ?></small></div>
  </div>
</section>

<section class="section">
<div class="form-card">
<div class="form-intro"><p class="eyebrow"><?= mmEscape($c['form']) ?></p><h2><?= mmEscape($c['form_title']) ?></h2><p><?= mmEscape($c['form_text']) ?></p></div>
<?php if ($success): ?><div class="alert success"><strong><?= mmEscape($c['success']) ?></strong><p><?= mmEscape($c['success_text']) ?></p></div><?php endif; ?>
<?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $error): ?><li><?= mmEscape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= mmEscape((string)$_SESSION['mm_csrf']) ?>">
<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Company URL<input name="company_url" tabindex="-1" autocomplete="off"></label></div>
<div class="form-grid">
<label><?= mmEscape($c['who']) ?> *<select name="type" required><option value=""><?= mmEscape($c['choose']) ?></option><?php foreach ($typeLabels as $value => $label): ?><option value="<?= mmEscape($value) ?>"><?= mmEscape($label) ?></option><?php endforeach; ?></select></label>
<label><?= mmEscape($c['name']) ?> *<input name="name" maxlength="150" required></label>
<label><?= mmEscape($c['org']) ?><input name="organisation" maxlength="200"></label>
<label><?= mmEscape($c['email']) ?> *<input type="email" name="email" maxlength="254" required></label>
<label><?= mmEscape($c['phone']) ?><input type="tel" name="phone" maxlength="60"></label>
<label><?= mmEscape($c['subject']) ?> *<input name="subject" maxlength="200" required></label>
<label class="wide"><?= mmEscape($c['message']) ?> *<textarea name="message" maxlength="10000" required></textarea></label>
<label class="wide privacy-check"><input type="checkbox" name="privacy" value="1" required> <span><?= mmEscape($c['privacy']) ?> *</span></label>
</div>
<button type="submit"><?= mmEscape($c['send']) ?></button>
</form>
</div>
</section>
<?php mmFooter(); ?>
