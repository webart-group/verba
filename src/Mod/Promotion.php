<?php

namespace Mod;

class Promotion extends \Verba\Mod
{
    use \Verba\ModInstance;
    function init()
    {
        \Verba\_mod('order');
    }

    function loadGlobalPromos($cart, $context = 'global')
    {
        $_prom = \Verba\_oh('promotion');
        $qm = new \Verba\QueryMaker($_prom, false, true);
        $qm->addWhere(1, 'active');
        $qm->addWhere('global', 'dcontext');
        $sqlr = $qm->run();
        if (!$sqlr || !$sqlr->getNumRows()) {
            return false;
        }
        $r = $this->extractDiscountsFromSqlResult($sqlr, $cart);
        return $r;
    }

    function loadPromosByGoods($cart, $ot_id, $iid, $context = 'global')
    {
        $_prom = \Verba\_oh('promotion');
        $qm = new \Verba\QueryMaker($_prom, false, true);
        $qm->addWhere(1, 'active');
        //$qm->addWhere('global','dcontext');
        $qm->addConditionByLinkedOT($ot_id, $iid);
        $sqlr = $qm->run();
        if (!$sqlr || !$sqlr->getNumRows()) {
            return false;
        }
        $r = $this->extractDiscountsFromSqlResult($sqlr, $cart, true);
        return $r;
    }

    static function decodeDCfg($str)
    {
        if (!is_string($str) || empty($str)) {
            return false;
        }
        $dl = PHP_EOL;
        $tok = strtok($str, $dl);
        $r = array();
        while ($tok !== false) {
            if (strpos($tok, ':')) {
                list($k, $v) = explode(':', $tok);
                $r[trim($k)] = trim($v, "\n\r\t ");
            }
            $tok = strtok($dl);
        }
        return $r;
    }

    function extractDiscountsFromSqlResult($sqlr, $cart, $skip_applicable_check = false)
    {
        $skip_applicable_check = (bool)$skip_applicable_check;
        $_prom = \Verba\_oh('promotion');
        $r = array();
        $pac = $_prom->getPAC();
        while ($row = $sqlr->fetchRow()) {
            $cstName = '\Mod\Order\Discount\\' . $row['dtype'];
            if (class_exists($cstName)) {
                $className = $cstName;
            } else {
                $className = '\Mod\Order\Discount';
            }

            $cfg = array(
                'id' => $row[$pac],
                'type' => $row['dtype'],
                'context' => $row['dcontext'],
                'affect' => $row['daffect'],
                'title' => $row['title'],
            );

            $dcfg = self::decodeDCfg($row['dcfg']);
            if (is_array($dcfg)) {
                $cfg = array_replace_recursive($cfg, $dcfg);
            }

            $d = new $className($cfg, $cart);
            if ($skip_applicable_check || $d->isApplicable()) {
                $r[$row[$_prom->getPAC()]] = $d;
            }
        }
        return $r;
    }
}
