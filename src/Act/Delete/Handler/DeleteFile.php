<?php

namespace Verba\Act\Delete\Handler;

use Act\Delete\Handler;

class DeleteFile extends Handler
{
    function run()
    {
        \Verba\_mod('file');
        $fc = new \FileCleaner($this->oh, $this->row['_'.$this->A->getCode().'_config'], basename($this->row[$this->A->getCode()]));
        return $fc->delete();
    }
}
