<?php

namespace Verba\Act\Form\Element;

class ForeignSelect extends Select
{
    /**
     * @var \Verba\Model\Item
     */
    protected $__cValueItem;
    /**
     * @var array массив с кодами полей из которых будет сформирована
     * текстовая часть Значения селекта при обработке данных из базы
     */
    public $fieldsToTitle = array();

    public $throwExpIfEmpty = false;
    /**
     * @var array возможный обработчик значения опшена селекта
     */
    public $valueFormater;

    /**
     * @var \Verba\QueryMaker
     */
    public $qm;

    function loadValues()
    {

        if (!$this->A) {
            return false;
        }
        $handlers = $this->A->getHandlers('present');

        foreach ($handlers as $h) {
            if ($h['ah_name'] == 'ForeignId'
                && isset($h['params']['ot_id'])
                && false != ($foh = \Verba\_oh($h['params']['ot_id']))) {
                $fattr = $h['params']['field2display'];
                break;
            }
        }

        if (!isset($foh) || !$foh) {

            return false;

        }

        if (!isset($fattr) || !($fA = $foh->A($fattr))) {
            return false;
        }

        $attr_code = $fA->getCode();

        $this->qm = new \Verba\QueryMaker($foh, false, true);
        if ($foh->isA('active')) {
            $this->qm->addWhere(1, 'active');
        }
        if (is_array($this->order) && count($this->order)) {
            $this->qm->addOrder($this->order);
        } elseif ($attr_code == 'title' || $fA->getDataType() == 'string') {
            $this->qm->addOrder(array($attr_code => 'a'));
        } elseif ($foh->isA('priority')) {
            $this->qm->addOrder(array('priority' => 'd'));
        }

        // ModifyQuery
        $this->fire('modifyQueryBefore');
        $this->modifyQuery($this->qm);
        $this->fire('modifyQueryAfter');

        $sqlr = $this->qm->run();
        if (!$sqlr) {
            return false;
        }

        if (!$sqlr->getNumRows() && $this->throwExpIfEmpty) {
            throw new \Exception($this->A->getCode() . ': Empty Values');
        }
        $this->fire('formateValuesBefore');
        if (!is_array($this->fieldsToTitle) || !count($this->fieldsToTitle)) {
            $this->fieldsToTitle = array($attr_code => array());
        }
        return $this->formateValues($sqlr, $foh);

    }

    /**
     * @param $sqlr \DBDriver\Result
     * @param $foh \Verba\Model
     * @param $default_attr_code
     * @return array
     */
    function formateValues($sqlr, $foh)
    {
        $r = array();
        $pac = $foh->getPAC();

        $this->fieldsToTitle = \Configurable::substNumIdxAsStringValues($this->fieldsToTitle);
        // если задан внешний обработчик значения, используем его.
        // Нет - обработчик по умолчанию
        if (is_array($this->valueFormater)) {
            $h = $this->valueFormater;
        } else {
            $h = false;
        }

        while ($row = $sqlr->fetchRow()) {
            $Item = $foh->initItem($row);
            if ($h) {
                $r[$row[$pac]] = call_user_func_array($h, array($Item, $this));
            } else {
                $r[$row[$pac]] = $this->formateValue($Item);
            }


        }

        return $r;
    }

    /**
     * @param $OItem \Verba\Model\Item
     * @param $default_attr_code
     * @return string
     */
    function formateValue($Item)
    {

        if (!is_array($this->fieldsToTitle) || !count($this->fieldsToTitle)) {
            return '?*?';
        }
        $r = array();
        foreach ($this->fieldsToTitle as $fcode => $nomatter) {
            $r[] = $Item->getValue($fcode);
        }

        return implode(', ', $r);
    }

    /**
     * @param $qm \Verba\QueryMaker
     */
    function modifyQuery($qm)
    {

    }
}
