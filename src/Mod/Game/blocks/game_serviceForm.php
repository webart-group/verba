<?php

class game_serviceForm extends \Verba\Block\Json
{

    public $GameItem;

    function build()
    {

        $this->content = false;
        try {
            $gid = (int)$_REQUEST['gid'];
            $sid = (int)$_REQUEST['sid'];

            if (!$gid || !$sid) {
                throw new Exception('Bad id data');
            }

            $mGame = \Verba\_mod('game');

            $GameItem = $mGame->getGame($gid);
            if (!$GameItem || !$GameItem->serviceExists($sid)) {
                throw new Exception('Bad data');
            }
            $service = $GameItem->getService($sid);

            $oh = \Verba\_oh($service->itemsOtId);
            $baseId = $oh->getBaseId();
            if ($baseId) {
                $_base = \Verba\_oh($baseId);
                $baseOtCfg = ' public/service/' . $_base->getCode();
            } else {
                $baseOtCfg = '';
            }
            $dcfg = array();
            if (isset($service->config['groups']['form_fields'])
                && is_array($service->config['groups']['form_fields'])) {
                \Verba\Hive::loadFormMakerClass();
                \Act\Form::extendCfgByUiConfigurator($oh, $dcfg, $service->config['groups']['form_fields']);
            } else {
                $dcfg = false;
            }

            if (isset($dcfg['fields']['price'])
                && !(isset($dcfg['fields']['price']['extensions']['items'])
                    && is_array($dcfg['fields']['price']['extensions']['items'])
                    && array_key_exists('offerbidPrice', $dcfg['fields']['price']['extensions']['items'])))
            {
                $dcfg['fields']['price']['extensions']['items']['OfferBidPrice'] = array();
            }

//            $dcfg['fields']['currencyId'] = [
//                'formElement' => 'hidden',
//                'extensions' => [
//                    'items' => [
//                        'OfferCurrency',
//                    ]
//                ]
//            ];

            $bp = array(
                'ot_id' => $oh->getID(),
                'action' => 'new',
                'cfg' => 'public public/service/default' . $baseOtCfg . ' public/service/' . $oh->getCode(),
                'dcfg' => $dcfg,
                'block' => $this,
            );

            $form = $oh->initForm($bp);
            $form->addExtendedData(array(
                'gameService' => $service,
                'gameCat' => $GameItem,
            ));
            $this->content = $form->makeForm();

        } catch (Exception $e) {
            $this->setOperationStatus(false);
            $msg = $e->getMessage();
            if (!empty($msg)) {
                $this->content = $e->getMessage();
            }
        }

        return $this->content;
    }

}
