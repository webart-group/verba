<?php
class captcha_fe extends \Verba\Block\Html {

  public $captCfg;

  public $templates = array(
    'content' => 'captcha/fe.tpl',
  );

  public $scripts = array(array('captcha', 'modules/captcha'));
  public $css = array('captcha');

  function build( ){
    /**
     * @var $mCaptcha Captcha
     */
    $mCaptcha = \Verba\_mod('captcha');

    $captCfg = $mCaptcha->gC('default');
    if(is_array($this->captCfg)){
      $captCfg = array_replace_recursive($captCfg, $this->captCfg);
    }

    $hash = $mCaptcha->getActiveHash();

    $this->tpl->assign(array(
      'CAPTCHA_IMAGE_URL'   => '/captcha/new/'.$hash,
      'CAPTCHA_KEY'         => $hash,
      'CAPTCHA_WIDTH'       => $captCfg['width'],
      'CAPTCHA_HEIGHT'      => $captCfg['height'],
      'CAPTCHA_MAXLENGTH'   => $captCfg['maxlenght'],
      'CAPTCHA_TABINDEX'   => $captCfg['tabindex'],
      'CAPTCHA_NAME'        => $mCaptcha->_c['captchaFEName'].'['.$hash.']',
    ));

    $this->content = $this->tpl->parse(false, 'content');

    return $this->content;
  }

}
?>