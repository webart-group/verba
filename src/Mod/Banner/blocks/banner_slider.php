<?php
class banner_slider extends \Verba\Block\Html{

  public $templates = array(
    'wrap' => '/index/slider/wrap.tpl',
    'item' => '/index/slider/item.tpl',
  );

  function init(){
    $this->addCss(array(
      array('base big-promo-slider', 'jcarousel'),
      array('index')
    ));
    $this->addScripts(array(
      array('jquery.jcarousel.min', 'jquery/jcarousel/')
    ));
  }

  function build(){

    $_banner = \Verba\_oh('banner');
    $_cat = \Verba\_oh('catalog');

    $qm = new \Verba\QueryMaker($_banner, false, true);
    $qm->addOrder(array('priority' => 'd'));
    $qm->addWhere(1, 'active');
    $qm->addWhere('index-slider', 'format');
    $sqlr = $qm->run();

    if(!$sqlr || !$sqlr->getNumRows()){
      return '';
    }

    $tpl = $this->tpl();

    $tpl->assign(array(
      'SLIDER_ITEMS' => '',
    ));
    $mImage = \Verba\_mod('image');
    while($item = $sqlr->fetchRow()){

      if(!empty($item['url'])){
        $tpl->assign(array(
          'ITEM_URL' => $item['url'],
          'ITEM_URL_SIGN' => '',
        ));
      }else{
        $tpl->assign(array(
          'ITEM_URL' => 'javascript:;',
          'ITEM_URL_SIGN' => ' no-link',
        ));
      }

      if(!empty($item['title']) || !empty($item['description'])){
        $tpl->assign(array(
          'ITEM_TITLE' => $item['title'],
          'ITEM_DESCRIPTION' => $item['description'],
          'ITEM_BUTTON_TITLE' => isset($item['button_title']) && !empty($item['button_title'])
            ? $item['button_title']
            : \Verba\Lang::get('banner slideshow defaultButtonTitle'),
          'INFO_WRAP_SIGN' => '',
        ));
      }else{
        $tpl->assign(array(
          'ITEM_TITLE' => '',
          'ITEM_DESCRIPTION' => '',
          'ITEM_BUTTON_TITLE' => '',
          'INFO_WRAP_SIGN' => ' no-info',
        ));
      }

      switch($item['scheme']){
        case '996': // inframe
            $scheme = 'bnr-scheme-inframe';
            $imgKey = 'inframe';
            break;
        case '995':
        default:
            $scheme = 'default';
            $imgKey = null;
      }

      $tpl->assign(array(
        'ITEM_SCHEME' => ' '.$scheme,
      ));

      if(!empty($item['picture'])){
        $imgCfg = $mImage->getImageConfig($_banner->p('picture_config'));
        $imageUrl = $imgCfg->getFullUrl(basename($item['picture']), $imgKey);

        $tpl->assign(array(
          'ITEM_PICTURE_URL' => $imageUrl,
        ));
      }else{
        $tpl->assign(array(
          'ITEM_PICTURE_URL' => '/images/1px.gif',
        ));
      }


      $tpl->parse('SLIDER_ITEMS', 'item', true);
    }

    $this->content = $tpl->parse(false, 'wrap');
    return $this->content;
  }
}
?>