<?php

namespace Mod\Offer\Act\MakeList\Handler\Field;

use \Act\MakeList\Handler\Field;

class Image extends Field
{

    /**
     * @var \Mod\Image\Act\Look\Handler\ImageTag
     */
    protected $avh;

    function init()
    {
        $this->avh = new \Mod\Image\Act\Look\Handler\ImageTag($this->ah->oh(),
            $this->A,
            [
                'attr_code' => $this->attr_code,
                'copy' => 'list'
            ],
            $this->ah
        );
    }

    function run()
    {
        $this->avh->value = $this->list->row[$this->attr_code];
        return $this->avh->run();
    }

}
