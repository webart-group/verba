<?php
class page_content extends \Verba\Block\Html{

  public $bodyClass;

  function prepare()
  {
    if(!empty($this->bodyClass)){
      $this->getBlockByRole('HtmlBody')->addCssClass($this->bodyClass);
    }
  }

}

?>