<?php
namespace Verba\ObjectType\Attribute;


use Verba\Mod\Image;
use Verba\Mod\Image\Config;

class PictureWithConfig extends \Verba\ObjectType\Attribute
{
    /**
     * @var Config
     */
    private $imageConfig = null;

    public function getImageConfig()
    {
        if(null !== $this->imageConfig){
            return $this->imageConfig;
        }

        $cfgName = $this->oh->p($this->attr_code . '_config');
        if(!$cfgName){

        }

        $this->imageConfig = Image::getImageConfig($cfgName);

        return $this->imageConfig;
    }
}
