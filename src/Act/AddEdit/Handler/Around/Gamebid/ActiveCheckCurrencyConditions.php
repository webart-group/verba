<?php

namespace Verba\Act\AddEdit\Handler\Around\Gamebid;

use Act\AddEdit\Handler\Around;

/**
 * Class ActiveCheckCurrencyConditions
 *
 * Проверка условий для поля активно лота
 *
 * @package Act\AddEdit\Handler\Around\Gamebid
 */
class ActiveCheckCurrencyConditions extends Around
{
    function run()
    {
        $qa = $this->ah->getActualValue('quantityAvaible');
        $minAmount = $this->ah->getActualValue('amountMin');

        if($this->value == 1 || $this->value === null){
            try{
                if(!$qa){
                    throw  new \Verba\Exception\Building(\Lang::get('offer errors active_check bad_qa'));
                }

                if(!$minAmount){
                    throw  new \Verba\Exception\Building(\Lang::get('offer errors active_check bad_minAmount'));
                }

                if($qa < $minAmount){
                    throw  new \Verba\Exception\Building(\Lang::get('offer errors active_check qa_less_minAmount'));
                }
            }catch ( \Verba\Exception\Building $e) {

                if($this->action == 'edit' || $this->value == 1) {
                    $this->ah->log()->error($e->getMessage());
                }
                $this->value = 0;
                return $this->value;
            }
        }

        return $this->value;
    }
}
