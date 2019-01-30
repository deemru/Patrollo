<?php

require_once __DIR__ . '/vendor/autoload.php';
use deemru\WavesKit;

// nodes array
$nodes_urls = [ 'https://nodes.wavesnodes.com' ];

// blocks left when to outbid
$threshold = 10;

// your Patrollo
$patrollo = new WavesKit();
$patrollo->setSeed( 'manage manual recall harvest series desert melt police rose hollow moral pledge kitten position add' );

// the Game
$game = new WavesKit();
$game->setSeed( 'apart flight entire ankle outdoor divide urge october rain name mass dizzy tenant hedgehog misery' );

// you can send reports to e-mail if you wish
// use "composer require phpmailer/phpmailer"
// uncomment and fill this function
function sendPatrolloReport( $subject, $body )
{
/*
    $mail = new PHPMailer\PHPMailer\PHPMailer;;
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';
    $mail->Host = 'smtp.example.com';
    $mail->Port = 465;    
    $mail->Username = 'example@example.com';
    $mail->Password = 'p@sswo0rd';
    $mail->From = 'example@example.com';
    $mail->FromName = 'Patrollo';
    $mail->addAddress( 'example@example.com' );
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->send();
*/
}
