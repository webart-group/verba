<?php

namespace Verba\Mod\Sitemap\Block;

class GenerateMenu extends \Verba\Mod\Sitemap\Block\Generator {

  public $riid = 0;
  public $include_cnt;
  public $item;

  public $dataExtractor;

  function build(){

    $ctx = $this->getContext();
    if(!$ctx){
      return false;
    }

    $_menu = \Verba\_oh('menu');


    $aots = array($_menu->getID());
    if($this->include_cnt){
      $_cnt = \Verba\_oh('content');
      $aots[] = $_cnt->getID();
    }

    $Tree = new \Tree($_menu, $this->riid, 5, $aots);

    $Node = $Tree->buildNodesTree();
    $this->parseNode($Node);

    return $this->content;
  }

  /**
   * @param $Node \TreeNode
   */
  function parseNode($Node){
    $_menu = \Verba\_oh('menu');

    if(!$Node
      || !$Node->ot_id
      || !$Node->iid
      || !$Node->item['active']
    ){
      return false;
    }

    $ctx = $this->getContext();
    if($this->dataExtractor){
      $parseData = ($this->dataExtractor)($Node);
    }else{
      $parseData = $this->extractParseData($Node);
    }

    if($parseData === null){
      goto HANDLE_SUB_NODES;
    }

    if(!is_array($parseData)){
      return false;
    }

    if(!array_key_exists('LASTMOD', $parseData)){
      $parseData['LASTMOD'] = $ctx->lastmod;
    }
    if(!array_key_exists('CHANGEFREQ', $parseData)){
      $parseData['CHANGEFREQ'] = $ctx->changefreq;
    }

    DATA_PREPARED:
    $this->tpl->assign($parseData);

    $ctx->write("\n".$this->tpl->parse(false, 'url'));

    HANDLE_SUB_NODES:
    if($Node->item['ot_id'] != $_menu->getID()){
      return true;
    }

    if(is_array($Node->getNodes())){
      foreach ($Node->getNodes() as $sNode){
        $this->parseNode($sNode);
      }
    }
    return true;
  }

  function extractParseData($Node){

    $_menu = \Verba\_oh('menu');
    $_cnt = \Verba\_oh('content');

    $r = array(
      'LOC' => '',
    );

    // menu node
    if($Node->item['ot_id'] == $_menu->getID()){

      if($Node->item['hidden'] = 0){
        return null;
      }

      $r['LOC'] = (new \Url($Node->item['url']))->get(true);

      // content node
    }elseif($Node->item['ot_id'] == $_cnt->getID()){

      $r['LOC'] = (new \Url($Node->item['url']))->get(true);

    }else{
      return false;
    }

    return $r;
  }
}

