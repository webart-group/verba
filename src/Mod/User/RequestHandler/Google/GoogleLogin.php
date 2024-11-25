<?php

namespace Verba\Mod\User\RequestHandler\Google;

use Verba\Block\Json;
use Verba\Mod\User;
use Verba\Mod\User\Authorization\BearerTokenAuthenticator;
use Verba\QueryMaker;
use function Verba\_mod;
use function Verba\_oh;

class GoogleLogin extends Json
{
    public function build()
    {
        try {
            $accessToken = $this->rq->post('accessToken');

            $googleService = new GoogleService();
            $userData = $googleService->getUserInfo($accessToken);
            if (!$userData) {
                throw new \Exception('Incorrect user data by GoogleToken: ' . $accessToken);
            }
            /**
             * @var $mUser User;
             */

            $mUser = _mod('user');

            $_user = _oh('user');

            $loginField = $mUser->gC('login_field');

            $qm = new QueryMaker($_user);
            $qm->addWhere($userData->email, 'email');
            $qm->addWhere(1, 'active');
            $qm->addLimit(1);

            $sqlr = $qm->run();
            if ($sqlr->getNumRows()) {
                $row = $sqlr->fetchRow();
                $U = new \Verba\Mod\User\Model\User($row);
                if (!$U) {
                    throw new \Exception(\Verba\Lang::get('user auth common_error'));
                }

                return $this->content = [
                    'token' => BearerTokenAuthenticator::generateAccessToken($U)
                ];

            }

            return $mUser->createUser([$loginField => $userData->email]);
        } catch (\Exception $e) {
            $this->setOperationStatus(false);
            $this->content = $e->getMessage();
        }
    }
}
