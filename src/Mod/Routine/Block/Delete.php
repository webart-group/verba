<?php

namespace Mod\Routine\Block;

class Delete extends \Verba\Block\Json
{

    use Common;

    public $contentType = 'json';

    public $dh;

    function build()
    {
        $this->content = false;

        $oh = \Verba\_oh($this->request->ot_id);

        $this->dh = $oh->initDelete();
        $this->dh->delete_objects($this->request->iid);
        $this->content = $this->dh->result;
        return $this->content;
    }

}
