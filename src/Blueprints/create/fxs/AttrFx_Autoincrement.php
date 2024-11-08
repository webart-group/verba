<?php

namespace Verba\Blueprints\create\fxs;


use Verba\Blueprints\AbstractAttrFx;

class AttrFx_Autoincrement extends AbstractAttrFx
{
    public function run()
    {
        $vars = func_get_args();

        //autoincrement
        $set_id = $this->db->table('_ath_links')->insert([
            'p_ot_id' => 7,
            'p_iid' => $this->attr_id,
            'ch_ot_id' => 5,
            'ch_iid' => 3,
        ])->getInsertId();

        return $set_id;
    }
}
