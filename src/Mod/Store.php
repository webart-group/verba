<?php
namespace Verba\Mod;

use \Verba\Mod\User\Model\User;

class Store extends \Verba\Mod
{
    use \Verba\ModInstance;
    protected $otic_ot = 'store';

    public $_tradersGroupId = 100;
    public $_tradersKeyId = 232;

    public $_notTradersGroupId = 101;
    public $_notTradersKeyId = 233;
    public $cpk_table = 'shop_pc_stores';

    /**
     * @param U|bool $U
     * @return mixed
     * @throws \Exception
     */
    function create($U = false)
    {
        if ($U === false) {
            $U = \Verba\User();
        } elseif (!$U instanceof \Verba\Mod\User\Model\User) {
            throw new \Exception('$U is not a U class');
        }
        //$_account = \Verba\_oh('account');
        //$account = $U->Accounts->getAccount();
        //if(!$account){
//      throw new Exception('Unknown account');
//    }
        //$accId = $account->id;
        $_cur = \Verba\_oh('currency');

        $currency = \Verba\_mod('cart')->getCurrency();
        if (!$currency) {
            $currency =  \Verba\Mod\Currency::getInstance()->getBaseCurrency();
        }

        if (!is_object($currency)) {
            throw new \Exception('Unknown currency');
        }
        $curId = $currency->getId();

        $_store = \Verba\_oh('store');
        $ae = $_store->initAddEdit('new');
        $ae->setGettedData(array(
            'title' => $U->getValue('display_name'),
            'owner' => $U->getID(),
            'currencyId' => $curId,
        ));


        $storeOtId = $_store->getID();
        $picAttrCode = 'picture';
        $idx = 'upl';
        // Случайная ава юзера
        if (!isset($_FILES['NewObject']['tmp_name']['picture'][$idx])) {
//      $themes = array('Film','Glass', 'Mosaic', 'Neon');
//      $selectedTheme = $themes[array_rand($themes)];
            $selectedTheme = 'first';
            $num = rand(1, 85);
            $num = $num < 10 ? '0' . $num : (string)$num;
            $ava_name = '_' . $num . '.png';

            $_FILES['NewObject']['tmp_name'][$storeOtId][$picAttrCode][$idx] = SYS_UPLOAD_DIR . '/random_ava/stores/' . $selectedTheme . '/' . $ava_name;
            $_FILES['NewObject']['type'][$storeOtId][$picAttrCode][$idx] = 'images/png';
            $_FILES['NewObject']['name'][$storeOtId][$picAttrCode][$idx] = $ava_name;
            $_FILES['NewObject']['error'][$storeOtId][$picAttrCode][$idx] = 0;
        }


        $storeId = $ae->addedit_object();
        if (!$storeId) {
            throw new \Exception('Unable to create new Store.');
        }
        // Обновление поля storeId у пользователя
        $aeU = \Verba\_oh('user')->initAddEdit('edit');
        $aeU->setIid($U->getId());
        $aeU->setGettedData(array(
            'storeId' => $storeId,
        ));
        $aeU->addedit_object();

        $U->planeToReload();

        $this->refreshStoreCPK($ae->getActualData());

        return $storeId;
    }

    function refreshStoresCPK()
    {
        $_store = \Verba\_oh('store');

        $this->DB()->query("TRUNCATE TABLE `" . SYS_DATABASE . "`.`" . $this->cpk_table . "`");

        $q = "SELECT *
    FROM " . $_store->vltURI() . "
    ORDER BY `" . $_store->getPAC() . "` DESC
    LIMIT";

        $nr = $step = 100;

        for ($i = 0; $nr == $step; $i = $i + $step) {
            $qc = $q . ' ' . $i . ',' . $step;
            $sqlr = $this->DB()->query($qc);
            $nr = $sqlr->getNumRows();
            if (!$nr) {
                break;
            }
            while ($row = $sqlr->fetchRow()) {
                $this->refreshStoreCPK($row);
            }
        }
    }

    /**
     * @param $store int|\Model\Store класс магазина
     */

    function refreshStoreCPK($Store)
    {

        if (!($Store instanceof \Model\Store)) {
            $Store = new \Model\Store($Store);
        }

        if (!$Store->id) {
            return false;
        }

        $qrm = "DELETE FROM `" . SYS_DATABASE . "`.`" . $this->cpk_table . "` WHERE storeId = '" . $Store->id . "'";
        $this->DB()->query($qrm);

        $mShop = \Verba\Mod\Shop::getInstance();

        $_ps = \Verba\_oh('paysys');
        $_cur = \Verba\_oh('currency');
        $_acc = \Verba\_oh('account');

        $mCurrency = \Verba\Mod\Currency::getInstance();
        $allCurrs = $mCurrency->getCurrency();
        $fields = array('storeId');
        $values = array($Store->getId());
        /**
         * @var $Cur \Verba\Model\Currency
         */
        foreach ($allCurrs as $Cur) {

            $fields[] = 'pcmin_' . $Cur->getId();

            $ps_pcs = $Store->getPcDataByCurrency($Cur);

            if (!is_array($ps_pcs) || !count($ps_pcs)) {
                $min_pc = 0;
            } else {

                if (array_key_exists($Cur->id, $ps_pcs)) {
                    $selectedCurOut = $ps_pcs[$Cur->id];
                } else {
                    reset($ps_pcs);
                    $selectedCurOut = current($ps_pcs);
                }

                $min_pc = is_float($selectedCurOut['PcMinExt']) ? $selectedCurOut['PcMinExt'] : 0;
            }
            $values[] = $min_pc;
        }

        $mStore = \Verba\Mod\Store::getInstance();

        $q = "INSERT INTO `" . SYS_DATABASE . "`.`" . $mStore->cpk_table . "` 
    (`" . implode("`,`", $fields) . "`) VALUES ('" . implode("','", $values) . "')
    ";

        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            $this->log()->error('Unable to create store cppr entry');
            return false;
        }

        return true;
    }

    function getPublicUrl($storeId, $subaction = false)
    {
        return '/store/' . $storeId . ($subaction ? '/' . $subaction : '');
    }

    function getInfoLinkWithPic($Store)
    {

        if (!is_object($Store) && is_numeric($Store)) {
            $Store = $this->OTIC()->getItem($Store);
        }

        if (!is_object($Store) || !$Store instanceof \Model\Store) {
            return false;
        }

        $displayName = $Store->getValue('title');

        if (!$displayName) {
            $displayName = $Store->getId();
        }

        if ($Store->getValue('picture')) {
            $storepic = '<i class="pic-32 img-thumbnail" style="background-image:url(\'' . $Store->getImageAttrUrl('ico32') . '\');"></i>';
        } else {
            $storepic = '';
        }

        return '<a href="' . $this->getPublicUrl($Store->getId()) . '">' . $storepic . htmlspecialchars($displayName) . '</a>';
    }

    function getStoreChannelName($store)
    {
        if (is_object($store) && $store instanceof \Model\Store) {
            $storeId = $store->getId();
        } elseif (is_numeric($store)) {
            $storeId = (int)$store;
        }

        if (!isset($storeId) || !is_int($storeId) || !$storeId) {
            return false;
        }

        return '$pd:store' . $storeId;
    }

    function genChatChannelToUser($store, $user = null)
    {
        if (is_object($store) && $store instanceof \Model\Store) {
            $storeId = $store->getId();
        } elseif (is_numeric($store)) {
            $storeId = $store = (int)$store;
        }
        if ($user === null) {
            $userId =\Verba\User()->getId();
        } elseif (is_numeric($user)) {
            $userId = (int)$user;
        } elseif (is_object($user) && $user instanceof \Verba\Mod\User\Model\User) {
            $userId = $user->getId();
        }

        if (!isset($userId) || !is_int($userId) || !$userId
            || !isset($storeId) || !is_int($storeId) || !$storeId) {
            return false;
        }

        return '$pd:str' . $storeId . 'u' . $userId;
    }

    function countOpenedOrders($storeId)
    {
        $storeId = (int)$storeId;
        if (!$storeId) {
            return 0;
        }

        $_order = \Verba\_oh('order');

        $q = "SELECT COUNT(*) FROM " . $_order->vltURI() . " 
    WHERE `storeId` = '" . $storeId . "' 
    && `payed` = 1 && `status` = 21";

        $sqlr = $this->DB()->query($q);

        return (int)$sqlr->getFirstValue();

    }
}
