<?php

namespace Verba\Mod\Review\Block;
class PublicListAndForm extends \Verba\Block\Html
{

    public $templates = array(
        'content' => 'review/list/wrap.tpl',
        'item' => 'review/list/item.tpl',
        'empty' => 'review/list/empty.tpl'
    );


    function build()
    {
        $_rw = \Verba\_oh('review');
        $qm = new \Verba\QueryMaker($_rw, false, true);
        $qm->addWhere(1, 'active');
        $qm->addOrder(array('priority' => 'd', $_rw->getPAC() => 'd'));
        $q = $qm->getQuery();
        $sqlr = $qm->run();

        if ($sqlr && $sqlr->getNumRows()) {
            $iCfg = \Verba\_mod('image')->getImageConfig('review');
            while ($row = $sqlr->fetchRow()) {
                if (!empty($row['picture'])) {
                    $pic = $row['picture'];
                    $pic_sign = '';
                } else {
                    $pic = '/images/1px.gif';
                    $pic_sign = 'no-image';
                }
                $this->tpl->assign(array(
                    'ITEM_NAME' => htmlspecialchars($row['name']),
                    'ITEM_TEXT' => htmlspecialchars($row['review']),
                    'ITEM_IMAGE_SIGN' => $pic_sign,
                    'ITEM_PICTURE' => $pic,
                ));
                $this->tpl->parse('REVIEWS_ITEMS', 'item', true);
            }
        } else {
            $this->tpl->parse('REVIEWS_ITEMS', 'empty');
        }
        $this->addCSS(array('review'));

        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }
}

?>