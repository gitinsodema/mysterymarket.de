<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin();
$isAdmin = (($user['role'] ?? '') === 'admin');

$sql = 'SELECT p.id, p.category, p.title, p.body, p.agency_name, p.project_context, p.region_label,
               p.external_url, p.publish_from, p.publish_until, p.is_pinned, p.created_at
        FROM elite_feed_posts p
        WHERE p.is_active = 1
          AND (p.publish_from IS NULL OR p.publish_from <= NOW())
          AND (p.publish_until IS NULL OR p.publish_until >= NOW())
        ORDER BY p.is_pinned DESC, p.created_at DESC';
$posts = mmDb()->query($sql)->fetchAll();

mmHeader('Elite Feed', 'Interne MysteryMarket Elite Informationen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Elite Feed</p>
    <h1>Interne Hinweise.</h1>
    <p class="lead">Projekt-, Agentur- und Partnerinformationen ausschließlich für den geschützten Bereich.</p>
    <div class="actions">
      <?php if ($isAdmin): ?><a class="button" href="/backoffice/feed-new.php">Info posten</a><?php endif; ?>
      <a class="button secondary" href="/backoffice/">Dashboard</a>
    </div>
  </div>
</section>
<section class="section">
  <div class="elite-feed-list">
    <?php if (!$posts): ?>
      <div class="notice"><strong>Noch keine internen Hinweise.</strong><p>Der Feed ist bereit für den ersten Beitrag.</p></div>
    <?php endif; ?>
    <?php foreach ($posts as $post): ?>
      <article class="elite-feed-post<?= (int)$post['is_pinned'] === 1 ? ' is-pinned' : '' ?>">
        <div class="elite-feed-meta">
          <span class="badge"><?= mmEscape((string)$post['category']) ?></span>
          <?php if ((int)$post['is_pinned'] === 1): ?><?= mmBackofficeStatusBadge('info', 'Pinned') ?><?php endif; ?>
        </div>
        <h2><?= mmEscape((string)$post['title']) ?></h2>
        <p><?= nl2br(mmEscape((string)$post['body'])) ?></p>
        <div class="elite-feed-details">
          <?php if (!empty($post['agency_name'])): ?><span><strong>Agentur</strong><?= mmEscape((string)$post['agency_name']) ?></span><?php endif; ?>
          <?php if (!empty($post['project_context'])): ?><span><strong>Projekt</strong><?= mmEscape((string)$post['project_context']) ?></span><?php endif; ?>
          <?php if (!empty($post['region_label'])): ?><span><strong>Region</strong><?= mmEscape((string)$post['region_label']) ?></span><?php endif; ?>
        </div>
        <?php if (!empty($post['external_url'])): ?>
          <a href="<?= mmEscape((string)$post['external_url']) ?>" target="_blank" rel="noopener noreferrer">Externe Information öffnen →</a>
        <?php endif; ?>
        <small><?= mmEscape((string)$post['created_at']) ?></small>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php mmFooter(); ?>
