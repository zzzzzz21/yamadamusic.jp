<?php
/**
 * form_new.php – PHP5.6/CGI向け 安定版（mail()使用・常時デバッグ表示）
 *  - テンプレ: form.html / form-confirm.html / form-transfer.html
 *  - include描画、JIS(ISO-2022-JP)で送信、CGIは -f 指定
 *  - ?debug=1 で入力/確認/完了 すべての画面先頭にデバッグ表示
 */

@ini_set('display_errors','0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED);

/* ===== 設定 ===== */
$mail_sys   = "info@yamadamusic.jp";      // 管理者（受信先）
$from_name  = "一般財団法人 山田音楽財団メールフォーム";
$from_mail  = "info@yamadamusic.jp";      // From 表示/Reply-To
$envelope_from = "info@yamadamusic.jp";   // -f 指定（さくらで作成済みの有効アドレス）

$user_mail_field = "item3"; // 自動返信先（メールアドレスの入力項目名）

// テンプレート
$form_html    = "form.html";
$confirm_html = "form-confirm.html";
$finish_html  = "form-transfer.html";

// 件名・本文
$subject_user = "お問い合わせありがとうございます\n";
$body_user    = "お問い合わせありがとうございます。\n以下の内容で承りました。\n\n";
$subject_sys  = "お問い合わせがありました\n";
$body_sys     = "";

$footer = "\n－－－－－－－－－－－－－－\n"
  . "一般財団法人 山田音楽財団\n"
  . "https://yamadamusic.jp/\n"
  . "Tel:03-6311-8792\n"
  . "FAX:03-6311-8793\n"
  . "－－－－－－－－－－－－－－\n";

/* ===== ヘルパ ===== */
function rq($k,$d=null){ return isset($_REQUEST[$k])?$_REQUEST[$k]:$d; }
function value($k){ return isset($_REQUEST[$k])?(is_array($_REQUEST[$k])?implode(",",$_REQUEST[$k]):(string)$_REQUEST[$k]):""; }
function str_has_crlf($s){ return is_string($s) && (strpos($s,"\r")!==false || strpos($s,"\n")!==false); }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ===== フィールド定義 ===== */
$form_input = array(
  "item1" => array("title"=>"ご氏名のフリガナ",   "name"=>"item1", "func"=>"2", "require"=>"1", "check"=>"1"),
  "item2" => array("title"=>"ご氏名　　　　　",   "name"=>"item2", "func"=>"2", "require"=>"1", "check"=>"1"),
  "item3" => array("title"=>"メールアドレス　",   "name"=>"item3", "func"=>"2", "require"=>"1", "check"=>"3"),
  "item4" => array("title"=>"電話番号　　　　",   "name"=>"item4", "func"=>"2", "require"=>"1", "check"=>"2"),
  "item5" => array("title"=>"お問い合わせ内容",   "name"=>"item5", "func"=>"7", "require"=>"1", "check"=>"1"),
);

/* ===== 入力取得・検証 ===== */
$msg=array(); $form=array();
$mail_value=array(); $mail_field=array(); $mail_title=array();

foreach ($form_input as $def) {
  $n=$def['name']; $t=$def['title']; $req=(int)$def['require']; $chk=(int)$def['check'];
  $v=rq($n,""); if(is_array($v)){$v=implode(",",array_map('trim',$v));} $v=trim((string)$v); $form[$n]=$v;

  if (rq('mode')==='form' && $req && $v===''){ $msg[$n]=$t.'が入力されていません。'; continue; }

  if ($v!=='') {
    if ($chk===2 && !preg_match('/^[0-9+\-\s]+$/', $v)) { $msg[$n]=$t.'が正しくありません'; }
    if (($chk===3 || $chk===4)) {
      if (!filter_var($v, FILTER_VALIDATE_EMAIL)) { $msg[$n]=$t.'が正しくありません'; }
      else { $mail_value[]=$v; $mail_field[]=$n; $mail_title[]=$t; }
    }
    if ($chk===1 && function_exists('mb_strlen') && mb_strlen($v,'UTF-8')>5000) { $msg[$n]=$t.'が長すぎます'; }
  }
}
if (count($mail_value)===2 && $mail_value[0]!==$mail_value[1]) {
  $m0=$mail_field[0]; $m1=$mail_field[1]; $t0=$mail_title[0];
  $msg[$m0]=$t0.'が一致していません'; $msg[$m1]=$t0.'が一致していません';
}

/* ===== 画面遷移 ===== */
$mode_req=(string)rq('mode','');
$debug_mode = (isset($_GET['debug']) && $_GET['debug']=='1');
$debug_message = "<div style='background:#eef;border:1px solid #99f;padding:10px;margin:10px 0;'>debug=1 有効（送信処理前なので情報なし）</div>";
$mail_body_for_debug = '';

if ($mode_req==='' || $mode_req==='reinput') {
  $mode='form';
} elseif ($mode_req!=='confirm') {
  $mode = ($msg ? 'form' : 'confirm');
} else {
  // confirm → 送信
  $user_to = isset($form[$user_mail_field]) ? $form[$user_mail_field] : '';

  if (($user_to!=='' && str_has_crlf($user_to)) || str_has_crlf($from_mail) || str_has_crlf($mail_sys)) {
    $msg['mail'] = "メールアドレスに不正な文字が含まれています";
    $mode='form';
  } else {
    // 本文
    $mail_body="";
    foreach ($form_input as $d) {
      $n=$d['name']; $t=$d['title']; $v=isset($form[$n])?(string)$form[$n]:"";
      $mail_body .= "■".$t."：".$v."\n";
    }
    $mail_body .= $footer;
    $mail_body_for_debug = $mail_body; // 画面表示用

    // 送信（管理者＆自動返信）
    $ok_sys=true; $ok_user=true;
    if ($mail_sys)  { $ok_sys  = send_mail_jp($mail_sys,  $subject_sys,  $body_sys.$mail_body,  $from_mail, $from_name, $envelope_from); }
    if ($user_to!==''){ $ok_user = send_mail_jp($user_to, $subject_user, $body_user.$mail_body, $from_mail, $from_name, $envelope_from); }

    // デバッグ枠を更新
    $debug_message  = "<div style='background:#fff3cd;border:1px solid #f9d38f;padding:10px;margin:10px 0;'>";
    $debug_message .= "<strong>送信デバッグ</strong><br>";
    $debug_message .= "管理者宛: ".($ok_sys?'OK':'NG')." / 自動返信: ".($ok_user?'OK':'NG')."<br>";
    $debug_message .= "AdminTo: ".h($mail_sys)." / UserTo: ".h($user_to)."<br>";
    $debug_message .= "Envelope-From(-f): ".h($envelope_from)." / SAPI: ".PHP_SAPI."</div>";

    // 送信内容も見たい場合はここを有効化（長文注意）:
    if ($debug_mode) {
      $debug_message .= "<pre style='white-space:pre-wrap;border:1px dashed #f9d38f;padding:8px;margin:8px 0;'>"
        . h($mail_body_for_debug) . "</pre>";
    }

    $mode = 'finish';
  }
}

/* ===== テンプレ描画 ===== */
$template = ($mode==='confirm')?$confirm_html:(($mode==='finish')?$finish_html:$form_html);

header('Content-Type: text/html; charset=UTF-8');

// 常に debug=1 を先頭表示
if ($debug_mode) {
  echo $debug_message;
}

if (!is_file($template) || !is_readable($template)) {
  echo "<!doctype html><meta charset='utf-8'><h1>テンプレートが見つかりません</h1><p>".h($template)."</p>";
  exit;
}
include $template;

/* ===== メール送信（旧フォーム同等の mail() 方式） ===== */
function send_mail_jp($to, $subject, $body, $from_email, $from_name, $envelope_from){
  if (str_has_crlf($to) || str_has_crlf($subject) || str_has_crlf($from_email) || str_has_crlf($from_name)) return false;

  if (function_exists('mb_language')) @mb_language('Japanese');
  if (function_exists('mb_internal_encoding')) @mb_internal_encoding('UTF-8');

  // 件名と差出人名をJISでエンコード
  $enc_subject  = mb_encode_mimeheader($subject, 'ISO-2022-JP', 'B', "\r\n");
  $enc_fromname = $from_name ? mb_encode_mimeheader($from_name, 'ISO-2022-JP', 'B', "\r\n") : '';
  $from_header  = $enc_fromname ? ($enc_fromname.' <'.$from_email.'>') : $from_email;

  // ヘッダー
  $headers = array(
    "From: ".$from_header,
    "Reply-To: ".$from_email,
    "MIME-Version: 1.0",
    "Content-Type: text/plain; charset=ISO-2022-JP",
    "Content-Transfer-Encoding: 7bit",
  );

  // 本文をJISへ
  $body = str_replace(array("\r\n","\r"), "\n", $body);
  $body_jis = mb_convert_encoding($body, 'ISO-2022-JP', 'UTF-8');

  // -f オプション（先頭にスペース必須）
  $extra_params = '';
  if ($envelope_from) {
    $extra_params = ' -f'.$envelope_from;
  }

  return @mail($to, $enc_subject, $body_jis, implode("\r\n", $headers), $extra_params);
}
?>
