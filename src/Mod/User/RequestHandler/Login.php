<?php

namespace Verba\Mod\User\RequestHandler;

use Verba\Mod\User\Authorization\BearerTokenAuthenticator;

class Login extends \Verba\Block\Json
{

    function build()
    {
        $mUser = \Verba\Mod\User::i();
        try {
            $post = $this->rq->post();
            $U = $mUser->authByLoginAndPass(
                $post['login'] ?? null,
                $post['password'] ?? null
            );
            if (!$U) {
                throw new \Exception(\Verba\Lang::get('user auth common_error'));
            }

            $this->content = [
                'token' => BearerTokenAuthenticator::generateAccessToken($U)
            ];

        } catch (\Exception $e) {
            $this->setOperationStatus(false);
            $this->content = $e->getMessage();
        }

        return $this->content;
    }
}
