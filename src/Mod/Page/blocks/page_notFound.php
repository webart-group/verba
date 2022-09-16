<?php
class page_notFound extends content_getBlock{

//  public $templates = array(
//    'content'  => 'page/srv/404/body_content.tpl',
//  );

  public $id = 'err_404';
  public $title = false;
  public $parseContent = true;

  function prepare(){
    $this->request->iid = $this->id;
    parent::prepare();
    $this->addHeader('HTTP/1.1 404 Not Found');

    $this->tpl()->assign(array(
      'FORWARD_URL' => (new \Url('/'))->get(),
    ));
  }

//  function build(){
//    parent::build();
//    $Item = $this->getOItem();
//    if($Item && $Item->title){
//      $this->setNamedMeta('title', $Item->title);
//    }
//  }
}
?>
