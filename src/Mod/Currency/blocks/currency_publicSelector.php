<?php

class currency_publicSelector extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'shop/currency/publicselector/wrap.tpl',
  );

  public $scripts = array(
    array('currencySelector', 'shop')
  );

  function build(){
 //   $mCur = \Verba\_mod('currency');
//    $currs = $mCur->getCurrency(false, true);
    $pssCfg = array(
      //'items' => $currs,
    );
    $this->tpl->assign(array(
      'PAGE_CURRENCY_SELECTOR_CFG' => json_encode($pssCfg),
    ));
    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }

}
?>
