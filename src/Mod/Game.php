<?php

namespace Mod;

class Game extends \Verba\Mod
{
    use \Verba\ModInstance;
    protected $_allGames;

    public static $orderTimeFormat = 'H:i d/m/y';

    protected $games_cache_filename = 'cache.games_struct.php';

    function getTopGames($count = false)
    {

        $this->getGames();

        $count = intval($count);

        $r = array();
        $i = 0;
        foreach ($this->_allGames as $gi => $Game) {
            if (!$Game->pop) {
                continue;
            }
            $r[$gi] = $Game;
            if ($count > 0 && $i++ == $count) break;
        }

        return $r;
    }

    function getGames($count = false)
    {

        if ($this->_allGames === null) {
            $this->_allGames = $this->extractGames();
        }

        if (!is_array($this->_allGames)) {
            return false;
        }

        if (!is_int($count) || $count < 1) {
            return $this->_allGames;
        }
        $r = array();
        $i = 0;
        foreach ($this->_allGames as $gi => $Game) {
            $r[$gi] = $Game;
            if ($i++ == $count) break;
        }
        return $r;
    }

    function extractGames()
    {

        $cacheFile = $this->getCacheDir() . '/' . $this->games_cache_filename;
        // Кеша нет, подгружаем из базы
        if (\Verba\Hive::$cacheEnable && file_exists($cacheFile)) {
            // Кеш есть, берем структуру из файла и создаем список Топовых
            $allGames = file_get_contents($cacheFile);
            if (is_string($allGames) && is_array($allGames = unserialize($allGames))) {
                return $allGames;
            }
        }

        $allGames = $this->loadGames();
        if (\Verba\Hive::$cacheEnable) {
             \Verba\FileSystem\Local::needDir($this->getCacheDir());
            $fp = fopen($cacheFile, 'w');
            fwrite($fp, serialize($allGames));
            fclose($fp);
        }

        return $allGames;
    }

    function loadGames()
    {

        $_game = \Verba\_oh('game');
        $_cat = \Verba\_oh('catalog');
        $qm = new \Verba\QueryMaker($_cat, false, true);
        list($cA, $cT, $cD) = $qm->createAlias();
        $prntCnd = $qm->addConditionByLinkedOT($_cat, 1);
        $prntCnd->setRelation(2);

        list($lgA, $lgT, $lgD) = $qm->createAlias($_cat->vltT($_game));
        list($gA, $gT, $gD) = $qm->createAlias($_game->vltT());
        //подключение таблицы связей каталог-продукты
        $qm->addCJoin(array(array('a' => $lgA)),
            array(
                array('p' => array('a' => $lgA, 'f' => 'ch_ot_id'),
                    's' => $_cat->getID(),
                ),
                array('p' => array('a' => $lgA, 'f' => 'ch_iid'),
                    's' => array('a' => $cA, 'f' => $_cat->getPAC()),
                ),
                array('p' => array('a' => $lgA, 'f' => 'p_ot_id'),
                    's' => $_game->getID(),
                ),
            ), true
        );

        // подключение таблицы каталога для выборки данных каталога
        $qm->addSelectPastFrom('icon', $gA);
        $qm->addCJoin(array(array('a' => $gA)),
            array(
                array('p' => array('a' => $lgA, 'f' => 'p_iid'),
                    's' => array('a' => $gA, 'f' => $_game->getPAC()),
                ),
            ), true
        );
        $qm->addOrder(array(
            'priority' => 'd'
        ));
        $qm->addWhere(1, 'active');

//    // Add code or id contition
//    if(!empty($id_or_code)){
//      if(is_numeric($id_or_code)){
//        $qm->addWhere($id_or_code, $_cat->getPAC());
//      }elseif(is_string($id_or_code)){
//        $qm->addWhere($id_or_code, $_cat->getStringPAC());
//      }
//    }

        $q = $qm->getQuery();

        $sqlr = $this->DB()->query($q);

        if (!$sqlr || !$sqlr->getNumRows()) {
            return array(false, false);
        }

        $catGames = array();
        while ($row = $sqlr->fetchRow()) {
            $catGames[$row['id']] = new Game\Item($row);
        }
        $cat_ot_id = $_cat->getID();

        $qms = new \Verba\QueryMaker($_cat, false, true);
        list($cA) = $qms->createAlias();
        //подключение таблицы связей каталог-каталог
        list($lcA, $lcT, $lcD) = $qms->createAlias($_cat->vltT($_cat));
        $qms->addCJoin(array(array('a' => $lcA)),
            array(
                array('p' => array('a' => $lcA, 'f' => 'ch_iid'),
                    's' => array('a' => $cA, 'f' => $_cat->getPAC()),
                ),
            ), false, 'catalogParent', 'RIGHT'
        );
        // подключение таблицы каталога для выборки данных каталога
        $qms->addSelectPastFrom('p_iid', $lcA);

        $qms->addWhere(1, 'active');
        $qms->addWhere($cat_ot_id, 'pot', 'p_ot_id', array($lcT, $lcD, $lcA));
        $qms->addWhere($cat_ot_id, 'chot', 'ch_ot_id', array($lcT, $lcD, $lcA));
        $qms->addWhere("`" . $lcA . "`.`p_iid` IN (" . implode(",", array_keys($catGames)) . ")", false, 'p_iid', array($lcT, $lcD, $lcA));

        $qms->addOrder(array('p_iid' => 'a'), false, array($lcT, $lcD, $lcA));
        $qms->addOrder(array('priority' => 'd'));

        $q = $qms->getQuery();
        $sqlr = $this->DB()->query($q);

        if ($sqlr && $sqlr->getNumRows()) {
            while ($row = $sqlr->fetchRow()) {
                $catGames[$row['p_iid']]->addService($row['id'], $row);
            }
        }

        return $catGames;
    }

    function getGame($code_or_id)
    {
        if (!$code_or_id) {
            return false;
        }
        $this->getGames();

        if (!is_array($this->_allGames) || !count($this->_allGames)) {
            return false;
        }

        foreach ($this->_allGames as $gid => $Game) {
            if ($Game->code == $code_or_id || $Game->id == $code_or_id) {
                return $Game;
            }
        }

        return false;
    }

    /** Multiselector */
    function getGamesForMultiSelector($pids = false)
    {

        $pids = !is_array($pids) ? array() : $pids;

        $k = current($pids);
        $r = array($k => array());

        $this->getGames();

        foreach ($this->_allGames as $gid => $GameItem) {
            $r[$k][$gid] = array(
                'title' => $GameItem->title,
                'id' => $gid,
            );
        }

        return $r;
    }

    function getServicesForMultiSelector($pids = false)
    {
        $pids = !is_array($pids) ? array() : $pids;
        $r = array();

        if (!count($pids)) {
            return $r;
        }

        $this->getGames();

        foreach ($pids as $gameId) {
            if (!array_key_exists($gameId, $this->_allGames)) {
                continue;
            }
            $r[$gameId] = array();

            $services = $this->_allGames[$gameId]->getServices();
            if (!is_array($services) || !count($services)) {
                continue;
            }
            foreach ($services as $serviceId => $ServiceItem) {
                $r[$gameId][$serviceId] = array(
                    'title' => $ServiceItem->title,
                    'id' => $serviceId,
                );
            }
        }

        return $r;
    }

    function loadServiceIdAndGameIdByProductId($_prod, $iid)
    {
        $_prod = \Verba\_oh($_prod);
        if (!$iid) {
            return array(false, false);
        }
        $_cat = \Verba\_oh('catalog');

        $cat_ot = $_cat->getID();
        $prod_ot = $_prod->getID();

        $br = \Verba\Branch::get_branch(array($prod_ot => array('iids' => $iid, 'aot' => $cat_ot)), 'up', 2, true, true, 'd', true, '');
        if (!is_array($br['handled'][$cat_ot]) || count($br['handled'][$cat_ot]) != 2) {
            return array(false, false);
        }
        $serviceId = current($br['pare'][$prod_ot][$iid][$cat_ot]);
        $gameId = current($br['pare'][$cat_ot][$serviceId][$cat_ot]);

        return array($serviceId, $gameId);
    }

    public static function parseToGameTimeHtml($sometime)
    {
        if (!$sometime) {
            return '';
        }
        if (is_numeric($sometime)) {
            $time = (int)$sometime;
        } elseif (is_string($sometime)) {
            $time = strtotime($sometime);
        }

        if (!isset($time) || !$time) {
            return '';
        }

        $r = '<div class="go-time"><span>' . date('d/m/y', $time) . '</span><span>' . date('H:i', $time) . '</span></div>';
        return $r;
    }

    /**
     * @param $aef \Act\Form
     * @return mixed
     */
    function genTformResourseProdCustomFields($aef)
    {
        /**
         * @var $FE
         */
        $af = '';
        foreach ($aef->getFormElements() as $fecode => $FE) {
            $acode = $FE->acode;
            if (array_key_exists(strtolower($acode), $aef->founded_in_particle)
                || $FE->getHidden()) {
                continue;
            }
            $af .= $aef->tpl()->getVar('AVALUE_' . strtoupper($acode) . '_0');
        }

        return $af;
    }

}
