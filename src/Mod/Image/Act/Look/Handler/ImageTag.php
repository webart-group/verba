<?php

namespace Mod\Image\Act\Look\Handler;

use Act\Look\Handler;

class ImageTag extends Handler
{

    protected $iCfg;
    /**
     * @var $copy string название кода картинок.
     */
    public $copy = '';
    public $attr_code;

    function run()
    {

        if (empty($this->value)) {
            return '';
        }

        if ($this->iCfg === null) {
            $this->iCfg = \Mod\Image::getImageConfig($this->oh()->p($this->attr_code . '_config'));
        }

        return '<img src="' . $this->iCfg->getFileUrl(basename($this->value), $this->copy) . '"/>';
    }

}
