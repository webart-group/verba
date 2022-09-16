<?php
class menu_CatalogMapParser extends \Verba\Block\Html{

  public $catalogMap;

  function build(){

    $this->catalogMap = new \Verba\Mod\Catalog\Map($this);

    $items = $this->catalogMap->build();

    $tpl = $this->tpl();
    $tpl->define(array(
      'menu_wrap' => '/menu/catalog-map/wrap.tpl',
      'top_item' => '/menu/catalog-map/top-item.tpl',
      'subitems_wrap' => '/menu/catalog-map/subitems-wrap.tpl',
      'subitems_column' => '/menu/catalog-map/subitems-column.tpl',
      'subitems_group' => '/menu/catalog-map/subitems-group.tpl',
      'subitems_group_head' => '/menu/catalog-map/subitems-group-head.tpl',
      'subitem' => '/menu/catalog-map/subitem.tpl',
    ));
    $ci = 1;
    foreach($items as $id => $top_item){
      $top_item_class = array();
      $tpl->assign(array(
        'ITEM_SUBITEMS' => '',
        'ITEMS_W_SIGN' => '',
      ));
      $gc = (int)$top_item['_groups'];
      if(count($top_item['items'])){
        if(count($top_item['items']) != $gc){
          foreach($top_item['items'] as $cid_l1 => $citem_l1){
            if(!count($citem_l1['items'])){
              $gc++;
              $top_item['items'][$cid_l1] = array(
                'no-head' => 1,
                'items' => array(
                  $cid_l1 => $citem_l1,
                )
              );
            }
          }
        }
        if(!$gc || $gc < 1 || $gc >= 3){
          $items_w = '';
          $cols = 3;
        }else{
          $items_w = 'x'.$gc;
          $cols = $gc;
        }

        $top_item_class[] = 'subitems';
        $tpl->assign(array(
          'ITEM_SUBITEMS_CLASS' => 'subitems',
          'ITEMS_W_SIGN' => $items_w,
        ));
        //columns
        $tpl->assign(array(
          'ITEM_ALL_COLUMNS' => '',
          'COLUMN_GROUPS' => ''
        ));
        $cols_groups = array_fill(1, $cols, array());
        $ci = 0;
        foreach($top_item['items'] as $id_l1 => $item_l1){
          if(++$ci > $cols){
            $ci = 1;
          }

          $tpl->assign(array(
            'GROUP_ITEMS' =>'',
            'GROUP_HEAD' =>'',
            'GROUP_SIGN' =>'',
          ));

          if(isset($item_l1['no-head']) && $item_l1['no-head'] == 1){
            $tpl->assign(array(
              'GROUP_SIGN' => ' no-head',
            ));
          }else{
            $tpl->assign(array(
              'GROUP_TITLE' => $item_l1['title'],
              'GROUP_URL' =>$item_l1['url'],
            ));
            $tpl->parse('GROUP_HEAD', 'subitems_group_head');
          }

          foreach($item_l1['items'] as $id_l2 => $item_l2){
            $tpl->assign(array(
              'ITEM_URL' => $item_l2['url'],
              'ITEM_TITLE' => $item_l2['title'],
            ));
            $tpl->parse('GROUP_ITEMS', 'subitem', true);
          }
          $cols_groups[$ci][] = $tpl->parse(false, 'subitems_group');
        }

        for($i = 1; $i <= $cols; $i++){
          $tpl->assign('COLUMN_GROUPS', implode('', $cols_groups[$i]));
          $tpl->parse('ITEM_ALL_COLUMNS', 'subitems_column', true);
        }

        $tpl->parse('ITEM_SUBITEMS', 'subitems_wrap', true);
      }
      if(!empty($top_item['css_class'])){
        $top_item_class[] = $top_item['css_class'];
      }
      $tpl->assign(array(
        'ITEM_URL' => $top_item['url'],
        'ITEM_TITLE' => $top_item['title'],
        'ITEM_CLASS' => count($top_item_class) ? implode(' ', $top_item_class) : '',
      ));

      $tpl->parse('MENU_ITEMS', 'top_item', true);
    }
    $this->content = $tpl->parse(false, 'menu_wrap');
    return $this->content;
  }
}
?>