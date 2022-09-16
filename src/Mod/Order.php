<?php

namespace Verba\Mod;

class Order extends \Verba\Mod
{
    use \Verba\ModInstance;
    protected $ordersCache = array();

    /**
     * @param \Verba\Mod\Order\CreateData $createOrderData
     * @return array(AddEdit, \Verba\Mod\Order\Model\Order)
     * @throws \Exception
     */
    function createOrder($orderCreateData = null)
    {

        if (!is_object($orderCreateData) || !$orderCreateData instanceof \Verba\Mod\Order\CreateData
            || !$orderCreateData->validate()) {
            throw new \Exception('Bad createOrder data');
        }

        $_order = \Verba\_oh('order');
        $ae = $_order->initAddEdit('new');

        $ae->setGettedObjectData($orderCreateData->data);

        if (isset($orderCreateData->extended) && is_array($orderCreateData->extended)) {
            $ae->addExtendedData($orderCreateData->extended);
        }

        if (is_array($orderCreateData->items) && count($orderCreateData->items)) {
            $ae->addExtendedData(array('items' => $orderCreateData->items));
        }

        if (is_object($orderCreateData->Cart) && $orderCreateData->Cart instanceof \Verba\Mod\Cart\CartInstance) {
            $ae->addExtendedData(array('cart' => $orderCreateData->Cart));
        }

        $ae->addExtendedData(array('customerId' => $orderCreateData->customerId));

        $ae->addExtendedData(array('orderCreateData' => $orderCreateData));

        $ae->addParents(\Verba\_oh('customer')->getID(), $orderCreateData->customerId);
        $orderId = $ae->addedit_object();
        if (!$orderId) {
            throw new \Exception($ae->log()->getMessagesAsStr('error'));
        }

        $Order = $this->getOrder($orderId);

        if ($orderCreateData->updateCustomerProfile) {
            \Verba\_mod('customer')->updateProfileFromOrder($Order);
        }
        if ($orderCreateData->clearCart) {
            $orderCreateData->Cart->resetAndClearItems();
        }

        return array($ae, $Order);
    }

    /**
     * @param integer|string $iid Order Id or Code
     * @param bool $searchByCode
     * @return bool| \Verba\Mod\Order\Model\Order
     */
    function getOrder($iid)
    {

        if (is_object($iid) && $iid instanceof \Verba\Mod\Order\Model\Order) {
            return $iid;
        }

        if (!$iid || !(is_string($iid) || is_numeric($iid))) {
            return false;
        }
//    $uper = strtoupper($iid);
//    if(array_key_exists($uper, $this->ordersCache)){
//      return $this->ordersCache[$uper];
//    }

        try {
            $o = new \Verba\Mod\Order\Model\Order($iid);

            if (!$o->id) {
                throw new \Exception();
            }

            $this->ordersCache[$o->getIid()] =
            $this->ordersCache[strtoupper($o->code)] = $o;


        } catch (\Exception $e) {
            return ($this->ordersCache[$iid] = false);
        }


        return $this->ordersCache[$iid];
    }

    /**
     * @param string $code Order Code
     * @return bool| \Verba\Mod\Order\Model\Order
     */
    function getOrderByCode($code)
    {
        return $this->getOrder($code);
    }

    function getUrlDownloadAllByOrder($orderId)
    {
        $crypted = \Verba\_mod('crypt')->encode($orderId . '-' . rand(0, 999) . '-' . rand(0, 999));
        return '/download/order/?h=' . urlencode(base64_encode($crypted));
    }

    function getTempDownloadDir()
    {
        $dir = SYS_ROOT . '/strg/_tmp';
        if (\FileSystem\Local::dirExists($dir)) {
            return $dir;
        }
        if (!\Verba\FileSystem\Local::needDir($dir)) {
            throw new \Exception('Unable to create temp zip download dir $dir[' . var_export($dir, true) . ']');
        }
        file_put_contents($dir . '/.htaccess', "deny from all\n");
        return $dir;
    }

    function downloadKeysByOrder($bp = null)
    {
        try {
            $bp = $this->extractBParams($bp);
            $h = isset($bp['h']) ? $bp['h'] : $_REQUEST['h'];
            if (!$h) {
                throw \Exception('Bad incoming data. $h[' . var_export($h, true) . ']');
            }

            $decrypted = trim(\Verba\_mod('crypt')->decode(base64_decode($h)));
            if (!$decrypted) {
                throw new \Exception('Invalid encrypted data. $decrypted[' . var_export($decrypted, true) . '] $h[' . var_export($h, true) . ']');
            }
            list($orderId) = explode('-', $decrypted);
            if (!$orderId || !is_object($order = \Verba\_mod('order')->getOrder($orderId))) {
                throw new \Exception('Invalid orderId. $orderId[' . var_export($orderId, true) . ']');
            }

            if ($order->status != 21) {
                throw new \Exception('Trying to get file when Order is not paid. $order->id[' . var_export($order->id, true) . '] $order->status[' . var_export($order->status, true) . ']');
            }

            $filesToZip = $order->getDownloadableItems();

            if (!count($filesToZip)) {
                throw new \Exception('File to download not found. $order->id[' . var_export($order->id, true) . ']');
            }

            $mFile = \Verba\_mod('file');
            $dir = $this->getTempDownloadDir();
            $dir .= '/' . $order->id;

            $zipInternalDir = 'all-keys-by-order' . $order->code;
            $zipname = $zipInternalDir . '.zip';
            $zippath = $dir . '/' . $zipname;
            if (!\Verba\FileSystem\Local::fileExists($zippath)
                || \Verba\Hive::$debug
                || !is_int($zipsize = filesize($zippath))
                || isset($_REQUEST['renew']) && $_REQUEST['renew'] == 1) {

                if (!\Verba\FileSystem\Local::needDir($dir)) {
                    throw new \Exception('Unable to create temp download dir for good $dir[' . var_export($dir, true) . ']');
                }
                $zip = new \ZipArchive;

                $zipRes = $zip->open($zippath,  \Verba\FileSystem\Local::fileExists($zippath) ? ZipArchive::OVERWRITE : ZipArchive::CREATE);
                if ($zipRes !== true) {
                    throw new \Exception('Unable to create Zip file. ZipErr:' . $zipRes . ',  $zippath: ' . var_export($zippath, true));
                }

                foreach ($filesToZip as $fileRow) {
                    $zip->addFile($fileRow['_path'], $zipInternalDir . '/' . basename($fileRow['_path']));
                }
                $zip->close();
                $zipsize = filesize($zippath);
            }

            if (!$zipsize) {
                throw new \Exception('Bad Zip size. $zipsize: ' . var_export($zipsize, true) . '; $zippath: ' . var_export($zippath, true));
            }

            return  \Verba\FileSystem\Local::outputFile($zippath, $zipsize);

        } catch (\Exception $e) {
            $this->log()->error($e->getMessage() . 'dump:[' . var_export($_REQUEST, true) . ']');
        }
    }

    function cron_cancelOverdueOrders()
    {
        $_order = \Verba\_oh('order');
        $dateFormat = 'Y-m-d H:i:s';

        $qm = new \Verba\QueryMaker($_order, false, array('status'));
        $qm->addWhere("`status` IN ('20', '24', '25', '26')");
        $qm->addWhere("`endPaymentTime` IS NOT NULL && `endPaymentTime` < '" . date($dateFormat) . "'");
        $qm->addSelectProp('SQL_CALC_FOUND_ROWS');

        for ($affected = 0, $i = 0, $start = 0, $n = $totalRows = 1000;
             $start < $totalRows;
             $start += $n) {

            $qm->addLimit($n, $start);
            $qm->makeQuery();
            $q = $qm->getQuery();
            $sqlr = $qm->run();
            if (!$sqlr || !$sqlr->getNumRows()) {
                break;
            }

            if ($start == 0) {
                $oResFoundRows = $this->DB()->query('SELECT FOUND_ROWS()');
                $totalRows = $oResFoundRows->getFirstValue();
                unset($oResFoundRows);
            }

            $statusMsg = \Verba\Lang::get('order statusUpdate statusMsg overdue');

            while ($row = $sqlr->fetchRow()) {
                $i++;
                $iid = $row[$_order->getPAC()];
                $ae = $_order->initAddEdit('update');
                $ae->setIID($iid);
                $ae->ignoreErrors = true;
                $ae->setGettedObjectData(array(
                    'status' => 23,
                    'statusMsg' => $statusMsg,
                ));
                $ae->addExtendedData(array('silenceClient' => true, 'silenceStaff' => true));
                try {
                    $ae->addedit_object();
                } catch (\Exception $e) {
                    $this->log()->error('Unable to cancel Order #' . $iid . ' Exception:' . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString());
                }
            }
        }

        $startAt = date($dateFormat, time() + 300);
        return array(2, array('startAt' => $startAt));
    }

    function getOrderCodeById($id)
    {
        $cfg = $this->gC('paymentStatusAliases');
        return ($r = array_search($id, $cfg)) !== null
            ? $r
            : null;
    }


    function getOrderStatusUrl($order)
    {
        global $S;
        $url = $order->getStatusUrlObj();
        $url = $url->get(true);
        if ($S->gC('isNationalDomain')) {
            require_once(SYS_EXTERNALS_DIR . '/idna/idna_convert.class.php');
            $idna = new \idna_convert();
            $url = $idna->decode($url);
        }

        return $url;
    }

    function handleDeliveryDateAndTime($list, $row)
    {
        if (!is_array && !settype($row, 'array') || empty($row)) {
            return '';
        }
        $tpl = $this->tpl();
        $tpl->define(array('deliveryDate' => 'shop/order/acp/list/fields/deliveryDate.tpl'));
        $tpl->assign(array(
            'ORDER_DELIVERYTIME_TILL' => '',
            'ORDER_DELIVERYTIME1' => '',
            'ORDER_DELIVERYTIME_FROM' => '',
            'ORDER_DELIVERYTIME0' => '',
            'ORDER_DELIVERYDATE' => '',
        ));
        if (!empty($row['deliveryDate'])
            && is_numeric($date = strtotime($row['deliveryDate']))) {
            $tpl->assign(array(
                'ORDER_DELIVERYDATE' => utf8fix(strftime('%d %b. %Y', $date))
            ));
        }
        if (!empty($row['deliveryTime0'])
            && is_numeric($t0 = strtotime($row['deliveryTime0']))
        ) {
            $tpl->assign(array(
                'ORDER_DELIVERYTIME_FROM' => \Verba\Lang::get('order acp list deliveryFrom'),
                'ORDER_DELIVERYTIME0' => utf8fix(strftime('%H:%M', $t0))
            ));
        }
        if (!empty($row['deliveryTime1'])
            && is_numeric($t1 = strtotime($row['deliveryTime1']))
        ) {
            $tpl->assign(array(
                'ORDER_DELIVERYTIME_TILL' => \Verba\Lang::get('order acp list deliveryTill'),
                'ORDER_DELIVERYTIME1' => utf8fix(strftime('%H:%M', $t1))
            ));
        }
        return $tpl->parse(false, 'deliveryDate');
    }

    function isOrderCode($str)
    {
        if (!is_string($str) || !preg_match('/^[a-z][0-9]+$/i', trim($str), $_buf)) {
            return false;
        }

        return strtoupper($_buf[0]);

    }


    /**
     * @param $Order \Verba\Mod\Order\Model\Order|integer
     */
    function finalOrderSellerGravity($Order, $Acc)
    {

        if (is_object($Order)) {

            $orderId = $Order->getId();

        } elseif (is_numeric($Order)) {

            $orderId = (int)$Order;

        }

        if (!isset($orderId) || !is_int($orderId)) {
            return false;
        }

        // Снятие суммы с блокированного баланса Торговца #balance #balance_change
        $balopSellerEase = $Acc->balanceUpdate(
            new \Verba\Mod\Balop\Cause\OrderPayedSellerEase(array(
                    'primitiveId' => $orderId,
                    'Acc' => $Acc,
                )
            )
        );

        if (!$balopSellerEase || !$balopSellerEase->active) {
            $this->log()->flow('critical', 'Unable to create sellerEase Order Id: ' . $orderId);
            return false;
        }

        // Перевод суммы на основной баланс Торговца #balance #balance_change
        $balopSellerGravity = $Acc->balanceUpdate(
            new \Verba\Mod\Balop\Cause\OrderPayedSellerGravityFinal($balopSellerEase)
        );
        if (!$balopSellerGravity || !$balopSellerGravity->active) {
            $this->log()->flow('critical', 'Unable to create sellerGravityFinal Order Id: ' . $orderId);
            return false;
        }

        return true;
    }
}
