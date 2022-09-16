<?php

namespace Verba\Mod\Game\Act\MakeList\Handler\Field\Finstat;

use \Act\MakeList\Handler\Field;

class Seller extends Field
{

    function run()
    {

        $r = '';

        switch ($this->list->row['status']) {

            // Ожидает оплаты
            case '20';
                break;
            // Оплачен
            case '21';
                $buttonCashback = new \profile_sellBtnCashback(array(), array(
                    'Order' => $this->list->rowItem,
                ));
                $r = $buttonCashback->run();
                $this->list->mergeHtmlIncludes($buttonCashback);
                break;

            // Отменен, закрыт
            case '23';
                break;

            // Выполнен, закрыт
            case '25';

                if ($this->list->row['sumHoldTill']) {
                    $r = '<div class="o-finstat-v">' . \Verba\Lang::get('game order fin_statuses holded-till') . '</div>';
                    if ($holdtime = strtotime($this->list->row['sumHoldTill'])) {
                        $r .= \Verba\Mod\Game::parseToGameTimeHtml($holdtime);
                    }
                }

                break;
            // Отменен
            case '26';
                break;
        }


        return $r;//'<div class="o-status-v">'.$this->list->row['status_pdv'].'</div>
//<div class="o-datetime-v">'.  date('H:i d/m/y', strtotime($this->list->row['created'])).'</div>';
    }

}
