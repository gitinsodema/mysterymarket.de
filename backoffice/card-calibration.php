<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CR80 Druckkalibrierung · MysteryMarket</title>
<style>
:root{
  --card-w:85.60mm;
  --card-h:53.98mm;
}
*{box-sizing:border-box}
html,body{margin:0}
body{
  min-height:100vh;
  background:#eef2f6;
  color:#001950;
  font-family:Arial,sans-serif;
}
.calibration-shell{
  min-height:100vh;
  display:grid;
  place-items:center;
  padding:24px;
}
.calibration-wrap{display:grid;gap:18px;justify-items:center}
.calibration-card{
  width:var(--card-w);
  height:var(--card-h);
  position:relative;
  overflow:hidden;
  background:#fff;
  border:0.25mm solid #001950;
  box-shadow:0 8px 30px rgba(0,25,80,.15);
}
.calibration-card::before{
  content:"";
  position:absolute;
  inset:2mm;
  border:0.20mm dashed #008c96;
}
.calibration-center-v,
.calibration-center-h{
  position:absolute;
  background:#be001e;
  opacity:.75;
}
.calibration-center-v{
  width:.20mm;
  height:100%;
  left:50%;
  top:0;
}
.calibration-center-h{
  height:.20mm;
  width:100%;
  top:50%;
  left:0;
}
.calibration-reference{
  position:absolute;
  left:50%;
  top:50%;
  width:20mm;
  height:20mm;
  transform:translate(-50%,-50%);
  border:.25mm solid #001950;
  display:grid;
  place-items:center;
  font-size:2.2mm;
  font-weight:700;
  text-align:center;
  line-height:1.2;
  background:#fff;
}
.calibration-reference::after{
  content:"";
  width:10mm;
  height:10mm;
  border:.25mm solid #008c96;
  border-radius:50%;
  position:absolute;
}
.calibration-label{
  position:absolute;
  font-size:1.65mm;
  font-weight:700;
  line-height:1;
  background:#fff;
  padding:.45mm .7mm;
}
.label-top{top:1mm;left:50%;transform:translateX(-50%)}
.label-bottom{bottom:1mm;left:50%;transform:translateX(-50%)}
.label-left{left:1mm;top:50%;transform:translateY(-50%) rotate(-90deg)}
.label-right{right:1mm;top:50%;transform:translateY(-50%) rotate(90deg)}
.calibration-corner{
  position:absolute;
  width:6mm;
  height:6mm;
}
.calibration-corner::before,
.calibration-corner::after{
  content:"";
  position:absolute;
  background:#001950;
}
.calibration-corner::before{width:6mm;height:.25mm;top:3mm;left:0}
.calibration-corner::after{height:6mm;width:.25mm;left:3mm;top:0}
.corner-tl{left:4mm;top:4mm}
.corner-tr{right:4mm;top:4mm}
.corner-bl{left:4mm;bottom:4mm}
.corner-br{right:4mm;bottom:4mm}
.calibration-scale-x{
  position:absolute;
  left:5mm;
  right:5mm;
  bottom:6.5mm;
  height:2mm;
  background:repeating-linear-gradient(
    to right,
    #001950 0,
    #001950 .20mm,
    transparent .20mm,
    transparent 5mm
  );
}
.calibration-scale-y{
  position:absolute;
  top:5mm;
  bottom:5mm;
  left:6.5mm;
  width:2mm;
  background:repeating-linear-gradient(
    to bottom,
    #001950 0,
    #001950 .20mm,
    transparent .20mm,
    transparent 5mm
  );
}
.calibration-id{
  position:absolute;
  right:4mm;
  bottom:4mm;
  font-size:1.45mm;
  color:#52617a;
  text-align:right;
}
.calibration-note{
  max-width:720px;
  background:#fff;
  border:1px solid #d8dee8;
  border-radius:12px;
  padding:18px 20px;
  line-height:1.5;
}
.calibration-note h1{margin:0 0 8px;font-size:22px}
.calibration-note p{margin:5px 0}
.calibration-note strong{color:#001950}
.calibration-actions{
  display:flex;
  gap:10px;
  justify-content:center;
  flex-wrap:wrap;
}
button,a{
  appearance:none;
  border:0;
  border-radius:9px;
  padding:11px 15px;
  font:700 14px/1 Arial,sans-serif;
  text-decoration:none;
  cursor:pointer;
}
button{background:#001950;color:#fff}
a{background:#fff;color:#001950;border:1px solid #cbd5e1}
@media print{
  @page{size:85.60mm 53.98mm;margin:0}
  html,body{
    width:85.60mm;
    height:53.98mm;
    background:#fff;
    -webkit-print-color-adjust:exact;
    print-color-adjust:exact;
  }
  .calibration-shell{
    display:block;
    min-height:0;
    padding:0;
  }
  .calibration-wrap{display:block}
  .calibration-card{
    width:85.60mm;
    height:53.98mm;
    border:.25mm solid #001950;
    box-shadow:none;
    break-after:avoid;
  }
  .calibration-note,.calibration-actions{display:none!important}
}
</style>
</head>
<body>
<div class="calibration-shell">
  <div class="calibration-wrap">
    <section class="calibration-card" aria-label="CR80 Kalibrierkarte">
      <div class="calibration-center-v"></div>
      <div class="calibration-center-h"></div>
      <div class="calibration-reference">20 × 20 mm</div>

      <div class="calibration-corner corner-tl"></div>
      <div class="calibration-corner corner-tr"></div>
      <div class="calibration-corner corner-bl"></div>
      <div class="calibration-corner corner-br"></div>

      <div class="calibration-scale-x"></div>
      <div class="calibration-scale-y"></div>

      <div class="calibration-label label-top">85,60 mm</div>
      <div class="calibration-label label-bottom">CR80 · Vorderseite</div>
      <div class="calibration-label label-left">53,98 mm</div>
      <div class="calibration-label label-right">2 mm Safe Frame</div>

      <div class="calibration-id">MysteryMarket<br>CR80 Calibration v1</div>
    </section>

    <div class="calibration-note">
      <h1>CR80 Druckkalibrierung</h1>
      <p><strong>Zielmaß:</strong> 85,60 × 53,98 mm.</p>
      <p>Beim späteren Epson-Test im Druckdialog <strong>100 % / Tatsächliche Größe</strong> verwenden. Keine automatische Anpassung an Seite oder Druckbereich aktivieren.</p>
      <p>Nach dem Testdruck messen wir: linken/rechten/oberen/unteren Versatz, tatsächliche Breite/Höhe sowie Front-/Rückseitenorientierung. Erst danach werden produktive Epson-/Tray-Werte dokumentiert.</p>
    </div>

    <div class="calibration-actions">
      <button type="button" onclick="window.print()">Kalibrierkarte drucken</button>
      <a href="/backoffice/credentials.php">Zurück zu Ausweisen</a>
    </div>
  </div>
</div>
</body>
</html>
