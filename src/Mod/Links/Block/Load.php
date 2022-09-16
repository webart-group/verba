<?php
namespace Verba\Mod\Links\Block;

class Load extends \Verba\Block\Html
{

    public $lcfg = array();
    /**
     * @var \Model
     */
    protected $poh;
    /**
     * @var \Model
     */
    protected $soh;
    protected $gid = false;

    public $links = array();

    function prepare()
    {
        $this->poh = \Verba\_oh($this->lcfg['p']['ot']);
        $this->soh = \Verba\_oh($this->lcfg['s']['ot']);
    }

    function build()
    {

        $v = array(
            'p' => array(
                'attrs' => '',
                'join' => '',
            ),
            's' => array(
                'attrs' => '',
                'join' => '',
            ),
        );

        foreach (array('p', 's') as $pkey) {

            $oh = $this->{$pkey . 'oh'};
            $sql_pfx = $pkey == 'p' ? 'p' : 'ch';

            if (is_array($this->lcfg[$pkey]['attrs']) && !empty($this->lcfg[$pkey]['attrs'])) {
                foreach ($this->lcfg[$pkey]['attrs'] as $attr => $acfg) {
                    if (!$A = $oh->A($attr)) {
                        continue;
                    }
                    $v[$pkey]['attrs'] .= ', `vlt_' . $pkey . '`.`' . $A->getFieldCode() . '` as `' . $pkey . '_data_' . $A->getCode() . '`';
                }
                if (!empty($v[$pkey]['attrs'])) {
                    $v[$pkey]['join'] = "LEFT JOIN " . $oh->vltURI() . " as `vlt_" . $pkey . "`
ON `lnk`.`" . $sql_pfx . "_iid` = `vlt_" . $pkey . "`.`" . $oh->getPAC() . "`";
                }
            }
        }

        $ruleAlias = isset($this->lcfg['rule'])
        && is_string($this->lcfg['rule'])
            ? $this->lcfg['rule']
            : '';

        $rule = $this->poh->getRule($this->soh, $ruleAlias);

        $q = "SELECT lnk.*" . $v['p']['attrs'] . $v['s']['attrs'] . "
FROM " . $rule['uri'] . " as lnk
" . $v['p']['join'] . "
" . $v['s']['join'] . "";

        if (is_array($this->gid)) {
            $q .= "
WHERE
`rule_alias` = '" . $this->DB()->escape($ruleAlias) . "'
&& `p_ot_id` = '" . $this->DB()->escape($this->gid[0]) . "'
&& `p_iid` = '" . $this->DB()->escape($this->gid[1]) . "'
&& `ch_ot_id` = '" . $this->DB()->escape($this->gid[2]) . "'
&& `ch_iid` = '" . $this->DB()->escape($this->gid[3]) . "'";
        }
        $orderArr = array();
        if (is_array($this->lcfg['order']) && !empty($this->lcfg['order'])) {
            foreach ($this->lcfg['order'] as $ofieldCode => $orderDirection) {
                $orderArr[] = $ofieldCode . ' ' . ($orderDirection == 'd' ? 'DESC' : 'ASC');
            }
        } else {
            $orderArr[] = '`lnk`.`priority` DESC';
        }

        $q .= "
ORDER BY " . implode(', ', $orderArr);

        $sqlr = $this->DB()->query($q);
        if ($sqlr && $sqlr->getNumRows()) {

            $mLinks = \Verba\_mod('links');

            while ($row = $sqlr->fetchRow()) {

                $id = $mLinks->makeGID($row);
                $row['id'] = $id;
                foreach ($this->lcfg['extFields'] as $fcode => $fcfg) {
                    if (!array_key_exists($fcode, $row)
                        || !array_key_exists('datatype', $fcfg)) {
                        continue;
                    }
                    settype($row[$fcode], $fcfg['datatype']);
                }
                $this->links[$id] = $row;
            }
        }
        $this->content = $this->links;
        return $this->content;
    }

    function setGid($val)
    {
        if (is_string($val)) {
            $val = explode('_', $val);
        }
        if (!is_array($val)
            || !isset($val[0]) || empty($val[0]) // p_ot_id
            || !isset($val[1]) || empty($val[1]) // p_iid
            || !isset($val[2]) || empty($val[2]) // ch_ot_id
            || !isset($val[3]) || empty($val[3]) // ch_iid
        ) {
            return false;
        }

        $this->gid = $val;

        $this->p['ot'] = $val[0];
        $this->s['ot'] = $val[2];
    }

    function setRuleAlias($val)
    {
        throw new \Exception('ЭТО НУЖНО?');
        //$this->ruleAlias = (string)$val;
    }

    function getLinks()
    {
        return $this->links;
    }
}
