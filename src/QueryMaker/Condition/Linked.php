<?php
namespace Verba\QueryMaker\Condition;


class Linked extends \Verba\QueryMaker\Condition
{
    protected $type = 'ByLinkedOT';
    protected $li_ot;
    protected $li_iids = array();
    protected $ltable_alias;
    protected $ltable_original_name;
    protected $negation = false;
    protected $rootOt = false;
    protected $ruleAlias = false;
    protected $descendants_primary = false;

    protected $compiledIIDs = false;

    public function setDescendantsPrimary($bool)
    {
        $this->descendants_primary = (bool)$bool;
    }

    function setLinkedOT($ot_id)
    {
        $oh = \Verba\_oh($ot_id);
        $this->li_ot = $oh->getID();
    }

    function setNegation($val)
    {
        $this->negation = (bool)$val;
    }

    function getNegation()
    {
        return $this->negation;
    }

    function getLinkedOT()
    {
        return $this->li_ot;
    }

    public function setLinkedIIDs($val)
    {
        $this->li_iids = \Verba\convertToIdList($val, true, true);
    }

    public function addLinkedIIDs($val)
    {
        $this->li_iids = array_merge($this->li_iids, (array)$val);
    }

    public function set_ltable_alias($val)
    {
        $this->ltable_alias = is_string($val) && !empty($val)
            ? $val
            : '';
    }

    public function get_ltable_alias()
    {
        return is_string($this->ltable_alias) && !empty($this->ltable_alias)
            ? $this->ltable_alias
            : false;
    }

    public function get_as_alias()
    {
        return is_string($this->ltable_alias) && !empty($this->ltable_alias) && $this->ltable_alias != $this->ltable_original_name ? 'as `' . $this->ltable_alias . '`' : '';
    }

    protected function compileIIDs()
    {
        $not = !((bool)$this->negation)
            ? ''
            : (is_numeric($this->li_iids) ? '!' : ' NOT');

        if (is_numeric($this->li_iids)) {
            $this->compiledIIDs = " " . $not . "= '" . $this->li_iids . "'";
        } elseif (is_array($this->li_iids)) {
            $this->li_iids = array_unique($this->li_iids);
            $this->compiledIIDs = $not . ' IN (\'' . implode("', '", $this->li_iids) . '\')';
        }
    }

    protected function getCompiledIIDs()
    {
        return is_string($this->compiledIIDs) && !empty($this->compiledIIDs) ? $this->compiledIIDs : false;
    }

    public function compile($QM)
    {

        $p_oh = \Verba\_oh($QM->get_ot_id());

        $this->compileIIDS();
        if ($this->rootOt) {
            $linkedOt = $this->rootOt;
        } else {
            $linkedOt = $this->li_ot;
        }

        $ruleAlias = !empty($this->ruleAlias) ? $this->ruleAlias : false;

        $rule = $p_oh->getRule($linkedOt, $ruleAlias);
        list($pix1, $pix2) = $this->makePIXs($p_oh->getID(), $linkedOt);
        if ($rule['rule'] == 'links_table') {
            $this->ltable_original_name = $rule['table'];

            if (!$this->get_ltable_alias()) {
                $this->set_ltable_alias($this->ltable_original_name);
            }
            $ltableAlias = $this->get_ltable_alias();

            if (!$pix1 || (!$this->rootOt && !$this->getCompiledIIDs()) || !$ltableAlias) {
                return false;
            }

            if ($this->descendants_primary
                && is_array($dscds = $p_oh->getDescendants())
                && count($dscds)) {
                $primOts = " IN ('" . implode("','", array_merge(array($p_oh->getID() => $p_oh->getID()), $dscds)) . "')";
            } else {
                $primOts = " = '" . $QM->get_ot_id() . "'";
            }


            if (!$this->rootOt) {
                $pix1_ot_condition = "\n && `" . $ltableAlias . '`.`' . $pix1 . "_ot_id`" . $primOts;
            } else {
                $pix1_ot_condition = '';
            }

            $whereCond =
                '`' . $QM->getAlias() . '`.`' . $p_oh->getPAC() . '` IN(
  SELECT `' . $pix1 . '_iid`
  FROM ' . $rule['uri'] . ' ' . $this->get_as_alias() . '
  WHERE `' . $ltableAlias . '`.`' . $pix2 . "_ot_id` = '" . $this->getLinkedOT() . "'" . $pix1_ot_condition . "
  && `" . $ltableAlias . '`.`' . $pix2 . '_iid` ' . $this->getCompiledIIDs() . '
)';

            $QM->addWhere($whereCond
                , false
                , false
                , false
                , false
                , $this->global_glue
            );
        } elseif ($rule['rule'] == 'fid') {

            list($alias, $table, $db) = $QM->createAlias($rule['table'], $rule['db']);
            if ($pix1 == 'p') {
                $QM->addFrom($table, $db);
            }
            $QM->addWhere("`$alias`.`" . $rule['glue_field'] . '` ' . $this->getCompiledIIDs()
                , false
                , false
                , false
                , false
                , $this->global_glue
            );
        }
        return true;
    }

    public function setRootOt($val)
    {
        $this->rootOt = \Verba\_oh($val)->getID();
    }

    public function setRuleAlias($val)
    {
        $this->ruleAlias = (string)$val;
    }
}