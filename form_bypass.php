<?php
// form_bypass.php
header('Content-Type: text/html; charset=UTF-8');

$tpl = __DIR__ . '/form.html';
echo "<h1>form_bypass.php</h1>";
echo "<p>PHP: ".phpversion()." (".php_sapi_name().")</p>";
echo "<p>DOCUMENT_ROOT: ".htmlspecialchars($_SERVER['DOCUMENT_ROOT'], ENT_QUOTES, 'UTF-8')."</p>";
echo "<p>template path: ".htmlspecialchars($tpl, ENT_QUOTES, 'UTF-8')."</p>";

if (!is_file($tpl)) { echo "<p style='color:red'>form.html が見つかりません。</p>"; exit; }
if (!is_readable($tpl)) { echo "<p style='color:red'>form.html が読み取れません（権限）。</p>"; exit; }

echo "<hr><h2>以下、form.html の中身をそのまま include します</h2>";
include $tpl;

echo "<hr><p>-- include 完了 --</p>";
