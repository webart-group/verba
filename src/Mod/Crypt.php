<?php

namespace Mod;

class Crypt extends \Verba\Mod
{
    protected $cipher = 'aes-256-cbc';
    protected $iv;
    protected $key;
    use \Verba\ModInstance;
    function init()
    {
        global $S;
        $this->key = hash('sha256', $S->gC('cryptKey'));
        $this->iv = substr($this->key, 0, \openssl_cipher_iv_length($this->cipher));
    }

    function encode($string, $key = null)
    {
        if ($key === null) {
            $key = $this->key;
        } else {
            settype($key, 'string');
        }
        return \openssl_encrypt($string, $this->cipher, $key, 0, $this->iv);
    }

    function decode($string, $key = null)
    {
        if ($key === null) {
            $key = $this->key;
        } else {
            settype($key, 'string');
        }
        return \openssl_decrypt($string, $this->cipher, $key, 0, $this->iv);
    }
}
