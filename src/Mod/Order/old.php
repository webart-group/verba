<?php

/*
class OrderAdmin extends\Mod{

    protected $valid_objects = array('order');

    function makeAction($bp){
        switch($bp['action']){
            case 'list':
                $handler = 'manageList';
                break;
            case 'createform':
                $handler = 'createForm';
                break;
            case 'createtestorder':
                $handler = 'createTestOrder';
                break;
            case 'productlist':
                $handler = 'orderProducts';
                break;
            case 'transactions':
                $handler = 'orderTransactions';
                break;
            //case 'orderscustomerssync':
//              $handler = 'OrdersCustomersSync';
//              break;
//      case 'recancel':
//                    $handler = 'recancelOrders';
//                    break;
//      case 'reopen':
//                    $handler = 'reOpenOrders';
//                    break;
            case 'customerstotalsumsync':
                $handler = 'customersTotalSumSync';
                break;
            default :
                $handler = null;
        }

        if(!$handler){
            $handler = parent::makeAction($bp);
        }
        return $handler;
    }

    function customersTotalSumSync(){
        //return;


        set_time_limit(3600*3);
        $_order = \Verba\_oh('order');
        $_cst = \Verba\_oh('customer');
        $mCustomer = \Verba\_mod('customer');

        $q = "
UPDATE ".$_cst->vltURI()." c
SET
  c.totalSum = (
  SELECT SUM(topay) FROM ".$_order->vltURI()." o WHERE o.customerId = c.code && o.status = 21
    )
";

        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getAffectedRows()){
            return;
        }
        $mCustomer->cron_recountCutomerStatuses();
        return $sqlr->getAffectedRows();
    }

    function OrdersCustomersSync(){


        return;


        set_time_limit(3600*3);
        $_order = \Verba\_oh('order');
        $_cst = \Verba\_oh('customer');
        $_user = \Verba\_oh('user');
        $_goldwow = \Verba\_oh('goldwow');
        $mUserPublic = \Verba\_mod('userpublic');
        $mCustomer = \Verba\_mod('customer');

        $q = "
SELECT ddd.*, count(ddd.email) emc FROM (
SELECT
  LOWER(o.`email`) as email
  , `customerId`
  , count(customerId) csc
  , cs.code as ccustomerId
  , us.user_id
FROM
  ".$_order->vltURI()." as o

LEFT JOIN ".$_cst->vltURI()." as cs
ON lower(cs.email) = lower(o.email)

LEFT JOIN  ".$_user->vltURI()." as us
ON lower(us.email) = lower(o.email)
 GROUP BY  o.customerId
 ORDER BY csc DESC
) AS ddd
  GROUP BY  ddd.email
  ORDER BY emc DESC
";

        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getNumRows()){
            return;
        }

        while($row = $sqlr->fetchRow()){
            try{
                $profile = $mCustomer->findProfile($row['email'], true, false);
            }catch(Exception $e){
                $this->log()->error($e->getMessage());
                continue;
            }

            if(!$profile){
                $this->log()->error("Unable to create Customer profile for email '".var_export($row['email'], true)."'");
                continue;
            }
            $customerId = $profile->getId();
            $email = $profile->getEmail();
            $user_id = $profile->owner;

            //if($row['emc'] > 1
//      || $row['customerId'] != $row['ccustomerId']
//      || $row['customerId'] != $customerId
//      || $row['ccustomerId']!= $customerId
//      || !$row['user_id']
//      || $row['user_id'] != $user_id){

            // Update Orders to one customerId
            $ordersU = "UPDATE ".$_order->vltURI()." SET
customerId = '".$customerId."',
owner = '".$user_id."',
email = '".$email."'
WHERE lower(`email`) = '".$email."'";

            $sqlru = $this->DB()->query($ordersU);


            // Update GoldOrders to one customerId
            $qgold = "UPDATE ".$_goldwow->vltURI()." SET
customerId = '".$customerId."',
owner = '".$user_id."',
email = '".$email."'
WHERE lower(`email`) = '".$email."'";

            $this->DB()->query($qgold);

            // Refresh totalSumm
            $summCalc = "
SELECT
SUM(aaa.topay) AS summ
FROM (
SELECT
LOWER(o.`email`) as email
, IF(o.status = '21'|| o.status = '22',  o.topay, '0' ) AS topay
FROM
".$_order->vltURI()." as o
WHERE LOWER(o.email) = '".$email."'
ORDER BY email DESC
) AS aaa

GROUP BY aaa.email
ORDER BY summ DESC";
            $sqlrsumm =  $this->DB()->query($summCalc);

            $summ = $sqlrsumm->fetchRow();
            $summ = $summ['summ'];

            $cstSummUpdate = $this->DB()->query("UPDATE ".$_cst->vltURI()." SET
totalSum = '".$summ."'
WHERE `code` = '".$customerId."'
LIMIT 1");

            //}
        }
        $mCustomer->cron_recountCutomerStatuses();
        return;
    }

    function recancelOrders(){
        return ;
        set_time_limit(3600*3);
        $_order = \Verba\_oh('order');
        $mOrder = \Verba\_mod('order');

        $q = "
SELECT `id` FROM ".$_order->vltURI()." as o
WHERE created > '2013-06-12 23:59:59' && status IN(23, 27)";

        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getNumRows()){
            return;
        }

        while($row = $sqlr->fetchRow()){
            $order = $mOrder->getOrder($row['id']);
            $files = $order->getDownloadableItems();
            if($files && count($files)){
                $state = '120';
                foreach($files as $cfile){
                    $oh = \Verba\_oh($cfile['ot_id']);
                    $ae = $oh->initAddEdit('edit');
                    $ae->setIID($cfile[$oh->getPAC()]);
                    $ae->setGettedObjectData(array('state' => $state));
                    $ae->addParents($cfile['__pot'], $cfile['__piid']);
                    $ae->addedit_object();
                }
            }
        }
    }

    function reOpenOrders(){
        return ;
        set_time_limit(3600*3);
        $_order = \Verba\_oh('order');
        $mOrder = \Verba\_mod('order');
        $date = '2013-08-20 23:59:59';
        $q = "
(SELECT orderId FROM ".SYS_DATABASE.".`paysys_yandex_notify` AS oo
WHERE `created` > '".$date."' && oo.`status` = 'success')
  UNION
(SELECT orderId FROM ".SYS_DATABASE.".`paysys_webmoney_notify` AS a
WHERE created > '".$date."' && a.`status` = 'success')
  UNION
(SELECT orderId FROM ".SYS_DATABASE.".`paysys_robokassa_notify`  AS aa
WHERE created > '".$date."' && aa.`status` = 'success')
  UNION
(SELECT orderId FROM ".SYS_DATABASE.".`paysys_qiwi_notify` AS oz
WHERE created > '".$date."' && oz.`status` = 'success')
  UNION
(SELECT orderId FROM ".SYS_DATABASE.".`paysys_liqpay_notify` AS uy
WHERE created > '".$date."' && uy.`responseData` LIKE '%<status>success</status>%')
  UNION
(SELECT orderId FROM ".SYS_DATABASE.".`paysys_easypay_notify` AS of
WHERE created > '".$date."' && of.`status` = 'success')";

        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getNumRows()){
            return;
        }
        $iids = array();
        while($row = $sqlr->fetchRow()){
            $iids[] = $row['orderId'];
        }

        $q = "SELECT id FROM ".$_order->vltURI()." WHERE id IN ('".implode("', '", $iids)."') && status = '23'";
        $sqlr = $this->DB()->query($q);
        if(!$sqlr || !$sqlr->getNumRows()){
            return;
        }

        $statusId = '21';

        while($row = $sqlr->fetchRow()){
            $ae = $_order->initAddEdit('edit');
            $ae->setIID($row['id']);
            $ae->setGettedObjectData(array('status' => $statusId));
            $ae->addExtendedData(array(
                'silenceClient' =>  true,
                'silenceStaff' =>  true,
                'customerUpdateTotalSum' => false
            ));
            $id = $ae->addedit_object();
        }
    }

    function manageList($cfg){
        $_order = \Verba\_oh('order');
        $cfg['cfg'] = 'acp-order';
        $list = $_order->initList($bp);
        $l = $list->generateList();
        $q = $list->QM()->getQuery();
        return \Verba\Response\Json::wrap(true, $l);
    }

    function formateCreated($list, $row){
        return !isset($row['created']) || !is_numeric($t = strtotime($row['created']))
            ? ''
            : utf8fix(strftime('%d %b. %Y %H:%M', $t));
    }

    function getOrderCreationButtonAction($list){

        $url = new \Url($this->gC('createFormOrderUrl'));
        $params = $url->getParams();
        $params['pSlId'] = $list->getID();
        $url->setParams($params);
        $urlStr = $url->get();
        $listSelector = "#" . $list->getWrapId();
        $list->addScripts('wrk-products-order', 'list');
        $list->addWorker('ListProductsOrderCreateFormCaller', 'ListProductsOrderCreateFormCaller', array('url' => $urlStr));
        return $urlStr;
    }

    function formJson($bp = null){
        $bp = $this->extractBParams($bp);
        $bp['cfg'] = 'acp-order acp-order-'.$bp['action'];
        return parent::formJson($bp);
    }

    protected function createForm($bp){

        $_order = \Verba\_oh('order');
        $_prod = \Verba\_oh('product');

        $pSl = \Verba\init_selection(false, false,  $_REQUEST['pSlId']);
        if(!$pSl){
            throw new Exception('Invalid products selectionID');
        }
        if(!count($items = $pSl->getSelected($_prod->getID()))){
            return \Verba\Lang::get('order acp prodlist no_prods_selected');
        }

        $prods = $_prod->getData($items, true);
        if(!$prods || !count($prods)){
            return \Verba\Lang::get('order acp createform no_products_found');
        }
        $this->tpl->define(array(
            'acporderform' => 'shop/order/acp/createfromlist/form.tpl',
            'test-product-item' => 'shop/order/acp/createfromlist/product-item.tpl',
        ));
        $tcost = 0;
        foreach($prods as $prodId => $c_prod){
            $this->tpl->assign(array(
                'ACP_TEST_ORDER_PRODUCT_TITLE' => $c_prod['title'],
                'ACP_TEST_ORDER_PRODUCT_PRICE' => $c_prod['price'],
            ));
            $this->tpl->parse('ACP_TEST_ORDER_PRODUCTS', 'test-product-item', true);
            $tcost += $c_prod['price'];
        }
        $formCfg = array(
            'ot_id' => $_order->getID(),
            'action' => 'create',
            'cfg' => 'acp-test-order',
        );

        $form = $_order->initForm($formCfg);
        $Customer = \Verba\_mod('customer');
        $custProfile = $Customer->getProfile();
        $gdata = array(
            'email' => $custProfile->getEmail(),
            'name' => $custProfile->getName(),
            'surname' => $custProfile->getSurname(),
            'patronymic' => $custProfile->getPatronymic(),
            'phone' => $custProfile->getFirstPhone(),
            'discount' => $custProfile->getDiscount(),
            'discount_card' => $custProfile->getDiscountCard(),
        );
        $form->setExistsValues($gdata);
        $form->addHidden('pSlId', $_REQUEST['pSlId']);
        $this->tpl->assign(array(
            'ACP_TEST_ORDER_TOTAL' => $tcost,
            'ACP_TEST_ORDER_FORM' => $form->makeForm(),
        ));

        return $this->tpl->parse(false, 'acporderform');
    }


    function implodeNameAndSurname($list, $row){
        $r = '';
        if(!empty($row['surname'])) $r .= $row['surname'];
        if(!empty($row['name'])) $r = !empty($r) ? $r.' '.$row['name'] : $row['name'];

        return $r;
    }

    function orderProducts($bp){
        $bp = $this->extractBParams($bp);
        try{
            $_order = \Verba\_oh('order');
            $orderid = false;
            $order = false;
            if(is_array($bp['pot'][$_order->getID()]) && count($bp['pot'][$_order->getID()])){
                reset($bp['pot'][$_order->getID()]);
                $orderid = current($bp['pot'][$_order->getID()]);
                $order = \Verba\_mod('order')->getOrder($orderid);
            }

            if(!$order instanceof \Verba\Mod\Order\Model\Order){
                throw new Exception('Unknown order');
            }
            $items = $order->getItems();
            $this->tpl->define(array(
                'table' => 'shop/order/acp/products/table.tpl',
                'row' => 'shop/order/acp/products/row.tpl',
                'emptyrow' => 'shop/order/acp/products/emptyrow.tpl',
            ));

            if(!count($items)){
                $this->tpl->parse('ORDER_PRDUCTS_ROWS', 'emptyrow');
            }else{
                foreach($items as $hash=> $item){
                    $this->tpl->assign(array(
                        'ORDER_PRDUCT_TITLE' => $item['title'],
                        'ORDER_PRDUCT_QUANTITY' => $item['quantity'],
                        'ORDER_PRDUCT_PRICE' => $item['price'],
                    ));

                    $this->tpl->parse('ORDER_PRDUCTS_ROWS', 'row', true);
                }
            }

            $r = $this->tpl->parse(false, 'table');

            return \Verba\Response\Json::wrap(true, $r);

        }catch(Exception $e){
            $this->log()->error($e->getMessage());
            return \Verba\Response\Json::wrap(false, $e->getMessage());
        }
    }

    function orderTransactions($bp){
        $bp = $this->extractBParams($bp);
        try{
            $_order = \Verba\_oh('order');
            $orderid = false;
            $order = false;
            if(is_array($bp['pot'][$_order->getID()]) && count($bp['pot'][$_order->getID()])){
                reset($bp['pot'][$_order->getID()]);
                $orderid = current($bp['pot'][$_order->getID()]);
                $order = \Verba\_mod('order')->getOrder($orderid);
            }

            if(!$order instanceof \Verba\Mod\Order\Model\Order){
                throw new Exception('Unknown order');
            }
            $trans = $order->getTrans();
            $this->tpl->define(array(
                'table' => 'shop/order/acp/transactions/table.tpl',
                'row' => 'shop/order/acp/transactions/row.tpl',
                'emptyrow' => 'shop/order/acp/transactions/emptyrow.tpl',
                'details_cell' => 'shop/order/acp/transactions/details_cell.tpl',
            ));

            if(!count($trans)){
                $this->tpl->parse('ORDER_TRANS_ROWS', 'emptyrow');
            }else{
                foreach($trans as $id => $item){
                    $this->tpl->assign(array(
                        'ORDER_TRAN_ID' => $id,
                        'ORDER_TRAN_DETAILS' => $item->getTranDataAsIni(),
                    ));
                    $this->tpl->assign(array(
                        'ORDER_TRAN_TIME' => $item->purchaseTime,
                        'ORDER_TRAN_CODE' => $item->tranCode,
                        'ORDER_TRAN_TOTAL' => $item->totalAmount,
                        'ORDER_TRAN_DATA' => $this->tpl->parse(false, 'details_cell')
                    ));



                    $this->tpl->parse('ORDER_TRANS_ROWS', 'row', true);
                }
            }

            $r = $this->tpl->parse(false, 'table');

            return \Verba\Response\Json::wrap(true, $r);

        }catch(Exception $e){
            $this->log()->error($e->getMessage());
            return \Verba\Response\Json::wrap(false, $e->getMessage());
        }
    }
}
*/