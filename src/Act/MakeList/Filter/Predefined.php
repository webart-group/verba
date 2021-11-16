<?php

namespace Verba\Act\MakeList\Filter;

class Predefined extends \Verba\Act\MakeList\Filter
{

    public $values;
    public $felement = '\Html\Select';

    function applyValue()
    {
        $fSqlAlias = $this->makeWhereAlias();
        $this->list->QM()->removeWhere($fSqlAlias);
        if (isset($this->value) && $this->A && $this->getValues() && array_key_exists($this->value, $this->values)) {
            $this->list->QM()->addWhere($this->value, $fSqlAlias, $this->A->getCode());
        }
    }

    function getValues()
    {
        if ($this->values === null) {
            $this->values = array('' => $this->getCaption());
            if (!$this->A) {
                return $this->values;
            }
            $existsValues = $this->A->getValues();
            if (is_array($existsValues)) {
                $this->values += $this->A->getValues();
            }

        }
        return $this->values;
    }

    function build()
    {
        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);


        if (!$this->A) {
            return 'Bad attr code \'' . htmlspecialchars($this->name) . '\'';
        }
        if (!$this->A->isPredefined()) {
            return 'Attr \'' . htmlspecialchars($this->name) . '\' is not a Predefined. Wrong filter class';
        }

        $this->setCaption($this->makeCaption());


        $this->getValues();
        $this->E->setValues($this->values);

        if (isset($this->value) && array_key_exists($this->value, $this->values)) {
            $this->E->setValue($this->value);
        }

        if (!$this->caption && !$this->captionLangKey) {
            $this->caption = $this->A->display();
        }

        $this->tpl->assign(array(
            'FILTER_ELEMENT' => $this->E->build()
        ));

        return $this->tpl->parse(false, 'content');
    }
}
