<?php
namespace Verba\Mod\Customer;

class Profile extends \Verba\Configurable
{

    protected $dbTimestamp;
    protected $_props = array(
        'id' => null,
        'status' =>null,
        'code' => null,
        'email' => null,
        'phone' => null,
        'name' => null,
        'surname' => null,
        'pdiscount' => null,
        'address' => array(),
        'city' => array(),
        'created' => null,
        'owner' => null,
    );
    static protected $_idName = 'code';
    static $multiValuesSeparator = '~';
    protected $inheritFromOrder = array(
        'name',
        'surname',
        'address',
        'phone',
        'city',
    );
    protected $inheritFromUser = array(
        //  'phone',
        //  'surname',
        //  'patronymic',
        //  'postcode',
        //  'address',
        //  'phone',
    );

    function __construct($id,  $data = null){

        $this->_props[self::$_idName] = $id;
        if(!is_array($data)){
            $data = array();
        }
        $data[self::$_idName] = $id;

        $this->fillProps($data);
    }

    static function getIdPropName(){
        return self::$_idName;
    }

    function __get($propName){
        $mtd = 'get'.ucfirst($propName);
        if(method_exists($this, $mtd)){
            return $this->$mtd();
        }elseif(array_key_exists($propName, $this->_props)){
            return $this->_props[$propName];
        }
        return;
    }

    function __set($propName, $val){
        $mtd = 'set'.ucfirst($propName);
        if(method_exists($this, $mtd)){
            return $this->$mtd($val);
        }elseif(array_key_exists($propName, $this->_props)){
            $this->_props[$propName] = $val;
        }
        return;
    }

    function setDbTimestamp($timestamp){
        $this->dbTimestamp = $timestamp;
    }

    function getDbTimestamp(){
        return $this->dbTimestamp;
    }

    function packToCart(){
        return array(
            'id' => $this->id,
            'email' => $this->email,
            'pdiscount' => $this->pdiscount,
            'statusId' => $this->getStatusId(),
            'statusTitle' => $this->getStatusTitle(),
            'totalSum' => $this->totalSum,
            'totalPurchases' => $this->totalPurchases,
            'name' => $this->name,
            'surname' => $this->surname,
            'city' => $this->city,
            'address' => $this->address,
            'phone' => $this->phone,
        );
    }

    function getInheritFromOrder(){
        return $this->inheritFromOrder;
    }
    function getInheritFromUser(){
        return $this->inheritFromUser;
    }

    function getId(){
        return $this->_props[self::$_idName];
    }

    function getNumericId(){
        return $this->_props['id'];
    }

    function setCode($val){
        if(is_string($this->_props['code']) && strlen($this->_props['code'])
            || !(is_string($val) || is_numeric($val) )
        ){
            return false;
        }
        $this->_props['code'] = (string) $val;
    }
    function getCode(){
        return $this->_props['code'];
    }

    function fillProps($data){
        if(!is_array($data)){
            return false;
        }
        foreach($data as $propName => $val){
            if(is_numeric($propName)){
                continue;
            }
            $mtd = 'set'.ucfirst($propName);
            if(method_exists($this, $mtd)){
                $this->$mtd($val, $data);
            }elseif(array_key_exists($propName, $this->_props)){
                $this->_props[$propName] = $val;
            }
        }
    }

    function propExists($propName){
        return array_key_exists($propName, $this->_props);
    }
    function getProps(){
        return $this->_props;
    }
    function getPropsFlat(){
        $r = $this->_props;

        foreach($r as $k => $v){
            if(!is_array($v)){
                continue;
            }
            $r[$k] = implode(self::$multiValuesSeparator, $v);
        }
        return $r;
    }

    function getFullName(){
        return \Verba\Mod\User::getFullName(array(
            'name' => $this->name,
            'patronymic' => $this->patronymic,
            'surname' => $this->surname
        ));
    }
    function getName(){
        return $this->_props['name'];
    }
    function getSurname(){
        return $this->_props['surname'];
    }
    function getPatronymic(){
        return $this->_props['patronymic'];
    }
    function getEmail(){
        return $this->_props['email'];
    }
    function setEmail($val){
        $tf = new \Verba\Data\Email();
        $tf->setValue($val);
        if(!$tf->validate()){
            return false;
        }
        return  $this->_props['email'] = $tf->getValue();
    }

    function setStatus($val, $data){
        $this->_props['status'] = (int)$val;
        if(isset($data['status__value'])){
            $this->_props['status__value'] = (string)$data['status__value'];
        }
    }
    function getStatusId(){
        return $this->_props['status'];
    }
    function getStatusTitle(){
        return $this->_props['status__value'];
    }
    function recountStatusId($sum){
        return \Verba\_mod('customer')->getCustomerStatusIdBySum((float)$sum + $this->getTotalSum());
    }

    function getPdiscount(){
        return $this->_props['pdiscount'];
    }

    function getDiscountCard(){
        return $this->_props['discount_card'];
    }

    function getOwner(){
        return $this->_props['owner'];
    }
    function setAddress($val){
        if(is_string($val) && strpos($val, self::$multiValuesSeparator) !== false){
            $val = explode(self::$multiValuesSeparator, $val);
        }
        $this->_props['address'] = array();

        if(is_array($val)){
            foreach($val as $cA){
                if(!is_string(!$cA)){
                    continue;
                }
                $this->_props['address'][] = $cA;
            }
        }else{
            $this->_props['address'][] = $val;
        }
    }

    function addAddress($address){
        if(!is_string($address) || empty($address)){
            return false;
        }
        $this->_props['address'][] = $address;
    }

    function getFirstrAddress(){
        reset($this->_props['address']);
        return current($this->_props['address']);
    }

    function getAddress($n = null){
        return is_int($n)
            ? (array_key_exists($n, $this->_props['address']) ? $this->_props['address'][$n] : null)
            : current($this->_props['address']);
    }

    function setPhone($val){
        if(is_string($val) && strpos($val, self::$multiValuesSeparator) !== false){
            $val = explode(self::$multiValuesSeparator, $val);
        }
        $this->_props['phone'] = array();
        if(is_array($val)){
            foreach($val as $cV){
                $this->_props['phone'][] = $cV;
            }
        }else{
            $this->_props['phone'][] = $val;
        }
    }

    function addPhone($val){
        if(!is_string($val) || empty($val)){
            return false;
        }
        $this->_props['phone'][] = $val;
    }

    function getFirstPhone(){
        if(!count($this->_props['phone'])){
            return false;
        }
        reset($this->_props['phone']);
        return current($this->_props['phone']);
    }

    function getPhone($n = null){
        if(!isset($this->_props['phone'])){
            return null;
        }elseif(!is_array($this->_props['phone'])){
            return $this->_props['phone'];
        }
        return is_int($n)
            ? (array_key_exists($n, $this->_props['phone']) ? $this->_props['phone'][$n] : null)
            : current($this->_props['phone']);
    }

    function getPhones(){
        return $this->_props['phone'];
    }

    function getTotalSum(){
        return $this->_props['totalSum'];
    }
    function setTotalSum($var){
        $this->_props['totalSum'] = (float)$var;
    }

    function getTotalPurchases(){
        return $this->_props['totalPurchases'];
    }
    function setTotalPurchases($var){
        $this->_props['totalPurchases'] = (int)$var;
    }

    function getUpdstamp(){
        return $this->_props['updstamp'];
    }
    function setUpdstamp($var){
        $this->_props['updstamp'] = (int)((bool)$var);
    }
    function updstampHandled(){
        $_cust = \Verba\_oh('customer');
        $ae = $_cust->initAddEdit(array('action' => 'edit'));
        $ae->setGettedObjectData(array(
            'updstamp' => 0,
        ));
        $ae->setIID($this->getNumericId());
        $ae->addedit_object();

        $this->setUpdstamp(0);

        return $ae->isUpdated();
    }

    function implodePhone($separator = false){
        if(!is_string($separator)){
            $separator = self::$multiValuesSeparator;
        }
        return implode($separator, $this->_props['phone']);
    }
}
