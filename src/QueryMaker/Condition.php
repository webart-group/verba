<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 21.08.19
 * Time: 18:05
 */

namespace Verba\QueryMaker;


class Condition extends \Verba\Base
{

    protected $type = '';
    public $global_glue = '&&';
    public $alias;
    protected $family_relation;

    public function __construct($alias = false)
    {
        $this->alias = $alias;
    }

    /**
     * Set family relation for this link condition
     *
     * @param integer $fr 1 - linked Ot is a child, 2 - linked Ot is a parent
     */
    public function setRelation($fr)
    {
        $fr = intval($fr);
        if (0 > $fr || $fr > 4) {
            return false;
        }
        $this->family_relation = $fr;

        return $this->family_relation;
    }

    public function set_global_glue($val)
    {
        $this->global_glue = in_array($val, array('&&', '||')) ? $val : '&&';
    }

    public function makePIXs($ot_id1, $ot_id2)
    {

        if (is_int($this->family_relation)) {
            $family_relations = $this->family_relation;
        } else {
            $_oh = \Verba\_oh($ot_id1);
            $family_relations = $_oh->getFamilyRelations($ot_id2);
        }

        if (!$family_relations) {
            return false;
        }

        switch ($family_relations) {
            case 2:
                $pix1 = 'ch';
                $pix2 = 'p';
                break;
            case 1:
            case 3:
                $pix1 = 'p';
                $pix2 = 'ch';
                break;
        }

        return is_string($pix1) && is_string($pix2) ? array($pix1, $pix2) : false;
    }

}
