<?php
namespace Verba\Mod\Game\Act\MakeList\Handler\Header;

class CustomByResource extends \Act\MakeList\Handler\Header
{
    public $langKey;
    public $req;

    function run()
    {

        $oh = $this->list->getOh();

        $nativeValue = $this->list->headerText;

        $unitSymbol = $oh->p('unitSymbol');
        $ext = '';
        /**
         * @var $Cur \Verba\Model\Currency
         */

        $Cur = $this->list->getExtendedData('cartCurrency');
        if ($Cur) {
            $curSymb = $Cur->symbol;
        } else {
            $curSymb = '';
        }

        $params = [
            'su' => $oh->p('su'),
            'unitSymbol' => $unitSymbol,
            'scale' => $oh->p('scale'),
            'curSymbol' => $curSymb,
        ];

        if (is_string($this->req) && array_key_exists($this->req, $params)) {
            if (!$params[$this->req]) {
                return $nativeValue;
            }
        }

        if (is_string($this->langKey)) {
            $ext = \Verba\Lang::get('game list headers __thandlers ' . $this->langKey, $params);
        } else {
            if (!empty($unitSymbol)) {
                $su = $oh->p('su');
                $ext = ',' . (!empty($su) ? ' ' . $su : '') . ' ' . $unitSymbol;
            }
        }

        return $nativeValue . $ext;
    }
}
