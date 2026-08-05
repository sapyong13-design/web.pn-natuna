<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=rtrim((string)getenv('PN_NATUNA_JPATH_ROOT'),'/\\');
$source=rtrim((string)getenv('PN_NATUNA_SOURCE_ROOT'),'/\\').'/tools/security/admin-login-guard.php';
$private=rtrim((string)getenv('PN_NATUNA_PRIVATE_ROOT'),'/\\').'/admin-login-guard.php';
$index=$root.'/administrator/index.php';
if(!is_file($source)||!is_file($index)) throw new RuntimeException('guard source or administrator index missing');
if(!copy($source,$private)) throw new RuntimeException('private guard copy failed');
chmod($private,0600);
$text=file_get_contents($index);
$line="require '/home/pnnatuna/private/admin-login-guard.php'; // PN_NATUNA_LOGIN_GUARD\n";
if(!str_contains($text,'PN_NATUNA_LOGIN_GUARD')){
  $text=preg_replace('/^<\?php\R/',"<?php\n".$line,$text,1,$count);
  if($count!==1||file_put_contents($index,$text,LOCK_EX)===false) throw new RuntimeException('administrator guard injection failed');
}
echo "administrator login guard installed\n";
