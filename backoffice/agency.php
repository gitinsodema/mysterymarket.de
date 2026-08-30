<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

$user=mmBackofficeRequireLogin('admin');
$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
if($id<1){http_response_code(404);exit('Not found');}
$error='';

if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
  if(!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))){
    http_response_code(400);$error='Ungültige Sitzung.';
  } else {
    $name=trim((string)($_POST['name'] ?? ''));
    $short=trim((string)($_POST['short_name'] ?? ''));
    $website=trim((string)($_POST['website_url'] ?? ''));
    $active=isset($_POST['is_active'])?1:0;
    if($name===''){$error='Name ist erforderlich.';}
    elseif($website!==''&&!filter_var($website,FILTER_VALIDATE_URL)){$error='Website ist ungültig.';}
    else{
      $stmt=mmDb()->prepare(
        'UPDATE agencies
         SET name=:name, short_name=:short_name, website_url=:website_url, is_active=:is_active, updated_at=NOW()
         WHERE id=:id'
      );
      $stmt->execute([
        'name'=>$name,'short_name'=>$short!==''?$short:null,
        'website_url'=>$website!==''?$website:null,'is_active'=>$active,'id'=>$id
      ]);
      mmBackofficeAudit((int)$user['id'],'agency.updated','agency',$id,['is_active'=>$active]);
      header('Location: /backoffice/agency.php?id='.$id.'&updated=1',true,303);exit;
    }
  }
}

$stmt=mmDb()->prepare('SELECT id,name,short_name,website_url,is_active,created_at,updated_at FROM agencies WHERE id=:id LIMIT 1');
$stmt->execute(['id'=>$id]);$row=$stmt->fetch();
if(!$row){http_response_code(404);exit('Not found');}

mmHeader('Agentur','Agentur-Stammdaten verwalten.','noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero"><div><p class="eyebrow">Admin · Agenturen</p><h1><?= mmEscape((string)$row['name']) ?></h1><div class="actions"><a class="button secondary" href="/backoffice/agencies.php">Zurück</a></div></div></section>
<section class="section">
  <div class="form-card">
    <?php if(isset($_GET['updated'])): ?><div class="alert success"><strong>Gespeichert.</strong></div><?php endif; ?>
    <?php if($error!==''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/agency.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <label>Name<input name="name" maxlength="200" required value="<?= mmEscape((string)$row['name']) ?>"></label>
      <label>Kurzname<input name="short_name" maxlength="120" value="<?= mmEscape((string)($row['short_name'] ?? '')) ?>"></label>
      <label>Website<input type="url" name="website_url" maxlength="500" value="<?= mmEscape((string)($row['website_url'] ?? '')) ?>"></label>
      <label class="privacy-check"><input type="checkbox" name="is_active" value="1"<?= (int)$row['is_active']===1?' checked':'' ?>> Aktiv</label>
      <button type="submit">Speichern</button>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
