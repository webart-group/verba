<?php

namespace Verba\Mod\Catalog\Block;

use Verba\Block\Json;
use Verba\Lang;
use function Verba\_mod;
use function Verba\_oh;
use function Verba\Mod\Catalog\make_padej_ru;

class GoodsIndex extends Json
{
    public $templates = array(
        'tpl' => 'catalog/indexContnet/block.tpl',
        'item' => 'catalog/indexContnet/item.tpl',
        'subitem' => 'catalog/indexContnet/subitem.tpl',
        'subitems_prefix' => 'catalog/indexContnet/subitems_prefix.tpl',
    );

    function build()
    {
        $this->content = '';
        $_catalog = _oh('catalog');
        $cot = $_catalog->getID();
        $mCat = _mod('catalog');

        $catsChain = $this->request->getParam('catsData');
        $ccat = end($catsChain);
        $iid = $ccat['id'];
        $childs = $this->request->getParam('childChain');
        if (!$childs || !isset($childs) || !is_array($childs) || !count($childs)) {
            return $this->content;
        }
        $mImage = _mod('image');
        $prefix = '';
        foreach ($catsChain as $cpcat) {
            $prefix .= '/' . $cpcat['code'];
        }

        $p = $childs['br']['pare'][$cot];
        $root = $childs['br']['pare'][$cot][$iid][$cot];
        foreach ($root as $chid) {
            $row = $childs['data'][$chid];
            $caturl = $prefix . '/' . $row['code'];
            if (!empty($row['picture'])) {
                $imgCfg = $mImage->getImageConfig($_catalog->p('picture_config'));
                $iUrl = $imgCfg->getFullUrl(basename($row['picture']));
                $sign = '';
            } else {
                $iUrl = SYS_IMAGES_URL . '/1px.gif';
                $sign = ' ni';
            }
            $this->tpl->assign(array(
                'SUBITEMS' => '',
                'SUBITEMS_ITEMS' => '',
                'SUBITEMS_PREFIX' => '',
                'CAT_ITEM_URL' => $caturl,
            ));
            if (isset($p[$chid][$cot])) {
                $i = count($p[$chid][$cot]);
                $z = 2;
                $restSubitems = $i - $z;
                $subitems_html = array();
                foreach ($p[$chid][$cot] as $subid) {
                    $subrow = $childs['data'][$subid];
                    $this->tpl->assign(array(
                        'SI_TITLE' => $subrow['title'],
                        'SI_HREF' => $caturl . '/' . $subrow['code'],
                    ));
                    $subitems_html[] = $this->tpl->parse(false, 'subitem');
                    if (--$i == $restSubitems && $restSubitems != 1) {
                        break;
                    }
                }

                if ($restSubitems > 1) {
                    $this->tpl->assign(array(
                        'MORE_SUBCATS_COUNT' => $restSubitems,
                        'MORE_SUBCATS_COUNT_WORD' => make_padej_ru($restSubitems, Lang::get('catalog more root'), Lang::get('catalog more v'))
                    ));
                    $this->tpl->parse('SUBITEMS_PREFIX', 'subitems_prefix');
                }
                $this->tpl->assign(array(
                    'SUBITEMS_ITEMS' => implode('', $subitems_html),
                ));
                $this->tpl->asg('SUBITEMS', $this->tpl->getVar('SUBITEMS_ITEMS')
                    . $this->tpl->getVar('SUBITEMS_PREFIX'));

            }

            $this->tpl->assign(array(
                'CAT_ITEM_TITLE' => $row['title'],
                'CAT_ITEM_ALT' => $row['title'],
                'CAT_ITEM_IMAGE_URL' => $iUrl,
                'CAT_ITEM_IMAGE_SIGN' => $sign,
            ));
            $this->tpl->parse('CAT_INDEX_ITEMS', 'item', true);
        }
        $this->addCSS(array('catalog'));

        $this->content = $this->tpl->parse(false, 'tpl');
        return $this->content;
    }
}
