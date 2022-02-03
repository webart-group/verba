<?php
class menu_Breadcrumbs extends \Verba\Block\Html{

  public $templates = array(
    'breadcrumbs_block' => 'menu/breadcrumbs/block.tpl',
    'breadcrumbs_row' => 'menu/breadcrumbs/item.tpl',
    'breadcrumbs_link' => 'menu/breadcrumbs/link.tpl',
    'breadcrumbs_delimiter' => 'menu/breadcrumbs/delimiter.tpl',
  );
  public $delimiter = '»';
  public $role = 'page-breadcrubms';

  function build(){
    $tpl = $this->tpl();

    $mMenu = \Verba\_mod('Menu');
    $nodes = $mMenu->getChain($this->rq->uf);
    if(!is_array($nodes)){
      return '';
    }

    $tpl->clear_vars(array('ITEMS'));
    $length = count($nodes);

    $rows = array();
    $iids = array();
    $num = 0;
    foreach($nodes as $row){
      ++$num;
      if(!$row['title']
      || $row['hidden'] == 1){
        continue;
      }
      $tpl->assign(array(
        'LINK_HREF' => $row['url'],
        'LINK_TITLE'  => htmlspecialchars($row['title']),
        'LINK_TEXT'  => $row['title'],
      ));
      $template = $num == $length  ? 'breadcrumbs_row' : 'breadcrumbs_link';
      $rows[] = $tpl->parse(false, $template, true);
    }

    $tpl->assign(array(
      'DELIMITER_VALUE' => $this->delimiter,
    ));
    $delimiter = $tpl->parse(false, 'breadcrumbs_delimiter');
    $tpl->assign(array(
      'ITEMS' => implode($delimiter, $rows),
    ));

    $this->content = $tpl->parse(false, 'breadcrumbs_block');
    return $this->content;
  }
}
?>