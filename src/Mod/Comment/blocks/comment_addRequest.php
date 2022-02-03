<?php
class comment_addRequest extends \Verba\Block\Html{
  public $templates = array(
    'content' => 'form/add-result-report.tpl',
  );
  function build(){

    $mComment = \Verba\_mod('comment');

    try{
      $ae = $mComment->addEntry();
      $msg = \Verba\Lang::get('comment add success message');
      $title = \Verba\Lang::get('comment add success title');
      $class = 'success';
      if(!$mComment->sendCreationNonifyEmail($ae->getObjectData())){
        $this->log()->error('Unable to set notify email after comment creation');
      }
    }catch(Exception $e){
      $msg = $e->getMessage();
      $class = 'error';
      $title = \Verba\Lang::get('comment add error title');
    }

    $this->tpl->assign(array(
      'ADD_RESULT_MESSAGE' => $msg,
      'ADD_RESULT_CLASS' => $class,
      'ADD_RESULT_BACK_URL' => \Verba\Hive::getBackURL()
    ));

    $this->content = array('title' => $title, 'body' => $this->tpl->parse(false, 'content'));
    return $this->content;
  }
}
?>