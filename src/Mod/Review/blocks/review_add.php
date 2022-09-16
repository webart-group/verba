<?php
class review_add extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'form/add-result-report.tpl',
  );
  function build(){

    throw  new \Verba\Exception\Building();

    $mReview = \Verba\_mod('review');

    try{
      $ae = $mReview->addEntry();
      $msg = \Verba\Lang::get('review add success message');
      $title = \Verba\Lang::get('review add success title');
      $class = 'success';
      if(!$mReview->sendCreationNonifyEmail($ae->getObjectData())){
        $this->log()->error('Unable to set notify email after review creation');
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