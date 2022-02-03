<?php
namespace Mod;

use phpcent\Client as CentClient;

class Centrifugo extends \Verba\Mod
{
    /**
     * @var \phpcent\Client
     */
    protected $Client;
    use \Verba\ModInstance;

    function Client()
    {
        if ($this->Client === null) {
            $this->initClient();
        }

        return $this->Client;
    }

    function initClient()
    {
        $this->Client = new CentClient($this->_c['address_backend']);
        $this->Client->setApiKey($this->_c['api_key']);
        $this->Client->setSecret($this->_c['secret']);
        $this->Client->setSafety($this->_c['safety']);

        return $this->Client;
    }

    function genClientToken($user, $exp = 0)
    {
        return $this->Client()->generateConnectionToken((string)$user, $exp);
    }

    function verifyClientToken($token, $user, $exp = 0)
    {
        $genToken = $this->genClientToken($user, $exp);

        return is_string($token) && $token
            && strcmp($token, $genToken) === 0;
    }

    function generateCentConnectCfg($U = null)
    {

        if ($U === null) {
            $U = User();
        }
        if (!$U || !$U instanceof \Verba\User\Model\User) {
            return false;
        }

        $userId = $U->getId();
        $ts = time();
        $jsCfg = array();

        $jsCfg['user'] = (string)$userId;
        $jsCfg['timestamp'] = (string)$ts;
        $jsCfg['token'] = $this->genClientToken($userId);
        $jsCfg['endpoint'] = $this->_c['address_frontend'];

        $jsCfg['clientCfg'] = array(
            "subscribeEndpoint" => $this->_c['subscribeEndpoint'],
            "refreshEndpoint" => $this->_c['refreshEndpoint'],
            'minRetry' => 1000,
            'maxRetry' => 30000,
        );

        if(!SYS_IS_PRODUCTION){
            $jsCfg['clientCfg']['debug'] = true;
        }

        return $jsCfg;
    }
}

Centrifugo::$_config_default = array(
    'secret' => '7e082f74-5ke1-9236-4a0d-2b2e87f2d2f5',
    'api_key' => 'loot.pro.v2',
    'safety' => false,
    'address_backend' => 'http://127.0.0.1:8288/api',
    'address_frontend' => 'wss://loot.pro:8891/centrifugo/connection/websocket',
    'subscribeEndpoint' => '/cntrgo/auth',
    'refreshEndpoint' => '/cntrgo/refresh'
);

if (!SYS_IS_PRODUCTION && $_SERVER['HTTP_HOST'] == 'loot.kmv')
{
    Centrifugo::$_config_default['address_frontend'] = 'ws://' . SYS_THIS_HOST . ':8889/connection/websocket';
    Centrifugo::$_config_default['address_backend'] = 'http://localhost:8889/api';
}





