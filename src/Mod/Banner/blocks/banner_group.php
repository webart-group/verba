<?php
class banner_group extends \Verba\Block\Html{

  public $templates = array(
    'block' => '/banners/group/wrap.tpl',
    'item' => '/banners/group/item.tpl'
  );

  public $pot = 52;
  public $limit;
  public $format;
  public $piid;

  public $group_class = '';
  public $group_class_default = 'clearfix bnr-group';
  public $item_class = '';
  public $item_class_default = 'bnr-item';

  public $tplvars = array(
    'ITEMS' => '',
  );

  function  setLimit($val){
    $this->limit = (int)$val;
  }

  function build(){

    if(!isset($this->piid)){
      return '';
    }

    $catId = $this->piid;
    $_cat = \Verba\_oh('catalog');
    $_banner = \Verba\_oh('banner');

    $qm = new \Verba\QueryMaker($_banner, false, true);
    $qm->addConditionByLinkedOT($_cat, $catId);
    if(is_string($this->format)){
      $qm->addWhere($this->format, 'format');
    }
    $qm->addOrder(array('priority' => 'd'));
    $qm->addWhere(1, 'active');
    if(is_numeric($this->limit) && $this->limit > 0){
      $qm->addLimit($this->limit);
    }

    $sqlr = $qm->run();

    if(!$sqlr || !$sqlr->getNumRows()){
      return '';
    }
    $mImg = \Verba\_mod('image');

    $gclass = (string)$this->group_class_default;

    $gclass .= is_string($this->group_class) && !empty($this->group_class)
      ? ' '.$this->group_class
      : '';

    $this->tpl->assign(array(
      'GROUP_CLASS' => $gclass,
    ));

    $iclass = (string)$this->item_class_default;
    $iclass .= is_string($this->item_class) && !empty($this->item_class)
      ? ' '.$this->item_class
      : '';

    while($item = $sqlr->fetchRow()){
      $imgCfg = $mImg->getImageConfig($_banner->p('picture_config'));
      $this->tpl->assign(array(
        'ITEM_TITLE' => $item['title'],
        'ITEM_URL' => $item['url'],
        'ITEM_IMAGE_URL' => $imgCfg->getFullUrl(basename($item['picture'])),
        'ITEM_CLASS' => (isset($item['css_class']) && !empty($item['css_class'])
          ? ($iclass . ' ' . $item['css_class'])
          : $iclass),
        'ITEM_DESCRIPTION' => $item['description'],
        'ITEM_BUTTON_TITLE' => $item['button_title'],
      ));
      $this->tpl->parse('ITEMS', 'item', true);
    }

    $this->content = $this->tpl->parse(false, 'block');
    return $this->content;
  }
}
?>