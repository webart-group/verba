<?php

namespace Verba\Act\MakeList\Handler\Field;

use \Verba\Act\MakeList\Handler\Field;
use Verba\Mod\Image;

class Url2imgtag extends Field
{

    public $index = null;

    function run()
    {
        $this->value = $this->list->row[$this->attr_code];
        if (!$this->value) {
            return '';
        }

        /**
         * @var $mImage \Verba\Mod\Image
         */
        $mImage = \Verba\_mod('image');

        $iCfgName = $this->list->oh()->p($this->attr_code . '_config');

        if (!$iCfgName) {
            return '';
        }

        $iCfg = $mImage->getImageConfig($iCfgName);
        if (!$iCfg) {
            return '';
        }

        return "<img src=\"".$iCfg->getFullUrl(basename($this->value), $this->index)."\" />";
    }
}
