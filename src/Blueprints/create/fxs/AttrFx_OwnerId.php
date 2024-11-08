<?php

namespace Verba\Blueprints\create\fxs;


use Verba\Blueprints\AbstractAttrFx;

class AttrFx_OwnerId extends AbstractAttrFx
{

    public function run()
    {
        $attrForeignIdFx = new AttrFx_ForeignId($this->db, $this->attr_id, $this->ot_id);
        $attrForeignIdFx->run('user', 'display_name');

        //Ah
        $this->db->table('_ath_links')->insert([
            'p_ot_id' => 7,
            'p_iid' => $this->attr_id,
            'ch_ot_id' => 5,
            'ch_iid' => 15,
        ]);


        //rule
        $this->db->table('_obj_links_rules')->insert([
            'p_ot_id' => 17,
            'ch_ot_id' => $this->ot_id,
            'rule' => 'fid',
            'statement' => 'user_id',
        ]);
    }
}
