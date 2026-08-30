<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $decision = (string)($_POST['decision'] ?? '');
        if ($id < 1 || !in_array($decision, ['approved','rejected'], true)) {
            $error = 'Ungültige Aktion.';
        } else {
            $pdo = mmDb();
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'SELECT r.id, r.member_id, r.request_type, r.request_status, m.user_id
                     FROM elite_membership_requests r
                     JOIN elite_members m ON m.id = r.member_id
                     WHERE r.id = :id
                     FOR UPDATE'
                );
                $stmt->execute(['id'=>$id]);
                $row = $stmt->fetch();

                if (!$row || $row['request_status'] !== 'open') {
                    throw new RuntimeException('Request not open');
                }

                $update = $pdo->prepare(
                    'UPDATE elite_membership_requests
                     SET request_status = :status, resolved_at = NOW(), resolved_by = :resolved_by
                     WHERE id = :id'
                );
                $update->execute([
                    'status'=>$decision,
                    'resolved_by'=>(int)$user['id'],
                    'id'=>$id,
                ]);

                if ($decision === 'approved') {
                    $newMemberStatus = $row['request_type'] === 'pause' ? 'paused' : 'ended';
                    $memberUpdate = $pdo->prepare(
                        'UPDATE elite_members
                         SET membership_status = :status,
                             paused_at = CASE WHEN :status = \'paused\' THEN NOW() ELSE paused_at END,
                             ended_at = CASE WHEN :status = \'ended\' THEN NOW() ELSE ended_at END,
                             updated_at = NOW()
                         WHERE id = :member_id'
                    );
                    $memberUpdate->execute([
                        'status'=>$newMemberStatus,
                        'member_id'=>(int)$row['member_id'],
                    ]);

                    $accountUpdate = $pdo->prepare(
                        'UPDATE backoffice_users
                         SET account_status = \'disabled\', updated_at = NOW()
                         WHERE id = :user_id'
                    );
                    $accountUpdate->execute(['user_id'=>(int)$row['user_id']]);
                }

                $pdo->commit();

                mmBackofficeAudit((int)$user['id'], 'elite_membership_request.' . $decision, 'elite_membership_request', $id, [
                    'request_type'=>(string)$row['request_type'],
                    'member_id'=>(int)$row['member_id'],
                ]);

                header('Location: /backoffice/membership-requests.php?updated=1', true, 303);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Anfrage konnte nicht verarbeitet werden.';
            }
        }
    }
}

$rows = mmDb()->query(
    'SELECT r.id, r.request_type, r.request_status, r.note, r.created_at,
            m.id AS member_id, m.member_code, m.display_name
     FROM elite_membership_requests r
     JOIN elite_members m ON m.id = r.member_id
     ORDER BY (r.request_status = \'open\') DESC, r.created_at DESC'
)->fetchAll();

mmHeader('Mitgliedschaftsanfragen', 'Interne Bearbeitung von Elite-Mitgliedschaftsanfragen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Elite Shopper</p>
    <h1>Mitgliedschaftsanfragen.</h1>
    <p class="lead">Pause oder Beendigung durch Elite Shopper prüfen und freigeben.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/">Dashboard</a></div>
  </div>
</section>

<section class="section">
  <?php if (isset($_GET['updated'])): ?><div class="alert success"><strong>Anfrage verarbeitet.</strong></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <div class="elite-feed-list">
    <?php if (!$rows): ?><div class="notice"><strong>Keine Mitgliedschaftsanfragen vorhanden.</strong></div><?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <article class="elite-feed-post">
        <div class="elite-feed-meta">
          <span class="badge"><?= mmEscape((string)$row['request_type']) ?></span>
          <?= mmBackofficeStatusBadge((string)$row['request_status']) ?>
        </div>
        <h2><?= mmEscape((string)$row['display_name']) ?></h2>
        <p><?= mmEscape((string)$row['member_code']) ?> · <?= mmEscape((string)$row['created_at']) ?></p>
        <?php if (!empty($row['note'])): ?><p><?= nl2br(mmEscape((string)$row['note'])) ?></p><?php endif; ?>
        <div class="actions">
          <a class="button secondary" href="/backoffice/member.php?id=<?= (int)$row['member_id'] ?>">Mitglied öffnen</a>
          <?php if ($row['request_status'] === 'open'): ?>
            <form method="post" action="/backoffice/membership-requests.php">
              <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button type="submit" name="decision" value="approved">Freigeben</button>
              <button type="submit" name="decision" value="rejected" class="button secondary">Ablehnen</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php mmFooter(); ?>
