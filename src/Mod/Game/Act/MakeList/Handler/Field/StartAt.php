<?php

namespace Verba\Mod\Game\Act\MakeList\Handler\Field;

use \Act\MakeList\Handler\Field;

class StartAt extends Field
{

    public $templates = array(
        'field-startAt' => '/game/list/fields/startAt.tpl'
    );

    function run()
    {
        $tpl = $this->list->tpl();

        if (!$tpl->isDefined('field-startAt')) {
            $tpl->define($this->templates);
        }
        $strDtEv = strtotime($this->list->row['startAt']);

        if (!$strDtEv || date('Y', $strDtEv) < 2000) {
            return \Verba\Lang::get('game form fields gameStartAt values 0');
        }

        $dtEvTime = new \DateTime(date('Y-m-d H:i', $strDtEv));
        $dtNowTime = new \DateTime(date('Y-m-d H:i'));

        $dtEvDate = new \DateTime(date('Y-m-d', $strDtEv));
        $dtNowDate = new \DateTime(date('Y-m-d'));

        $firstPart = '';
        $secPart = strftime('%H:%M', $strDtEv);

        $interval = $dtEvTime->diff($dtNowTime);
        $intervalDate = $dtEvDate->diff($dtNowDate);

        // в прошлом
        if ($dtEvTime < $dtNowTime) {
            $class_sign = 'in-past';

            // сегодня
        } elseif ($dtEvDate == $dtNowDate) {

            // меньше часа
            if ($interval->h < 1) {
                $class_sign = 'in-now';
                $firstPart = \Verba\Lang::get('date now');

                // больше часа но меньше 4 часов
            } elseif ($interval->h < 4) {
                $class_sign = 'in-few-hours';
                $mkpadejMethod = '\Verba\make_padej_' . SYS_LOCALE;
                if (!function_exists($mkpadejMethod)) {
                    $mkpadejMethod = '\Verba\make_padej_ru';
                }

                if ($interval->i <= 25) {
                    $roundedHour = $interval->h;
                } elseif ($interval->i > 25 && $interval->i <= 40) {
                    $roundedHour = $interval->h . ',5';
                } else {
                    $roundedHour = $interval->h + 1;
                }

                $firstPart = \Verba\Lang::get('date overHours', array(
                    'hours' => $roundedHour,
                    'word' => $mkpadejMethod($interval->h, \Verba\Lang::get('date hours root'), \Verba\Lang::get('date hours cases'))
                ));

                // более 4 часов
            } else {
                $class_sign = 'in-today';
                $firstPart = \Verba\Lang::get('date today');
            }
            // в будущем
        } else {
            $class_sign = 'in-fut';
            if ($intervalDate->d == 1) {
                $class_sign = 'in-tomorrow';
                $firstPart = \Verba\Lang::get('date tomorrow');
            } elseif ($intervalDate->d == 2
                && (SYS_LOCALE == 'ru' || SYS_LOCALE == 'ua')) {
                $class_sign = 'in-after-tomorrow';
                $firstPart = \Verba\Lang::get('date dayAfterTomorrow');
            }
        }

        if (!$firstPart) {
            $firstPart = utf8fix(strftime('%d %b %Y', $strDtEv));
        }

        $tpl->assign(array(
            'START_AT_CLASS_SIGN' => $class_sign,
            'FIRST_PART' => $firstPart,
            'SEC_PART' => $secPart,
            'E_TITLE' => utf8fix(strftime('%Y-%m-%d, %A, %H:%M', $strDtEv))
        ));

        //Lang::get('review date yesterday');
        return $tpl->parse(false, 'field-startAt');
    }
}
