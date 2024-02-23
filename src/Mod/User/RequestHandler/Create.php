<?php

namespace Verba\Mod\User\RequestHandler;

class Create extends \Verba\Block\Json
{

    public $data = array(
        //'<login attr code>' => null, //см инит()
        'password' => array(
            0 => null, // password
            1 => null, // password_confirm
        ),
    );

    function init()
    {
        $mUser = \Verba\_mod('user');
        $loginField = $mUser->gC('login_field');
        if (!array_key_exists($loginField, $this->data)) {
            $this->data[$loginField] = null;
        }
    }

    function build()
    {

        try {

            /**
             * @var $mCaptcha Captcha
             */

//      $mCaptcha = \Verba\_mod('captcha');
//      if(!$mCaptcha->useCurrentCaptcha()){
//        throw new Exception(Lang::get('captcha wrong'));
//      }

            /**
             * @var $mUser User
             */
            $mUser = \Verba\_mod('User');
            $url = \Verba\Hive::getBackURL();

            $loginField = $mUser->gC('login_field');

            if (!is_string($this->data[$loginField])) {
                $request = $this->rq->post();
                $this->data[$loginField] = $request['login'] ?? null;
                $this->data['password'][0] = $request['password'] ?? null;
                $this->data['password'][1] = $request['password_confirm'] ?? null;
            }

            if (!is_string($this->data[$loginField]) || !is_string($this->data['password'][0]) || !is_string($this->data['password'][1])) {
                throw  new \Verba\Exception\Building('Bad data');
            }

            list($userId, $ae) = $mUser->createUser($this->data);

            if ($userId) {
                $this->content = true;
                $mUser->sendEmailConfirmationLink($ae->getActualData(), false, false);

            } else {
                if ($ae instanceof \Exception) {
                    $msg = $ae->getMessage();
                } elseif ($ae instanceof \Verba\Act\AddEdit) {
                    $msg = $ae->log()->getMessagesAsStr('error');
                } else {
                    $msg = \Verba\Lang::get('user registration general_error');
                }
                throw new \Exception($msg);
            }

        } catch (\Exception $e) {
            $this->failed($e->getMessage());
        }

        return $this->content;
    }

}
