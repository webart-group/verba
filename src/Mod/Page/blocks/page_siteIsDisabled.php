<?php
class page_siteIsDisabled extends \Verba\Block\Html{

  function route(){
    $h = new \Verba\Response\Raw($this->request);

    $response = $h->route();
    $response->addItems($this);
    return $response;
  }

  function build(){

//    $ban = array(
//      '37.79.227.157',
//    );
//    if(in_array(\Verba\getClientIP(), $ban)){

//    }
    $this->addHeader('Location', '/update.html');
  }
}

?>
