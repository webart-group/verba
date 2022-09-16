<?php
namespace Verba\Mod;

class Callback extends \Verba\Mod{
    use \Verba\ModInstance;
  function sendNewCallbackEmail($item){
    if(!is_array($item) || empty($item)){
      $this->log()->error('Unable to find visit data');
      return false;
    }
    $tpl = $this->tpl();
    $mcfg = \Verba\_mod('order')->gC('mailing');

    if(!isset($mcfg['to']['creation']) || !is_array($mcfg['to']['creation'])
    || empty($mcfg['to']['creation'])){
      $this->log()->error('Emails list is empty');
      return false;
    }

    $mMail = \Verba\_mod('comail');
    $mail = $mMail->PHPMailer($mcfg['mail']);

    $tpl->define(array(
      'body' => 'callback/create/notify/body.tpl',
      'subject' => 'callback/create/notify/subject.tpl',
    ));

    $tpl->assign(array(
      'ACP_URL' => SYS_THIS_HOST.\Verba\_mod('acp')->gC('url'),
      'PHONE' => htmlspecialchars($item['phone']),
      'CREATED' => utf8fix(strftime("%d %b %Y %H:%M", strtotime($item['created']))),
      'COMMENT' => htmlspecialchars($item['comment']),
      'SHOP_NAME' => \Verba\Lang::get('order shopName'),
    ));

    $mail->setSubject($tpl->parse(false, 'subject'));
    $mail->MsgHTML($tpl->parse(false, 'body'));

    foreach($mcfg['to']['creation'] as $tomail => $toname){
      $mail->AddAddress($tomail, $toname);
    }
    if(!$mMail->Send($mail)){
      $this->log()->error($mail->ErrorInfo);
      return false;
    }
    return true;
  }
}
?>