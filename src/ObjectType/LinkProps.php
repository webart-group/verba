<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 21.08.19
 * Time: 12:24
 */

namespace Verba\ObjectType;


class LinkProps extends \Verba\Configurable
{

    public $gid = false;
    public $fr = false;
    public $rule = false;
    public $prim_ot_id = false;
    public $sec_ot_id = false;
    public $extData = null;


    function __construct($cfg)
    {
        $this->applyConfigDirect($cfg);
        $this->gid = ((string)$this->rule) . '-' . ((string)$this->fr);
    }

    function setRule($val)
    {
        $this->rule = (string)$val;
    }

    //setRule alias
    function setAlias($val)
    {
        $this->setRule($val);
    }

    function asArray()
    {
        return $this->asCfg();
    }

    function asCfg()
    {
        return array(
            'gid' => $this->gid,
            'prim_ot_id' => $this->prim_ot_id,
            'sec_ot_id' => $this->sec_ot_id,
            'fr' => $this->fr,
            'rule' => $this->rule,
            'extData' => $this->extData
        );
    }

    function getRule()
    {
        return $this->rule;
    }

    function getFr()
    {
        return $this->fr;
    }

    function getExtData()
    {
        return $this->extData;
    }

    function setExtData($val)
    {
        if (!is_array($val)) {
            return false;
        }
        $this->extData = $val;
        return $this->extData;
    }

}
