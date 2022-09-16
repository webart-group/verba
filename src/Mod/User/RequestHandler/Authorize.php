<?php
namespace Verba\Mod\User\RequestHandler;

class Authorize extends \Verba\Block\Json
{
    function build()
    {
        $mUser = \Verba\Mod\User::i();
        try {
            $rq = \json_decode(file_get_contents("php://input"), true);
            if(!isset($rq)){
                $rq = isset($_POST) ? $_POST : null;
            }
            if ($mUser->authNow(null,
                isset($rq['login']) ? $rq['login'] : null,
                isset($rq['password']) ? $rq['password'] : null,
            )) {
                $this->content = $mUser->getHistoryBackUrl();
            } else {
                throw new \Exception(\Verba\Lang::get('user auth common_error'));
            }
        } catch (\Exception $e) {
            $this->setOperationStatus(false);
            $this->content = $e->getMessage();
        }

        return $this->content;
    }
}

