<?php

namespace Verba\Mod\User\Authorization;

use Verba\Base;
use Verba\Mod\User;
use Verba\Model\Item;
use Verba\QueryMaker;
use function Verba\_mod;
use function Verba\_oh;

class BearerTokenAuthenticator extends Base implements AuthenticatorInterface
{
    protected ?string $token;
    protected ?Item $userAuthToken = null;
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function authorize()
    {
        return $this->findIdentity();
    }

    function findIdentity()
    {
        if(!preg_match('/^Bearer\s+([a-zA-Z0-9_\.]+)$/', $this->token, $_)) {
            return null;
        }
        $token = $_[1];

        $qm = new QueryMaker('user_auth_token', false, true);
        $qm->addWhere($token, 'token');
        $qm->addWhere('expires_at > NOW()');
        $qm->addWhere(self::STATUS_ACTIVE, 'status');
        $q = $qm->getQuery();
        $sqlr = $qm->run();
        if(!$sqlr->getNumRows()) {
            return null;
        }

        $tokenRow = $sqlr->fetchRow();
        $this->userAuthToken = new Item($tokenRow);
        $ae = _oh('user_auth_token')->initAddEdit(['action' => 'edit', 'iid' => $tokenRow['id']]);
        $ae->setGettedData([
            'last_activity_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'count' => $tokenRow['count'] + 1
        ]);

        $iid = $ae->addedit_object();

        return new User\Model\User($tokenRow['user_id']);
    }

    static function generateAccessToken(User\Model\User $user)
    {
        $token = bin2hex(random_bytes(32));

        $user_auth_token = _oh('user_auth_token');
        $ae = $user_auth_token->initAddEdit('create');
        $ae->setGettedData([
            'user_id' => $user->getId(),
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 365),
            'session_id' => session_id(),
            'status' => self::STATUS_ACTIVE
        ]);

        $iid = $ae->addedit_object();

        if (!$iid) {
            throw new \Exception('Access token generation error');
        }

        return $token;
    }

    function getUserAuthToken()
    {
        return $this->userAuthToken;
    }

}
