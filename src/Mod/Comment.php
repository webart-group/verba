<?php

namespace Verba\Mod;

class Comment extends \Verba\Mod
{
    use \Verba\ModInstance;
    function addEntry()
    {

        $oh = \Verba\_oh('comment');
        $ae = $oh->initAddEdit(array('action' => 'new'));
        if (!isset($_REQUEST['NewObject'][$oh->getID()])
            || !is_array($_REQUEST['NewObject'][$oh->getID()])
            || !isset($_REQUEST['NewObject'][$oh->getID()]['name'])
            || empty($_REQUEST['NewObject'][$oh->getID()]['name'])
            || !isset($_REQUEST['NewObject'][$oh->getID()]['email'])
            || empty($_REQUEST['NewObject'][$oh->getID()]['email'])
            || !isset($_REQUEST['NewObject'][$oh->getID()]['comment'])
            || empty($_REQUEST['NewObject'][$oh->getID()]['comment'])
        ) {
            $e = new \Exception(\Lang::get('comment add error badIncoming'));
            $e->ae = $ae;
            throw $e;
        }
        $data = array(
            'name' => $_REQUEST['NewObject'][$oh->getID()]['name'],
            'email' => $_REQUEST['NewObject'][$oh->getID()]['email'],
            'comment' => $_REQUEST['NewObject'][$oh->getID()]['comment'],
            'active' => 0,
            'visible' => 0,
        );
        $ae->addMultipleParents($_REQUEST['pot']);
        $ae->setGettedObjectData($data);
        $ae->addedit_object();
        if (!$ae->getIID()) {
            $e = new \Exception(\Lang::get('comment add error badOperation'));
            $e->ae = $ae;
            throw $e;
        }

        return $ae;
    }

    function sendCreationNonifyEmail($item)
    {
        $tpl = $this->tpl();
        if (!is_array($item)) {
            $this->log()->error('Unable to find comment data');
            return false;
        }

        $mcfg = \Verba\_mod('order')->gC('mailing');

        $tpl->define(array(
            'body' => 'comment/create/notify/body.tpl',
            'subject' => 'comment/create/notify/subject.tpl',
        ));
        $acpUrl = new \Url(SYS_THIS_HOST . \Verba\_mod('acp')->gC('url'));
        $acpUrl = $acpUrl->get(true);
        $tpl->assign(array(
            'ACP_URL' => $acpUrl,
            'NAME' => htmlspecialchars($item['name']),
            'EMAIL' => htmlspecialchars($item['email']),
            'TEXT' => htmlspecialchars($item['comment']),
            'CREATED' => utf8fix(strftime("%d %b %Y %H:%M", strtotime($item['created']))),
            'SHOP_NAME' => \Verba\Lang::get('shop name'),
        ));

        $mMail = \Verba\_mod('comail');
        $mail = $mMail->PHPMailer($mcfg['mail']);

        $mail->setSubject($tpl->parse(false, 'subject'));
        $mail->MsgHTML($tpl->parse(false, 'body'));

        if (!isset($mcfg['to']['creation']) || empty($mcfg['to']['creation'])) {
            return false;
        }
        foreach ($mcfg['to']['creation'] as $tomail => $toname) {
            $mail->AddAddress($tomail, $toname);
        }
        if (!$mMail->Send($mail)) {
            $this->log()->error($mail->ErrorInfo);
            return false;
        }
        return true;
    }
    /*
      function sendMessageToAdmin($data){
        global $S;
        $tpl = $this->tpl();
        $_feedback = \Verba\_oh('feedback');

        $mail = \Verba\_mod('SnailMail');
        $letter = new Email();
        $cfg = $this->gC('emailToAdmin');
        $tpl->define(array(
          'template' => 'feedback/email/toAdminTemplate.tpl',
          'subject' => 'feedback/email/toAdminSubject.tpl'
        ));
        $length = is_numeric($cfg['subj_length'])
                ? $cfg['subj_length']
                : 30;

        $replyTo  = isset($data['email']) && is_string($data['email']) && ($data['email'] = trim($data['email'])) && !empty($data['email']) && Email::validateEmail($data['email'])
                  ? $data['email']
                  : false;

        $messageFromUser  = isset($data['text']) && (is_string($data['text']) || is_numeric($data['text']))&& ($data['text'] = trim($data['text'])) && !empty($data['text'])
                            ? $data['text']
                            : false;

        $subject  = (is_string($data['title']) || is_numeric($data['title'])) && !empty($data['title'])
                  ? $data['title']
                  : '';

        if($messageFromUser){
          $subject = $subject.' '.$this->substr_long_text($messageFromUser, $length).'...';
        }
        $name  = isset($data['name']) && (is_string($data['name']) || is_numeric($data['name']))&& ($data['name'] = trim($data['name'])) && !empty($data['name'])
              ? $data['name']
              : '';

        if(!$messageFromUser){
          return false;
        }
        $tpl->assign(array(
          'NAME' => $name,
          'MESSAGE' => EMail::removeHTMLtags($messageFromUser),
          'SUBJECT' => $subject,
          'HOST' => SYS_THIS_HOST,
          ));
        $letter->addTo($cfg['to']);
        $letter->setText($tpl->parse(false, 'template'));
        $letter->setSubject($tpl->parse(false, 'subject'));
        $letter->setFrom($cfg['emailFrom'], $cfg['nameFrom']);
        if($replyTo){
          $letter->setReplyTo($replyTo, $name);
        }
        return $mail->send($letter);
      }

      function handlerTrimText($list, $row, $attr = 'description', $length = 300){
        if(!is_string($attr) || !isset($row[$attr])){
          return '';
        }
        return HTMLGetFormattedText($row[$attr], $length);
      }
    */
}
