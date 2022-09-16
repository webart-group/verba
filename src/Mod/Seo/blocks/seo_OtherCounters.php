<?php
class seo_OtherCounters extends seo_public{

  function build(){
    $c = new textblock_getBlock(array('iid' => 'footer_counters'), array(
      'title' => false,
    ));
    $c->build();
    return $this->content = $c->content;
  }
}
?>