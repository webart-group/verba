<?php

namespace Verba\Act\AddEdit\Handler\Around\Balop;

use \Verba\Act\AddEdit\Handler\Around;

class Active extends Around
{
    function run()
    {
        if($this->action != 'new'){
            $this->log()->error('Unable to continue, somthing is wrong');
            return false;
        }

        $ownerId = $this->ah->getTempValue('owner');

        $Acc = new \Mod\Account\Model\Account($this->ah->getTempValue('accountId'));
        $_user = \Verba\_oh('user');
        $ownerUser = new \Verba\Model\Item($_user->getData($ownerId, 1));
        $cause = $this->ah->getExtendedData('cause');

        try{
            if(!$ownerUser
                || !$ownerUser->getIid()
                || !$ownerUser->active){
                throw new \Exception('bad user');
            }

            // Валидация Причины
            if(!$cause|| !$cause instanceof \Verba\Mod\Balop\Cause){
                throw new \Exception('Сause is invalid');
            }

            if(!$cause->isValid()){
                throw new \Exception('Сause is invalid');
            }
            // Сумма не ноль и равна сумме Причины
            $sum = $this->ah->getTempValue('sumout');
            if(!$sum || $sum != $cause->getSum()){
                throw new \Exception('Balop and cause sum mismach');
            }

            if(!$Acc || $Acc->owner != $ownerUser->getId())
            {
                throw new \Exception('Bad Acc');
            }

            /*
             1. Если операция списания (сумма меньше нуля), проверяем,
             что на балансе есть нужная сумма
             2. Проверка режима - допустима ли данная операция.
            */
            if(!$Acc->isSumApproved($sum, $cause->isBlockedBalance(), $cause->getInternal())){
                throw new \Exception('Sum not accepted by Account');
            }


            if(!$this->ah->getTempValue('crossrate')){
                throw new \Exception('Bad crossrate');
            }

            if(!$this->ah->getTempValue('accCurrencyId')){
                throw new \Exception('Bad acc currency');
            }

            if(!$this->ah->getTempValue('currencyId')){
                throw new \Exception('Bad currency');
            }
        }catch(\Exception $e){

            \Verba\Loger::create('balop-validation')->error(
                'Validation error: '.$e->getMessage()
            );

            $this->log()->error(\Verba\Lang::get('balop errors invalid'));

            return 0;
        }

        return 1;
    }
}
