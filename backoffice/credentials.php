<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin();
$subject = mmCredentialSubjectForUser($user, true);
if (!$subject) {
    http_response_code(500);
    exit('Credential subject unavailable');
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'subject_name') {
            $displayName = trim((string)($_POST['display_name'] ?? ''));
            if ($displayName === '' || mb_strlen($displayName) > 150) {
                $error = 'Bitte einen gültigen Namen angeben.';
            } else {
                $stmt = mmDb()->prepare(
                    'UPDATE credential_subjects SET display_name = :display_name, updated_at = NOW() WHERE id = :id'
                );
                $stmt->execute(['display_name'=>$displayName,'id'=>(int)$subject['id']]);
                mmBackofficeAudit((int)$user['id'], 'credential_subject.updated', 'credential_subject', (int)$subject['id']);
                header('Location: /backoffice/credentials.php?profile_saved=1', true, 303);
                exit;
            }
        }

        if ($action === 'order') {
            $channel = (string)($_POST['order_channel'] ?? '');
            $allowedChannels = [
                'apple_wallet',
                'physical_card',
                'transparent_holder',
                'mysterymarket_lanyard',
                'elite_shopper_lanyard',
                'full_set',
                'replacement_card',
            ];

            if (!in_array($channel, $allowedChannels, true)) {
                $error = 'Ungültige Ausweis-/Ausstattungsoption.';
            } else {
                $pdo = mmDb();
                try {
                    $pdo->beginTransaction();

                    $credentialStmt = $pdo->prepare(
                        'SELECT *
                         FROM credentials
                         WHERE subject_id = :subject_id
                           AND credential_status IN ('draft','approved','active')
                         ORDER BY id DESC
                         LIMIT 1
                         FOR UPDATE'
                    );
                    $credentialStmt->execute(['subject_id'=>(int)$subject['id']]);
                    $credential = $credentialStmt->fetch();

                    if (!$credential) {
                        $type = ($user['role'] ?? '') === 'elite' ? 'elite_shopper' : 'field_credential';
                        $title = ($user['role'] ?? '') === 'elite' ? 'Elite Shopper Credential' : 'MysteryMarket Personal Credential';

                        $insertCredential = $pdo->prepare(
                            'INSERT INTO credentials
                             (subject_id, credential_code, credential_type, title, credential_status, created_by_user_id, created_at, updated_at)
                             VALUES (:subject_id, :credential_code, :credential_type, :title, 'draft', :created_by, NOW(), NOW())'
                        );

                        do {
                            $credentialCode = mmCredentialCode();
                            try {
                                $insertCredential->execute([
                                    'subject_id'=>(int)$subject['id'],
                                    'credential_code'=>$credentialCode,
                                    'credential_type'=>$type,
                                    'title'=>$title,
                                    'created_by'=>(int)$user['id'],
                                ]);
                                break;
                            } catch (PDOException $e) {
                                if ((string)$e->getCode() !== '23000') {
                                    throw $e;
                                }
                            }
                        } while (true);

                        $credentialId = (int)$pdo->lastInsertId();
                    } else {
                        $credentialId = (int)$credential['id'];
                    }

                    $duplicate = $pdo->prepare(
                        'SELECT COUNT(*) FROM credential_orders
                         WHERE credential_id = :credential_id
                           AND order_channel = :channel
                           AND order_status IN ('requested','approved','processing','ready')'
                    );
                    $duplicate->execute(['credential_id'=>$credentialId,'channel'=>$channel]);

                    if ((int)$duplicate->fetchColumn() > 0) {
                        throw new RuntimeException('Für diese Option gibt es bereits eine offene Bestellung.');
                    }

                    $order = $pdo->prepare(
                        'INSERT INTO credential_orders
                         (credential_id, requested_by_user_id, order_channel, order_status, requested_at, created_at, updated_at)
                         VALUES (:credential_id, :requested_by, :channel, 'requested', NOW(), NOW(), NOW())'
                    );
                    $order->execute([
                        'credential_id'=>$credentialId,
                        'requested_by'=>(int)$user['id'],
                        'channel'=>$channel,
                    ]);
                    $orderId = (int)$pdo->lastInsertId();

                    $pdo->commit();
                    mmBackofficeAudit(
                        (int)$user['id'],
                        'credential_order.created',
                        'credential_order',
                        $orderId,
                        ['channel'=>$channel,'credential_id'=>$credentialId]
                    );

                    header('Location: /backoffice/credentials.php?ordered=1', true, 303);
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = $e instanceof RuntimeException
                        ? $e->getMessage()
                        : 'Die Bestellung konnte nicht gespeichert werden.';
                }
            }
        }
    }
}

$subject = mmCredentialSubjectForUser($user, false);

$credentialStmt = mmDb()->prepare(
    'SELECT *
     FROM credentials
     WHERE subject_id = :subject_id
     ORDER BY id DESC'
);
$credentialStmt->execute(['subject_id'=>(int)$subject['id']]);
$credentials = $credentialStmt->fetchAll();

$orderStmt = mmDb()->prepare(
    'SELECT o.*, c.credential_code, c.title
     FROM credential_orders o
     JOIN credentials c ON c.id = o.credential_id
     WHERE c.subject_id = :subject_id
     ORDER BY o.requested_at DESC, o.id DESC'
);
$orderStmt->execute(['subject_id'=>(int)$subject['id']]);
$orders = $orderStmt->fetchAll();

$adminCredentials = [];
$adminOrders = [];
if (($user['role'] ?? '') === 'admin') {
    $adminCredentials = mmDb()->query(
        'SELECT c.*, s.display_name, u.email
         FROM credentials c
         JOIN credential_subjects s ON s.id = c.subject_id
         LEFT JOIN backoffice_users u ON u.id = s.backoffice_user_id
         ORDER BY c.created_at DESC
         LIMIT 100'
    )->fetchAll();

    $adminOrders = mmDb()->query(
        'SELECT o.*, c.credential_code, s.display_name
         FROM credential_orders o
         JOIN credentials c ON c.id = o.credential_id
         JOIN credential_subjects s ON s.id = c.subject_id
         ORDER BY o.requested_at DESC
         LIMIT 100'
    )->fetchAll();
}

mmHeader('Credentials', 'Persönliche MysteryMarket Credentials.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow"><?= ($user['role'] ?? '') === 'admin' ? 'Admin · persönlicher Ausweis' : 'Elite Shopper · Ausweis' ?></p>
    <h1>Mein Credential.</h1>
    <p class="lead">Digital, QR, Apple Wallet und physische Karte greifen künftig auf dieselbe persönliche Credential-Identität zu.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/">Dashboard</a></div>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="card">
      <span class="badge">Credential Holder</span>
      <h2><?= mmEscape((string)$subject['display_name']) ?></h2>
      <p><?= mmEscape((string)$user['email']) ?></p>
      <p>Rolle im Backoffice: <strong><?= mmEscape((string)$user['role']) ?></strong></p>
    </article>
    <article class="card">
      <span class="badge">Prinzip</span>
      <h3>Person statt Rolle</h3>
      <p>Der Ausweis gehört zu deiner Identität. Admin- oder Elite-Rechte bestimmen nur den Zugriff auf das Backoffice.</p>
    </article>
  </div>
</section>

<section class="section">
  <div class="form-card">
    <?php if (isset($_GET['profile_saved'])): ?><div class="alert success"><strong>Credential-Profil gespeichert.</strong></div><?php endif; ?>
    <?php if (isset($_GET['ordered'])): ?><div class="alert success"><strong>Anfrage gespeichert.</strong></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

    <h2>Persönliche Ausweisidentität</h2>
    <form method="post" action="/backoffice/credentials.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="action" value="subject_name">
      <label>Name auf dem Ausweis
        <input name="display_name" maxlength="150" required value="<?= mmEscape((string)$subject['display_name']) ?>">
      </label>
      <button type="submit">Speichern</button>
    </form>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow">Ausweis bestellen</p>
    <h2>Eine Identität, mehrere Ausgabewege.</h2>
    <p>Apple Wallet und physische Ausstattung sind keine getrennten Identitäten. Sie beziehen sich auf dasselbe Credential.</p>
  </div>
  <div class="backoffice-module-grid">
    <?php
    $channels = [
      'apple_wallet' => ['Apple Wallet','Digitaler Pass auf dem iPhone'],
      'physical_card' => ['Physische Karte','CR80-Ausweiskarte'],
      'transparent_holder' => ['Karte + Halter','Transparenter Schutzhüllen-Halter'],
      'mysterymarket_lanyard' => ['MysteryMarket Lanyard','Ausweisband für Feldeinsätze'],
      'elite_shopper_lanyard' => ['Elite Shopper Lanyard','Elite-spezifisches Ausweisband'],
      'full_set' => ['Komplettset','Karte, Halter und Lanyard'],
      'replacement_card' => ['Ersatzkarte','Ersatz bei Verlust oder Beschädigung'],
    ];
    foreach ($channels as $channel => [$title,$copy]):
    ?>
      <form class="backoffice-module credential-order-card" method="post" action="/backoffice/credentials.php">
        <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
        <input type="hidden" name="action" value="order">
        <input type="hidden" name="order_channel" value="<?= mmEscape($channel) ?>">
        <span><?= $channel === 'apple_wallet' ? 'DIGITAL' : 'PHYSICAL' ?></span>
        <strong><?= mmEscape($title) ?></strong>
        <small><?= mmEscape($copy) ?></small>
        <button type="submit" class="credential-order-action"><?= mmEscape(mmCredentialOrderLabel($channel)) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
  <p class="partner-note">Apple Wallet ist als Ausgabeweg bereits im Lifecycle vorgesehen. Die echte .pkpass-Signierung folgt, sobald Pass Type ID und Apple-Zertifikat serverseitig hinterlegt sind.</p>
</section>

<section class="section">
  <div class="section-head"><h2>Meine Credentials</h2></div>
  <?php if (!$credentials): ?>
    <div class="notice">Noch kein Credential angelegt. Die erste Ausweis-/Wallet-Anfrage legt automatisch einen persönlichen Draft an.</div>
  <?php else: ?>
    <div class="grid two">
      <?php foreach ($credentials as $credential): ?>
        <article class="card">
          <span class="badge"><?= mmEscape(mmCredentialTypeLabel((string)$credential['credential_type'])) ?></span>
          <h3><?= mmEscape((string)$credential['credential_code']) ?></h3>
          <p><?= mmBackofficeStatusBadge((string)$credential['credential_status']) ?></p>
          <p><?= mmEscape((string)$credential['title']) ?></p>
          <p><strong>Gültig:</strong> <?= mmEscape((string)($credential['valid_from'] ?: '—')) ?> – <?= mmEscape((string)($credential['valid_until'] ?: '—')) ?></p>
          <p><strong>Verify:</strong> <?= mmEscape((string)($credential['verify_reference_code'] ?: 'noch nicht gebunden')) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="section">
  <div class="section-head"><h2>Meine Ausweis-/Ausstattungsanfragen</h2></div>
  <?php if (!$orders): ?>
    <p class="partner-note">Noch keine Anfrage.</p>
  <?php else: ?>
    <div class="backoffice-list">
      <?php foreach ($orders as $order): ?>
        <article>
          <div>
            <strong><?= mmEscape(mmCredentialOrderLabel((string)$order['order_channel'])) ?></strong>
            <small><?= mmEscape((string)$order['credential_code']) ?> · <?= mmEscape((string)$order['requested_at']) ?></small>
          </div>
          <?= mmBackofficeStatusBadge((string)$order['order_status']) ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if (($user['role'] ?? '') === 'admin'): ?>
<section class="section">
  <div class="section-head">
    <p class="eyebrow">Admin</p>
    <h2>Credential-Übersicht</h2>
  </div>
  <?php if (!$adminCredentials): ?>
    <p class="partner-note">Noch keine Credentials.</p>
  <?php else: ?>
    <div class="backoffice-list">
      <?php foreach ($adminCredentials as $credential): ?>
        <article>
          <div>
            <strong><?= mmEscape((string)$credential['display_name']) ?> · <?= mmEscape((string)$credential['credential_code']) ?></strong>
            <small><?= mmEscape(mmCredentialTypeLabel((string)$credential['credential_type'])) ?> · <?= mmEscape((string)($credential['email'] ?? '')) ?></small>
          </div>
          <?= mmBackofficeStatusBadge((string)$credential['credential_status']) ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="section-head credential-admin-orders-head"><h3>Offene Ausgabe-/Ausstattungsanfragen</h3></div>
  <?php if (!$adminOrders): ?>
    <p class="partner-note">Noch keine Anfragen.</p>
  <?php else: ?>
    <div class="backoffice-list">
      <?php foreach ($adminOrders as $order): ?>
        <article>
          <div>
            <strong><?= mmEscape((string)$order['display_name']) ?> · <?= mmEscape(mmCredentialOrderLabel((string)$order['order_channel'])) ?></strong>
            <small><?= mmEscape((string)$order['credential_code']) ?> · <?= mmEscape((string)$order['requested_at']) ?></small>
          </div>
          <?= mmBackofficeStatusBadge((string)$order['order_status']) ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php mmFooter(); ?>
