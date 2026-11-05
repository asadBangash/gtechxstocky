<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;}
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly','1');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure','1');
session_start();
if (empty($_SESSION['enquiry_token'])) $_SESSION['enquiry_token']=bin2hex(random_bytes(32));
echo json_encode(['token'=>$_SESSION['enquiry_token']]);
