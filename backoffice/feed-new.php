<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

$categories = ['project_hint','agency','training','ops','important','general'];
$agencies = mmDb()->query("SELECT id, name, short_name FROM agencies WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $category = (string)($_POST['category'] ?? 'general');
        $title = trim((string)($_POST['title'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        $agencyId = (int)($_POST['agency_id'] ?? 0);
        $agency = trim((string)($_POST['agency_name'] ?? ''));
        if ($agencyId > 0) {
            $agencyStmt = mmDb()->prepare('SELECT name FROM agencies WHERE id = :id AND is_active = 1 LIMIT 1');
            $agencyStmt->execute(['id'=>$agencyId]);
            $selectedAgency = $agencyStmt->fetchColumn();
            if ($selectedAgency !== false) {
                $agency = (string)$selectedAgency;
            } else {
                $agencyId = 0;
            }
        }
        $project = trim((string)($_POST['project_context'] ?? ''));
        $region = trim((string)($_POST['region_label'] ?? ''));
        $url = trim((string)($_POST['external_url'] ?? ''));
        $pinned = isset($_POST['is_pinned']) ? 1 : 0;

        if (!in_array($category, $categories, true) || $title === '' || $body === '') {
            $error = 'Kategorie, Titel und Text sind erforderlich.';
        } elseif ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            $error = 'Der externe Link ist ungültig.';
        } else {
            $stmt = mmDb()->prepare(
                'INSERT INTO elite_feed_posts
                 (author_user_id, category, title, body, agency_id, agency_name, project_context, region_label, external_url,
                  publish_from, publish_until, is_pinned, is_active, created_at, updated_at)
                 VALUES
                 (:author, :category, :title, :body, :agency_id, :agency, :project, :region, :url,
                  NOW(), NULL, :pinned, 1, NOW(), NOW())'
            );
            $stmt->execute([
                'author'=>(int)$user['id'],
                'category'=>$category,
                'title'=>$title,
                'body'=>$body,
                'agency_id'=>$agencyId > 0 ? $agencyId : null,
                'agency'=>$agency !== '' ? $agency : null,
                'project'=>$project !== '' ? $project : null,
                'region'=>$region !== '' ? $region : null,
                'url'=>$url !== '' ? $url : null,
                'pinned'=>$pinned,
            ]);
            $id = (int)mmDb()->lastInsertId();
            mmBackofficeAudit((int)$user['id'], 'elite_feed.created', 'elite_feed_post', $id, ['category'=>$category]);
            header('Location: /backoffice/feed.php?created=1', true, 303);
            exit;
        }
    }
}

mmHeader('Elite Info posten', 'Internen Elite-Feed-Beitrag erstellen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div><p class="eyebrow">Admin · Elite Feed</p><h1>Info posten.</h1><p class="lead">Kurze interne Projekt- oder Partnerinformation für Elite Shopper.</p></div>
</section>
<section class="section">
  <div class="form-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/feed-new.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <label>Kategorie
        <select name="category">
          <?php foreach ($categories as $category): ?><option value="<?= mmEscape($category) ?>"><?= mmEscape($category) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Titel<input name="title" maxlength="200" required></label>
      <label>Text<textarea name="body" required></textarea></label>
      <div class="form-grid">
        <label>Agentur
          <select name="agency_id">
            <option value="0">Keine / Freitext</option>
            <?php foreach ($agencies as $agencyRow): ?>
              <option value="<?= (int)$agencyRow['id'] ?>"><?= mmEscape((string)($agencyRow['short_name'] ?: $agencyRow['name'])) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Agentur-Freitext<input name="agency_name" maxlength="200" placeholder="Nur falls noch nicht in Stammdaten"></label>
        <label>Projekt / Kontext<input name="project_context" maxlength="255"></label>
        <label>Region<input name="region_label" maxlength="160"></label>
        <label>Externer Link<input type="url" name="external_url" maxlength="500"></label>
      </div>
      <label class="privacy-check"><input type="checkbox" name="is_pinned" value="1"> Beitrag oben anheften</label>
      <div class="actions">
        <button type="submit">Veröffentlichen</button>
        <a class="button secondary" href="/backoffice/feed.php">Abbrechen</a>
      </div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
