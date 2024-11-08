<?php

namespace Verba\Blueprints\create\fxs;


use Verba\Blueprints\AbstractAttrFx;

class AttrFx_ForeignId extends AbstractAttrFx
{
    public function run($target_ot_id, $target_attr_id)
    {
//fid ae
        $this->db->table('_ath_links')->insert([
            'p_ot_id' => 7,
            'p_iid' => $this->attr_id,
            'ch_ot_id' => 5,
            'ch_iid' => 29,
        ]);

        //ah present
        $set_id = $this->db->table('_ath_links')->insert([
            'p_ot_id' => 7,
            'p_iid' => $this->attr_id,
            'ch_ot_id' => 5,
            'ch_iid' => 30,
        ])->getInsertId();

        if(!is_numeric($target_ot_id) && is_string($target_ot_id)){
            $target_ot_id = $this->db->query("SELECT id FROM _obj_types WHERE ot_code='{$target_ot_id}'")
                ->fetchColumn();

            $target_attr_id = $this->db->query("SELECT attr_id FROM _obj_attributes WHERE ot_iid={$target_ot_id} AND attr_code='{$target_attr_id}'")
                ->fetchColumn();
        }

        if(!$target_ot_id || !$target_attr_id){
            throw new \Exception('Target object type or attribute not found');
        }

        $this->db->table('_athp_foreignid')->insert([
            'set_id' => $set_id,
            '_ot_id' => 0,
            'ot_id' => $target_ot_id,
            'field2display' => $target_attr_id,
            'priority' => 0,
        ]);

        return $set_id;
    }
}
