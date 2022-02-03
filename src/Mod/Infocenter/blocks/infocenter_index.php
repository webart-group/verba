<?php
class infocenter_index extends infocenter_page {

  function prepare()
  {
    $this->content_title = \Verba\Lang::get('infocenter title');
    $this->cssClass[] = 'index';
  }

}
?>