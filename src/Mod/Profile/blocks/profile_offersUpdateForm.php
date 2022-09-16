<?php

class profile_offersUpdateForm extends \Verba\Mod\Routine\Block\Form
{
    /**
     * Выполняет init() - устанавливает валид-от в значение пришедшего ОТ
     * если тот имеет роль public-product
     */
    use profile_offersActionRoutine;

    /**
     * Ствновится доступным свойство owd - объект OffersWorkingData
     */
    use game_offers;

    public $contentType = 'json';

    function prepare()
    {
        $_prod = \Verba\_oh($this->owd->service->itemsOtId);

        $this->cfg = 'public public/service/default public/profile/storebid';

        $this->dcfg = array(
            'title' => array(
                'value' => \Verba\Lang::get('profile storebid form edit', array(
                    'id' => $this->rq->iid,
                    'serviceName' => $this->owd->service->title,
                    'gameName' => $this->owd->game->title,
                )),
            ),
            'extendedData' => array(
                'gameService' => $this->owd->service
            )
        );

        if (isset($this->owd->service->config['groups']['update_form_fields'])
            && is_array($this->owd->service->config['groups']['update_form_fields'])) {
            \Verba\Hive::loadFormMakerClass();
            \Act\Form::extendCfgByUiConfigurator($_prod, $this->dcfg, $this->owd->service->config['groups']['update_form_fields']);
        } else {
            $this->dcfg = false;
        }

        if (isset($this->dcfg['fields']['price'])
            && !isset($this->dcfg['fields']['price']['extensions']['items']['offerBidPrice'])) {
            $this->dcfg['fields']['price']['extensions']['items']['offerBidPrice'] = array();
        }
        if (isset($this->dcfg['fields']['picture'])) {
            $this->dcfg['fields']['picture'] = array_replace_recursive(
                $this->dcfg['fields']['picture']
                , array(
                    'restrictions' => false,
                    'preview' => array(
                        'idxs' => '!acp orig primary'
                    ),
                )
            );
        }
    }
}
