<?php
namespace Verba\Mod\Langu\Block;

use Verba\Block\Json;
use Verba\Lang;

class Translations extends Json
{
    function build()
    {
        $locale = $this->rq->node;
        if(!$locale || !Lang::isLCValid($locale)){
            $locale = Lang::$lang;
        }
        $result = Lang::generateTranslationsContent($locale);
        return $this->content = $result->content;
    }
}
