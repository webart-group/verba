<?php
namespace Verba\Mod;

class Review extends \Verba\Mod{
    use \Verba\ModInstance;
  public static $ratingNominals = array(
    1165 => 1,
    1166 => 2,
    1167 => 3,
    1168 => 4,
    1169 => 5,
  );
/*
  function addEntry(){
    $tpl = $this->tpl();
    try{
      $_review = \Verba\_oh('review');
      $ot_id = $_review->getID();
      if(!isset($_REQUEST['NewObject'][$ot_id])
      || !is_array($_REQUEST['NewObject'][$ot_id])
      || !isset($_REQUEST['NewObject'][$ot_id]['name'])
      || !isset($_REQUEST['NewObject'][$ot_id]['review'])
      ){
        throw new Exception(Lang::get('review add error badIncoming'));
      }
      $data = array(
        'name' => $_REQUEST['NewObject'][$ot_id]['name'],
        'review' => $_REQUEST['NewObject'][$ot_id]['review'],
        //'_picture_config' => $_REQUEST['NewObject'][$ot_id]['_picture_config'],
        'active' => '0',
      );
      $ae = $_review->initAddEdit('new');
      $ae->setGettedObjectData($data);
      $ae->addedit_object();

    }catch(Exception $e){
      $e = new Exception(Lang::get('review add error badOperation'));
      $e->ae = isset($ae) ? $ae : null;
      throw $e;
    }

    return $ae;
  }
*/
/*
  function sendCreationNonifyEmail($item){
    $tpl = $this->tpl();
    if(!is_array($item)){
      $this->log()->error('Unable to find review data');
      return false;
    }

    $mcfg = \Verba\_mod('order')->gC('mailing');

    $tpl->define(array(
      'body' => 'review/create/notify/body.tpl',
      'subject' => 'review/create/notify/subject.tpl',
    ));

    $acpUrl = new \Url(SYS_THIS_HOST.\Verba\_mod('acp')->gC('url'));
    $acpUrl = $acpUrl->get(true);
    $tpl->assign(array(
      'ACP_URL' => $acpUrl,
      'NAME' => htmlspecialchars($item['name']),
      'EMAIL' => htmlspecialchars($item['email']),
      'TEXT' => htmlspecialchars($item['review']),
      'CREATED' => utf8fix(strftime("%d %b %Y %H:%M", strtotime($item['created']))),
      'SHOP_NAME' => \Verba\Lang::get('order shopName'),
    ));

    $mMail = \Verba\_mod('comail');
    $mail = $mMail->PHPMailer($mcfg['mail']);

    $mail->setSubject($tpl->parse(false, 'subject'));
    $mail->MsgHTML($tpl->parse(false, 'body'));

    if(!isset($mcfg['to']['creation']) || empty($mcfg['to']['creation'])){
      return false;
    }
    foreach($mcfg['to']['creation'] as $tomail => $toname){
      $mail->AddAddress($tomail, $toname);
    }
    if(!$mMail->Send($mail)){
      $this->log()->error($mail->ErrorInfo);
      return false;
    }
    return true;
  }
*/
  function getNominalFromRatingId($val){
    return $val && is_numeric($val) && array_key_exists($val,self::$ratingNominals)
      ? self::$ratingNominals[$val]
      : false;
  }
  function getRatingIdFromNominal($val){
    if(!is_array($keys = array_keys(self::$ratingNominals, $val))
      || !count($keys)){
      return false;
    }
    return current($keys);
  }

  /**
   * @param $Store \Model\Store
   * @param $U \Verba\Mod\User\Model\User
   */
  function checkIfAllowCreateReview($Store, $U){
    $_order = \Verba\_oh('order');
    $q = "SELECT COUNT(id) FROM ".$_order->vltURI()."
    WHERE 
    `storeId` = '".$Store->getId()."'
    && `owner` = '".$U->getID()."'
    && `payed` = 1";

    $sqlr = $this->DB()->query($q);
    $ordersCount = $sqlr->getNumRows() ? (int)$sqlr->getFirstValue() : 0;
    if(!$ordersCount){
      return \Verba\Lang::get('review check no_orders');
    }
    $_review = \Verba\_oh('review');
    $q = "SELECT COUNT(id) FROM ".$_review->vltURI()."
    WHERE 
    `storeId` = '".$Store->getId()."'
    && `owner` = '".$U->getID()."'";

    $sqlr = $this->DB()->query($q);
    $reviewsCount = $sqlr->getNumRows() ? (int)$sqlr->getFirstValue() : 0;
    if($reviewsCount >= $ordersCount){
      return \Verba\Lang::get('review check rv_must_be_less');
    }

    return true;
  }
}
?>