<?php

namespace Verba\Mod\User\RequestHandler;

class EmailConfirm extends \Verba\Block\Html
{

    public function build()
    {

        $code = isset($_REQUEST['code'])
            ? $_REQUEST['code']
            : false;

        $encrypted = base64_decode($code);
        if (!$encrypted) {
            throw  new \Verba\Exception\Building('Bad result');
        }

        $mCrypt = \Verba\_mod('crypt');
        $mUser = \Verba\_mod('user');
        $decrypted = $mCrypt->decode($encrypted, $mUser->gC('email_confirm_secret'));
        list($email, $time, $randomseed) = explode('/', $decrypted);
        $tf = new \Verba\Data\Email(array('value' => $email));
        $tf->setValue($email);
        if (!$tf->validate()) {
            throw  new \Verba\Exception\Building('Bad request');
        }
        $email = $tf->value;
        $_user = \Verba\_oh('user');

        $QM = new \Verba\QueryMaker($_user, false, array('email_confirmed', 'email', 'confirmation_code'));
        $QM->addWhere($email, 'email');
        $QM->addWhere($code, 'confirmation_code');
        $sqlr = $QM->run();
        if (!$sqlr || $sqlr->getNumRows() != 1) {
            throw  new \Verba\Exception\Building('Not found');
        }

        $userData = $sqlr->fetchRow();
        $data = array(
            'confirmation_code' => '',
            'email_confirmed' => 1,
        );
        $userId = $userData[$_user->getPAC()];
        $ae = $_user->initAddEdit(array('iid' => $userId));
        $ae->setGettedData($data);
        $ae->addedit_object();
        if ($ae->haveErrors()) {
            throw  new \Verba\Exception\Building($ae->log()->getMessagesAsString('error', '<br>'));
        }
        $cUser = \Verba\User();
        if ($userId == $cUser->getID()) {
            $cUser->planeToReload();
        }

        $b = new \textblock_alert($this, array(
            'type' => 'success',
            'text' => \Verba\Lang::get('user email_confirm success'),
        ));

        $b->prepare();
        $b->build();

        $this->content = $b->content;

        return $this->content;
    }
}
