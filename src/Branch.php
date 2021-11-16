<?php
namespace Verba;

class Branch extends Base
{

    protected static function get_branch_direction($direction = 'down')
    {
        return $direction == 'down' ? array('p', 'ch') : array('ch', 'p');
    }

    protected static function get_branch_queries(&$target, &$c_level, &$result, $direction, $RootIntoHandled, $priority, $activeCheck, $linkRule)
    {

        if (!is_array($target))
            return false;

        list($px1, $px2) = self::get_branch_direction($direction);
        $px_ot1 = $px1;
        $px_ot2 = $px2;

        if (is_string($linkRule) && !empty($linkRule)) {
            global $S;
            $db = $S->DbConnect();
            $linkRuleVal = $db->escape_string($linkRule);
        } else {
            $linkRuleVal = '';
        }

        foreach ($target as $c_ot_id => $c_ot_data) {
            if (!is_numeric($c_ot_id)
                || !\Verba\convertToIdList($c_ot_data['iids'], true)
                || !($c_oh = \Verba\_oh($c_ot_id))
                || !count($c_aot = $c_oh->getFamilyOTs($direction))
            ) {
                continue;
            }

            if (!is_array($c_ot_data['aot'])) {
                if (!count($result['aot'])) {
                    $c_ot_data['aot'] = $c_aot;
                } else {
                    $c_ot_data['aot'] = array_intersect($result['aot'], $c_aot);
                }
            }
            if (!count($c_ot_data['aot'])) continue;

            // Формирование where-части по iids primary-объекта
            $where_iid_str = '';
            foreach ($c_ot_data['iids'] as $c_iid) {
                $where_iid_str .= ", '" . $c_iid . "'";
                // Составление корневых нод.
                if ($c_level == 1) {
                    $result['root_nodes'][$c_ot_id][$c_iid] = (int)$c_iid;
                    if ($RootIntoHandled !== false) {
                        $result['handled'][$c_ot_id][$c_iid] = (int)$c_iid;
                    }
                }
            }
            $where_iid_str = substr($where_iid_str, 1);

            // Формирование where-части по aot
            foreach ($c_ot_data['aot'] as $c_aot) {
                $_linked = \Verba\_oh($c_aot);
                $fam_rl = $c_oh->getFamilyRelations($c_aot);

                // По какому правилу
                $rule = $c_oh->getRule($c_aot, $linkRule);
                switch ($rule['rule']) {
                    case 'links_table':
//            if(!is_string($c_oh->vltURI($c_aot))){
//              break;
//            }
                        $orderByPriority =
                        $isActiveCondition = false;
                        if ($activeCheck && $_linked->isA('active')) {
                            $isActiveCondition = "\n&& dt.active = 1";
                        }
                        if (is_string($priority) && $_linked->isA('priority')) {
                            $orderByPriority = "\nORDER BY dt.priority " . ($priority == 'a' ? 'ASC' : 'DESC');
                        }

                        if ($orderByPriority || $isActiveCondition) {
                            $leftJOIN = "\nLEFT JOIN " . $_linked->vltURI() . " as dt ON lt." . $px2 . "_iid = dt." . $_linked->getPAC();
                        } else {
                            $leftJOIN = '';
                        };

                        $separated_query[] = "SELECT lt." . $px_ot1 . "_ot_id, lt."
                            . $px1 . "_iid, lt." . $px_ot2 . "_ot_id, lt." . $px2 . "_iid FROM " . $rule['uri'] . " as lt"
                            . $leftJOIN .
                            "\nWHERE lt.`rule_alias` = '" . $linkRuleVal . "' && lt.`" . $px1 . "_ot_id` = '" . $c_ot_id . "' && lt.`" . $px1 . "_iid` IN (" . $where_iid_str . ") && lt.`" . $px_ot2 . "_ot_id`='" . $c_aot . "'"
                            . $isActiveCondition
                            . $orderByPriority;

                        break;

                    case 'fid':

                        switch (true) {

                            //search down
                            case $fam_rl == 1 && $rule['inverted'] === true:
                                $prim_f = $c_oh->getPAC();
                                $sec_f = $rule['glue_field'];
                                $tpref = 'dt';

                                break;

                            case $fam_rl == 1:
                            case $fam_rl == 3 && $direction == 'down':
                                $prim_f = $rule['glue_field'];
                                $sec_f = \Verba\_oh($c_aot)->getPAC();
                                $tpref = 'dt';
                                $leftJOIN = '';
                                $ot_field_value = $c_ot_id;
                                break;

                            //search up
                            case $fam_rl == 2 || $fam_rl == 3:
                                $prim_f = $c_oh->getPAC();
                                $sec_f = $rule['glue_field'];
                                $tpref = 'lt';
                                $leftJOIN = "\nLEFT JOIN " . $_linked->vltURI() . " as lt ON dt." . $sec_f . " = lt." . $_linked->getPAC();
                                $ot_field_value = $c_aot;
                                break;

                            default;
                                break 2;
                        }
                        $orderByPriority =
                        $isActiveCondition = false;
                        if ($activeCheck && $_linked->isA('active')) {
                            $isActiveCondition = "\n&& " . $tpref . ".active = 1";
                        }
                        if (is_string($priority) && $_linked->isA('priority')) {
                            $orderByPriority = "\nORDER BY " . $tpref . ".priority " . ($priority == 'a' ? 'ASC' : 'DESC');
                        }

                        if (is_string($rule['ot_field']) && !empty($rule['ot_field'])) {
                            $ot_field_cond = "\n&& dt.`" . $rule['ot_field'] . "` = '" . $ot_field_value . "'";
                        } else {
                            $ot_field_cond = '';
                        }

                        $separated_query[] = "SELECT ('" . $c_ot_id . "') as " . $px_ot1 . "_ot_id"
                            . "\n, dt." . $prim_f . " as " . $px1 . "_iid"
                            . "\n, ('" . $c_aot . "') as " . $px2 . "_ot_id"
                            . "\n, dt." . $sec_f . " as " . $px2 . "_iid"
                            . "\nFROM " . $rule['uri'] . " as `dt`"
                            . $leftJOIN
                            . "\nWHERE dt." . $prim_f . " IN (" . $where_iid_str . ")"
                            . "&& dt." . $sec_f . " IS NOT NULL"
                            . $ot_field_cond
                            . $isActiveCondition
                            . $orderByPriority;
                        break;
                }
            }
        }

        return is_array($separated_query) && count($separated_query) > 0 ? $separated_query : false;
    }

    /**
     * put your comment there...
     *
     * @param mixed array(  $ot => array("iids" => array(), "aot" => array()) )
     *                      $ot      - ОТ объектов, перечисленных в "iids", для которых ищутся связи,
     *                      "iids"  - массив iid объектов $ot,
     *                      "aot"    - массив разрешенных ОТ искомых объектов.
     * @param string $direction направление сканирования up | down
     * @param int $max_level глубина сканирования
     * @param mixed $ShowPairs формировать или нет структуру пар
     * @param mixed $RootIntoHandled включать или нет корневые узлы в массив обработанных
     * @param mixed $priority учитывать приоритет детей 'd'
     * @param mixed $activeCheck выбирать только активных детей
     * @param string $linkRule алиас правила связи
     * @param mixed $c_level внутренний  флаг рекурсии показывающий текущий уровень
     * @param mixed $result внутрення переменная рекурсии
     *
     * @return array
     *
     * Примечание. Префиксы при различных направлениях поиска.
     *         | px1|  px2
     *    -----|----|-----
     *    up   | ch |  p
     *    down | p  |  ch
     *  */
    public static function get_branch($target, $direction = 'down', $max_level = 1, $ShowPairs = true, $RootIntoHandled = true, $priority = 'd', $activeCheck = true, $linkRule = false, $c_level = 0, &$result = array())
    {
        global $S;

        $ShowPairs = (bool)$ShowPairs;
        $RootIntoHandled = (bool)$RootIntoHandled;
        $activeCheck = (bool)$activeCheck;
        $priority = is_string($priority)
            ? ($priority == 'a' ? $priority : 'd')
            : ($priority === false ? $priority : 'd');

        $c_level++;

        if ($c_level == 1) {
            $result['direction'] = $direction;
            $result['root_nodes'] = array();
            $result['handled'] = array();
            if ($ShowPairs === true) {
                $result['pare'] = array();
            }
            $prim_ot_id = key($target);
            if (isset($target[$prim_ot_id]['aots'])) {
                $target[$prim_ot_id]['aot'] = $target[$prim_ot_id]['aots'];
            }
            if (isset($target[$prim_ot_id]['aot']) && settype($target[$prim_ot_id]['aot'], 'array')) {
                $result['aot'] = $target[$prim_ot_id]['aot'];
            } else {
                $result['aot'] = array();
            }
        }

        // Формирование массива запросов по каждой таблице
        $separated_query = self::get_branch_queries($target, $c_level, $result, $direction, $RootIntoHandled, $priority, $activeCheck, $linkRule);
        if (!is_array($separated_query) || count($separated_query) < 1) {
            return $result;
        }
        $union_query = "(" . implode($separated_query, ") \nUNION\n (") . ")";

        list($px1, $px2) = self::get_branch_direction($direction);
        $db = $S->DbConnect();
        if (!$db || !($res = $db->query($union_query)) || $res->getNumRows() < 1) {
            return $result;
        }
        // Парсинг результата.
        $need2handle = array();
        while ($row = $res->fetchRow()) {
            $ot1 = (int)$row[$px1 . '_ot_id'];
            $ot2 = (int)$row[$px2 . '_ot_id'];
            $iid1 = is_numeric($row[$px1 . '_iid']) ? (int)$row[$px1 . '_iid'] : $row[$px1 . '_iid'];
            $iid2 = is_numeric($row[$px2 . '_iid']) ? (int)$row[$px2 . '_iid'] : $row[$px2 . '_iid'];

            // Если обрабатываемый ОТ не присутствует в разрешенных.
            if (count($result['aot']) && !in_array($ot2, $result['aot'])) continue;

            $_oh1 = \Verba\_oh($ot1);
            $_oh2 = \Verba\_oh($ot2);

            $c_aot = $_oh2->getFamilyOTs($direction);
            if (isset($target[$ot2]['aot'])) { // если ОТ уже есть в пришедших копируем оттуда
                $c_aot = $target[$ot2]['aot'];
            } elseif (count($result['aot'])) {
                $c_aot = array_intersect($c_aot, $result['aot']);
            }

            // создание узла ОТ для следующего поиска
            if ($c_level < $max_level && count($c_aot)) {
                if (!isset($need2handle[$ot2])) {
                    $need2handle[$ot2] = array('iids' => array(), 'aot' => $c_aot);
                }
                // Добавление узла в следующий поиск
                //Исключение рекурсии
                if (false === ($ot1 == $ot2 && $iid1 == $iid2)) {
                    $need2handle[$ot2]['iids'][] = $iid2;
                    $need2handle[$ot2]['aot'] = $c_aot;
                }
            }
            // Это не ошибочно залинкованный сам на себя объект.
            if (false === ($ot1 == $ot2 && $iid1 == $iid2)) {
                if (!isset($result['handled'][$ot2])) {
                    $result['handled'][$ot2] = array();
                }
                // Если он не был обработан ранее и текущий уровень не является максимальным
                if (!isset($result['handled'][$ot2][$iid2])) {
                    //Фиксация узла в обработанных
                    $result['handled'][$ot2][$iid2] = $iid2;
                }
                if ($ShowPairs === true) {
                    $result['pare'][$ot1][$iid1][$ot2][$iid2] = $iid2;
                }
            }
        }

        if (count($need2handle) > 0 && $c_level < $max_level && $c_level < 99) {
            $result = self::get_branch($need2handle, $direction, $max_level, $ShowPairs, $RootIntoHandled, $priority, $activeCheck, $linkRule, $c_level, $result);
        }

        return $result;
    }

    /**
     * На базе результата полученного в get_branch() строит дерево объектов
     *
     * @param mixed $sr - результат работы \Verba\Branch::get_branch()
     * @param bool $mode - не включать (true) или включать (false) узлы с ОТ_id; 2 - генерировать одномерный массив только из айди
     * @param array|false $items массив, определяющий текущий перечень родительских узлов.
     * @return mixed
     */
    public static function build_tree(&$sr, $mode = false, $items = false)
    {
        $mode = (int)$mode;
        if (!is_array($sr["handled"]) || !is_array($sr["root_nodes"])) {
            return false;
        }

        if (!$items) {
            $items = &$sr["root_nodes"];
        }

        if (!is_array($items)) {
            return array();
        }
        $array = array();
        foreach ($items as $c_ot => $iids) {
            if (!is_array($iids)) {
                continue;
            }
            foreach ($iids as $iid) {
                if ($mode == 2) {
                    $array[] = $iid;
                }
                if (!isset($sr['pare'][$c_ot][$iid]) || !is_array($sr['pare'][$c_ot][$iid])) {
                    if ($mode === 1)
                        $array[$iid] = null;
                    else
                        $array[$c_ot][$iid] = null;
                    continue;
                }
                foreach ($sr['pare'][$c_ot][$iid] as $ch_ot_id => $ch_iids) {
                    if (!is_array($ch_iids)) {
                        continue;
                    }
                    foreach ($ch_iids as $ch_iid) {
                        if (isset($sr["pare"][$ch_ot_id][$ch_iid]) && is_array($sr["pare"][$ch_ot_id][$ch_iid])) {
                            $z = self::build_tree($sr, $mode, array($ch_ot_id => array($ch_iid)));
                            if ($mode == 2) {
                                $array = array_merge($array, $z);
                            } else {
                                if ($mode) {
                                    $array[$iid][$ch_iid] = &$z[$ch_iid];
                                } else {
                                    $array[$c_ot][$iid][$ch_ot_id][$ch_iid] = &$z[$ch_ot_id][$ch_iid];
                                }
                            }

                        } else {
                            if ($mode == 2) {
                                $array[] = $ch_iid;
                            } else {
                                if ($mode)
                                    $array[$iid][$ch_iid] = $ch_iid;
                                else
                                    $array[$c_ot][$iid][$ch_ot_id][$ch_iid] = $ch_iid;
                            }
                        }
                    }
                }
            }
        }

        return $array;
    }

    public static function build_plain_chains(&$sr, $items = false, &$r = array(), $ckey = false)
    {

        if (!is_array($sr['handled']) || !is_array($sr['root_nodes'])) {
            return false;
        }

        if (!$items) {
            $items = &$sr['root_nodes'];
        }

        if (!is_array($items)) {
            return array();
        }
        $rootLevel = !$ckey;
        foreach ($items as $c_ot => $iids) {
            if (!is_array($iids)) {
                continue;
            }
            foreach ($iids as $iid) {
                // если это стартовый узел, заносим его в корень результата
                if ($rootLevel) {
                    $r[$iid] = array();
                    $ckey = $iid;
                } else {
                    $r[$ckey][$iid] = $iid;
                }
                // если у текущего узла нет детей
                if (!isset($sr['pare'][$c_ot][$iid])
                    || !is_array($sr['pare'][$c_ot][$iid])) {
                    continue;
                }

                foreach ($sr['pare'][$c_ot][$iid] as $ch_ot_id => $ch_iids) {
                    if (!is_array($ch_iids)) {
                        continue;
                    }

                    foreach ($ch_iids as $ch_iid) {
                        if (isset($sr['pare'][$ch_ot_id][$ch_iid])
                            && is_array($sr['pare'][$ch_ot_id][$ch_iid])) {
                            self::build_plain_chains($sr, array($ch_ot_id => array($ch_iid)), $r, $ckey);
                        } else {
                            $r[$ckey][$ch_iid] = $ch_iid;
                        }
                    }
                }
            }
        }

        return $r;
    }

}
