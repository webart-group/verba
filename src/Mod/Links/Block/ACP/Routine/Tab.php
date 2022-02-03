<?php
namespace Mod\Links\Block\ACP\Routine;

/* ACP links currencies Tab, or links action routed */

class Tab extends \Verba\Block\Html
{

    public $templates = array(
        'content' => '/links/acp/linksUI/tab.tpl'
    );

    public $url = array(
        'create' => false,
        'remove' => false,
        'update' => false,
    );

    public $lcfg;

    protected $poh;
    protected $soh;

    protected $urlbase = '/acp/h/links';

    function setUrlbase($val)
    {
        if (!is_string($val) || empty($val)) {
            return false;
        }

        $val = rtrim($val, '/');
        foreach ($this->url as $akey => $urlval) {
            $this->url[$akey] = $val . '/' . $akey;
        }
    }

    function prepare()
    {
        if (!is_array($this->lcfg)) {
            return false;
        }
        $this->poh = \Verba\_oh($this->lcfg['p']['ot']);
        $this->soh = \Verba\_oh($this->lcfg['s']['ot']);
    }

    // LinksUI Tab
    function build()
    {

        if (!$this->poh || !$this->soh) {
            return false;
        }

        $jsCfg = array(
            'values' => array(
                'p' => null,
                's' => null,
            ),
            'items' => array(),
            'url' => $this->url,
            'p' => $this->lcfg['p'],
            's' => $this->lcfg['s'],
            'extFields' => $this->lcfg['extFields'],
            'workers' => $this->lcfg['workers'],
            'cols_order' => $this->lcfg['cols_order']
        );
        $prevCode = false;
        $prevKey = false;
        foreach (array('p', 's') as $key) {
            $coh = $this->{$key . 'oh'};
            $otCode = $coh->getCode();
            if ($otCode == $prevCode) {
                $jsCfg['values'][$key] = $jsCfg['values'][$prevKey];
            }

            $prevKey = $key;
            $prevCode = $otCode;

            if (isset($jsCfg['values'][$key])
                && is_array($jsCfg['values'][$key])) {
                continue;
            }
            $jsCfg['values'][$key] = array();
            $attrs = array('title');

//      isset($this->lcfg[$key]['attrs'])
//      && is_array($this->lcfg[$key]['attrs'])
//      && !empty($this->lcfg[$key]['attrs'])
//        ? $this->lcfg[$key]['attrs']
//        : ( $coh->isA('title')
//          ? array('title')
//          : true);

            $qm = new \Verba\QueryMaker($coh, false, $attrs);
            //if($coh->isA('active')){
//        $qm->addWhere(1, 'active');
//      }
            $sqlr = $qm->run();

            if (!$sqlr || !$sqlr->getNumRows()) {
                continue;
            }
            while ($row = $sqlr->fetchRow()) {
                $jsCfg['values'][$key][$row[$coh->getPAC()]] = $row['title'];
            }
        }


        $loader = $this->getBlockByRole('linksLoader');
        $links = $loader->getLinks();
        if (is_array($links)) {
            $jsCfg['items'] = $links;
        }

        $this->tpl->assign(array(
            'LINKSUI_ID' => rand(1, 10000),
            'LINKS_CFG' => json_encode($jsCfg),
        ));

        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }
}
