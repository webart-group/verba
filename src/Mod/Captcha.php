<?php

namespace Verba\Mod;

class Captcha extends \Verba\Mod
{
    use \Verba\ModInstance;

    public function init()
    {
        require_once 'Captcha/w3captcha/w3captcha.php';
    }

    function getActiveHash()
    {
        $hash = $this->getFromSession('hash');
        if (!$hash) {
            $hash = $this->genCapHash();
            $this->saveToSession('hash', $hash);
        }
        return $hash;
    }

    function genCapHash()
    {

        $ip = \Verba\getClientIP();
        $hash = md5($ip . session_id());

        return $hash;
    }

    function useCurrentCaptcha($hash = null, $string = null)
    {
        if ($hash === null) {
            $cn = $this->_c['captchaFEName'];
            if (array_key_exists($cn, $_REQUEST)
                && is_array($_REQUEST[$cn]) && count($_REQUEST[$cn])) {
                $hash = key($_REQUEST[$cn]);
                $string = $_REQUEST[$cn][$hash];
            }
        }

        $generatedHash = $this->genCapHash();

        $session_hash = $this->getFromSession('hash');
        $session_string = $this->getFromSession('string');

        if (!$session_hash
            || $session_hash !== $hash
            || $hash !== $generatedHash
            || !$session_string
            || $session_string !== $string) {
            return false;
        }

        $this->removeSSP();
        return true;
    }
}

Captcha::$_config_default = [
    'sessionKey' => 'aef_user_captcha',
    'timeout' => 660,
    'captchaFEName' => 'capo__0',
    'default' => array(
        'height' => 35,
        'width' => 100,
        'font_size_min' => 22,
        'font_size_max' => 26,
        'maxlenght' => 5,
        'tabindex' => 50,
    ),
];
