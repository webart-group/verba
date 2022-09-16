<?php

namespace Verba\Act\Form\Element\Extension;

use \Verba\Act\Form\Element\Extension;

class OtypeBaseTypeSelector extends Extension
{
    public $prod = 0;

    function engage()
    {
        $this->fe->listen('loadValuesBefore', 'loadValues', $this);
    }

    function loadValues()
    {
        $r = array('' => '');

        $_oh = \Verba\_oh('otype');
        $qm = new \Verba\QueryMaker($_oh, false, array('ot_code', 'title'));
        if ($this->prod) {
            $qm->addWhere('public_product_base', 'role');
        }
        $sqlr = $qm->run();
        $pac = $_oh->getPAC();
        if ($sqlr && $sqlr->getNumRows()) {
            while ($row = $sqlr->fetchRow()) {
                $r[$row[$pac]] = $row['title'] . ' (' . $row['ot_code'] . ')';
            }
        }

        $this->fe()->setValues($r);
    }
}
