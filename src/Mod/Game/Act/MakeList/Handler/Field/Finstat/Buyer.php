<?php

namespace Mod\Game\Act\MakeList\Handler\Field\Finstat;

use \Act\MakeList\Handler\Field;

class Buyer extends Field
{

    function run()
    {

        $r = '';

        switch ($this->list->row['status']) {

            // Ожидает оплаты
            case '20';
                $r = '<button 
 class="btn light btn-green" 
 data-role="purchase-btn-gotopay" 
 data-o-code="' . $this->list->row['code'] . '"
 >'
                    . \Verba\Lang::get('game order buttons go-to-pay') . '</button>';
                break;

            // Оплачен
            case '21';

                $buttonConfirm = new \profile_purchaseBtnConfirm(array(), array(
                    'Order' => $this->list->rowItem,
                ));
                $r = $buttonConfirm->run();
                $this->list->mergeHtmlIncludes($buttonConfirm);

                break;

            // Отменен, закрыт
            case '23';
                break;

            // Выполнен, закрыт
            case '25';
                break;

            // Отменен
            case '26';
                break;
        }

        return $r;
    }

}
