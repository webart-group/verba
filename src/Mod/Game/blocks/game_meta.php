<?php
/**
 *
 * Meta-теги для каталога или страницы товара
 *
 */
class game_meta extends \Verba\Block\Html{
  /**
   * @var \Model\Item
   */
  public $prodItem;

    /**
     * @var \Verba\Mod\Game\ServiceRequest
     */
    public $gsr;


  function build(){
    $this->content = '';
    if(!$this->gsr|| !$this->gsr->isValid()){
      return $this->content;
    }

    /**
     * @var $mCat Catalog
     */
    $mCat = \Verba\_mod('catalog');
    $mCat->addCatsToBreadcrumbs(array(
      $this->gsr->game->id => $this->gsr->game->getData(),
      $this->gsr->service->id => $this->gsr->service->toArray(),
    ), '/buy');

    if(is_object($this->prodItem)){
      /**
       * @var $mMenu Menu
       */
      $mMenu = \Verba\_mod('menu');

      $meta_item = array(
        'ot_id' => $this->prodItem->getOh()->getID(),
        $this->prodItem->getOh()->getPAC() => $this->prodItem->getIid(),
        'title' => $this->prodItem->getValue('title'),
      );
      if($this->prodItem->getOh()->A('title')->isLcd()){

        $meta_item['meta_'.SYS_LOCALE] =
        $meta_item['title_'.SYS_LOCALE] = (
        !empty($this->prodItem->getRawValue('title_'.SYS_LOCALE))
          ? $this->prodItem->getRawValue('title_'.SYS_LOCALE)
          : $this->prodItem->getRawValue('title')
        );
      }
      $mMenu->addMenuChain($meta_item);
    }


    return $this->content;
  }
}