<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class LinkingSelect extends Element
{
    public $templates = array(
        'body' => 'aef/fe/linkingselect/e.tpl',
    );
    public $lot;
    public $rootId = false;
    public $direction = 'up';
    public $linkRule;
    public $leveled = false;
    public $attrs = array(
        'title'
    );
    public $where = [];
    public $values;
    protected $lot_data = array();
    protected $br;

    function loadValues()
    {
        if ($this->values !== null) {
            return $this->values;
        }
        $this->values = array();
        $_linked = \Verba\_oh($this->lot);
        $l_ot = $_linked->getID();
        $pac = $_linked->getPAC();

        if (!$this->rootId) {

            $qm = new \Verba\QueryMaker($_linked, false, $this->attrs);

            if (is_array($this->where) && count($this->where))
            {
                foreach($this->where as $c_where)
                {
                    $qm->addWhere($c_where);
                }
            }

            $sqlr = $qm->run();

            if ($sqlr && $sqlr->getNumRows() > 0) {
                while ($row = $sqlr->fetchRow()) {
                    $this->values['i' . $row[$pac]] = array(
                        'id' => $row[$pac],
                        'title' => $row['title'],
                        'lvl' => 0,
                    );
                }
            }
        } else {
            $cache = new \cache('byOt/' . $_linked->getCode() . '/linkingSelectValues/' . $this->aef->oh->getCode() . '_' . $this->acode . '-' . $_linked->getCode());
            if ($cache->validateDataCache(6000)) {
                $this->values = $cache->getAsRequire();
            } else {

                $this->br = \Verba\Branch::get_branch(array(
                    $l_ot => array(
                        'iids' => $this->rootId,
                        'aot' => $l_ot
                    )
                ),
                    'down',
                    10,
                    true,
                    false,
                    true,
                    false,
                    is_string($this->linkRule) ? $this->linkRule : false
                );

                if (isset($this->br['handled'][$l_ot]) && is_array($this->br['handled'][$l_ot]) && count($this->br['handled'][$l_ot])) {
                    $this->lot_data = $_linked->getData($this->br['handled'][$l_ot]);
                    $this->parseLeveledValues($this->br['pare'][$l_ot][$this->rootId][$l_ot], 0);
                }
                $cache->writeDataToCache($this->values);
            }
        }

        return $this->values;
    }

    function parseLeveledValues($iids, $lvl)
    {
        $_linked = \Verba\_oh($this->lot);
        $l_ot = $_linked->getID();
        $pac = $_linked->getPAC();
        foreach ($iids as $iid) {
            $this->values['i' . $iid] = array(
                'id' => $iid,
                'title' => $this->lot_data[$iid]['title'],
                'lvl' => $lvl,
            );
            if (isset($this->br['pare'][$l_ot][$iid])) {
                $this->parseLeveledValues($this->br['pare'][$l_ot][$iid][$l_ot], $lvl + 1);
            }
        }
    }

    function loadSelected()
    {
        if ($this->selected !== null) {
            return $this->selected;
        }
        $this->selected = array();

        if ($this->aef->getAction() == 'new') {
            return $this->selected;
        }

        $loh = \Verba\_oh($this->lot);
        $br = \Verba\Branch::get_branch(array(
            $this->aef->oh->getID() => array(
                'iids' => $this->aef->getIID(),
                'aot' => $loh->getID()
            )
        ),
            $this->direction,
            1,
            false,
            false,
            true,
            false,
            isset($this->linkRule) && is_string($this->linkRule) ? $this->linkRule : false
        );
        $l_ot_id = $loh->getID();
        if (isset($br['handled'][$l_ot_id]) && is_array($br['handled'][$l_ot_id]) && count($br['handled'][$l_ot_id])) {
            $this->selected = $br['handled'][$l_ot_id];
        }

        return $this->selected;
    }

    function makeE()
    {
        $this->fire('makeE');

        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);
        $eId = 'ls_' . $this->aef->getFormId() . '_' . $this->acode;

        $this->loadValues();
        $this->loadSelected();

        $loh = \Verba\_oh($this->lot);

        $this->tpl->assign(array(
            'LS_JS_CFG' => json_encode(array(
                'E' => array(
                    'formwrap' => '#' . $this->aef->getFormWrapId(),
                    'wrap' => '#' . $eId,
                    'form' => '#' . $this->aef->getFormId(),
                ),
                'ot_id' => $this->aef()->oh()->getID(),
                'direction' => $this->direction,
                'selected' => $this->selected,
                'values' => $this->values,
                'acode' => $this->acode,
                'lot' => $loh->getID(),
                'leveled' => is_bool($this->leveled) ? $this->leveled : ($this->rootId ? true : false),
            )),
            'LS_ID' => $eId,
        ));

        $this->setE($this->tpl->parse(false, 'body'));
        if ($this->linkRule) {
            $this->aef->addHidden('NewObject[' . $this->aef->oh->getID() . '][_linkRule][' . $this->oh->getID() . '][' . $loh->getID() . '][' . $this->linkRule . '][direction]', $this->direction);
        }
        $this->fire('makeEFinalize');
    }

    function setRooted($val)
    {
        if (!is_array($val)) {
            return;
        }
        $val = array_intersect_key($val, $this->rooted);
        if (!count($val)) {
            return;
        }
        $this->rooted = array_replace_recursive($this->rooted, $val);
    }
}
