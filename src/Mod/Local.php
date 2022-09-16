<?php

namespace Verba\Mod;

class Local extends \Verba\Mod
{
    use \Verba\ModInstance;

    function makeItemNavigation($ot, $cfg = array())
    {
        $tpl = $this->tpl();
        $cfg = (array)$cfg;

        $slId = isset($cfg['listId']) ? $cfg['listId'] : $_REQUEST['slID'];
        $oh = \Verba\_oh($ot);

        $seq = $_REQUEST['seq'];
        $seq = (int)$seq;
        if (!is_numeric($seq) || $seq <= 0) {
            return false;
        }
        if (isset($cfg['sl']) && $cfg['sl'] instanceof \Selection) {
            $sl = $cfg['sl'];
        } else {
            $sl = \Verba\init_selection(false, false, $slId);
            if (!is_object($sl)) {
                return '';
            }
        }
        $sl->setDefaultOrderApplied(true);
        $sl->refreshOrder();
        $qm = $sl->QM;

        $qm->addLimit(3, $seq - 2);
        if (isset($cfg['attrs']) && is_array($cfg['attrs'])) {
            foreach ($cfg['attrs'] as $attrCode) {
                $qm->addSelect($attrCode);
            }
        }
        $qm->makeQuery();
        $q = $qm->getQuery();
        $sqlr = $this->DB()->query($q);

        if (!is_object($sqlr) || $sqlr->getNumRows() <= 1) {
            return '';
        }
        while ($row = $sqlr->fetchRow()) {
            $data[] = $row;
        }

        $slCount = $sl->get_count_v();
        switch ($seq) {
            case 1:
                $nextItem = $data[1];
                break;
            case $slCount:
                $prevItem = $data[0];
                break;
            default:
                $prevItem = $data[0];
                $nextItem = $data[2];
                break;
        }
        if (!isset($cfg['tpls']) || !is_array($cfg['tpls'])) {
            $cfg['tpls'] = array(
                'navigation' => '/local/navigation/navigation.tpl',
                'link' => '/local/navigation/link.tpl'
            );
        }

        $tpl->define($cfg['tpls']);
        if ($prevItem) {
            $urlVars = array('seq' => $seq - 1);
            if (isset($cfg['hrefHandler']) && is_array($cfg['hrefHandler'])
                && isset($cfg['hrefHandler'][0])
            ) {
                if (isset($cfg['hrefHandler'][1])) {
                    $urlVars = array_replace_recursive($urlVars, $cfg['hrefHandler'][1]);
                }
                $args = array($prevItem, $urlVars);
                $href = call_user_func_array($cfg['hrefHandler'][0], $args);
            } else {
                $urlVars['iid'] = $prevItem[$oh->getPAC()];
                $href = var2url($_SERVER['REQUEST_URI'], $urlVars);
            }
            $tpl->assign(array(
                'NAV_HREF' => $href,
                'NAV_PREV_HREF' => $href,
                'NAV_TIP' => $cfg['buttons']['prev'],
                'NAV_CLASS' => 'item prev',
                'PREV_VISIBILITY_CLASS' => '',
            ));
            $tpl->parse('PREV_ITEM', 'link', true);
        } else {
            $tpl->assign(array(
                'PREV_ITEM' => '',
                'PREV_VISIBILITY_CLASS' => ' hidden',
            ));
        }
        if ($nextItem) {
            $urlVars = array('seq' => $seq + 1);

            if (isset($cfg['hrefHandler']) && is_array($cfg['hrefHandler'])
                && isset($cfg['hrefHandler'][0])
            ) {
                if (isset($cfg['hrefHandler'][1])) {
                    $urlVars = array_replace_recursive($urlVars, $cfg['hrefHandler'][1]);
                }
                $args = array($nextItem, $urlVars);
                $href = call_user_func_array($cfg['hrefHandler'][0], $args);
            } else {
                $urlVars['iid'] = $nextItem[$oh->getPAC()];
                $href = var2url($_SERVER['REQUEST_URI'], $urlVars);
            }
            $tpl->assign(array(
                'NAV_HREF' => $href,
                'NAV_TIP' => $cfg['buttons']['next'],
                'NAV_CLASS' => 'item next',
                'NEXT_VISIBILITY_CLASS' => '',

            ));

            $tpl->parse('NEXT_ITEM', 'link', true);
        } else {
            $tpl->assign(
                array(
                    'NEXT_ITEM' => '',
                    'NEXT_VISIBILITY_CLASS' => ' hidden',
                ));
        }

        $tpl->assign(array(
            'NAV_DELIMITER_CLASS_SIGN' => $nextItem && $prevItem ? '' : ' hidden'
        ));

        return $tpl->parse(false, 'navigation');
    }

}
