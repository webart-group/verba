<?php

namespace Verba\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class ACPTabHandler extends Worker
{
    // - Если в true - использовать для генерации состояний добавления и редактирования объекта списка
    // состояния из TabView, например, когда состояния уже заложены в TabView - ACPTab_List. Дефолтные состояния этим хендлером генерироваться и передаваться на клиент не будут.
    // - Если false - будут сгенерированы дефолные состояния - addlistobject, editlistobject. Эти и другие
    // возможные состояния будут переданы на клиент и перезаписаны в нодевью если их там нет.
    public $overwriteTabViewStates = true;
    // Массив состояний в виде  код => конфигурация Нода на AdminCP. Можно задавать через конфиг списка.
    // Эти состояния мигрируют в Ноду и ACPTabHandler будет аппелировать к ним при генерации списком
    public $viewStates = array();

    public $jsScriptFile = 'acp/ACPTabHandler';

    function init()
    {

        if ($this->overwriteTabViewStates == false) {
            return;
        }

        // ставим наполнение дефолтными
        $this->parent->listen('queryExecuted', 'fillListStates', $this);

    }

    function fillListStates()
    {

        \Verba\_mod('ACP')->loadUiClasses();

        if (!array_key_exists('addlistobject', $this->viewStates)) {

            $this->viewStates['addlistobject'] =

                \ACPTabset::createTabsetByName('ListAEForm', array(
                    'tabs' => array(
                        'ListObjectForm' => array(
                            'action' => 'createform',
                            'button' => array(
                                'title' => 'acp list tabs addobject'
                            )
                        )
                    ),
                ));


        }
        if (!array_key_exists('editlistobject', $this->viewStates)) {

            $this->viewStates['editlistobject'] = \ACPTabset::createTabsetByName('ListAEForm', array(
                'tabs' => array(
                    'ListObjectForm' => array(
                        'action' => 'updateform',
                        'button' => array(
                            'title' => 'acp list tabs editobject'
                        )
                    )
                ),
            ));

        }
    }
}
