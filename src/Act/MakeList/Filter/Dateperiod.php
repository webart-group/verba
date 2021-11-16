<?php

namespace Verba\Act\MakeList\Filter;

class Dateperiod extends \Verba\Act\MakeList\Filter
{
    public $templates = array(
        'list_filter_element' => 'list/default/filters/items/dateperiod.tpl'
    );
    public $captionLangKey = 'list filters dateperiod';
    public $ftype = 'dateperiod';
    public $value = array('from' => false, 'till' => false, 'alias' => false);
    public $dateFormat = array(
        'datepicker' => 'yy-mm-dd',
        'display' => 'Y-m-d',
        'sql' => 'Y-m-d H:i:s',
    );
    public $attr = 'created';
    public $periodAliases = array(
        'yesterday', 'month', 'week'
    );
    public $globalStoreName;

    function extractValue()
    {
        $rawValue = $this->C->getFilterValue($this->getAlias());
        if (!isset($rawValue) || !is_array($rawValue)) {
            if ($this->globalStoreName
                && isset($_SESSION['listGlobalFilters'][$this->globalStoreName])) {
                $this->value = $_SESSION['listGlobalFilters'][$this->globalStoreName];
                return;
            }
            return;
        }

        if (isset($rawValue['alias']) && in_array($rawValue['alias'], $this->periodAliases)) {
            $this->value['alias'] = $rawValue['alias'];
            switch ($rawValue['alias']) {
                case 'month':
                    $this->value['from'] = mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'));
                    break;
                case 'week':
                    $this->value['from'] = mktime(0, 0, 0, date('m'), date('d') - 7, date('Y'));
                    break;
                case 'yesterday':
                    $this->value['from'] = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                    $this->value['till'] = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                    break;
            }
        } else {
            if (isset($rawValue['from']) && ($from = strtotime($rawValue['from']))) {
                $this->value['from'] = $from;
            }
            if (isset($rawValue['till']) && ($till = strtotime($rawValue['till']))) {
                $till = mktime(23, 59, 59, date('m', $till), date('d', $till), date('Y', $till));
                $this->value['till'] = $till;
            }
        }
        // If GlobalStoreName is definded save value to session
        if ($this->globalStoreName) {
            $_SESSION['listGlobalFilters'][$this->globalStoreName] = $this->value;
        }

    }

    function applyValue()
    {
        $wgAlias = $this->makeWhereAlias();
        $this->list->QM()->removeWhere($wgAlias);
        $GW = $this->list->QM()->addWhereGroup($wgAlias);
        if ($this->value['from'] || $this->value['till']) {
            if ($this->value['from']) {
                $GW->addWhere(date($this->dateFormat['sql'], $this->value['from']), $wgAlias . '_from', $this->attr, false, '>=');
            }
            if ($this->value['till']) {
                $GW->addWhere(date($this->dateFormat['sql'], $this->value['till']), $wgAlias . '_till', $this->attr, false, '<=');
            }
        }
    }

    function build()
    {
        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);

        $datepickerCfg = array(
            'showOn' => 'both',
            'buttonImage' => '/images/calendar-ico.jpg',
            'buttonImageOnly' => true,
            'dateFormat' => $this->dateFormat['datepicker'],
        );
        $namePrefix = $this->makeName();

        $this->tpl->assign(array(
            'LIST_FILTER_CAPTION' => \Verba\Lang::get('list filters dateperiod'),
            'FLT_LIST_WRAP_ID' => $this->list->getWrapId() . '_filters',
            'DATESELECT_JS_CLASS_NAME' => 'datepicker',
            'DATESELECT_CFG' => json_encode($datepickerCfg),
            'DATESELECT_REGION' => SYS_LOCALE,

            'FLT_DATEPERIOD_FROM_NAME' => $namePrefix . '[from]',
            'FLT_DATEPERIOD_FROM_VALUE' => $this->value['from'] ? date($this->dateFormat['display'], $this->value['from']) : '',
            'FLT_DATEPERIOD_TILL_NAME' => $namePrefix . '[till]',
            'FLT_DATEPERIOD_TILL_VALUE' => $this->value['till'] ? date($this->dateFormat['display'], $this->value['till']) : '',
        ));

        $this->tpl->assign(array(
            'FILTER_ELEMENT' => $this->tpl->parse(false, 'list_filter_element'),
        ));

        return $this->tpl->parse(false, 'content');
    }
}

?>