<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/db.php';

$base = mmPageCopy('verify');
$verifyLang = strtolower(trim((string)($_GET['verify_lang'] ?? mmLanguage())));
if (!in_array($verifyLang, ['de','en','nl','tr','ar'], true)) {
    $verifyLang = mmLanguage();
}

$extra = [
    'en' => [
        'title'=>'Audit Verification',
        'hero'=>'Verify an audit or authorisation.',
        'lead'=>'Were you contacted about an audit or is an auditor on site? Verify the supplied MysteryMarket reference here.',
        'invalid'=>'Please enter a valid verification reference.',
        'missing'=>'No active and currently valid verification record was found for this reference.',
        'unavailable'=>'Verification is currently unavailable. Please contact MysteryMarket directly.',
        'verified'=>'Verified · active record',
        'conf'=>'This audit programme is confidential. Client and project details are not displayed publicly.',
        'partner'=>'Audit Partner',
        'client'=>'Client',
        'valid'=>'Validity',
        'label'=>'Verification Code / Reference',
        'button'=>'Verify',
    ],
    'nl' => [
        'title'=>'Audit Verification',
        'hero'=>'Audit of legitimatie controleren.',
        'lead'=>'Bent u benaderd over een audit of is er een auditor op locatie? Controleer hier de opgegeven MysteryMarket-referentie.',
        'invalid'=>'Voer een geldige verificatiereferentie in.',
        'missing'=>'Voor deze referentie is geen actieve en momenteel geldige verificatie gevonden.',
        'unavailable'=>'Verificatie is momenteel niet beschikbaar. Neem rechtstreeks contact op met MysteryMarket.',
        'verified'=>'Geverifieerd · actief dossier',
        'conf'=>'Dit auditprogramma is vertrouwelijk. Opdrachtgever- en projectdetails worden niet openbaar weergegeven.',
        'partner'=>'Audit Partner',
        'client'=>'Klant',
        'valid'=>'Geldigheid',
        'label'=>'Verification Code / Referentie',
        'button'=>'Verifiëren',
    ],
    'tr' => [
        'title'=>'Audit Doğrulama',
        'hero'=>'Denetimi veya yetkilendirmeyi doğrulayın.',
        'lead'=>'Bir denetim için sizinle iletişime mi geçildi veya sahada bir denetçi mi var? MysteryMarket referansını burada doğrulayabilirsiniz.',
        'invalid'=>'Lütfen geçerli bir doğrulama referansı girin.',
        'missing'=>'Bu referans için aktif ve geçerli bir doğrulama kaydı bulunamadı.',
        'unavailable'=>'Doğrulama şu anda kullanılamıyor. Lütfen MysteryMarket ile doğrudan iletişime geçin.',
        'verified'=>'Doğrulandı · aktif kayıt',
        'conf'=>'Bu denetim programı gizlidir. Müşteri ve proje ayrıntıları kamuya açık olarak gösterilmez.',
        'partner'=>'Audit Partner',
        'client'=>'Müşteri',
        'valid'=>'Geçerlilik',
        'label'=>'Doğrulama Kodu / Referans',
        'button'=>'Doğrula',
    ],
    'ar' => [
        'title'=>'التحقق من التدقيق',
        'hero'=>'تحقق من التدقيق أو التفويض.',
        'lead'=>'هل تم التواصل معك بخصوص تدقيق أو يوجد مدقق في الموقع؟ يمكنك هنا التحقق من مرجع MysteryMarket.',
        'invalid'=>'يرجى إدخال مرجع تحقق صالح.',
        'missing'=>'لم يتم العثور على سجل تحقق نشط وساري لهذا المرجع.',
        'unavailable'=>'خدمة التحقق غير متاحة حالياً. يرجى التواصل مباشرة مع MysteryMarket.',
        'verified'=>'تم التحقق · سجل نشط',
        'conf'=>'برنامج التدقيق هذا سري. لا يتم عرض بيانات العميل أو تفاصيل المشروع للعامة.',
        'partner'=>'شريك التدقيق',
        'client'=>'العميل',
        'valid'=>'الصلاحية',
        'label'=>'رمز التحقق / المرجع',
        'button'=>'تحقق',
    ],
];

$c = $extra[$verifyLang] ?? $base;
$scanCopy = [
    'de' => [
        'title' => 'QR-Code scannen',
        'text' => 'Scannen Sie den MysteryMarket-QR-Code des Auditors. Die Kameraauswertung erfolgt lokal in Ihrem Browser; Kamerabilder werden nicht an MysteryMarket übertragen.',
        'start' => 'Kamera öffnen',
        'stop' => 'Scanner schließen',
        'ready' => 'QR-Code in den markierten Bereich halten.',
        'invalid' => 'Der gelesene QR-Code enthält keine gültige MysteryMarket-Referenz.',
        'camera' => 'Die Kamera konnte nicht geöffnet werden. Bitte prüfen Sie die Kameraberechtigung oder geben Sie die Referenz manuell ein.',
        'found' => 'Referenz erkannt. Verifikation wird gestartet …',
        'manual' => 'Alternativ können Sie die Referenz weiterhin manuell eingeben.',
    ],
    'en' => [
        'title' => 'Scan QR code',
        'text' => 'Scan the auditor’s MysteryMarket QR code. Camera processing takes place locally in your browser; camera images are not transmitted to MysteryMarket.',
        'start' => 'Open camera',
        'stop' => 'Close scanner',
        'ready' => 'Hold the QR code inside the marked area.',
        'invalid' => 'The scanned QR code does not contain a valid MysteryMarket reference.',
        'camera' => 'The camera could not be opened. Please check camera permission or enter the reference manually.',
        'found' => 'Reference detected. Starting verification …',
        'manual' => 'You can still enter the reference manually instead.',
    ],
    'nl' => [
        'title' => 'QR-code scannen',
        'text' => 'Scan de MysteryMarket-QR-code van de auditor. De cameraverwerking gebeurt lokaal in uw browser; camerabeelden worden niet naar MysteryMarket verzonden.',
        'start' => 'Camera openen',
        'stop' => 'Scanner sluiten',
        'ready' => 'Houd de QR-code binnen het gemarkeerde gebied.',
        'invalid' => 'De gescande QR-code bevat geen geldige MysteryMarket-referentie.',
        'camera' => 'De camera kon niet worden geopend. Controleer de cameratoestemming of voer de referentie handmatig in.',
        'found' => 'Referentie herkend. Verificatie wordt gestart …',
        'manual' => 'U kunt de referentie ook handmatig blijven invoeren.',
    ],
    'tr' => [
        'title' => 'QR kodunu tara',
        'text' => 'Denetçinin MysteryMarket QR kodunu tarayın. Kamera görüntüsü yalnızca tarayıcınızda yerel olarak işlenir; görüntüler MysteryMarket’e gönderilmez.',
        'start' => 'Kamerayı aç',
        'stop' => 'Tarayıcıyı kapat',
        'ready' => 'QR kodunu işaretli alanın içinde tutun.',
        'invalid' => 'Okunan QR kodu geçerli bir MysteryMarket referansı içermiyor.',
        'camera' => 'Kamera açılamadı. Kamera iznini kontrol edin veya referansı manuel olarak girin.',
        'found' => 'Referans algılandı. Doğrulama başlatılıyor …',
        'manual' => 'İsterseniz referansı manuel olarak da girebilirsiniz.',
    ],
    'ar' => [
        'title' => 'مسح رمز QR',
        'text' => 'امسح رمز MysteryMarket QR الخاص بالمدقق. تتم معالجة صورة الكاميرا محلياً داخل المتصفح ولا يتم إرسال صور الكاميرا إلى MysteryMarket.',
        'start' => 'فتح الكاميرا',
        'stop' => 'إغلاق الماسح',
        'ready' => 'ضع رمز QR داخل المنطقة المحددة.',
        'invalid' => 'رمز QR المقروء لا يحتوي على مرجع MysteryMarket صالح.',
        'camera' => 'تعذر فتح الكاميرا. يرجى التحقق من إذن الكاميرا أو إدخال المرجع يدوياً.',
        'found' => 'تم التعرف على المرجع. جارٍ بدء التحقق …',
        'manual' => 'يمكنك أيضاً إدخال المرجع يدوياً.',
    ],
][$verifyLang] ?? [];

$detailCopy = [
    'de' => [
        'identity'=>'Legitimierte Person','role'=>'Rolle','agency'=>'Beauftragte Agentur','project'=>'Projekt / Prüfkontext',
        'scope'=>'Umfang der Berechtigung','restriction'=>'Wichtige Einschränkung',
        'restriction_text'=>'Die Berechtigung ist auf Vodafone-relevante Prozesse und Datenverarbeitungen beschränkt. Eine Prüfung sonstiger Geschäftstätigkeiten des Untervertriebspartners ist nicht umfasst.',
        'scope_items'=>[
            'Durchführung von Vor-Ort-Kontrollen und Prüfgesprächen im Umfeld der Vodafone-Vermarktung.',
            'Einsichtnahme in für Vodafone relevante Prozesse, Nachweise und Dokumentationen.',
            'Prüfung der weitergegebenen Datenschutz- und Compliance-Vorgaben sowie der Prozess- und Qualitätsanforderungen von Vodafone.',
        ],
        'document'=>'Offizieller Legitimationsnachweis','document_open'=>'Legitimationsschreiben ansehen',
        'reference'=>'Verify-Referenz','status'=>'Legitimation gültig','photo_alt'=>'Foto der legitimierten Person',
        'logo_alt'=>'Projektlogo','partner_logo_alt'=>'Logo der beauftragten Agentur',
        'print'=>'Ausweisansicht vorbereiten',
    ],
    'en' => [
        'identity'=>'Authorised person','role'=>'Role','agency'=>'Commissioned agency','project'=>'Project / audit context',
        'scope'=>'Scope of authorisation','restriction'=>'Important limitation',
        'restriction_text'=>'The authorisation is limited to Vodafone-related processes and data processing. It does not include an audit of other business activities of the subcontracting sales partner.',
        'scope_items'=>[
            'On-site checks and audit discussions in the context of Vodafone marketing.',
            'Inspection of Vodafone-relevant processes, evidence and documentation.',
            'Verification of applicable data-protection and compliance requirements as well as Vodafone process and quality requirements.',
        ],
        'document'=>'Official authorisation document','document_open'=>'View authorisation letter',
        'reference'=>'Verify reference','status'=>'Authorisation valid','photo_alt'=>'Photo of the authorised person',
        'logo_alt'=>'Project logo','partner_logo_alt'=>'Logo of the commissioned agency',
        'print'=>'Prepare ID card view',
    ],
    'nl' => [
        'identity'=>'Geleitimeerde persoon','role'=>'Rol','agency'=>'Opdrachtgevend bureau','project'=>'Project / auditcontext',
        'scope'=>'Omvang van de bevoegdheid','restriction'=>'Belangrijke beperking',
        'restriction_text'=>'De bevoegdheid is beperkt tot Vodafone-gerelateerde processen en gegevensverwerking. Controle van andere bedrijfsactiviteiten van de onderverkooppartner valt hier niet onder.',
        'scope_items'=>[
            'Controles op locatie en auditgesprekken in de context van Vodafone-verkoop.',
            'Inzage in voor Vodafone relevante processen, bewijsstukken en documentatie.',
            'Controle van toepasselijke privacy- en compliancevereisten en van proces- en kwaliteitseisen van Vodafone.',
        ],
        'document'=>'Officieel legitimatiebewijs','document_open'=>'Legitimatiebrief bekijken',
        'reference'=>'Verify-referentie','status'=>'Legitimatie geldig','photo_alt'=>'Foto van de gelegitimeerde persoon',
        'logo_alt'=>'Projectlogo','partner_logo_alt'=>'Logo van het opdrachtgevende bureau',
        'print'=>'ID-kaartweergave voorbereiden',
    ],
    'tr' => [
        'identity'=>'Yetkilendirilmiş kişi','role'=>'Rol','agency'=>'Görevlendirilen ajans','project'=>'Proje / denetim kapsamı',
        'scope'=>'Yetki kapsamı','restriction'=>'Önemli sınırlama',
        'restriction_text'=>'Yetki yalnızca Vodafone ile ilgili süreçler ve veri işleme faaliyetleriyle sınırlıdır. Alt satış ortağının diğer ticari faaliyetlerinin denetimini kapsamaz.',
        'scope_items'=>[
            'Vodafone pazarlama ortamında yerinde kontroller ve denetim görüşmeleri.',
            'Vodafone açısından ilgili süreç, kanıt ve belgelerin incelenmesi.',
            'İlgili veri koruma ve uyum gereklilikleri ile Vodafone süreç ve kalite gerekliliklerinin kontrolü.',
        ],
        'document'=>'Resmî yetkilendirme belgesi','document_open'=>'Yetkilendirme yazısını görüntüle',
        'reference'=>'Verify referansı','status'=>'Yetkilendirme geçerli','photo_alt'=>'Yetkilendirilmiş kişinin fotoğrafı',
        'logo_alt'=>'Proje logosu','partner_logo_alt'=>'Görevlendirilen ajansın logosu',
        'print'=>'Kimlik kartı görünümünü hazırla',
    ],
    'ar' => [
        'identity'=>'الشخص المخول','role'=>'الصفة','agency'=>'الوكالة المكلفة','project'=>'المشروع / نطاق التدقيق',
        'scope'=>'نطاق التفويض','restriction'=>'قيد مهم',
        'restriction_text'=>'يقتصر التفويض على العمليات ومعالجة البيانات ذات الصلة بـ Vodafone، ولا يشمل فحص الأنشطة التجارية الأخرى لشريك التوزيع الفرعي.',
        'scope_items'=>[
            'إجراء عمليات تفتيش ميدانية ومحادثات تدقيق في سياق تسويق Vodafone.',
            'الاطلاع على العمليات والأدلة والوثائق ذات الصلة بـ Vodafone.',
            'فحص متطلبات حماية البيانات والامتثال المعمول بها ومتطلبات العمليات والجودة الخاصة بـ Vodafone.',
        ],
        'document'=>'وثيقة التفويض الرسمية','document_open'=>'عرض خطاب التفويض',
        'reference'=>'مرجع التحقق','status'=>'التفويض ساري','photo_alt'=>'صورة الشخص المخول',
        'logo_alt'=>'شعار المشروع','partner_logo_alt'=>'شعار الوكالة المكلفة',
        'print'=>'إعداد عرض بطاقة الهوية',
    ],
][$verifyLang] ?? [];

$result = null;
$error = null;
$code = '';

$getCode = strtoupper(trim((string)($_GET['code'] ?? '')));
if ($getCode !== '' && preg_match('/^[A-Z0-9-]{4,64}$/', $getCode)) {
    $code = $getCode;
}

mmStartSecureSession();
$_SESSION['mm_verify_attempts'] ??= [];
$now = time();
$_SESSION['mm_verify_attempts'] = array_values(array_filter(
    (array)$_SESSION['mm_verify_attempts'],
    static fn($ts): bool => is_int($ts) && $ts > $now - 600
));

$autoVerify = $_SERVER['REQUEST_METHOD'] === 'GET' && $getCode !== '' && preg_match('/^[A-Z0-9-]{4,64}$/', $getCode);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $autoVerify) {
    $rateLimited = count($_SESSION['mm_verify_attempts']) >= 12;
    $ipHash = mmClientIpHash();

    if (!$rateLimited && $ipHash !== '') {
        try {
            $pdo = mmDb();
            $pdo->exec("DELETE FROM verify_rate_limits WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");

            $limitStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM verify_rate_limits
                 WHERE ip_hash = :ip_hash
                   AND attempted_at > (NOW() - INTERVAL 10 MINUTE)'
            );
            $limitStmt->execute(['ip_hash' => $ipHash]);
            $rateLimited = ((int)$limitStmt->fetchColumn()) >= 12;
        } catch (Throwable $e) {
            $rateLimited = count($_SESSION['mm_verify_attempts']) >= 12;
        }
    }

    if ($rateLimited) {
        http_response_code(429);
        $error = $verifyLang === 'de'
            ? 'Zu viele Verifikationsversuche. Bitte versuchen Sie es in einigen Minuten erneut.'
            : ($verifyLang === 'tr'
                ? 'Çok fazla doğrulama denemesi. Lütfen birkaç dakika sonra tekrar deneyin.'
                : ($verifyLang === 'ar'
                    ? 'عدد محاولات التحقق كبير جداً. يرجى المحاولة مرة أخرى بعد بضع دقائق.'
                    : ($verifyLang === 'nl'
                        ? 'Te veel verificatiepogingen. Probeer het over enkele minuten opnieuw.'
                        : 'Too many verification attempts. Please try again in a few minutes.')));
    } else {
        $_SESSION['mm_verify_attempts'][] = $now;

        if ($ipHash !== '') {
            try {
                $attemptStmt = mmDb()->prepare(
                    'INSERT INTO verify_rate_limits (ip_hash, attempted_at) VALUES (:ip_hash, NOW())'
                );
                $attemptStmt->execute(['ip_hash' => $ipHash]);
            } catch (Throwable $e) {
                // Session-based limiting remains active if persistent limiting is temporarily unavailable.
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        }

        if ($code === '' || strlen($code) > 64) {
            $error = $c['invalid'];
        } else {
            try {
                $stmt = mmDb()->prepare(
                    'SELECT reference_code, public_title, public_partner, public_client, valid_from, valid_until, confidentiality_mode, public_note,
                            person_name, role_label, agency_name, project_name, brand_name,
                            photo_asset, brand_logo_asset, agency_logo_asset, scope_key,
                            document_asset, document_label, document_enabled, print_card_enabled, is_personal_verification
                     FROM audit_verifications
                     WHERE reference_code = :code
                       AND is_active = 1
                       AND (valid_from IS NULL OR valid_from <= CURRENT_DATE())
                       AND (valid_until IS NULL OR valid_until >= CURRENT_DATE())
                     LIMIT 1'
                );
                $stmt->execute(['code' => $code]);
                $row = $stmt->fetch();
                if ($row) {
                    $result = $row;
                    $_SESSION['mm_verified_records'] ??= [];
                    $_SESSION['mm_verified_records'][(string)$row['reference_code']] = time();
                } else {
                    $error = $c['missing'];
                }
            } catch (Throwable $e) {
                $error = $c['unavailable'];
            }
        }
    }
}

$verifyRobots = in_array($verifyLang, ['tr','ar'], true) || (($result['is_personal_verification'] ?? 0) == 1)
    ? 'noindex,follow'
    : 'index,follow';
mmHeader($c['title'], $c['lead'], $verifyRobots, $verifyLang);
$rtl = $verifyLang === 'ar';
?>
<section class="hero">
  <div>
    <p class="eyebrow">Audit Verification</p>
    <h1><?= mmEscape($c['hero']) ?></h1>
    <p class="lead"><?= mmEscape($c['lead']) ?></p>
  </div>
  <div class="verify-language" aria-label="Verification language">
    <span>Verify language</span>
    <?php foreach (['de'=>'DE','en'=>'EN','nl'=>'NL','tr'=>'TR','ar'=>'AR'] as $key => $label): ?>
      <?php $globalLang = in_array($key, ['de','en','nl'], true) ? $key : mmLanguage(); ?>
      <?php $langHref = '/verify.php?lang=' . rawurlencode($globalLang) . '&verify_lang=' . rawurlencode($key) . ($code !== '' ? '&code=' . rawurlencode($code) : ''); ?>
      <a href="<?= mmEscape($langHref) ?>"<?= $verifyLang === $key ? ' aria-current="page"' : '' ?>><?= mmEscape($label) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<section class="section"<?= $rtl ? ' dir="rtl"' : '' ?>>
  <div class="verify-layout">
  <div class="verify-box">
    <?php if ($error): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <?php if ($result): ?>
      <?php $isPersonal = ((int)($result['is_personal_verification'] ?? 0)) === 1; ?>
      <?php if ($isPersonal): ?>
        <article class="verify-credential">
          <div class="verify-credential-head">
            <div class="verify-brand-lockup">
              <?php if (!empty($result['brand_logo_asset'])): ?>
                <img src="/verify-asset.php?code=<?= rawurlencode((string)$result['reference_code']) ?>&type=brand_logo" alt="<?= mmEscape($detailCopy['logo_alt']) ?>">
              <?php else: ?>
                <strong><?= mmEscape((string)($result['brand_name'] ?: $result['public_client'])) ?></strong>
              <?php endif; ?>
              <span><?= mmEscape($detailCopy['status']) ?></span>
            </div>
            <span class="status"><?= mmEscape($c['verified']) ?></span>
          </div>

          <div class="verify-person">
            <?php if (!empty($result['photo_asset'])): ?>
              <img class="verify-person-photo" src="/verify-asset.php?code=<?= rawurlencode((string)$result['reference_code']) ?>&type=photo" alt="<?= mmEscape($detailCopy['photo_alt']) ?>">
            <?php endif; ?>
            <div>
              <small><?= mmEscape($detailCopy['identity']) ?></small>
              <h2><?= mmEscape((string)$result['person_name']) ?></h2>
              <?php if (!empty($result['role_label'])): ?><p class="verify-role"><?= mmEscape((string)$result['role_label']) ?></p><?php endif; ?>
              <p><strong><?= mmEscape($detailCopy['reference']) ?>:</strong> <?= mmEscape((string)$result['reference_code']) ?></p>
              <?php if (!empty($result['valid_until'])): ?><p><strong><?= mmEscape($c['valid']) ?>:</strong> <?= mmEscape((string)$result['valid_until']) ?></p><?php endif; ?>
            </div>
          </div>

          <div class="verify-context-grid">
            <div><small><?= mmEscape($detailCopy['agency']) ?></small>
              <?php if (!empty($result['agency_logo_asset'])): ?><img class="verify-partner-logo" src="/verify-asset.php?code=<?= rawurlencode((string)$result['reference_code']) ?>&type=agency_logo" alt="<?= mmEscape($detailCopy['partner_logo_alt']) ?>"><?php endif; ?>
              <strong><?= mmEscape((string)($result['agency_name'] ?: $result['public_partner'])) ?></strong>
            </div>
            <div><small><?= mmEscape($detailCopy['project']) ?></small><strong><?= mmEscape((string)($result['project_name'] ?: $result['public_title'])) ?></strong></div>
          </div>

          <?php if (($result['scope_key'] ?? '') === 'vodafone_skopos_2026'): ?>
            <div class="verify-scope">
              <h3><?= mmEscape($detailCopy['scope']) ?></h3>
              <ul><?php foreach ($detailCopy['scope_items'] as $item): ?><li><?= mmEscape($item) ?></li><?php endforeach; ?></ul>
              <div class="notice"><strong><?= mmEscape($detailCopy['restriction']) ?></strong><p><?= mmEscape($detailCopy['restriction_text']) ?></p></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($result['public_note'])): ?><p><?= mmEscape((string)$result['public_note']) ?></p><?php endif; ?>

          <div class="actions">
            <?php if (!empty($result['document_enabled']) && !empty($result['document_asset'])): ?>
              <a class="button" href="/verify-asset.php?code=<?= rawurlencode((string)$result['reference_code']) ?>&type=document" target="_blank" rel="noopener" title="<?= mmEscape((string)($result['document_label'] ?: $detailCopy['document'])) ?>"><?= mmEscape((string)($result['document_label'] ?: $detailCopy['document_open'])) ?></a>
            <?php endif; ?>
            <?php if (!empty($result['print_card_enabled'])): ?>
              <a class="button secondary" href="/verify-card.php?code=<?= rawurlencode((string)$result['reference_code']) ?>"><?= mmEscape($detailCopy['print']) ?></a>
            <?php endif; ?>
          </div>
        </article>
      <?php else: ?>
        <div class="alert success">
          <strong><?= mmEscape($c['verified']) ?></strong>
          <p><?= mmEscape((string)$result['public_title']) ?></p>
          <?php if (($result['confidentiality_mode'] ?? '') === 'confidential'): ?>
            <p><?= mmEscape($c['conf']) ?></p>
          <?php else: ?>
            <?php if (!empty($result['public_partner'])): ?><p><strong><?= mmEscape($c['partner']) ?>:</strong> <?= mmEscape((string)$result['public_partner']) ?></p><?php endif; ?>
            <?php if (!empty($result['public_client'])): ?><p><strong><?= mmEscape($c['client']) ?>:</strong> <?= mmEscape((string)$result['public_client']) ?></p><?php endif; ?>
          <?php endif; ?>
          <?php if (!empty($result['valid_from']) || !empty($result['valid_until'])): ?><p><strong><?= mmEscape($c['valid']) ?>:</strong> <?= mmEscape((string)($result['valid_from'] ?: '—')) ?> – <?= mmEscape((string)($result['valid_until'] ?: '—')) ?></p><?php endif; ?>
          <?php if (!empty($result['public_note'])): ?><p><?= mmEscape((string)$result['public_note']) ?></p><?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <form method="post" action="/verify.php?lang=<?= mmEscape(mmLanguage()) ?>&verify_lang=<?= mmEscape($verifyLang) ?>">
      <label><?= mmEscape($c['label']) ?>
        <input name="code" maxlength="64" autocomplete="off" placeholder="MM-26-XXXX" value="<?= mmEscape($code) ?>" required>
      </label>
      <button type="submit"><?= mmEscape($c['button']) ?></button>
    </form>
  </div>

  <aside class="verify-scanner-card">
    <p class="eyebrow"><?= mmEscape($scanCopy['title']) ?></p>
    <h2><?= mmEscape($scanCopy['title']) ?></h2>
    <p><?= mmEscape($scanCopy['text']) ?></p>
    <div class="verify-scanner" data-verify-scanner
         data-ready="<?= mmEscape($scanCopy['ready']) ?>"
         data-invalid="<?= mmEscape($scanCopy['invalid']) ?>"
         data-camera="<?= mmEscape($scanCopy['camera']) ?>"
         data-found="<?= mmEscape($scanCopy['found']) ?>">
      <div class="verify-video-wrap" hidden data-verify-video-wrap>
        <video data-verify-video playsinline muted></video>
        <span class="verify-scan-frame" aria-hidden="true"></span>
      </div>
      <p class="verify-scan-status" data-verify-scan-status aria-live="polite"></p>
      <div class="actions">
        <button type="button" data-verify-scan-start><?= mmEscape($scanCopy['start']) ?></button>
        <button type="button" class="button secondary" data-verify-scan-stop hidden><?= mmEscape($scanCopy['stop']) ?></button>
      </div>
      <p class="verify-scan-manual"><?= mmEscape($scanCopy['manual']) ?></p>
    </div>
  </aside>
  </div>
</section>
<script type="module" src="/public/js/verify-qr.js"></script>
<?php mmFooter(); ?>
