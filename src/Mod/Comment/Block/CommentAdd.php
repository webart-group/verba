<?php

namespace Verba\Mod\Comment\Block;

use Verba\Block\Json;
use Verba\Mod\Comment;
use function Verba\_mod;

class CommentAdd extends Json
{

    function build()
    {
        /**
         * @var Comment $mComment
         */

        $mComment = _mod('comment');

        $ae = $mComment->addEntry();

//        if (!$mComment->sendCreationNonifyEmail($ae->getObjectData())) {
//            $this->log()->error('Unable to set notify email after comment creation');
//        }

        return $this->content = $ae->getResponseByFormat();
    }
}
