<?php

namespace Verba\Blueprints\create\fxs;


use Verba\Blueprints\AbstractAttrFx;

class AttrFx_CreatedAt extends AbstractAttrFx
{

    public function run()
    {
        $this->db->table('_ath_links')->insert([
            'p_ot_id' => 7,
            'p_iid' => $this->attr_id,
            'ch_ot_id' => 5,
            'ch_iid' => 4,
        ]);

        $this->db->table('_ath_links')->insert([
            'p_ot_id' => 7,
            'p_iid' => $this->attr_id,
            'ch_ot_id' => 5,
            'ch_iid' => 33,
        ]);
        return null;
    }
}
