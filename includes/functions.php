<?php
require_once __DIR__.'/../config/db.php';
session_start();
function base_url(string $path=''): string { $base=rtrim(dirname($_SERVER['SCRIPT_NAME']??''),'/\\'); while(str_ends_with($base,'/auth')||str_ends_with($base,'/admin')||str_ends_with($base,'/user')||str_ends_with($base,'/api'))$base=rtrim(dirname($base),'/\\'); return ($base==='/'?'':$base).'/'.ltrim($path,'/'); }
function e($v): string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function redirect_to(string $path): never { header('Location: '.base_url($path)); exit; }
function flash(string $key, ?string $value=null){ if($value!==null){$_SESSION['_flash'][$key]=$value;return;} $v=$_SESSION['_flash'][$key]??null;unset($_SESSION['_flash'][$key]);return $v; }
function csrf_token(): string { if(empty($_SESSION['_csrf']))$_SESSION['_csrf']=bin2hex(random_bytes(32));return $_SESSION['_csrf']; }
function csrf_field(): string{return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">';}
function verify_csrf(): void {if($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['_csrf']??'',$_POST['_csrf']??'')){http_response_code(419);exit('Invalid CSRF token.');}}
function require_auth():void{if(empty($_SESSION['user_id']))redirect_to('auth/login.php');}
function require_admin():void{require_auth();if(($_SESSION['role']??'')!=='admin'){http_response_code(403);exit('Forbidden');}}
function old(string $key):string{return e($_SESSION['_old'][$key]??'');}
function set_old(array $v):void{$_SESSION['_old']=$v;}
function clear_old():void{unset($_SESSION['_old']);}
function parse_rank_to_value($raw):?int{$raw=trim((string)$raw);if($raw==='')return null;return preg_match('/\d+/',str_replace(['–','—'],'-',$raw),$m)?(int)$m[0]:null;}
function flash_redirect(string $path,string $key,string $msg):never{flash($key,$msg);redirect_to($path);}
