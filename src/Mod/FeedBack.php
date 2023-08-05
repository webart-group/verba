<?php
namespace Verba\Mod;
use Verba\Mod\SnailMail\Email;

class FeedBack extends \Verba\Mod
{

    use \Verba\ModInstance;

    function sendCreationNonifyEmail($item)
    {
        $tpl = $this->tpl();
        if (!is_array($item)) {
            $this->log()->error('Unable to find feedback data');
            return false;
        }

        $mcfg = \Verba\_mod('order')->gC('mailing');

        $tpl->define(array(
            'body' => 'feedback/create/notify/body.tpl',
            'subject' => 'feedback/create/notify/subject.tpl',
        ));
        $acpUrl = new \Verba\Url(SYS_THIS_HOST . \Verba\_mod('acp')->gC('url'));
        $acpUrl = $acpUrl->get(true);
        $tpl->assign(array(
            'ACP_URL' => $acpUrl,
            'NAME' => htmlspecialchars($item['name']),
            'EMAIL' => htmlspecialchars($item['email']),
            //'TITLE' => htmlspecialchars($item['title']),
            'TEXT' => htmlspecialchars($item['text']),
            'CREATED' => \Verba\utf8fix(strftime("%d %b %Y %H:%M", strtotime($item['created']))),
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

    function sendAnswerToUser($bp, $action, $ot, $iid, $extData, $data)
    {
        if (!$_REQUEST['user-email-send']) {
            return false;
        }
        $tpl = \Verba\Hive::initTpl();
        $mail = \Verba\_mod('SnailMail');
        $letter = new \Verba\Mod\SnailMail\Email();
        $_feedback = \Verba\_oh('feedback');

        $tpl->define(array(
            'letter-body' => '/feedback/email/to-user/body.tpl',
            'letter-subject' => '/feedback/email/to-user/subject.tpl'
        ));

        $emailTo = isset($data['email']) && is_string($data['email']) && ($data['email'] = trim($data['email'])) && !empty($data['email']) && Email::validateEmail($data['email'])
            ? $data['email']
            : false;
        $answer = isset($data['answer']) && (is_string($data['answer']) || is_numeric($data['answer'])) && ($data['answer'] = trim($data['answer'])) && !empty($data['answer'])
            ? $data['answer']
            : false;
        $messageFromUser = isset($data['text']) && (is_string($data['text']) || is_numeric($data['text'])) && ($data['text'] = trim($data['text'])) && !empty($data['text'])
            ? $data['text']
            : false;

        $creationDate = !empty($data['created'])
            ? date('j.m.Y H:i', strtotime($data['created']))
            : '';

        $subject = '';
        if ((is_string($data['title']) || is_numeric($data['title'])) && !empty($data['title'])) {
            $subject = $data['title'];
        }
        $length = 30;
        if ($messageFromUser) {
            $subject = $subject . ' ' . $this->reduceText(false, array('text' => $messageFromUser), $length) . '...';
        }
        $name = isset($data['name']) && (is_string($data['name']) || is_numeric($data['name'])) && ($data['name'] = trim($data['name'])) && !empty($data['name'])
            ? ' ' . $data['name']
            : '';

        if (!$emailTo && !$answer) {
            $this->log()->error('Bad param for send', __METHOD__ . ' emailTo:[' . var_export($emailTo, true) . '], answer:[' . var_export($answer, true) . '] usr:[' . $S->U()->getID() . '] action:[' . var_export($bp, true) . ']');
            return false;
        }

        $tpl->assign(array(
            'NAME' => $name,
            'DATE' => $creationDate,
            'ORIGINAL_MESSAGE' => \Verba\Mod\SnailMail\Email::removeHTMLtags($messageFromUser),
            'ANSWER' => $answer,
            'HOST' => SYS_THIS_HOST,
            'SUBJECT' => $subject
        ));

        $letter->addTo($emailTo, $name);
        $letter->setText($tpl->parse(false, 'letter-body'));
        $letter->setSubject($tpl->parse(false, 'letter-subject'));
        $letter->setFrom($this->gC('email-to-user from email'), $this->gC('email-to-user from name'));
        $letter->setReplyTo($this->gC('email-to-admin to'));
        //$letter->addBCopyTo($this->gC('emailToUser','emailBCopyTo'));

        if ($mail->send($letter) !== true) {
            return false;
        } else {
            return true;
        }
    }

    function sendMessageToAdmin($data)
    {
        global $S;
        $tpl = $this->tpl();
        $_feedback = \Verba\_oh('feedback');

        $mail = \Verba\_mod('SnailMail');
        $letter = new \Verba\Mod\SnailMail\Email();
        $cfg = $this->gC('emailToAdmin');
        $tpl->define(array(
            'template' => 'feedback/email/toAdminTemplate.tpl',
            'subject' => 'feedback/email/toAdminSubject.tpl'
        ));
        $length = is_numeric($cfg['subj_length'])
            ? $cfg['subj_length']
            : 30;

        $replyTo = isset($data['email']) && is_string($data['email']) && ($data['email'] = trim($data['email'])) && !empty($data['email']) && Email::validateEmail($data['email'])
            ? $data['email']
            : false;

        $messageFromUser = isset($data['text']) && (is_string($data['text']) || is_numeric($data['text'])) && ($data['text'] = trim($data['text'])) && !empty($data['text'])
            ? $data['text']
            : false;

        $subject = (is_string($data['title']) || is_numeric($data['title'])) && !empty($data['title'])
            ? $data['title']
            : '';

        if ($messageFromUser) {
            $subject = $subject . ' ' . $this->substr_long_text($messageFromUser, $length) . '...';
        }
        $name = isset($data['name']) && (is_string($data['name']) || is_numeric($data['name'])) && ($data['name'] = trim($data['name'])) && !empty($data['name'])
            ? $data['name']
            : '';

        if (!$messageFromUser) {
            return false;
        }
        $tpl->assign(array(
            'NAME' => $name,
            'MESSAGE' => \Verba\Mod\SnailMail\Email::removeHTMLtags($messageFromUser),
            'SUBJECT' => $subject,
            'HOST' => SYS_THIS_HOST,
        ));
        $letter->addTo($cfg['to']);
        $letter->setText($tpl->parse(false, 'template'));
        $letter->setSubject($tpl->parse(false, 'subject'));
        $letter->setFrom($cfg['emailFrom'], $cfg['nameFrom']);
        if ($replyTo) {
            $letter->setReplyTo($replyTo, $name);
        }
        return $mail->send($letter);
    }
}
