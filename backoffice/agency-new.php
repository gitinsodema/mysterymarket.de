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
        $name = trim((string)($_POST['name'] ?? ''));
        $short = trim((string)($_POST['short_name'] ?? ''));
        $website = trim((string)($_POST['website_url'] ?? ''));
        if ($name === '') {
            $error = 'Name ist erforderlich.';
        } elseif ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            $error = 'Website ist ungültig.';
        } else {
            try {
                $stmt = mmDb()->prepare(
                    'INSERT INTO agencies (name, short_name, website_url, is_active, created_at, updated_at)
                     VALUES (:name, :short_name, :website_url, 1, NOW(), NOW())'
                );
                $stmt->execute([
                    'name'=>$name,
                    'short_name'=>$short !== '' ? $short : null,
                    'website_url'=>$website !== '' ? $website : null,
                ]);
                $id=(int)mmDb()->lastInsertId();
                mmBackofficeAudit((int)$user['id'],'agency.created','agency',$id,['name'=>$name]);
                header('Location: /backoffice/agency.php?id='.$id.'&created=1',true,303);
                exit;
            } catch (Throwable $e) {
                $error='Agentur konnte nicht angelegt werden. Name eventuell bereits vorhanden.';
            }
        }
    }
}

mmHeader('Agentur anlegen','Neue Agentur-Stammdaten anlegen.','noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero"><div><p class="eyebrow">Admin · Agenturen</p><h1>Agentur anlegen.</h1><p class="lead">Minimaler Stammdatensatz für interne Prozesse.</p></div></section>
<section class="section">
  <div class="form-card">
    <?php if ($error!==''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/agency-new.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <label>Name<input name="name" maxlength="200" required></label>
      <label>Kurzname<input name="short_name" maxlength="120"></label>
      <label>Website<input type="url" name="website_url" maxlength="500"></label>
      <div class="actions">
        <button type="submit">Agentur anlegen</button>
        <a class="button secondary" href="/backoffice/agencies.php">Abbrechen</a>
      </div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
