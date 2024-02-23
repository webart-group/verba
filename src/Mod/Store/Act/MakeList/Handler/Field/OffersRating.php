<?php
namespace Verba\Mod\Store\Act\MakeList\Handler\Field;

use Verba\Act\MakeList\Handler\Field;

class OffersRating extends Field {

    public $templates = [
        'content' => 'game/buy/list/fields/store_rating.tpl'
    ];

    public $sharedTpl = true;

    function run()
    {
        $rating = (int)$this->list->row['store_rating'];

        $this->tpl->assign([
            'WIDTH_PERCENT' => $rating > 0 && $this->list->row['store_reviews_count'] > 0
                ? $this->list->row['store_reviews_stars'] / $this->list->row['store_reviews_count'] * 20
                : 0,
            'COUNT_TEXT' => \Verba\Lang::get('review count how_mutch',[
                'count' => $this->list->row['store_reviews_count'],
                'word' => \Verba\make_padej_ru($this->list->row['store_reviews_count'],
                    \Verba\Lang::get('review count forms root'),
                    \Verba\Lang::get('review count forms cases')
                )
            ])
        ]);

        return $this->tpl->parse(false, 'content');
    }
}
