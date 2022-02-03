<?php

class profile_accountsCUNow extends \Verba\Mod\Routine\Block\CUNow
{

    public $responseAs = 'json-item-updated-keys';
    public $responseAsKeys = array(
        'mode',
    );

    public $avaible_attrs = array(
        'mode' => true,
    );

    function routedActions()
    {
        return [
            'update' => true,
        ];
    }

    function prepare()
    {
        parent::prepare();

        if (is_array($this->data)
            && is_array($this->avaible_attrs)
            && count($this->avaible_attrs)) {
            $this->data = array_intersect_key($this->data, $this->avaible_attrs);

            if (array_key_exists('mode', $this->data)) {
                $val = (string)$this->data['mode'];
                if ($val === '1') {
                    $this->data['mode'] = 1158;
                } elseif ($val === '0') {
                    $this->data['mode'] = 1159;
                } else {
                    $this->data['mode'] = null;
                }
            }


        }
    }

}