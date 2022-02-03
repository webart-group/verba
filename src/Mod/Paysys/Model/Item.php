<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 12:46
 */

namespace Mod\Paysys\Model;

class Item extends \Verba\Configurable{

    public $id;
    public $code;
    public $title = '';
    public $description = '';
    public $active;
    public $priority;
    public $module;
    public $tax_input;
    public $tax_output;
    public $tax_transaction;
    public $tax_merch_m;
    public $tax_merch_mp;
    public $tax_profit;
    public $isInternal;
    public $payment_awaiting;
    public $key_id = null;
    //public $currencies = array();
    public $account_description = '';
    /**
     * @var \Model\Collection
     */
    protected $Textes;


    protected $_confPropsMeta = array(
        'tax_input' => array('dataType' => 'float'),
        'tax_output' => array('dataType' => 'float'),
        'tax_transaction' => array('dataType' => 'float'),
        'tax_merch_m' => array('dataType' => 'float'),
        'tax_merch_mp' => array('dataType' => 'float'),
        'tax_profit' => array('dataType' => 'float'),
    );

    function __construct($cfg){
        $this->Textes = new \Model\Collection(\Verba\_oh('textblock'), array());
        $this->applyConfigDirect($cfg);
    }
    function getCode(){
        return $this->code;
    }
    function setCode($val){
        $this->code = $val;
    }
    function getId(){
        return $this->id;
    }
    function getTitle(){
        return $this->title;
    }

}