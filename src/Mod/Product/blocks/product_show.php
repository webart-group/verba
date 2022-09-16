<?php
class product_show extends \Verba\Block\Html{

  public $item;
  public $oh;
  public $iid;
  public $cats;
  public $cdata;
  public $plH;
  public $flt_attrs = array();
  public $urlClear;
  public $catFltNamePfx;

  function init(){

    $this->addCss(array(
      array('products promotion'),
      array('base product', 'jcarousel'),
      array('jquery.fancybox helpers/jquery.fancybox-buttons', SYS_JS_URL.'/jquery/plugins/fancybox'),

    ));

    $this->addScripts(array(
      array('product-vars'),
      array('jquery.jcarousel.min jcarousel.basic', 'jquery/jcarousel'),
      array('jquery.fancybox.pack jquery.mousewheel-3.0.6.pack helpers/jquery.fancybox-buttons', 'jquery/plugins/fancybox'),
    ));

    $_catalog = \Verba\_oh('catalog');
    $mProduct = \Verba\_mod('product');

    $this->cats = $this->request->getParam('cats');
    $this->cdata = current($this->cats);

    $this->oh = $_product = \Verba\_oh($this->request->ot_id);
    $this->iid = $iid = $this->request->iid;
    $this->item = $item = $mProduct->getItem($this->oh, $this->iid, $this->cdata[$_catalog->getPAC()]);

    $this->addItems(array(
      'ITEM_COMMENTS_LIST' => new comment_publicList($this->request),
      'ITEM_COMMENT_FORM' => new comment_publicForm($this->request),
    ));

    $mMenu = \Verba\_mod('menu');
    $mCatalog = \Verba\_mod('catalog');
    $mCatalog->addCatsToBreadcrumbs(array_reverse($this->cats), '');
    $meta_item = array(
      'ot_id' => $this->oh->getID(),
      $this->oh->getPAC() => $this->iid,
      'title' => $item['title'],
    );
    if($_product->A('title')->isLcd()){
      $lc_val =  isset($item['title_'.SYS_LOCALE]) ? $item['title_'.SYS_LOCALE] : $item['title'];
      $meta_item['meta_'.SYS_LOCALE] =
      $meta_item['title_'.SYS_LOCALE] = $lc_val;
    }
    $mMenu->addMenuChain($meta_item);

  }

  function build(){

    $slID = isset($_REQUEST['slID']) ? $_REQUEST['slID'] : false;

    $_catalog = \Verba\_oh('catalog');
    $mCatalog = \Verba\_mod('catalog');

    $iid = $this->iid;
    if(!$this->item){
      throw new Exception(Lang::get('products not_found'));
    }
    $_image = \Verba\_oh('image');
    $mImage = \Verba\_mod('image');

    $this->urlClear = new \Url(Seo::idToSeoStr($this->item));

    if($slID){
      $this->addHeadTag('link', array('rel' => 'canonical', 'href'=>$this->urlClear->get(true)));
    }

    $cur = \Verba\_mod('cart')->getCurrency();

    $this->tpl->define(array(
        'block' => 'product/show/block.tpl',
        'images_by_variant_block' => 'product/show/images_by_variant_block.tpl',
        'big_images_by_variant_block' => 'product/show/big_images_by_variant_block.tpl',
        'image_by_variant' => 'product/show/image_by_variant.tpl',
        'big_image_by_variant' => 'product/show/big_image_by_variant.tpl',
        'tab-button' => 'product/show/tab-button.tpl',
        'tab-content' => 'product/show/tab-content.tpl',
        'extended-param' => 'product/show/extended-param.tpl',
        'param-link' => 'product/show/param-link.tpl',
        'param-link-itemprop' => 'product/show/param-link-itemprop.tpl',
        'param-flt-link' => 'product/show/param-flt-link.tpl',
        'item-comments-tab' => 'product/show/comment/wrap.tpl',
//        'item_video' => 'product/show/item_video.tpl',
    ));

    $this->tpl->assign(array(
      'ITEM_ID' => $this->item[$this->oh->getPAC()],
      'ITEM_OT_ID' => $this->item['ot_id'],
      'ITEM_TITLE' => $this->item['title'],
      'ITEM_ARTICUL' => $this->item['articul'],
      'ITEM_COUNTRY' => $this->item['country__value'],
      'ITEM_PRICE' => \Verba\reductionToCurrency($this->item['price']),
      'ITEM_PRICE_SIGN' => $cur->short,
      'ITEM_PICTURE' => '',
      'ITEM_ALL_VARIANTS_IMAGES' => '',
      'CATALOG_ID' => $this->item['p_iid'],
      'CATALOG_TITLE' => $this->item['ctitle'],
      'ITEM_EXTENDED_PARAMS' => '',
      'INFO_TABS' => '',
      'PRICE_VARIANTS' => $this->parsePriceVariants($this->item),
      'ITEM_ALL_VARIANTS_BIG_IMAGES' => '',
    ));

    $catUrl = array();
    foreach($this->cats as $ccat){
      $catUrl[] = $ccat['code'];
    }
    $catUrl[] = 'catalog';
    $catUrl = '/'.implode('/',array_reverse($catUrl));
    $this->catUrlBase = $catUrl.'?slID='.$slID;
    $this->catFltNamePfx = $slID.'[flt]';
    /*brand*/
    $this->tpl->assign(array(
      'LINK_HREF' => $this->catUrlBase.'&'.$this->catFltNamePfx.'['.$this->oh->A('brandId')->getId().']='.$this->item['brandId'],
      'LINK_TEXT' => $this->item['brandId__value'],
      'LINK_ITEMPROP' => 'name',
    ));
    $this->tpl->parse('ITEM_BRAND_LINK','param-link-itemprop');
    //type
    $this->tpl->assign(array(
      'LINK_HREF' => $catUrl,
      'LINK_TEXT' => $this->oh->getTitle(),
    ));
    $this->tpl->parse('ITEM_TYPE','param-link');

    $catCfg = unserialize($this->cdata['config']);
    if(is_array($catCfg) && isset($catCfg['filters']) && is_array($catCfg['filters']) && count($catCfg['filters'])){
      foreach($catCfg['filters'] as $fcode => $fdata){
        if($this->oh->isA($fcode)){
          $fltalias = $this->oh->A($fcode)->getID();
        }else{
          $fltalias = $fcode;
        }
        $this->flt_attrs[$fdata['attr']] = $fltalias;
      }
    }
    //extended params
    $this->tpl->assign(array(
      'ITEM_EXTENDED_PARAMS' => $this->parseExtraFields()
    ));

    // images
    $variantsImages = array();

    self::imagesByVariants($variantsImages, $this->iid, $this->item);

    if(is_array($this->item['_variants'])){
      foreach($this->item['_variants'] as $cvar){
        $cvarId = $cvar[$this->oh->getPAC()];
        self::imagesByVariants($variantsImages, $this->iid, $cvar);
      }
    }

    $i = 0;
    foreach($variantsImages as $key => $images){
      $this->tpl->clear_vars(array('ITEM_IMAGES_BY_VARIANT', 'ITEM_BIG_IMAGES_BY_VARIANT'));
      if(!is_array($images) || !count($images)){
        continue;
      }
      $this->tpl->assign(array(
        'ITEM_IMAGES_VARIANT_KEY' => $key,
      ));
      $i=0;
      foreach($images as $imgParams){
        $imgCfg = $mImage->getImageConfig($imgParams['cfg']);
        $largeUrl = $imgCfg->getFullUrl($imgParams['image'], 'large');
        $this->tpl->assign(array(
          'ITEM_IMAGE_INDEX' => $i++,
          'ITEM_IMAGES_ID' => $key.'-'.md5($imgCfg->getFullUrl($imgParams['image'], 'largest')),
          'ITEM_IMAGE_THUMB_URL' => $imgCfg->getFullUrl($imgParams['image'], 'thumbs'),
          'ITEM_IMAGE_NORMAL_URL' => $imgCfg->getFullUrl($imgParams['image']),
          'ITEM_IMAGE_LARGE_URL' => $largeUrl,
          'ITEM_IMAGE_ALT' => isset($imgParams['alt']) && !empty($imgParams['alt']) ? htmlspecialchars($imgParams['alt']) : '',
        ));

        $this->tpl->parse('ITEM_IMAGES_BY_VARIANT', 'image_by_variant', true);
        $this->tpl->parse('ITEM_BIG_IMAGES_BY_VARIANT', 'big_image_by_variant', true);
      }
      $this->tpl->parse('ITEM_ALL_VARIANTS_IMAGES', 'images_by_variant_block', true);
      $this->tpl->parse('ITEM_ALL_VARIANTS_BIG_IMAGES', 'big_images_by_variant_block', true);
    }

    // info tabs
    $tabs = array('description', 'composition', 'instruction');
    $itemprops = array('description');
    $tabs = Configurable::substNumIdxAsStringValues($tabs);
    foreach($tabs as $tabkey => $tabcfg){
      if(!isset($this->item[$tabkey])){
        continue;
      }
      if(isset($tabcfg['title'])){
        $tabtitle = \Verba\Lang::get($tabcfg['title']);
      }elseif($this->oh->isA($tabkey)){
        $tabtitle = $this->oh->A($tabkey)->getTitle();
      }else{
        $tabtitle = '?';
      }

      $this->tpl->assign(array(
        'ITEM_TAB_ID' => 'itab_'.$this->oh->getID().'_'.$this->iid.'_'.$tabkey,
        'ITEM_TAB_BUTTON_TITLE' => $tabtitle,
        'ITEM_TAB_CONTENT' => $this->item[$tabkey],
        'ITEM_TABBED_ITEMPROP' => in_array($tabkey, $itemprops) ? ' itemprop="'.$tabkey.'"' : '',
      ));
      $this->tpl->parse('ITEM_TABS_BUTTONS', 'tab-button', true);
      $this->tpl->parse('ITEM_TABS_PANES', 'tab-content', true);
    }

    // comments
    $this->tpl->assign(array(
      'ITEM_TAB_ID' => 'itab_'.$this->oh->getID().'_'.$this->iid.'_comments',
      'ITEM_TAB_BUTTON_TITLE' => \Verba\Lang::get('comment form tab-title', array('comm_count' => $this->item['comments_count'])),
      'COMMENTS_LIST_TITLE_ENDING' => $this->item['title'],
    ));
    $this->tpl->parse('ITEM_TAB_CONTENT', 'item-comments-tab');

    $this->tpl->parse('ITEM_TABS_BUTTONS', 'tab-button', true);
    $this->tpl->parse('ITEM_TABS_PANES', 'tab-content', true);

    //promos
    $_promo = \Verba\_oh('promotion');
    $qm_promo = new \Verba\QueryMaker($_promo, false, true);
    $pcnd = $qm_promo->addConditionByLinkedOT($this->oh, $this->iid);
    $qm_promo->addWhere(1, 'active');
    $q = $qm_promo->getQuery();
    $sqlr = $this->DB()->query($q);
    if($sqlr && $sqlr->getNumRows()){
      $this->tpl->define(array(
        'promo-wrap' => '/product/list/promo/wrap.tpl',
        'promo-item' => '/product/list/promo/item.tpl',
      ));
      $this->tpl->clear_vars(array('PROMO_ITEMS'));
      while($prow = $sqlr->fetchRow()){
        $this->tpl->assign(array(
          'PROMO_ITEM_ANNO' => !empty($prow['annotation']) ? $prow['annotation'] : $prow['title']
        ));
        $this->tpl->parse('PROMO_ITEMS', 'promo-item', true);
      }
      $this->tpl->parse('ITEM_PROMOS', 'promo-wrap');
    }else{
      $this->tpl->assign(array('ITEM_PROMOS' => ''));
    }

    // js cfg
    $jsVariants = array();
    self::jsCfgByVariant($jsVariants, $this->iid, $this->item);
    if(is_array($this->item['_variants'])){
      foreach($this->item['_variants'] as $cvar){
        $cvarId = $cvar[$this->oh->getPAC()];
        self::jsCfgByVariant($jsVariants, $cvarId, $cvar);
      }
    }

    $jsItemCfg = array('variants' => $jsVariants);

    $this->tpl->assign(array(
      'ITEM_CFG' => json_encode($jsItemCfg),
    ));

    $this->content = $this->tpl->parse(false, 'block');
    return $this->content;
  }

  function parseExtraFields(){
    $eparams = $this->oh->getAttrsByBehaviors('custom');
    unset($eparams[8241]);
    if(!count($eparams)){
      return '';
    }
    $eparams = Configurable::substNumIdxAsStringValues($eparams);
    $r = '';
    foreach($eparams as $pkey => $pcfg){
      if(!isset($this->item[$pkey])){
        continue;
      }
      $A = $this->oh->A($pkey);
      $acode = $A->getCode();
      if(isset($pcfg['title'])){
        $ptitle = \Verba\Lang::get($pcfg['title']);
      }else{
        $ptitle = $A->getTitle();
      }

      if($A->isPredefined()){
        // is multiple select
        if($A->data_type == 'multiple' && !empty($this->item[$pkey])){
          $pvalue_items = explode('#', $this->item[$pkey.'__value']);
          $pvalue = array();
          // Attr as Filter
          foreach($pvalue_items as $cpvalue){
            $vd = explode(':',$cpvalue);
            if(array_key_exists($acode, $this->flt_attrs)){
              $this->tpl->assign(array(
                'LINK_HREF' => $this->catUrlBase.'&'.$this->catFltNamePfx.'['.$this->flt_attrs[$acode].']='.$vd[0],
                'LINK_TEXT' => $vd[1],
              ));
              $pvalue[] = $this->tpl->parse(false, 'param-flt-link');
            }else{
              $pvalue[] = $vd[1];
            }
          }
          $pvalue = implode(', ', $pvalue);

        // siimple select
        }else{
          // Attr as Filter
          if(array_key_exists($acode, $this->flt_attrs)){
            $this->tpl->assign(array(
              'LINK_HREF' => $this->catUrlBase.'&'.$this->catFltNamePfx.'['.$this->flt_attrs[$acode].']='.$this->item[$pkey],
              'LINK_TEXT' => $this->item[$pkey.'__value'],
            ));
            $pvalue = $this->tpl->parse(false, 'param-flt-link');
          }else{
            $pvalue = $this->item[$pkey.'__value'];
          }
        }
      }else{
        $pvalue = $this->item[$pkey];
      }
      if($pvalue === false ||  $pvalue === null || is_string($pvalue) && !is_numeric($pvalue) && !$pvalue){
        continue;
      }
      $this->tpl->assign(array(
        'ITEM_PARAM_TITLE' => $ptitle,
        'ITEM_PARAM_VALUE' => $pvalue,
      ));
      $r .= $this->tpl->parse(false, 'extended-param');
    }
    return $r;
  }

  function parsePriceVariants($item){
    $_product = \Verba\_oh($this->request->ot_id);
    $cur = \Verba\_mod('cart')->getCurrency();

    $variants = array(
      $this->request->iid => array(
        'size' => $item['size'],
        'price' => \Verba\reductionToCurrency($item['price'] * $cur->rate),
        'size_unit' =>  $item['size_unit'],
        'size_unit__value' =>  $item['size_unit__value'],
        'in_stock' =>  $item['in_stock'],
        'color' => $item['color'],
      ),
    );
    if(!empty($item['_variants'])){
      foreach($item['_variants'] as $v){
        $variants[$v[$_product->getPAC()]] = array(
          'price' => \Verba\reductionToCurrency($v['price'] * $cur->rate),
          'color' => $v['color'],
          'size' => $v['size'],
          'size_unit' =>  $v['size_unit'],
          'size_unit__value' =>  $v['size_unit__value'],
          'in_stock' =>  $v['in_stock'],
        );
      }
      uasort($variants, array(\Verba\_mod('product'), 'sortVariants'));
    }

    $this->tpl->define(array(
      'variant-wrap' => '/product/list/variant/wrap.tpl',
      'variant-item' => '/product/list/variant/item.tpl',
    ));
    reset($variants);
    $first_var = current($variants);

    $this->tpl->assign(array(
      'ITEM_SIZE_UNIT_TITLE' => $first_var['size_unit__value'],
      'ITEM_CURRENCY_SHORT' => $cur->short,
      'VARIANT_OT_ID' => $this->oh->getID(),
    ));

    $this->tpl->clear_vars(array('VARIANTS_ITEMS'));
    foreach($variants as $vid => $cvar){
      $cvar_in_stock = isset($cvar['in_stock']) && $cvar['in_stock'] == 1;
      if(!empty($cvar['color'])){
        $color = htmlspecialchars($cvar['color']);
        $color_sign = '';
      }else{
        $color = '';
        $color_sign = ' no-color';
      }

      $this->tpl->assign(array(
        'VARIANT_ID' => $vid,
        'VARIANT_SIZE' => reductionToFloat($cvar['size']),
        'VARIANT_SIZE_UNIT' => (string)$cvar['size_unit__value'],
        'VARIANT_PRICE' => $cvar['price'],
        'VARIANT_COLOR' => $color,
        'VARIANT_COLOR_SIGN' => $color_sign,
        'VARIANT_IN_STOCK_TITLE' => \Verba\Lang::get('products fields in_stock '.((int)$cvar_in_stock)),
        'VARIANT_IN_STOCK_SIGN' => $cvar_in_stock ? 'in-stock' : 'not-in-stock',
        'VARIANT_IN_STOCK' => (int)$cvar_in_stock,
      ));

      $this->tpl->parse('VARIANTS_ITEMS', 'variant-item', true);
    }

    return $this->tpl->parse(false, 'variant-wrap');
  }

  function genColorSelector($item){
    $oh = \Verba\_oh($item['ot_id']);
    $ls = new \Verba\Html\Select();
    $ls->setName('item-colors');
    $ls->setId('item'.$item[$oh->getPAC()].'_variant_selector');

    $values = array($item[$oh->getPAC()] => $item['color']);
    if(is_array($item['_variants']) && !empty($item['_variants'])){
      foreach($item['_variants'] as $var){
        $values[$var[$oh->getPAC()]] = $var['color'];
      }
    }else{
      $ls->setDisabled(true);
    }
    $ls->setValues($values);
    $ls->setValue($item[$oh->getPAC()]);
    return $ls->parse();
  }

  function genSizesSelector($item){
    $ls = new \Verba\Html\Select();
    $ls->setName('item-sizes');
    $oh = \Verba\_oh($item['ot_id']);

    $sizes = $oh->A('sizes')->getValues();

    $cSizes = explode(',', $item['sizes']);

    $ls->setValues($sizes);

    $ls->setValue($cSizes);

    return $ls->parse();
  }

  static function jsCfgByVariant(&$jsVariants, $key, $item){
    if(!isset($item['size']) || empty($item['size'])){
      return;
    }
    if(!isset($jsVariants[$key]) || !is_array($jsVariants[$key])){
      $jsVariants[$key] = array();
    }
    $jsVariants[$key]['size'] = $item['size'];

  }

  static function imagesByVariants(&$colorsImages, $key, $item, $addLinkedImages = true){
    $addLinkedImages = (bool)$addLinkedImages;

    if(!isset($colorsImages[$key]) || !is_array($colorsImages[$key])){
      $colorsImages[$key] = array();
    }

    if($item['picture']){
      $colorsImages[$key][] = array(
        'image' => basename($item['picture']),
        //'cfg' => $item['_picture_config'],
        'alt' => isset($item['color']) && !empty($item['color'])
            ? $item['title'].' '.$item['color']
            : $item['title']
      );
    }

    if(!$addLinkedImages || !is_array($item['_images']) || !count($item['_images'])){
      return;
    }
    foreach($item['_images'] as $img){
      if(!$img['storage_file_name'] || !$img['_storage_file_name_config']){
        continue;
      }

      $colorsImages[$key][] = array(
        'image' => $img['storage_file_name'],
        'cfg' => $img['_storage_file_name_config'],
        'alt' => isset($item['color']) && !empty($item['color'])
            ? $item['title'].' '.$item['color']
            : $item['title'],
      );
    }
  }
}
?>
