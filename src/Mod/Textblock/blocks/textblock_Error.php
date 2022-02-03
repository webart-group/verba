<?php
class textblock_Error extends \Verba\Block\Html{

  public $templates = array(
    'content' => '/textblock/error.tpl',
  );

  public $title = '';
  public $content = '';

  function build(){
    $title = !is_string($this->title) || !$this->title ? \Verba\Lang::get('general error') : $this->title;
    $this->tpl->assign(array(
      'TITLE' => $title,
      'CONTENT' => $this->content,
      'TITLE_VISIBILITY_CLASS' => empty($title) ? ' hidden' : '',
    ));
    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }
}
?>