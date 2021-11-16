<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 21.08.19
 * Time: 18:10
 */

namespace Verba\QueryMaker\Condition;


class LinkedRight extends Linked
{
    /**
     * @param \Verba\QueryMaker $QM
     */
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
                list($lta) = $QM->createAlias($rule['table'], $rule['db'], $this->alias . '_qqr_lt');
                $this->set_ltable_alias($lta);
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
            $QM->addCJoin(array(array('a' => $ltableAlias)),
                array(
                    array('p' => array('a' => $QM->getAlias(), 'f' => $p_oh->getPAC()),
                        's' => array('a' => $ltableAlias, 'f' => $pix1 . '_iid'),
                    ),
                )
                , true, $this->alias . '_qqr_rj', 'RIGHT');
            $WG = $QM->addWhereGroup($this->alias . '_qqr_wg');
            $WG->addWhere(
                "\n`" . $ltableAlias . "`.`" . $pix2 . "_ot_id` = '" . $this->getLinkedOT() . "'"
                . $pix1_ot_condition
                . "\n&& `" . $ltableAlias . "`.`" . $pix2 . "_iid` " . $this->getCompiledIIDs()
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
}
