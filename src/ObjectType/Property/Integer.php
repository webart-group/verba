<?php
namespace Verba\ObjectType\Property;


class Integer extends  \Verba\ObjectType\Property
{
    protected $_confPropsMetaExtend = array(
        'value' => array('dataType' => 'integer'),
    );
}
