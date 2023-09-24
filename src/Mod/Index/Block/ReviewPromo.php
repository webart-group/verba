<?php

namespace Verba\Mod\Index\Block;
class review_IndexPromo extends \Verba\Block\Html
{

    function build()
    {
        $_review = \Verba\_oh('review');

        $qm = new \Verba\QueryMaker($_review, false, true);
        $qm->addWhere(1, 'active');
        $qm->addWhere(1, 'promo');
        $qm->addOrder(array('priority' => 'd'));
        $sqlr = $qm->run();

        if (!$sqlr || !$sqlr->getNumRows()) {
            return '';
        }

        $tpl = $this->tpl();
        $tpl->define(array(
            'wrap' => '/review/index/wrap.tpl',
            'item' => '/review/index/item.tpl',
        ));
        $iCfg = \Verba\_mod('image')->getImageConfig('review');
        while ($row = $sqlr->fetchRow()) {
            if (!empty($row['picture'])) {
                $pic = $row['picture'];
                $pic_sign = '';
            } else {
                $pic = '/images/1px.gif';
                $pic_sign = 'no-image';
            }
            $tpl->assign(array(
                'ITEM_NAME' => htmlspecialchars($row['name']),
                'ITEM_TEXT' => htmlspecialchars($row['review']),
                'ITEM_IMAGE_SIGN' => $pic_sign,
                'ITEM_PICTURE' => $pic,
            ));

            $tpl->parse('REVIEWS_ITEMS', 'item', true);
        }

        $this->content = $tpl->parse(false, 'wrap');

        return $this->content;
    }
}

?>