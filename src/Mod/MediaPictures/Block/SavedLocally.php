<?php

namespace Verba\Mod\MediaPictures\Block;

use Verba\Block\Json;
use Verba\QueryMaker;
use function Verba\_oh;
use function Verba\_mod;
use Verba\Mod\User\Model\User;

class SavedLocally extends Json
{
    function build()
    {
        $req = $this->rq->post();

        $this->media_data['video_id'] = isset($req['video_id']) ? $req['video_id'] : null;
        $this->media_data['title'] = isset($req['title']) ? $req['title'] : null;

        if (!is_array($this->media_data)) {
            throw  new \Verba\Exception\Building('Bad data');
        }

        $U = \Verba\getUser();

        $updateQuery = "
        INSERT INTO " . SYS_DATABASE . ".saved_media (
            user_id,
            video_id,
            title
        )
        VALUES (
            '" . $this->DB()->escape_string($U->getId()) . "',
            '" . $this->DB()->escape_string($this->media_data['video_id']) . "',
            '" . $this->DB()->escape_string($this->media_data['title']) . "'
            )
            ";
        $this->DB()->query($updateQuery);

        $this->content = 'Ok';

        return $this->content;
    }
}
