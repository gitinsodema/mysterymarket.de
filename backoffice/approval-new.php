<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $agency = trim((string)($_POST['agency_name'] ?? ''));
        $contact = trim((string)($_POST['contact_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['contact_email'] ?? '')));
        $purpose = trim((string)($_POST['purpose'] ?? ''));
        $note = trim((string)($_POST['internal_note'] ?? ''));

        if ($agency === '' || $purpose === '') {
            $error = 'Agentur und Zweck sind erforderlich.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Die Kontakt-E-Mail ist ungültig.';
        } else {
            $stmt = mmDb()->prepare(
                'INSERT INTO agency_approvals
                 (agency_name, contact_name, contact_email, purpose, approval_status,
                  requested_at, responded_at, evidence_reference, internal_note, created_by, created_at, updated_at)
                 VALUES
                 (:agency, :contact, :email, :purpose, \'draft\',
                  NULL, NULL, NULL, :note, :created_by, NOW(), NOW())'
            );
            $stmt->execute([
                'agency'=>$agency,
                'contact'=>$contact !== '' ? $contact : null,
                'email'=>$email !== '' ? $email : null,
                'purpose'=>$purpose,
                'note'=>$note !== '' ? $note : null,
                'created_by'=>(int)$user['id'],
            ]);
            $id = (int)mmDb()->lastInsertId();
            mmBackofficeAudit((int)$user['id'], 'agency_approval.created', 'agency_approval', $id, ['agency'=>$agency]);
            header('Location: /backoffice/approval.php?id=' . $id . '&created=1', true, 303);
            exit;
        }
    }
}

mmHeader('Freigabevorgang anlegen', 'Neuen Agentur-Freigabevorgang anlegen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div><p class="eyebrow">Admin · Kommunikation</p><h1>Vorgang anlegen.</h1><p class="lead">Zum Beispiel Logo-OK, Nutzungsfreigabe oder Projektbezug.</p></div>
</section>
<section class="section">
  <div class="form-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/approval-new.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <label>Agentur<input name="agency_name" maxlength="200" required></label>
      <div class="form-grid">
        <label>Ansprechpartner<input name="contact_name" maxlength="160"></label>
        <label>Kontakt-E-Mail<input type="email" name="contact_email" maxlength="254"></label>
      </div>
      <label>Zweck<input name="purpose" maxlength="255" required placeholder="z. B. Logo-Nutzung auf Verify-Ausweis"></label>
      <label>Interne Notiz<textarea name="internal_note"></textarea></label>
      <div class="actions">
        <button type="submit">Als Entwurf anlegen</button>
        <a class="button secondary" href="/backoffice/approvals.php">Abbrechen</a>
      </div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
