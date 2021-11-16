<?php

namespace Verba\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;
use Mod\Image;

class Url2imgtag extends Field
{

    function run()
    {
        return ($this->value = $this->list->row[$this->attr_code])
            ? Image::pictureToImgTag($this->value, $this->attr_code, $this->list->oh())
            : '';

    }

}
