<?php


namespace Verba\Mod\Blog\Blocks;


use Verba\Mod\Seo;

class IndexBlogPosts extends \Verba\Block\Html
{
    public $templates = [
        'wrap' => 'blog/index/wrap.tpl',
        'item' => 'blog/index/item.tpl',
    ];

    function build() {
        $_blog = \Verba\_oh('blog');

        $qm = new \Verba\QueryMaker($_blog, false, true);
        $qm->addWhere(1, 'active');

        $qm->addOrder([
            'priority' => 'd'
        ]);

        $sqlr = $qm->run();

        if(!$sqlr || !$sqlr->getNumRows()){
            return ($this->content = '');
        }

        $tpl = $this->tpl();
        $tpl->define($this->templates);

        $iCfg = \Verba\_mod('image')->getImageConfig('blog');
        while ($row = $sqlr->fetchRow()) {
            if(!empty($row['picture'])){
                $pic = $row['picture'];
                $pic_sign = '';
            }else{
                $pic = '/images/1px.gif';
                $pic_sign = 'no-image';
            }
            $tpl->assign(array(
                'ITEM_TITLE' => $row['title'],
                'ITEM_TEXT' => $row['text_preview'],
                'ITEM_IMAGE_SIGN' => $pic_sign,
                'ITEM_DATE' => (new \DateTime($row['created']))->format('Y-m-d'),
                'ITEM_PICTURE' => $pic,
                'ITEM_URL' => Seo::idToSeoStr($row),
            ));

            $tpl->parse('BLOG_ITEMS', 'item', true);
        }

        $this->content = $tpl->parse(false, 'wrap');

        return $this->content;
    }
}
