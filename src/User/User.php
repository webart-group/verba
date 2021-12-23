<?php

namespace Verba\User;

use Verba\Url;

class User extends \Verba\Mod
{
    use \Verba\ModInstance;

    function pwdHash($val)
    {
        settype($val, 'string');
        return password_hash($val, PASSWORD_BCRYPT);
    }

    function pwdVerify($val, $hash)
    {
        settype($val, 'string');
        settype($hash, 'string');
        return password_verify($val, $hash);
    }

    function authorize($authData, $nostore = false)
    {
        global $S;
        $_user = \Verba\_oh('user');
        if (!is_array($authData)) {
            $authData = [];
        }

        $Authorizator = new Authorization\Basic;

        $udata = $Authorizator->authorize($authData);

        if (is_array($udata) && !empty($udata)) {
            $S->setUser($udata);
            $nostore = (bool)$nostore;

            $this->updateSessionId($nostore ? 0 : null);

            $this->DB()->query('UPDATE ' . $_user->vltURI() . " SET last_login = '" . date('Y-m-d H:i:s') . "' WHERE " . $_user->getPAC() . "='" . $S->U()->getID() . "' LIMIT 1");
        }

        return $udata;
    }

    function authorizeAsSystem($nostore = false)
    {
        /**
         * @var $S \Verba\Hive
         */
        global $S;
        $_user = \Verba\_oh('user');

        $pac = $_user->getPAC();
        $qm = new \Verba\QueryMaker($_user, false, true);
        $qm->addSelectPastFrom($_user->getPAC(), null, 'id');
        $qm->addWhere("`" . $pac . "`=3");
        $qm->addWhere(1, 'active');
        $qm->setQuery();

        $sqlr = $this->DB()->query($qm->getQuery());

        if (!is_object($sqlr) || !$sqlr->getNumRows()) {
            return false;
        }

        $udata = $sqlr->fetchRow();

        if (is_array($udata) && !empty($udata)) {
            $S->setUser($udata);
            $nostore = (bool)$nostore;
            if ($nostore) {
                $time = 0;
            } else {
                $time = time() + 7776000;
            }
            setcookie(session_name(), session_id(), $time, '/', '.' . SYS_PRIMARY_HOST);
            $this->DB()->query('UPDATE ' . $_user->vltURI() . " SET last_login = '" . date('Y-m-d H:i:s') . "' WHERE " . $_user->getPAC() . "='" . $S->U()->getID() . "' LIMIT 1");
        }

        return $S->U();
    }

    function getHistoryBackUrl()
    {
        $url = \Verba\Hive::getBackURL();
        return !$url ? '/' : $url;
    }

    function logout()
    {
        global $S;
        $_SESSION = array();
        $_COOKIE[session_name()] = [];
        session_destroy();
        $S->destroyUser();
        session_start();
        $this->updateSessionId(0);

    }

    function updateSessionId($time = null)
    {
        if (is_string($time) || is_numeric($time)) {
            $time = strtotime($time);
        } else {
            $time = strtotime('+1 year');
        }

        // if(isset($_SERVER['HTTP_ORIGIN'])){
        //     $origin = (new Url($_SERVER['HTTP_ORIGIN']))->host;
        //     if($origin){
        //         $domain = $origin;
        //     }
        // }

        if(!isset($domain)){
            $domain = '.' . SYS_THIS_HOST;
        }

        setcookie(session_name(), session_id(), $time, '/', $domain);
    }

    function ajaxCheckLoginAvailability($BParams = null)
    {

        $v = trim($_REQUEST['value']);

        $result = new \stdClass;
        $result->state = 0;
        $result->enteredValue = $v;

        if (is_string($v) && !empty($v)) {
            $field = 'email';
            $oh = \Verba\_oh('user');
            $qm = new \Verba\QueryMaker($oh->getID(), $oh->getBaseKey(), $field);
            $qm->addWhere("`email` = '" . $this->DB()->escape($v) . "'");
            $qm->makeQuery();
            $oRes = $this->DB()->query($qm->getQuery());

            if ($oRes->getNumRows() == 0) {
                $result->state = 1;
            } else {
                $result->state = 2;
                $qm->reset();

                $eArray = explode('@', $v);
                $year = date('Y', time());
                $year2d = date('y', time());
                $variants = array(
                    $eArray[0] . $year . '@' . $eArray[1],
                    $eArray[0] . $year2d . '@' . $eArray[1],
                );

                foreach ($variants as $c_v) {
                    $qm->addWhere("`$field` = '" . $this->DB()->escape($c_v) . "'", false, false, false, '=', '||');
                }

                $qm->makeQuery();

                $oRes2 = $this->DB()->query($qm->getQuery());
                if ($oRes2->getNumRows > 0) {
                    while ($row = $oRes2->fetchRow()) {
                        $k = array_search($row[$field], $variants);
                        unset($variants[$k]);
                    }
                }
                if (!empty($variants)) {
                    $result->loginVariants = array_merge(array(), $variants);
                }
            }
        }
        $json_result = \json_encode($result);
        echo $json_result;
        exit;
    }

    function authNow($bp = null, $login = false, $password = false)
    {
        $_user = \Verba\_oh('user');

        $authData = array(
            'login' => $login
                ? (string)$login
                : (isset($_REQUEST['login']) ? (string)$_REQUEST['login'] : false),
            'password' => $password
                ? (string)$password
                : (isset($_REQUEST['password']) ? (string)$_REQUEST['password'] : false)
        );
        $nostore = isset($_REQUEST['nostore']) && $_REQUEST['nostore'] == 'on';
        $user_data = $this->authorize($authData, $nostore);

        if (!is_array($user_data) || !isset($user_data[$_user->getPAC()]) || $user_data[$_user->getPAC()] < 1) {
            return false;
        }

        return true;
    }

    function getAuthorizationUrl($global = true)
    {
        return $global
            ? (defined('SYS_REQUEST_PROTO') && !empty(SYS_REQUEST_PROTO) ? SYS_REQUEST_PROTO . '://' : ''). SYS_THIS_HOST . $this->gC('auth', 'authorization_path')
            : $this->gC('auth', 'authorization_path');
    }

    function getRegisterUrl($global = true)
    {
        return $global
            ? (defined('SYS_REQUEST_PROTO') && !empty(SYS_REQUEST_PROTO) ? SYS_REQUEST_PROTO . '://' : '') . SYS_THIS_HOST . $this->gC('auth', 'register_path')
            : $this->gC('auth', 'register_path');
    }

    function getSpecifyUrl($global = true)
    {
        return $global
            ? (defined('SYS_REQUEST_PROTO') && !empty(SYS_REQUEST_PROTO) ? SYS_REQUEST_PROTO . '://' : '') . SYS_THIS_HOST . $this->gC('auth', 'specify')
            : $this->gC('auth', 'specify');
    }

    function getLoginPageUrl($global = true)
    {
        return $global
            ? (defined('SYS_REQUEST_PROTO') && !empty(SYS_REQUEST_PROTO) ? SYS_REQUEST_PROTO . '://' : '') . SYS_THIS_HOST . $this->_c['auth']['login_page_url']
            : $this->_c['auth']['login_page_url'];

    }

    function getLogoutUrl($global = true)
    {
        return $global
            ? (defined('SYS_REQUEST_PROTO') && !empty(SYS_REQUEST_PROTO) ? SYS_REQUEST_PROTO . '://' : '') . SYS_THIS_HOST . $this->gC('auth', 'logout_path')
            : $this->gC('auth', 'logout_path');

    }

    function getProfileUrl($user_id = '', $global = true)
    {
        $relUrl = $this->gC('auth profile_path') . (!empty($user_id) ? '/' . $user_id : '');
        return $global
            ? SYS_REQUEST_PROTO . '://' . SYS_THIS_HOST . '/' . ltrim($relUrl, '/')
            : $relUrl;
    }

    function getLostpasswordUrl($global = true)
    {
        return $global
            ? (defined('SYS_REQUEST_PROTO') && !empty(SYS_REQUEST_PROTO) ? SYS_REQUEST_PROTO . '://' : '') . SYS_THIS_HOST . $this->gC('auth lostpassword')
            : $this->gC('auth lostpassword');
    }

    function getReclaimpasswordUrl($global = true)
    {
        return $global
            ? (defined('SYS_REQUEST_PROTO') && !empty(SYS_REQUEST_PROTO) ? SYS_REQUEST_PROTO . '://' : '') . SYS_THIS_HOST . $this->gC('auth reclaimpassword')
            : $this->gC('auth reclaimpassword');
    }

    function getLoginfaildUrl($global = true)
    {
        return $global
            ? (defined('SYS_REQUEST_PROTO') && !empty(SYS_REQUEST_PROTO) ? SYS_REQUEST_PROTO . '://' : '') . SYS_THIS_HOST . $this->gC('auth loginfaild_path')
            : $this->gC('auth loginfaild_path');
    }

    function createUser($data = null, $extendedData = null)
    {

        try {
            $_user = \Verba\_oh('user');
            $ae = $_user->initAddEdit('new');
            if (!is_array($data)) {
                throw new \Exception(\Lang::get('error bad_data'));
            }

            $loginField = $this->gC('login_field');
            if (!array_key_exists($loginField, $data)) {
                throw new \Exception(\Lang::get('error bad_data'));
            }

            $qm = new \Verba\QueryMaker($_user, false, false);
            $qm->addWhere($data[$loginField], $loginField);
            $sqlr = $qm->run();
            if ($sqlr->getNumRows()) {
                throw new \Exception(\Lang::get('user registration profile_exists', array(
                    'restore_url' => $this->getLostpasswordUrl()
                )));
            }

            if (is_array($extendedData)) {
                $ae->addExtendedData($extendedData);
            }

            $userOtId = $_user->getID();
            $picAttrCode = 'picture';
            $idx = 'upl';
            // Случайная ава юзера
            if (!isset($_FILES['NewObject']['tmp_name']['picture'][$idx])) {
                $themes = array('Film', 'Glass', 'Mosaic', 'Neon');
                $selectedTheme = $themes[array_rand($themes)];
                $num = rand(1, 100);
                $num = $num < 10 ? '0' . $num : (string)$num;
                $ava_name = '_' . $num . '.png';

                $_FILES['NewObject']['tmp_name'][$userOtId][$picAttrCode][$idx] = SYS_UPLOAD_DIR . '/random_ava/users/' . $selectedTheme . '/' . $ava_name;
                $_FILES['NewObject']['type'][$userOtId][$picAttrCode][$idx] = 'images/png';
                $_FILES['NewObject']['name'][$userOtId][$picAttrCode][$idx] = $ava_name;
                //$_FILES['NewObject']['size'][$userOtId][$picAttrCode][$idx],
                $_FILES['NewObject']['error'][$userOtId][$picAttrCode][$idx] = 0;
            }

            $ae->setGettedObjectData($data);

            $iid = $ae->addedit_object();
            if (!$iid) {
                throw new \Exception(\Lang::get('user registration general_error'));
            }

            // Accounts create
            $_account = \Verba\_oh('account');

            $mCurr = \Mod\Currency::getInstance();
            $currs = $mCurr->getCurrencies();

            try {

                foreach ($currs as $curId => $Cur) {

                    $ae_acc = $_account->initAddEdit();

                    $ae_acc->setGettedData(array(
                        'owner' => $iid,
                        'currencyId' => $curId,
                        'mode' => 1158,
                        'active' => 1,
                    ));

                    $ae_acc->addedit_object();

                }

            } catch (\Exception $e) {
                $this->log()->error($e);
                $this->log()->error('User creation error: user accounts creation process error');
            }

        } catch (\Exception $e) {
            return array(false, $e);
        }

        return array($iid, $ae);
    }

    static function getFullName($arr)
    {
        $r = '';
        if ($arr['name']) $r .= $arr['name'];
        if ($arr['patronymic']) $r .= ' ' . $arr['patronymic'];
        if ($arr['surname']) $r = $arr['surname'] . (!empty($r) ? ' ' . $r : '');
        return $r;
    }

    function genEmailConfirmationCode($email)
    {
        /**
         * @var $_crypt Crypt
         */
        $_crypt = \Verba\_mod('crypt');

        return base64_encode($_crypt->encode($email . '/' . time() . '/' . rand(1, 1000000), $this->gC('email_confirm_secret')));
    }

    function sendEmailConfirmationLink($userData, $resetEmailConfirmStatus = false, $updateLastSendingTime = true)
    {
        $_user = \Verba\_oh('user');
        $userId = $userData[$_user->getPAC()];

        if (!$userId || !is_array($userData) || !array_key_exists('email', $userData)) {
            return false;
        }

        $code = $this->genEmailConfirmationCode($userData['email']);

        $ae = $_user->initAddEdit(array('iid' => $userId));
        $data = array(
            'confirmation_code' => $code,
        );
        if ($resetEmailConfirmStatus) {
            $data['email_confirmed'] = '0';
        }

        if ($updateLastSendingTime) {
            $data['last_confirmation_request_time'] = time();
        }

        $ae->setGettedData($data);
        $ae->addedit_object();
        if ($ae->haveErrors()) {
            return false;
        }

        $_text = \Verba\_oh('textblock');

        $email_template = $_text->getData('email_confirm');

        $url = SYS_REQUEST_PROTO . '://' . SYS_THIS_HOST . '/user/email-confirm?code=' . urlencode($code);
        $this->tpl->assign(array(
            'EMAIL_CONFIRM_URL' => $url,
            'THIS_HOST' => SYS_THIS_HOST,
        ));
        /**
         *
         * @var $mMail \Mod\CoMail
         */
        $mMail = \Verba\_mod('comail');

        $mail = $mMail->PHPMailer();

        $mail->setSubject($this->tpl->parse_template($email_template['title']), true);
        $mail->MsgHTML($this->tpl()->parse_template($email_template['text']));

        $mail->AddAddress($userData['email']);

        if (!$mMail->Send($mail, true)) {
            $this->log()->error($mail->ErrorInfo);
            return false;
        }
        return true;
    }

    function genPassResetCode($userId)
    {
        return strtoupper(\Verba\Hive::make_random_string(5, 5, 'l'));
    }

    function validatePasswordResetTime($last_request_time)
    {
        $timout = (int)$this->gC('password_reset_timeout');
        if (!$timout) {
            $timout = 600;
        }
        $now = time();
        if ($last_request_time > 0 && $now - $last_request_time < $timout) {
            $dateNow = new \DateTime(date('Y-m-d H:i', $now));
            $dateNext = new \DateTime(date('Y-m-d H:i', $last_request_time + $timout));
            return array(false, $dateNext->diff($dateNow));
        }
        return array(true, null);
    }

    /**
     * @param $userId integer
     * @return string
     */
    function getChannelName($user = null)
    {
        if ($user === null) {
            $user = \User();
        }
        if (is_object($user) && $user instanceof \Verba\User\Model\User) {
            $userId = $user->getId();
        } elseif (is_numeric($user)) {
            $userId = (int)$user;
        }

        if (!isset($userId) || !is_int($userId) || !$userId) {
            return false;
        }


        return '$pv:usrntf#' . $userId;
    }

    function getOnlineStatusByDatetime($date)
    {
        $delta = is_int($ts = strtotime($date)) ? time() - $ts : false;

        return is_int($delta) && $delta <= 60 * 15
            ? 'online'
            : 'offline';
    }

}
