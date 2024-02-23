<?php

namespace Verba\Mod\Banner\Act\MakeList\Handler\Field;

use Verba\Mod\Image;

class Pic extends \Verba\Act\MakeList\Handler\Field
{

    function run()
    {
        if (empty($this->list->row[$this->attr_code])) {
            return '';
        }
        $oh = $this->list->oh();
        $cfgName = $oh->p($this->attr_code.'_config');
        if(!$cfgName) {
            $cfgAttrCode = '_'.$this->attr_code.'_config';
            if(!$oh->isA($cfgAttrCode) || empty($cfgName = $this->list->row[$cfgAttrCode])) {
                return '';
            }
        }

        $imgCfg = Image::getImageConfig($cfgName);
        if (!($src = $imgCfg->getFullUrl(basename($this->list->row[$this->attr_code]), 'acp-list'))) {
            return '';
        }
        return '<div style="max-width:300px;max-height:200px; overflow: hidden;"><img src="' . $src . '" class="acp-banner-preview-thumb"/></div>';
    }
}
