<?php
namespace Verba\User\RequestHandler;

use Verba\Exception\Routing;

class Profile extends \Verba\Block\Json
{
    function build()
    {
        $user = \Verba\User();
        if(!$user->getAuthorized()){
            throw new Routing('Authorization required', 403);
        }

        $data = $user->toArray();
        unset($data['password'], $data['hash']);
        $this->content = $data;

        return $this->content;
    }
}

