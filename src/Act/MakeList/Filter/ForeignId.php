<?php

namespace Verba\Act\MakeList\Filter;


class ForeignId extends \Verba\Act\MakeList\Filter
{

    public $values;
    public $attr;
    public $check_active;
    public $check_priority;
    public $felement = '\Html\Select';

    function __construct($list, $cfg)
    {
        parent::__construct($list, $cfg);
        $this->A = $this->oh->A($this->attr);
        if (!$this->name) {
            $this->name = $this->A->getCode();
        }
    }

    function getValues()
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $this->values = array('' => $this->getCaption());

        $handlers = $this->A->getHandlers('present');
        $fattr = false;
        foreach ($handlers as $h) {
            if ($h['ah_name'] == 'foreign_id'
                && isset($h['params']['ot_id'])
                && false != ($foh = \Verba\_oh($h['params']['ot_id']))) {
                $fattr = $h['params']['field2display'];
                break;
            }
        }

        if (!isset($foh) || !($A = $foh->A($fattr))) {
            return $this->values;
        }
        $attr_code = $A->getCode();
        $pac = $foh->getPAC();

        $qm = new \Verba\QueryMaker($foh, false, array($attr_code));
        if ($this->check_active && $foh->isA('active')) {
            $qm->addWhere(1, 'active');
        }
        if ($this->check_priority && $foh->isA('priority')) {
            $qm->addOrder(array('priority' => 'd'));
        }

        $sqlr = $qm->run();
        if (!$sqlr) {
            return false;
        }
        while ($row = $sqlr->fetchRow()) {
            $this->values[$row[$pac]] = $row[$attr_code];
        }
        return $this->values;
    }

    function applyValue()
    {
        $fSqlAlias = $this->makeWhereAlias();
        $this->list->QM()->removeWhere($fSqlAlias);
        if (isset($this->value)
            && $this->A && $this->getValues()
            && array_key_exists($this->value, $this->values)) {
            $this->list->QM()->addWhere($this->value, $fSqlAlias, $this->A->getCode());
        }
    }

    function build()
    {
        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);
        $A = $this->oh->A($this->attr);
        if (!$A) {
            return 'Bad attr code \'' . htmlspecialchars($this->attr) . '\'';
        }

        $this->getValues();
        $this->E->setValues($this->values);

        if (isset($this->value) && array_key_exists($this->value, $this->values)) {
            $this->E->setValue($this->value);
        }
        if (!$this->caption && !$this->captionLangKey) {
            $this->caption = $A->display();
        }

        $this->tpl->assign(array(
            'FILTER_ELEMENT' => $this->E->build()
        ));

        return $this->tpl->parse(false, 'content');
    }
}
