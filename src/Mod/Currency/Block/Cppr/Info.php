<?php
namespace Mod\Currency\Block\Cppr;

class Info extends \Verba\Block\Json
{

    function build()
    {
        $Shop = \Mod\Shop::getInstance();
        $_cur = \Verba\_oh('currency');
        $_ps = \Verba\_oh('paysys');
        $this->content = array('lastoptime' => '', 'dataset' => array());
        $q = "SELECT 
    cur.code as cur1, 
    ps.title_" . SYS_LOCALE . " as ps1, 
    cur2.code as cur2, 
    c.*
FROM `" . SYS_DATABASE . "`.`" . $Shop->cppr_table . "` as `c`
LEFT JOIN " . $_cur->vltURI() . " as cur
ON cur.id = c.iCurId 
LEFT JOIN " . $_cur->vltURI() . " as cur2
ON cur2.id = c.oCurId
LEFT JOIN " . $_ps->vltURI() . " as ps
ON ps.id = c.iPaysysId
ORDER BY cur1, ps1, cur2
";

        $sqlr = $this->DB()->query($q);
        if ($sqlr && $sqlr->getNumRows()) {
            while ($row = $sqlr->fetchRow()) {
                $key = $row['iCurId'] . '_' . $row['iPaysysId'] . '_' . $row['oCurId'];
                unset($row['iPaysysId'], $row['oCurId'], $row['iCurId']);
                $this->content['dataset'][$key] = $row;
            }
        }
        $mCron = \Mod\Cron::getInstance();
        $task = $mCron->getTask('', 'cron_shopRecalcCurrencyPaysysPairsRatio');
        if ($task) {
            $this->content['lastoptime'] = $task['lastStart'];
        }

        return $this->content;
    }

}
