<?php

namespace Verba\Mod\Game\Act\MakeList\Handler\Field\Order;

use \Act\MakeList\Handler\Field;

class Nom extends Field
{

    protected $code;
    protected $profileUrl;
    protected $profileText;

    function prepare()
    {
        $this->code = $this->list->row['code'];
    }

    function run()
    {

        $this->prepare();

        $s = '<span class="order-buyer-v">
<a href="' . $this->profileUrl . '">'
            . $this->profileText
            . '</a>
</span>';

        $s .= '<br><span class="order-code-v">' . $this->code . '</span>';
        return $s;
    }

}
