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

$c = $extra[$verifyLang] ?? ($verifyLang === mmLanguage() ? $base : $base);
$result = null;
$error = null;
$code = '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['mm_verify_attempts'] ??= [];
$now = time();
$_SESSION['mm_verify_attempts'] = array_values(array_filter(
    (array)$_SESSION['mm_verify_attempts'],
    static fn($ts): bool => is_int($ts) && $ts > $now - 600
));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (count($_SESSION['mm_verify_attempts']) >= 12) {
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
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        if ($code === '' || strlen($code) > 64) {
            $error = $c['invalid'];
        } else {
            try {
                $stmt = mmDb()->prepare(
                    'SELECT reference_code, public_title, public_partner, public_client, valid_from, valid_until, confidentiality_mode, public_note
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
                } else {
                    $error = $c['missing'];
                }
            } catch (Throwable $e) {
                $error = $c['unavailable'];
            }
        }
    }
}

mmHeader($c['title'], $c['lead']);
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
      <a href="/verify.php?verify_lang=<?= mmEscape($key) ?>"<?= $verifyLang === $key ? ' aria-current="page"' : '' ?>><?= mmEscape($label) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<section class="section"<?= $rtl ? ' dir="rtl"' : '' ?>>
  <div class="verify-box">
    <?php if ($error): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <?php if ($result): ?>
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
    <form method="post" action="/verify.php?verify_lang=<?= mmEscape($verifyLang) ?>">
      <label><?= mmEscape($c['label']) ?>
        <input name="code" maxlength="64" autocomplete="off" placeholder="MM-26-XXXX" value="<?= mmEscape($code) ?>" required>
      </label>
      <button type="submit"><?= mmEscape($c['button']) ?></button>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
