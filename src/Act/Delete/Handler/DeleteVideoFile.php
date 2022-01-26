<?php

namespace Verba\Act\Delete\Handler;

use \Verba\Act\Delete\Handler;

class DeleteVideoFile extends Handler
{
    function run()
    {
        $mod_video = \Verba\_mod('video');
        $iu = new \VideoCleaner($this->oh, $this->row['_'.$this->A->getCode().'_config'], basename($this->row[$this->A->getCode()]));
        return $iu->delete();
    }
}
