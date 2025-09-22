<?php
// form_diag.php
header('Content-Type: text/plain; charset=UTF-8');

function perms($path){
  $p = @fileperms($path);
  return $p ? substr(sprintf('%o', $p), -4) : '----';
}

echo "=== form_diag.php ===\n";
echo "PHP: ".phpversion()." (".php_sapi_name().")\n";
echo "document_root: ".$_SERVER['DOCUMENT_ROOT']."\n";
echo "cwd: ".getcwd()."\n\n";

// テンプレ候補
$files = ['form.html','form-confirm.html','form-transfer.html'];
foreach ($files as $f) {
  $rp = __DIR__ . '/'.$f;
  echo "File: $f\n";
  echo "  path: $rp\n";
  echo "  exists: ".(is_file($rp)?'yes':'NO')."\n";
  echo "  readable: ".(is_readable($rp)?'yes':'NO')."\n";
  echo "  perms: ".perms($rp)."\n";
  if (is_file($rp) && is_readable($rp)) {
    $first = @file_get_contents($rp, false, null, 0, 200);
    echo "  head(200): ".($first!==false ? "OK (".strlen($first)." bytes)" : "READ FAIL")."\n";
  }
  echo "\n";
}

// ディレクトリ権限
$dir = __DIR__;
echo "Dir perms: ".perms($dir)." (should be 0755)\n\n";

// /tmp書込み確認（sys_get_temp_dir）
$td = sys_get_temp_dir();
echo "sys_get_temp_dir: $td\n";
$tf = @tempnam($td, 'tpl_');
if ($tf === false) {
  echo "tempnam: FAIL (書込み不可 or open_basedir)\n";
} else {
  $ok = @file_put_contents($tf, "test");
  echo "tempnam: OK, write: ".($ok!==false?'OK':'FAIL').", path=$tf\n";
  @unlink($tf);
}
echo "\n";

// mbstring 有無
echo "mb_send_mail: ".(function_exists('mb_send_mail')?'yes':'NO')."\n";
echo "mb_convert_encoding: ".(function_exists('mb_convert_encoding')?'yes':'NO')."\n";

echo "\n=== end ===\n";
