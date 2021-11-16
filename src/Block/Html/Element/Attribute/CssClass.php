<?php
namespace Verba\Block\Html\Element\Attribute;

trait CssClass
{
    public $cssClass = [];

    function addCssClass($class)
    {
        if (is_string($class)) {
            if (false !== strpos($class, ' ')) {
                $class = explode(' ', $class);
            } elseif (!empty($class)) {
                $class = array($class);
            }
        }

        if (!is_array($class) || !count($class)) {
            return false;
        }

        foreach ($class as $cclass) {
            $this->cssClass[] = $cclass;
        }
        return true;
    }

    function getCssClass()
    {
        return $this->cssClass;
    }

    function implodeCssClass()
    {
        return implode(' ', $this->cssClass);
    }

    function clearCssClass()
    {
        $this->cssClass = array();

        return $this->cssClass;
    }
}