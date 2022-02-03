<?php
class shop_paymentSelector extends \Verba\Block\Html{

  public $content = '';

  public $templates = array(
    'content' => '/shop/paymentSelector/wrap.tpl',
    'selector' => '/shop/paymentSelector/select.tpl',
  );

  public $css = array(
    array('payment-selector','shop'),
  );

  public $tplvars = array(
    'PSYS_SELECTOR_CFG' => '',
    'PAYMENT_SELECTOR' => '',
  );
  /**
   * Валюта оплаты
   *
   * @var \Verba\Model\Currency
   */
  public $currency;
  public $Store;
  public $clientCfg = array(
    'warns' => array(
      'messages' => array(
        'tax_merch_mp' => '',
        'tax_merch_mp_details' => '',
      ),
    ),
    'items' => array(),
  );

  function prepare(){

    if(!is_object($this->Store)
      || !is_object($this->currency)){
      return $this->content;
    }
    /**
     * @var $mShop Shop
     */
    $mShop = \Mod\Shop::getInstance();
    $this->clientCfg['items'] = $mShop->getPaymentSelectorItems($this->currency->getId(), $this->Store);
    $this->clientCfg['warns']['messages']['tax_merch_mp'] = \Verba\Lang::get('paysys warns tax_merch_mp');

    $this->tpl->assign(array(
      'PSYS_SELECTOR_CFG' => json_encode($this->clientCfg, JSON_FORCE_OBJECT),
    ));
    $this->tpl->parse('PAYMENT_SELECTOR', 'selector');

  }

}
?>