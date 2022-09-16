<?php
class game_allGamesSelectorButton extends \Verba\Block\Html{

  public $scripts = array(
    array('all-games-selector', 'game')
  );

  public $templates = array(
    'content' => 'game/hooter-selector/button.tpl'
  );


  function build()
  {

    $this->mergeHtmlIncludes(new page_htmlIncludesForm($this));
    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }
}
?>