<?php
namespace Verba\Mod;

require_once(SYS_EXTERNALS_DIR.'/PHPMailer/class.phpmailer.php');

class CoMail extends \Verba\Mod{

    use \Verba\ModInstance;
  /*Wrapper for PHPMail class. https://github.com/PHPMailer */
  /**
   * @param null $custom_cfg
   * @return CoMail/PHPMailer
   */
 function PHPMailer($custom_cfg = null){

    $cfg = $this->gC();
    if(isset($custom_cfg) && is_array($custom_cfg))
    {
      $cfg = array_replace_recursive($cfg, $custom_cfg);
    }
    $mail = new CoMail\PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPDebug  = 0;
    $mail->Debugoutput = 'html';
    $mail->Encoding = $cfg['encoding'];
    $mail->CharSet = $cfg['charset'];
    $mail->Host = $cfg['host'];
    $mail->Port = $cfg['port'];
    $mail->SMTPSecure = $cfg['SMTPSecure'];
    $mail->SMTPAuth = $cfg['SMTPAuth'];
    $mail->Username =  $cfg['login'];
    $mail->Password = $cfg['password'];
    if(isset($cfg['from'])){
      if(is_array($cfg['from'])){
        $mail->SetFrom(key($cfg['from']), current($cfg['from']));
      }else{
        $mail->SetFrom($cfg['from']);
      }
    }

    if(isset($cfg['replyto'])){
      if(is_array($cfg['replyto'])){
        $mail->AddReplyTo(key($cfg['replyto']), current($cfg['replyto']));
      }else{
        $mail->AddReplyTo($cfg['replyto']);
      }
    }

    return $mail;
 }
 /**
 * @param PHPMailer $mail
 */
 function Send($mail, $forcedSend = false){
   global $S;
   if(!is_object($mail)){
     return false;
   }

   if(!$forcedSend && $S->gC('debug mailSilence') == true){
     return true;
   }

   return $mail->Send();
 }

 function maskEmail($email){
   list($lpart, $rpart) = explode('@', $email);
   if(!$lpart || !$rpart){
     return $email;
   }
   // формирование левой части емейла
   $lplenght = strlen($lpart);
   if($lplenght == 1){
     $cleft = '*';
   }elseif($lplenght == 2){
     $cleft = substr($lpart, 0, 1).'*';
   }else{
     $cleft = substr($lpart, 0, 2).str_repeat('*', $lplenght - 2);
   }

   // формирование правой части емейла
   if(!preg_match('/(:?.*)\.(\w+)$/i', $rpart, $_)){
     return $email;
   }
   // Средняя часть
   $mlenght = strlen($_[1]);
   if($mlenght == 1){
     $mid = '*';
   }else{
     $mid = substr($_[1], 0, 1).str_repeat('*', $mlenght - 1);
   }
   // Окончание
   $elenght = strlen($_[2]);
   if($elenght == 1){
     $end = '*';
   }else{
     $end = substr($_[2], 0, 1).str_repeat('*', $elenght - 1);
   }

   return $cleft.'@'.$mid.'.'.$end;
 }
}

