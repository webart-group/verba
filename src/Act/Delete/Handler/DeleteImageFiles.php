<?php

namespace Verba\Act\Delete\Handler;

use \Verba\Act\Delete\Handler;

class DeleteImageFiles extends Handler
{
    function run()
    {
        $imageConfName = $this->oh->p($this->A->getCode().'_config');

        if (empty($imageConfName) || empty($this->row[$this->A->getCode()]))
        {
            return null;
        }

        $iu = new \Mod\Image\Cleaner($imageConfName, basename($this->row[$this->A->getCode()]));
        return $iu->delete();
    }
}
