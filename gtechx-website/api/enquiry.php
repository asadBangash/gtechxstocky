<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
function respond(int $code, bool $ok, string $msg): never {http_response_code($code);echo json_encode(['success'=>$ok,'message'=>$msg]);exit;}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405,false,'Only POST requests are accepted.');
if ((int)($_SERVER['CONTENT_LENGTH']??0)>32000) respond(413,false,'Your message is too large.');
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly','1');
if (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off') ini_set('session.cookie_secure','1');
session_start();
if (!isset($_SESSION['enquiry_token'])||!is_string($_POST['form_token']??null)||!hash_equals($_SESSION['enquiry_token'],$_POST['form_token'])) respond(403,false,'Your form session expired. Refresh the page and try again.');
if (trim((string)($_POST['website']??''))!=='') respond(200,true,'Thank you.');
$lastTime=(int)($_SESSION['last_enquiry']??0);
if ($lastTime>0&&time()-$lastTime<30) respond(429,false,'Please allow a moment before sending another message.');
function field(string $key, int $max=200): string { $v=$_POST[$key]??'';if(!is_string($v))respond(422,false,'Invalid form field.');$v=trim(strip_tags($v));if(strlen($v)>$max)respond(422,false,'One of the fields is too long.');return $v;}
$first=field('first_name',80);$last=field('last_name',80);$email=field('email',190);$phone=field('phone',50);$service=field('service',100);$message=field('message',5000);$budget=field('budget',100);$timeline=field('timeline',100);$type=field('form_type',15);
if($first===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($message)<10||!in_array($type,['contact','quote'],true))respond(422,false,'Please enter a valid name, email and message (at least 10 characters).');
foreach ([$first,$last,$phone,$service,$budget,$timeline] as $v) if(preg_match('/[\r\n]/',$v)) respond(422,false,'Invalid characters in a form field.');
// Configuration is intentionally stored outside public web root, preferably via environment variables.
$configFile=dirname(__DIR__).'/config.php';
if(is_file($configFile)) require $configFile;
$recipient=getenv('GTECHX_TO_EMAIL')?:'info@globaltechxsolutions.com';
$sender=getenv('GTECHX_FROM_EMAIL')?:'info@globaltechxsolutions.com';
if(!filter_var($recipient,FILTER_VALIDATE_EMAIL)||!filter_var($sender,FILTER_VALIDATE_EMAIL))respond(500,false,'Email delivery is not configured correctly.');
$subject='Global TechX '.($type==='quote'?'Quote Request':'Contact Enquiry');
$body="New ".strtoupper($type)." enquiry\n\n";
foreach(['Name'=>$first.' '.$last,'Email'=>$email,'Phone'=>$phone,'Service'=>$service,'Budget'=>$budget,'Timeline'=>$timeline,'Message'=>$message] as $key=>$value){$body.=$key.": ".$value."\n";}
$body.="\nSubmitted: ".gmdate('Y-m-d H:i:s').' UTC';
$sent=false;
$autoload=dirname(__DIR__).'/vendor/autoload.php';
if(file_exists($autoload) && getenv('GTECHX_SMTP_HOST')){
 try{
   require_once $autoload;
   $mailer=new \PHPMailer\PHPMailer\PHPMailer(true);
   $mailer->isSMTP();$mailer->Host=getenv('GTECHX_SMTP_HOST');
   $mailer->SMTPAuth=true;$mailer->Username=getenv('GTECHX_SMTP_USER')?:$sender;
   $mailer->Password=getenv('GTECHX_SMTP_PASS')?:'';
   $mailer->Port=(int)(getenv('GTECHX_SMTP_PORT')?:587);
   $mailer->SMTPSecure=$mailer->Port===465?\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS:\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
   $mailer->CharSet='UTF-8';$mailer->setFrom($sender,'Global TechX Solutions');
   $mailer->addAddress($recipient);$mailer->addReplyTo($email,$first.' '.$last);
   $mailer->Subject=$subject;$mailer->Body=$body;$mailer->send();$sent=true;
 }catch(\Throwable $e){error_log('GlobalTechX SMTP enquiry error: '.$e->getMessage());}
}else{
 $headers=['From: Global TechX Solutions <'.$sender.'>','Reply-To: '.$email,'Content-Type: text/plain; charset=UTF-8','X-Mailer: PHP'];
 $sent=@mail($recipient,$subject,$body,implode("\r\n",$headers));
}
if(!$sent)respond(503,false,'Email could not be delivered. Please email info@globaltechxsolutions.com directly.');
$_SESSION['last_enquiry']=time();
$_SESSION['enquiry_token']=bin2hex(random_bytes(32));
respond(200,true,'Thanks! Your enquiry has been sent. Our team will contact you.');
