<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:38
 */

namespace Verba\Mod\Paysys;

use Verba\Mod\Instance;

class Easypay extends \Verba\Mod
{

    use \Verba\ModInstance;
    use \Verba\Mod\Payment\Paysys;

//  function handleNotify($bp = null){
//    $bp = $this->extractBParams($bp);

    //$c = var_export($_REQUEST, true);
//    file_put_contents(SYS_VAR_DIR.'/easypay_log.txt', $c);
//    $this->log()->event($c);

//    try{
//      if(!$bp['iid']){
//        throw new Exception('Unknown Order Id');
//      }
//      $n = new PayNotify_Easypay($bp['iid']);

//      switch($n->status){
//        case 'success':
//          $this->log()->event("Payment is successful. Order Id:".$n->orderId);
//          break;
//        case 'error':
//          $this->log()->error("Payment Notify error. Status: ".$n->status.", Order Id:".$n->orderId);
//          break;
//        case 'not_valid':
//          $this->log()->error("Payment Result not valid. Status: ".$n->status.", Order Id:".$n->orderId);
//          break;
//        default:
//          $this->log()->error("Notify response unknown status".var_export($n, true));
//          break;
//      }

//      $this->updateOrderStatus($n);

//    }catch(Exception $e){
//      $this->log()->error($e->getTraceAsString());
//    }

//    return '';
//  }

    function updateOrderStatus($n){
        if(!$n instanceof PayNotify_Easypay
            || !$n->orderId
        ){
            throw new Exception('Unable to update Order status - notify object wrong format or notify order id missing');
        }

        $_order = \Verba\_oh('order');
        $mOrder = \Verba\_mod('order');
        $cfg = $mOrder->gC('paymentStatusAliases');
        $ae = $_order->initAddEdit('edit');
        $ae->setIID($n->orderId);
        $data = array();

        if($n->isValid()
            && isset($cfg[$n->status])){
            $data['status'] = $cfg[$n->status];
        }

        if(!isset($data['status'])){
            $data['status'] = $cfg['not_valid'];
        }

        $data['statusMsg'] = $n->getStatusMsg();

        $ae->setGettedObjectData($data);
        $r = $ae->addedit_object();
        return $ae;
    }

    function parsePaymentStatus($order){
        //require_once(SYS_VIEWS_DIR.'/shop/paysys/easypay/status/paymentstatus.php');
        $vw = new ViewOrderEasypayStatus();
        return $vw->parse($order);
    }

    function extractOrderDataFromRequest(&$ct){
        if(!is_array($ct)){
            return false;
        }
        if(isset($ct['merchant_id'])
            && isset($ct['order_id'])
            && isset($ct['payment_id'])
            && isset($ct['payment_type'])
            && isset($ct['commission'])
        ){
            $ct['iid'] = $ct['order_id'];
            $ct['__orderDataFoundBy'] = 'easypay';
        }
        return;
    }

}