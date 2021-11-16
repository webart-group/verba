<?php

namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class UnactivateIfStartAtOverdue extends Around
{
    function run()
    {

        $startAt = $this->ah->getActualValue('startAt');

        if($this->value === 0 || $startAt == '0000-00-00 00:00:00'){
            return $this->value;
        }

        $err = false;
        $startAtTs = strtotime($startAt);
        if(!$startAtTs || $startAtTs < time() ){
            $err = \Verba\Lang::get('game errors startAt_must_be_greater');
            $this->value = 0;
        }

        // если произошла принудительная деактивация
        if($this->action == 'edit' && is_string($err)){
            $existsValue = $this->getExistsValue($this->A->getCode());
            // и был запрос на активирование записи: предыдущее значение - 0, новое значение - 1
            if($existsValue == 0){
                // записываем ошибку в процесс ae
                $this->ah->log()->error($err);
            }
        }

        return $this->value;
    }
}
