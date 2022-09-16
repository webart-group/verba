<?php
class order_statusItems extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'shop/order/status/items.tpl',
    'item' => 'shop/order/status/item.tpl',
    'item-price-details' => 'shop/order/status/item-price-details.tpl',
    'item-old-price' => 'shop/order/status/item-old-price.tpl',
    // extra fields
    'extra' => 'shop/order/status/extra.tpl',
    'extraItem' => 'shop/order/status/extraItem.tpl',
    // promos
    'promos' => 'shop/order/status/promotions.tpl',
    'promos-item' => 'shop/order/status/promotions-item.tpl',
  );
  public $tplvars = array(
    'ITEMS_ROWS' => '',
    'ORDER_CURRENCY_UNIT' => '',
  );

  public $parsePromotions = false;

  /**
   * @var \Verba\Mod\Order\Model\Order
   */
  public $Order;
  protected $curr;
  protected $paysys;
  public $grid_cfg;

  function init(){
    if(is_array($this->grid_cfg)){
      $this->tpl->assign($this->grid_cfg);
    }
  }

  function build(){
    $this->content = '';
    if(!$this->Order instanceof \Verba\Mod\Order\Model\Order){
      return $this->content;
    }
    $this->curr = $this->Order->getCurrency();
    $this->paysys = $this->Order->getPaysys();

    $this->tpl->assign(array(
      'ORDER_CURRENCY_UNIT' => $this->curr->symbol,
    ));

    $items = $this->Order->getItems();

    $this->tpl->clear_vars('ITEMS_ROWS');
    $mPrefix = 'parseOrderItem';
    if(is_array($items) && count($items)){
      foreach($items as $hash => $item){
        switch($item['ot_id']){
//          case 'zzzz':
//            $method = $mPrefix.'ZZZZ';
//            break;
          default:
          case '': $method = $mPrefix;
        }
        $this->tpl->assign('ITEMS_ROWS', $this->$method($item), true);
      }
    }

    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }

  function parseOrderItem($item){
    $dsc = strip_tags($item['description']);
    if(mb_strlen($dsc) > 124){
      $dsc = mb_substr($dsc, 0, 124).' ...';
    }
    $oh = \Verba\_oh($item['ot_id']);

    $itemCurId = $item['currencyId'];
    $rateIn = (float)$item['rate'];

    $orderCurId = $this->Order->getCurrencyId();
    $rateOut = $this->Order->getRate();
    /**
     * @var $mShop Shop
     */
    $mShop = \Verba\_mod('shop');
    // price
    $price = $mShop->convertCur($item['price'], $itemCurId, $orderCurId, $rateIn, $rateOut);
    $price_final = $mShop->convertCur($item['price_final'], $itemCurId, $orderCurId, $rateIn, $rateOut);

    $_tform = !empty($item['tformOtId'])
      ? \Verba\_oh($item['tformOtId'])
      : false;

    // picture
//    if(isset($item['_extra']['picture']) && !empty($item['_extra']['picture'])){
//      $picUrl = $imgCfg->getFullUrl(basename($item['_extra']['picture']), 'thumbs');
    if(isset($item['_extra']['image']) && !empty($item['_extra']['image'])){
      $picUrl = $item['_extra']['image'];
    }else{
      $picUrl = '/images/1px.gif';
    }

    $this->tpl->assign(array(
      'ORDER_ITEM_TITLE' => htmlspecialchars($item['title']),
      'ORDER_ITEM_GAME' => htmlspecialchars($item['_extra']['data']['gameCatTitle']),
      'ORDER_ITEM_SERVICE' => htmlspecialchars($item['_extra']['data']['serviceCatTitle']),
      'ORDER_ITEM_PRICE' => number_format($price_final, 2, '.',' '),
      'ORDER_ITEM_DESCRIPTION' => htmlspecialchars($dsc),
      'ORDER_ITEM_QUANTITY' => $item['quantity'],
      'ORDER_ITEM_DOWNLOAD_BLOCK_SCROLL_SIGN' => '',
      'ORDER_ITEM_TOTAL_COST' => number_format($price_final * $item['quantity'], 2, '.',' '),
      'ORDER_ITEM_PICTURE' => $picUrl,
      'ORDER_ITEM_OLDPRICE_BLOCK' => '',
      'ORDER_ITEM_EXTRA_ITEMS' => '',
      'ORDER_ITEM_EXTRA' => '',
    ));

    // old price
    if($price_final < $price){
      $this->tpl->assign(array(
        'ORDER_ITEM_OLDPRICE' => number_format($price, 2, '.',' '),
      ));
      $this->tpl->parse('ORDER_ITEM_OLDPRICE_BLOCK', 'item-old-price');
    }

    if($item['quantity'] == 1){
      $this->tpl->assign(array('ORDER_ITEM_PRICE_DETAILS' => ''));
    }else{
      $this->tpl->parse('ORDER_ITEM_PRICE_DETAILS', 'item-price-details');
    }

    // INFO (EXTRA) FIELDS
    $parsedExtra = '';
    if(isset($item['_extra']['info']) && is_array($item['_extra']['info']) && count($item['_extra']['info'])){
      $parsedExtra .= $this->parseExtraFields($item['_extra']['info'], $oh);
    }
    if(isset($item['_extra']['tform']) && is_array($item['_extra']['tform']) && count($item['_extra']['tform'])){
      $parsedExtra .= $this->parseExtraFields($item['_extra']['tform'], $_tform);
    }
    if(!empty($parsedExtra)){
      $this->tpl->assign('ORDER_ITEM_EXTRA_ITEMS',$parsedExtra);
      $this->tpl->parse('ORDER_ITEM_EXTRA', 'extra');
    }


 /**
  *    downloadable items
    try{
      if($this->Order->status != 21){
        throw new Exception();
      }

      $Ph = \Verba\_mod('product')->getProductHandler($oh);
      if(!$Ph->isDownloadable()){
        throw new Exception();
      }

      $downItems = $Ph->getDownItems($this->Order->id, $oh, $itemId);
      if(!$downItems){
        throw new Exception();
      }
      $this->tpl->clear_vars('ORDER_ITEM_DOWNLOAD_ROWS');
      foreach($downItems as $cid => $citem){
        $this->tpl->assign(array(
          'ORDER_ITEM_DOWNLOAD_FILE_URL' => $citem['_url'],
          'ORDER_ITEM_DOWNLOAD_FILE_NAME' => $citem['filename'],
          'ORDER_ITEM_DOWNLOAD_FILE_SIZE' => $citem['_sizeFormated'],
        ));
        $this->tpl->parse('ORDER_ITEM_DOWNLOAD_ROWS', 'item-download-item', true);
        $this->downloadItems++;
      }
      if(count($downItems) > 10){
        $this->tpl->assign(array(
          'ORDER_ITEM_DOWNLOAD_BLOCK_SCROLL_SIGN' => ' scrollable',
        ));
      }
      if(count($downItems) > 1){
        $this->tpl->assign(array(
          'ORDER_ITEM_DOWNLOAD_ALL_URL' => $Ph->getDownloadUrlAllItemsByGoods($this->Order->id, $oh, $itemId),
        ));
        $this->tpl->parse('ORDER_ITEM_DOWNLOAD_ALL_ELEMENT', 'item-download-item-all');

      }else{
        $this->tpl->assign(array(
          'ORDER_ITEM_DOWNLOAD_ALL_ELEMENT' => '',
        ));
      }

      $this->tpl->parse('ORDER_ITEM_DOWNLOAD_BLOCK', 'item-download-block');

    }catch(Exception $e){
      $this->tpl->assign(array('ORDER_ITEM_DOWNLOAD_BLOCK' => ''));
    }
*/

    // Promotions
    if($this->parsePromotions
      && is_array($item['promotions']) && !empty($item['promotions'])){
      $this->tpl->clear_vars(array('PROMO_ITEMS'));
      foreach($item['promotions'] as $promoId => $promo){
        $this->tpl->assign(array(
          'PROMO_ITEM_TEXT' => $promo['title']
        ));
        $this->tpl->parse('PROMO_ITEMS', 'promos-item', true);
      }
      $this->tpl->parse('ORDER_ITEM_PROMOTIONS', 'promos');
    }else{
      $this->tpl->assign(array('ORDER_ITEM_PROMOTIONS' => ''));
    }

    // Tform

    return $this->tpl->parse(false, 'item');
  }

  function parseExtraFields($data, $oh){
    $r = '';
    if(!is_array($data) || !count($data)){
      return $r;
    }
    foreach($data as $eName => $eValue){

      $A = $oh->isA($eName) ? $oh->A($eName) : false;
      if(!$A){
        continue;
      }

      $eValue = isset($data[$eName.'__value']) && !empty($data[$eName.'__value'])
        ? $data[$eName.'__value']
        : $eValue;

      $eName = $A->display();
      $n = htmlspecialchars($eName);
      $v = htmlspecialchars($eValue);
      $this->tpl->assign(array(
        'ORD_EXTRA_NAME' => htmlspecialchars($eName),
        'ORD_EXTRA_VALUE' => htmlspecialchars($eValue),
        'ORD_EXTRA_NAME_'.strtoupper($A->getCode()) => $n,
        'ORD_EXTRA_VALUE_'.strtoupper($A->getCode()) => $v,
      ));

      $r .= $this->tpl->parse(false, 'extraItem');
    }
    return $r;
  }

}
