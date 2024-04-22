<?php

namespace Verba\Mod\User\RequestHandler;

use Verba\Lang;
use function Verba\_mod;
use function Verba\utf8fix;

class PasswordAuthedResetSendCode extends \Verba\Block\Json
{

    public function build()
    {
        $request = $this->request->post();

        $_user = \Verba\_oh('user');
        /**
         * @var $mUser User
         */
        $mUser = \Verba\_mod('User');

        $email = isset($request['email'])
            ? $request['email']
            : false;

        $qm = new \Verba\QueryMaker($_user->getID(), false, array('email', 'last_password_reset_time'));
        $qm->addWhere("`email` = '" . $this->DB()->escape_string($email) . "'");
        $qm->addLimit(1);
        $qm->makeQuery();
        $sqlr = $qm->run();
        if (!$sqlr || $sqlr->getNumRows() !== 1) {
            throw new Exception(Lang::get('user reclaim_pass email-not-found'));
        }

        $userData = $sqlr->fetchRow();
        $userId = $userData[$_user->getPAC()];

        list($pswdTimeValid, $interval) = $mUser->validatePasswordResetTime($userData['last_password_reset_time']);
        if (!$pswdTimeValid) {
            throw  new \Verba\Exception\Building(Lang::get('user reclaim_pass delayed', array(
                    'hours' => $interval->h,
                    'minutes' => $interval->i)
            ));
        }

        $code = $mUser->genPassResetCode($userId);
        if (!$code) {
            throw new Exception(Lang::get('error error'));
        }

        $ae = $_user->initAddEdit(array('iid' => $userId));
        $ae->setGettedData(array(
            'password_reset_code' => $code,
            'last_password_reset_time' => time(),
        ));
        $ae->addedit_object();
        if ($ae->haveErrors()) {
            throw new Exception(Lang::get('error process'));
        }
        
        $this->content = [
            'code' => $code,
        ];

        return $this->content;
    }

}

?>