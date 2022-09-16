<?php

class page_contentTitled extends page_content
{

  public $templates = array(
    'content' => 'content/titled/content.tpl',
    'content_wrap' => 'content/titled/body.tpl',
    'title' => 'content/titled/title.tpl',
  );

  public $title;

  public $agregate_parse = true;

  function setContent($var){
    if(is_object($var) && $var instanceof BlockInterface){
      $this->addItems(array(
        'CONTENT' => $var,
      ));
      return null;
    }
    parent::setContent($var);
  }

  function build(){

    /**
     * @var $contentBlock profile_pageContent
     */
    $contentBlock = $this->getItem('CONTENT');

    if(!is_string($this->tpl->getVar('TITLE'))){

      if(!is_string($this->title)){

        if($contentBlock && is_string($contentBlock->titleLangKey) && !empty($contentBlock->titleLangKey)) {

          $this->title = \Verba\Lang::get($contentBlock->titleLangKey);

        }else{

          /**
           * @var $mMenu Menu
           */
          $mMenu = \Verba\_mod('menu');
          $menu = $mMenu->getActiveNode();
          $this->title = isset($menu['title']) && is_string($menu['title']) ? $menu['title'] : '';

        }
      }

      $this->tpl->assign(array(
        'TITLE' => $this->title
      ));
    }

    if($this->tpl->isDefined('title') && $this->tpl->getTemplate('title')){
      $this->tpl->parse('TITLE', 'title');
    }

    $this->title = $this->tpl->getVar('TITLE');


    // CONTENT

    if(!is_string($this->tpl->getVar('CONTENT'))){

      if(!is_string($this->content)){
        if(is_array($this->items) && count($this->items)){
          $this->content = '';
          foreach($this->items as $itm){
            $this->content .= $itm->content;
          }
        }else{
          $this->content = '~ missed content ~';
        }

      }
      $this->tpl->assign(array(
        'CONTENT' => $this->content,
      ));
    }

    if($this->tpl->isDefined('content_wrap') && $this->tpl->getTemplate('content_wrap')){
      $this->tpl->parse('CONTENT', 'content_wrap');
    }

    if($this->agregate_parse){
      $this->content = $this->tpl->parse(false, 'content');
    }else{
      $this->content = $this->tpl->getVar('CONTENT');
    }

    $this->tpl->clear_vars();

    return $this->content;
  }

  function setTitle($val){
    if(!is_string($val)){
      return false;
    }
    $this->title = trim($val);
  }
}

?>