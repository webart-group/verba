<?php

namespace Verba\Blueprints\create\fxs;


use Verba\Blueprints\AbstractAttrFx;

class AttrFx_AgId extends AbstractAttrFx
{

    public function run()
    {
//        $this->tableInterface->table('_ath_links')->insert([
//            'p_ot_id' => 7,
//            'p_iid' => $this->attr_id,
//            'ch_ot_id' => 5,
//            'ch_iid' => ,
//        ])->saveData();
//
//        $stmt = $this->tableInterface->query('SELECT LAST_INSERT_ID() as last_id');
//        return $stmt->fetchColumn();
        return 0;
    }
}
