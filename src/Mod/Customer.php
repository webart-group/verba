<?php

namespace Verba\Mod;

class Customer extends \Verba\Mod
{

    use \Verba\ModInstance;
    protected $customerId;
    protected $userId;
    protected $customerIdLength = 10;
    protected $profile;
    protected $customerStatuses = null;
    protected $customerStatuses_cache = array();
    protected $cachedProfiles = array();

    protected function __construct()
    {
        parent::__construct();
    }

    function init()
    {
        $this->initCurrentUserProfile();
    }

    function initCurrentUserProfile()
    {
        $cU = \Verba\User();

        $sessP = $this->loadProfileFromSession();
        if (!is_object($sessP) || !$sessP instanceof Customer\Profile) {
            $sessP = false;
        }

        $userP = false;
        if ($cU->getAuthorized()) {
            $userP = $this->loadProfileByUserId($cU->getID());
            // если у пользователя в БД нет профиля - создание нового Профиля
            if (!$userP instanceof Customer\Profile) {
                $ae = $this->createUserCustomerProfile($cU);
                if (!$ae || $ae->haveErrors()) {

                    $this->log()->error('Unable to create User Customer Profile. AE Errors: ' . (is_object($ae) ? $ae->log()->getLastError() : 'ae is false'));

                } else {

                    if ($ae->getIID()) {

                        $userP = $this->loadProfileByUserId($cU->getID());
                        //user profile successfuly created and are a copy of session guest profile. Nulling guset profile.
                        $sessP = false;

                    }

                }

            }
        }

        // если и в сессии и по текущему юзеру есть Профиль
        if ($sessP instanceof Customer\Profile && $userP instanceof Customer\Profile) {
            // если это профили одного владельца
            if ($sessP->getOwner() == $userP->getOwner()) {
                $r = $this->mergeProfiles($userP, $sessP);
            } else {
                // если сессионый профиль - неавторизованного владельца,
                // а текущий - авторизированного - слияние Профилей
                if (!$sessP->getOwner() && $userP->getOwner()) {
                    $r = $this->mergeProfiles($userP, $sessP);

                    // по умолчанию - Профиль из базы
                } else {
                    $r = $userP;
                }
            }

        } elseif ($userP instanceof Customer\Profile) {


            $r = $userP;


        } elseif ($sessP instanceof Customer\Profile) {

            $r = $sessP;

        }

        if (!isset($r) || !$r instanceof Customer\Profile) {
            $r = $this->createGuestProfile();
        }

        $this->profile = $r;

        $this->saveProfileToSession();

        return $this->profile;
    }

    function getProfile($cid = null)
    {
        if ($cid === null
            || is_object($this->profile) && $this->profile->getCode() == $cid) {
            return $this->profile;
        }

        return $this->loadProfile($cid);

    }

    function loadProfile($cid)
    {
        if (!isset($this->cachedProfiles[$cid])) {
            $profile = $this->loadProfileFromDb($cid);
            if (count($this->cachedProfiles) > 10) {
                reset($this->cachedProfiles);
                unset($this->cachedProfiles[key($this->cachedProfiles)]);
            }
            $this->cachedProfiles[$cid] = $profile;
        }
        return $this->cachedProfiles[$cid];
    }

    function loadProfileByUserId($uid)
    {
        return $this->loadProfileFromDb($uid, true);
    }

    function loadProfileByEmail($email)
    {
        return $this->loadProfileFromDb($email, false, true);
    }

    protected function loadProfileFromDb($cid, $byUserId = false, $byEmail = false)
    {
        $byUserId = (bool)$byUserId;
        $byEmail = (bool)$byEmail;
        $_customer = \Verba\_oh('customer');
        $_user = \Verba\_oh('user');
        $_order = \Verba\_oh('order');

        $qm = new \Verba\QueryMaker($_customer, false, true);
        list($a, $t) = $qm->createAlias();
        list($ua, $ut) = $qm->createAlias($_user->vltT());
        list($oa, $ot) = $qm->createAlias($_order->vltT());
        if ($byUserId) {
            $uCond = $qm->addConditionByLinkedOT($_user, $cid);
        } elseif ($byEmail) {
            $qm->addWhere($cid, 'email');
        } else {
            $qm->addWhereIids($cid);
        }
        $qm->addGroupBy(array($_customer->getPAC()));

        $qm->addCJoin(array(array('t' => $ut, 'a' => $ua)), array(
                array(
                    'p' => array('t' => $t, 'f' => 'owner'),
                    's' => array('t' => $ut, 'f' => $_user->getPAC()),
                )
            )
        );
        $qm->addCJoin(array(array('t' => $ot, 'a' => $oa)), array(
            array(
                'p' => array('a' => $a, 'f' => Customer\Profile::getIdPropName()),
                's' => array('a' => $oa, 'f' => 'customerId'),
            ),
            array(
                'p' => array('a' => $oa, 'f' => 'status'),
                's' => 21,
            )
        ));
        $qm->addSelectPastFrom('user_id', $ua);
        $qm->addSelectPastFrom('name', $ua, 'u_name');
        $qm->addSelectPastFrom('surname', $ua, 'u_surname');
        $qm->addSelectPastFrom('patronymic', $ua, 'u_patronymic');
        $qm->addSelectPastFrom('email', $ua, 'user_email');

        $qm->addSelectPastFrom('COUNT(`' . $oa . '`.`id`) AS totalPurchases', null, null, true);

        $qm->addLimit(1);
        $q = $qm->getQuery();
        $sqlr = $qm->run();
        if (!$sqlr || !$sqlr->getNumRows()) {
            return false;
        }

        $row = $sqlr->fetchRow();
        // User not exist. Collision.
        if ((!$row['user_id'] && $row['owner'] && $row['owner'] > 0)
            || (!$row['user_email'] || !$row['user_email'])
            || ($row['user_email'] != $row['email'])
        ) {
            $this->DB()->query("UPDATE " . $_customer->vltURI() . " SET owner = NULL WHERE `" . $_customer->getPAC() . "` = '" . $row[$_customer->getPAC()] . "'");
            $this->log()->error('Unexists User entry #' . $row['owner'] . ' for Customer Profile #' . $row['id'] . '. Nulled now.');
            $row['owner'] = null;
        }
        unset(
            $row['ot_id'],
            $row['key_id']
        );
        $profile = new Customer\Profile($row[Customer\Profile::getIdPropName()], $row);
        $profile->setDbTimestamp(microtime(true));
        return $profile;
    }

    protected function loadProfileFromSession()
    {
        if (!isset($_SESSION['mod']['customer']['profile'])) {
            return false;
        }

        $profile = unserialize($_SESSION['mod']['customer']['profile']);
        $owner = (int)$profile->owner;
        if ($owner > 0) {
            $profile = $this->loadProfileFromDb($profile->getId());
        }

        return $profile;
    }

    /**
     * @param $U \Verba\Mod\User
     * @return \Verba\Act\AddEdit|bool
     */
    function createUserCustomerProfile($U)
    {
        if (!$U instanceof \Verba\Mod\User\Model\User
            || !$U->getID()) {
            return false;
        }
        $customerCode = $this->genCustomerCode($U->getID());

        if (!$customerCode) {
            return false;
        }
        try {
            $_cst = \Verba\_oh('customer');
            $ae = $_cst->initAddEdit(array(
                'action' => 'new',
            ));
            $data = array();

            if (is_object($this->profile)
                && is_array($inheritFromUser = $this->profile->getInheritFromUser())
                && !empty($inheritFromUser)) {
                foreach ($inheritFromUser as $k) {
                    $val = $U->getValue($k);
                    if (isset($val)) {
                        $data[$k] = $val;
                    }
                }
            }

            $data['email'] = $U->getValue('email');
            $data['code'] = $customerCode;
            $data['owner'] = $U->getId();

            $ae->setGettedObjectData($data);
            $ae->addParents(\Verba\_oh('user')->getID(), $U->getID());
            $ae->addedit_object();
        } catch (\Exception $e) {
            $this->log()->error('Unable to create customer profile');
            return false;
        }


        return $ae;
    }

    function createGuestProfile($userId = null, $data = null)
    {
        return new Customer\Profile($this->genCustomerCode($userId), $data);
    }

    function finalizeProfileAndCart($email, $create = false)
    {
        $create = (bool)$create;
        $profile = $this->findProfile($email, $create);
        if (!$profile) {
            throw new \Exception('Unable to handle \Verba\Mod\Customer\Profile for Email [' . var_export($email, true) . ']');
        }
        $mCart = \Verba\_mod('cart');
        $newCart = $mCart->renewCartByProfile($profile);
        //switch customer
        $this->switchCurrentProfile($profile);
        $mCart->switchCurrentCart($newCart);
        return array($profile, $newCart);
    }

    function findProfile($email, $create = false, $mergeWithCurrent = true)
    {

        $create = (bool)$create;
        $profile = false;
        if (((bool)$mergeWithCurrent) === true) {
            $currentProfile = $this->getProfile();
        } else {
            $currentProfile = false;
        }

        $tf = new \Verba\Data\Email(array('value' => $email));
        if (!$tf->validate()) {
            throw new \Exception($tf->getErrorsAsString() . "\nemail'" . var_export($email, true) . "'");
        };

        $email = $tf->getValue(); // lowercase and safe

        if (!$email) {
            throw new \Exception('Bad Email value. email"' . var_export($email, true) . '"');
        }
        $_cust = \Verba\_oh('customer');
        $_user = \Verba\_oh('user');
        $mUser = \Verba\_mod('user');

        $profile = $this->loadProfileByEmail($email);
        if (!$profile) {
            //find user if profile not found
            $q = "SELECT * FROM " . $_user->vltURI() . " WHERE `email` = '" . $email . "' LIMIT 1";
            $sqlr = $this->DB()->query($q);
            if ($sqlr && $sqlr->getNumRows()) {
                //find customer Id
                $U = new \Verba\Mod\User\Model\User($sqlr->fetchRow());
                $profile = $this->loadProfileByUserId($U->getId());

                // User - yes, Profile - yes, but User and Profile Email is Mismatch
                // And update owner field to mismatched Profile
                if ($profile && $profile->getEmail() != $email) {
                    // Find or Create User for mismatched Profile
                    $sqlruf = $this->DB()->query("SELECT `" . $_user->getPAC() . "` FROM " . $_user->vltURI() . " WHERE `email` = '" . $this->DB()->escape_string($profile->getEmail()) . "'");
                    if ($sqlruf->getNumRows() > 0) {
                        $sqlruf = $sqlruf->fetchRow();
                        $userIdMsm = $sqlruf[$_user->getPAC()];
                    } else {
                        $userIdMsm = $mUser->createUser(array('email' => $profile->getEmail()));
                    }

                    if (!$userIdMsm) {
                        $userIdMsm = 0;
                        $this->log()->error('Unable to create or find User for Customer Profile. email: ' . var_export($profile->getEmail(), true));
                    }
                    $q = "UPDATE " . $_cust->vltURI() . " SET `" . $_cust->getOwnerAttributeCode() . "` = '" . $userIdMsm . "' WHERE `" . $_cust->getOwnerAttributeCode() . "` = '" . $U->getId() . "'";
                    $this->DB()->query($q);
                    $this->log()->error('Customer Profile and User Email is mismatch. Mismatchet profile. email \'' . var_export($profile->getEmail(), true) . '\', old owner: \'' . var_export($U->getId(), true) . '\' newUserId \'' . var_export($userIdMsm, true) . '\', code \'' . $profile->getCode() . '\'');
                    $profile = false;
                }

                //User - yes, Profile - no
                //Profile creation
                if (!$profile) {
                    $ae = $this->createUserCustomerProfile($U);
                    $profile = $this->loadProfile($ae->getIID());
                }
            }
        }
        // Profile Yes, No User entry
        if ($profile && !$profile->owner) {

            $sqlruf = $this->DB()->query("SELECT `" . $_user->getPAC() . "` FROM " . $_user->vltURI() . " WHERE `email` = '" . $email . "'");
            if ($sqlruf->getNumRows() > 0) {
                $sqlruf = $sqlruf->fetchRow();
                $userId = $sqlruf[$_user->getPAC()];
            } else {
                $userId = $mUser->createUser(array('email' => $email));
            }
            if (!$userId) {
                throw new \Exception('Unable to create User for Customer Profile. email: ' . var_export($email, true));
            }
            $this->DB()->query("UPDATE " . $_cust->vltURI() . " SET `" . $_cust->getOwnerAttributeCode() . "` = '" . $userId . "' WHERE `" . $_cust->getStringPAC() . "` = '" . $profile->getCode() . "'");
            $profile = $this->loadProfile($profile->getId());
        }

        // No profile, No User entry
        if (!$profile) {
            if ($create) {

                $userId = $mUser->createUser(array('email' => $email));
                if (!$userId) {
                    throw new \Exception('Unable to create User for Customer Profile. email: ' . var_export($email, true));
                }
                $U = new \U($userId);
                $ae = $this->createUserCustomerProfile($U);
                $profile = $this->loadProfile($ae->getIID());
            } else {
                $profile = $this->createGuestProfile(null, array('email' => $email));
            }
        }
        return $profile;
    }

    function saveProfileToSession()
    {
        $_SESSION['mod']['customer']['profile'] = serialize($this->profile);
        return true;
    }

    function switchCurrentProfile($profile)
    {
        $this->profile = $profile;
        $this->saveProfileToSession();
    }

    function genCustomerCode($userId)
    {
        global $S;
        if ($userId === null) {
            $customerId = md5(\Verba\getClientIP() . time() . rand());
        } elseif (is_int($userId = intval($userId)) && $userId > 0) {
            $customerId = md5($userId . '.' . $S->gC('cryptKey'));
        } else {
            return false;
        }
        return $customerId;
    }

    function getCustomerId()
    {
        return $this->profile instanceof Customer\Profile
            ? $this->profile->getId()
            : false;
    }

    function mergeProfiles($toP, $fromP)
    {
        if (!$toP instanceof Customer\Profile
            || !$fromP instanceof Customer\Profile
        ) {
            return false;
        }
        //rebind cart items to new profile
        $_customer = \Verba\_oh('customer');
        $_prod = \Verba\_oh('product');
        $toId = $toP->getId();
        $fromId = $fromP->getId();
        $lt = $_customer->vltURI($_prod);
        if (!$lt) {
            $this->log()->error('Unable to relink cart items to new Customer Profile. Invalid Link table');
            return false;
        }

        $q = "SELECT * FROM " . $lt . " WHERE p_ot_id = '" . $_customer->getId() . "' && p_iid = '" . $this->DB()->escape_string($fromId) . "'";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            $this->log()->error('SQL error while getting cart items to new Customer Profile');
            return false;
        }
        $this->log()->event('Merging cart from customerId [' . $fromId . '] to [' . $toId . ']');
        if ($sqlr->getNumRows()) {
            while ($row = $sqlr->fetchRow()) {
                $qU = "INSERT INTO " . $lt . " (
        `p_ot_id`, `p_iid`, `ch_ot_id`, `ch_iid`, `title`, `hash`, `quantity`, `price`, `extra`, `created`
        ) VALUES ("
                    . "'" . $row['p_ot_id'] . "',"
                    . "'" . $toId . "',"
                    . "'" . $row['ch_ot_id'] . "',"
                    . "'" . $row['ch_iid'] . "',"
                    . "'" . $this->DB()->escape_string($row['title']) . "',"
                    . "'" . $row['hash'] . "',"
                    . "'" . $row['quantity'] . "',"
                    . "'" . $row['price'] . "',"
                    . "'" . $this->DB()->escape_string($row['extra']) . "',"
                    . "'" . $row['created'] . "'"
                    . ") ON DUPLICATE KEY UPDATE "
                    . "`quantity` = '" . $row['quantity'] . "',"
                    . "`price` = '" . $row['price'] . "',"
                    . "`extra` = '" . $this->DB()->escape_string($row['extra']) . "',"
                    . "`title` = '" . $this->DB()->escape_string($row['title']) . "'";
                $this->DB()->query($qU);
            }

            // remove old entries
            $qD = "DELETE FROM " . $lt . " WHERE p_ot_id = '" . $_customer->getId() . "' && p_iid = '" . $this->DB()->escape_string($fromId) . "'";
            $this->DB()->query($qD);
        }

        $fProps = $fromP->getPropsFlat();
        if (isset($fProps[Customer\Profile::getIdPropName()])) {
            unset($fProps[Customer\Profile::getIdPropName()]);
        }
        $i = 0;
        foreach ($fProps as $cPName => $cPValue) {
            if (!$cPValue) {
                continue;
            }
            $i++;
            $toP->$cPName = $cPValue;
        }
        if ($i) {
            $this->updateProfile(false, $toP);
        }
        return $toP;
    }

    function updateProfile($data, $profile = false)
    {
        try {
            $_cust = \Verba\_oh('customer');
            if (!$profile instanceof \Verba\Mod\Customer\Profile) {
                $profile = $this->profile;
            }
            if (is_array($data)) {
                $profile->fillProps($data);
            }
            if ($profile->owner > 0) {
                $iid = $profile->getId();

                //check if profile really exists
                // possible colision  if table was cleared
                // but profile was cached
                $ed = $_cust->getData($iid, 1);
                if (!$ed) { //profile not found into DB, create it
                    $ae = $this->createUserCustomerProfile(\User());
                    if (!$ae->getIID()) {
                        throw new \Exception('Unable to restore user profile while update request');
                    }
                }

                //updating Profile db-entry
                $data = $profile->getPropsFlat();

                $ae = $_cust->initAddEdit(array('action' => 'edit'));
                $ae->setIID($iid);
                $ae->setGettedObjectData($data);

                $r = $ae->addedit_object();
                if (!$r) {
                    throw new \Exception('Unable to update customer profile');
                }
            }

            $this->saveProfileToSession();
            return isset($ae) ? $ae : true;
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
            return false;
        }
    }

    function updateProfileFromOrder($Order)
    {
        $inhfromord = $this->profile->getInheritFromOrder();
        if (!is_array($inhfromord) || empty($inhfromord)) {
            return false;
        }
        $intersect = array_intersect_key($Order->toArray(), array_flip($inhfromord));
        if (!count($intersect)) {
            return false;
        }
        $this->updateProfile($intersect);
    }

    /**
     * @param $order \Verba\Mod\Order\Model\Order
     * @param bool $add
     */
    function updateCustomerStatusByOrderTotal($order, $add = true)
    {

        $statuses = $this->getCustomerStatuses('a');
        $customer = $order->getCustomer();
        if (!$customer instanceof \Verba\Mod\Customer\Profile) {
            $this->log()->error('Unknown \Verba\Mod\Customer\Profile. orderId:' . $order->getId());
            return;
        }

        $cStatusId = $customer->getStatusId();
        $cSum = $customer->getTotalSum();
        /**
         * @var $modShop Shop
         */
        $modShop = \Verba\_mod('shop');

        $data = array();

        $orderCur = $order->getCurrency();
        $orderBaseTotal = $modShop->convertToBase($order->getTotal(), $orderCur->getId());

        if ($add) {
            $sum = $orderBaseTotal + $customer->getTotalSum();
            $totalPurchases = (int)($customer->getTotalPurchases() + 1);

            //Add Customer personal Discount IF its first payed Order
            $discDetailed = $order->getDiscountDetails();
            if (is_array($discDetailed)
                && count($discDetailed)) {
                $rq = false;
                foreach ($discDetailed as $discount_id => $dd) {
                    if ($dd['_class'] == '\Mod\Order\Discount\Cart\FirstPurchase') {
                        $rq = $dd['percent'];
                        break;
                    }
                }
                if ($rq != false) {
                    $data['pdiscount'] = $rq;
                }
            }

        } else {
            $sum = $customer->getTotalSum() - $orderBaseTotal;
            $totalPurchases = (int)($customer->getTotalPurchases() - 1);
        }
        if ($totalPurchases < 0) {
            $totalPurchases = 0;
        }
        $newStatusId = $this->getCustomerStatusIdBySum($sum);


        $data['status'] = $newStatusId;
        $data['totalSum'] = $sum;
        $data['totalPurchases'] = $totalPurchases;
        $data['updstamp'] = 1;

        $_cst = \Verba\_oh('customer');
        $ae = $_cst->initAddEdit(array('action' => 'edit'));
        $ae->setGettedObjectData($data);
        $ae->setIID($customer->getId());
        $customerId = $ae->addedit_object();
        //switch current profile
        if ($this->profile->getId() == $customerId) {
            $customerProfile = $this->loadProfile($customerId);
            $this->switchCurrentProfile($customerProfile);
        }
    }

    function getCustomerProfileData($bp)
    {
        try {
            $f = $this->profile->getInheritFromOrder();
            $d = array_intersect_key($this->profile->toArray(), array_flip($f));
            return \Verba\Response\Json::wrap(true, $d);
        } catch (\Exception $e) {
            return \Verba\Response\Json::wrap(false);
        }
    }

    function loadStatuses()
    {
        $_cs = \Verba\_oh('customerstatus');
        $qm = new \Verba\QueryMaker($_cs, false, true);
        $qm->addWhere(1, 'active');
        $qm->addOrder(array('priority' => 'd', 'amount' => 'a'));
        $sqlr = $qm->run();
        $q = $qm->getQuery();
        if (!$sqlr || !$sqlr->getNumRows()) {
            return array();
        }
        $r = array();
        $pac = $_cs->getPAC();
        while ($row = $sqlr->fetchRow()) {
            $r[$row[$pac]] = $row;
        }
        return $r;
    }

    /**
     * @param mixed $mode 'a' - amount, 'cart' - cart, false = full data
     */
    function getCustomerStatuses($mode = false)
    {
        $mode = (string)$mode;
        if ($this->customerStatuses === null) {
            $this->customerStatuses = $this->loadStatuses();
        }

        if ($mode && in_array($mode, array('cart', 'a'))) {
            if (array_key_exists($mode, $this->customerStatuses_cache)) {
                return $this->customerStatuses_cache[$mode];
            }
            $this->customerStatuses_cache[$mode] = array();
        } else {
            return $this->customerStatuses;
        }

        $pac = \Verba\_oh('customerstatus')->getPAC();
        if ($mode == 'cart') {
            $this->customerStatuses_cache[$mode][] = array(
                'id' => 0,
                'amount' => 0,
                'title' => '',
            );
            foreach ($this->customerStatuses as $id => $data) {
                $this->customerStatuses_cache[$mode][] = array(
                    'id' => (int)$data[$pac],
                    'amount' => (float)$data['amount'],
                    'title' => (string)$data['title'],
                );
            }
        } elseif ($mode == 'a') {
            foreach ($this->customerStatuses as $id => $data) {
                $this->customerStatuses_cache[$mode][$data[$pac]] = $data['amount'];
            }
        }


        return $this->customerStatuses_cache[$mode];
    }

    /**
     * put your comment there...
     *
     * @param mixed $ot
     * @param mixed $iid
     * @param mixed $price
     * @param mixed $exists
     * @param mixed $mode - 1: title; 2: amount
     */
    function loadStatusesPrice($ot, $iid, $price, $exists = false, $mode = 1)
    {
        $mode = (int)$mode;
        if ($this->customerStatuses === null) {
            $this->customerStatuses = $this->loadStatuses();
        }

        if (!$this->customerStatuses) {
            return array();
        }

        $oh = \Verba\_oh($ot);

        $sts = array(0 => array(
            'id' => 0,
            'price' => \Verba\reductionToCurrency($price),
            'percent' => 0,
            'percentInverse' => 0,
        ));
        if ($mode & 1) {
            $sts[0]['title'] = '';
        }
        if ($mode & 2) {
            $sts[0]['amount'] = 0;
        }

        foreach ($this->customerStatuses as $stId => $row) {
            $sts[$stId] = array(
                'id' => $stId,
                'price' => null,
                'percent' => 0,
                'percentInverse' => 0,
            );
            if ($mode & 1) {
                $sts[$stId]['title'] = $row['title'];
            }
            if ($mode & 2) {
                $sts[$stId]['amount'] = $row['amount'];
            }
        }

        if (!$iid) {
            return $sts;
        }
        if (!is_array($exists)) {
            $_cs = \Verba\_oh('customerstatus');
            $vlt = $_cs->vltURI($oh);
            $q = "SELECT * FROM " . $vlt . " WHERE p_ot_id = '" . $oh->getId() . "' && ch_ot_id='" . $_cs->getID() . "' && p_iid='" . $this->DB()->escape_string($iid) . "'";
            $sqlr = $this->DB()->query($q);
            $exists = array();
            if ($sqlr && $sqlr->getNumRows()) {
                while ($ex = $sqlr->fetchRow()) {
                    $exists[$ex['ch_iid']] = $ex['price'];
                }
            }
        }

        $cPrice = $basePrice = $sts[0]['price'];
        foreach ($sts as $sid => $sdata) {
            if (!isset($exists[$sid]) || !$exists[$sid]) {
                $sts[$sid]['price'] = \Verba\reductionToCurrency($cPrice);
                continue;
            }
            $sts[$sid]['price'] = $cPrice = \Verba\reductionToCurrency($exists[$sid]);
            $sts[$sid]['percent'] = (1 - ($cPrice / $basePrice)) * 100;
            $sts[$sid]['percentInverse'] = (($basePrice / $cPrice) - 1) * 100;
        }
        return $sts;
    }

    function getCustomerStatusIdBySum($sum)
    {
        $newId = 0;
        $statuses = $this->getCustomerStatuses('cart');

        for ($i = 0; $i < count($statuses); $i++) {
            if ($statuses[$i]['amount'] > $sum) {
                break;
            }
            $newId = $statuses[$i]['id'];
        }
        return $newId;
    }

    function personalPrice($item)
    {
        $oh = \Verba\_oh($item['ot_id']);
        $c = $this->getProfile();
        $stpr = $this->loadStatusesPrice($item['ot_id'], $item[$oh->getPAC()], $item['price']);
        $statusId = $c->getStatusId();
        return isset($stpr[$statusId]) ? $stpr[$statusId]['price'] : $stpr[0]['price'];
    }

    function getNextStatus($currentStatusId, $sts)
    {
        if (!is_array($sts) || !array_key_exists($currentStatusId, $sts)) {
            return false;
        }

        $sts = array_values($sts);
        for ($i = 0, $n = 1; $i < count($sts); $i++, $n++) {
            if ($currentStatusId == $sts[$i]['id']) {
                return array_key_exists($n, $sts) ? $sts[$n] : false;
            }
        }

        return false;
    }

    function getInfoLink($Customer)
    {
        $ownerId = $Customer->getOwner();
        if (!$ownerId) {
            return 'Unknown user';
        }

        $cU = new \Verba\Mod\User\Model\User($ownerId);

        if (!$cU) {
            return $ownerId;
        }
        $displayName = $cU->getValue('display_name');
        if (!$displayName) {
            $displayName = $cU->getID();
        }

        if ($cU->getUserpic()) {
            $userpic = '<i class="pic-32 img-thumbnail" style="background-image:url(\'' . $cU->getUserpic() . '\');"></i>';
        } else {
            $userpic = '';
        }

        return '<a target="_blank" href="' . \Verba\_mod('profile')->getPublicUrl($cU->getID()) . '">' . $userpic . $displayName . '</a>';
    }

    function countOpenedOrders($userId)
    {
        $userId = (int)$userId;
        if (!$userId) {
            return 0;
        }

        $_order = \Verba\_oh('order');

        $q = "SELECT COUNT(*) FROM " . $_order->vltURI() . " 
    WHERE `owner` = '" . $userId . "' 
    && `payed` = 1 && `status` = 21";

        $sqlr = $this->DB()->query($q);

        return (int)$sqlr->getFirstValue();

    }

    //function order_customerDiscount($ae, $items){
    /*
      $discount = new \Verba\Mod\Order\Discount();
      $discount->title = \Verba\Lang::get('customer discount title');
      if(!is_array($items)){
        return false;
      }

      $mCustomer = \Verba\_mod('Customer');
      $profile = $mCustomer->getProfile();
      $cstStatusId = $profile->getStatusId();
      $total = $ae->getTempValue('total');

      if(!$total){
        return $discount;
      }

      $cstStatusId = $profile->recountStatusId($total);

      $r = 0;
      foreach($items as $hash => $item){
        $r += ($item->getCustomerStatusDiscount($cstStatusId) * $item->getQuantity());
      }
      $discount->total = $r;
      return $discount;
    }
    */

    function cron_recountCutomerTotalPurchases()
    {
        $_customer = \Verba\_oh('customer');
        $_order = \Verba\_oh('order');
        $cpac = $_customer->getPAC();
        $qm = new \Verba\QueryMaker($_customer, false, array('totalPurchases'));
        list($a, $t) = $qm->createAlias();
        list($oa, $ot) = $qm->createAlias($_order->vltT());
        $qm->addGroupBy(array($_customer->getPAC()));

        $qm->addCJoin(array(array('t' => $ot, 'a' => $oa)), array(
            array(
                'p' => array('a' => $a, 'f' => \Verba\Mod\Customer\Profile::getIdPropName()),
                's' => array('a' => $oa, 'f' => 'customerId'),
            ),
            array(
                'p' => array('a' => $oa, 'f' => 'status'),
                's' => 21,
            )
        ));

        $qm->addSelectPastFrom('COUNT(`' . $oa . '`.`id`) AS totalPurchasesByOrders', null, null, true);

        $qm->addOrder(array(
            $cpac => 'a'
        ));

        $affected = $i = 0;
        $start = 0;
        $step = 300;
        do {
            $qm->addLimit($step, $start);
            $qm->makeQuery();
            $sqlr = $qm->run();
            $q = $qm->getQuery();
            if ($sqlr && $sqlr->getNumRows()) {
                while ($row = $sqlr->fetchRow()) {
                    $i++;
                    if ($row['totalPurchases'] == $row['totalPurchasesByOrders']) {
                        continue;
                    }
                    $qu = "UPDATE " . $_customer->vltURI() . " SET `totalPurchases` = '" . (int)$row['totalPurchasesByOrders'] . "' WHERE `" . $cpac . "` = '" . $row[$cpac] . "' LIMIT 1";
                    $sqlru = $this->DB()->query($qu);
                    $affected += $sqlru->getAffectedRows();
                }
                $start += $step;
            }

        } while ($sqlr && $sqlr->getNumRows());

        return array(50, array('handled' => $i, 'changed' => $affected));
    }

    function cron_recountCutomerStatuses()
    {
        $_cust = \Verba\_oh('customer');

        $sqlr = $this->DB()->query("SELECT COUNT(*) as ccc FROM " . $_cust->vltURI());
        if ($sqlr && $sqlr->getNumRows()) {
            $totalRows = (int)$sqlr->getFirstValue();
        } else {
            $totalRows = 0;
        }
        $qm = new \Verba\QueryMaker($_cust, false, array('totalSum', 'status'));

        for ($affected = 0, $i = 0, $start = 0, $n = 1000; $start < $totalRows; $start += 1000) {
            $qm->addLimit($n, $start);
            $qm->makeQuery();
            $q = $qm->getQuery();
            $sqlr = $qm->run();
            if (!$sqlr || !$sqlr->getNumRows()) {
                break;
            }

            while ($row = $sqlr->fetchRow()) {
                $i++;
                $cCustStatusId = $row['status'];
                $newStatusId = $this->getCustomerStatusIdBySum($row['totalSum']);
                if ($newStatusId == $cCustStatusId) {
                    continue;
                }
                $sqlr_update = $this->DB()->query("UPDATE " . $_cust->vltURI() . " SET status='" . $newStatusId . "' WHERE `" . $_cust->getPAC() . "` = '" . $row[$_cust->getPAC()] . "'");
                $affected += $sqlr_update->getAffectedRows();
            }
        }
        return array(0, array('handled' => $i, 'changed' => $affected));
    }
}
