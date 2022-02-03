<?php

class captcha_img extends \Block\Raw
{

    public $hash;

    function route()
    {

        if (!$this->hash) {
            throw new \Exception\Routing();
        }

        $response = new \Verba\Response\Raw();
        $response->addItems($this);
        return $response;
    }


    function build()
    {

        /**
         * @var $mCaptcha \Mod\Captcha
         */

        $mCaptcha = \Verba\_mod('captcha');
        $hash = $mCaptcha->getActiveHash();

        $captCfg = $mCaptcha->gC('default');

        $captcha = new w3captcha;
        $captcha->applyConfigDirect($captCfg);

        $captcha->generate();


        $mCaptcha->saveToSession('string', $captcha->getCaptchaStr());

        $this->addHeader('Content-type: image/png');
        $this->content = imagepng($captcha->image);
        imagedestroy($captcha->image);
        return $this->content;
    }

}