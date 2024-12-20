<?php

namespace Verba\Blueprints;


class CreateService extends AbstractBlueprintService
{
    public function run(): BlueprintInterface
    {
        $blueprint = $this->blueprint;

        if(!$blueprint->validate()){
            throw new \Exception('Otype blueprint validation failed');
        }

        $tableName = $blueprint->tableName;

        // Create object type base key
        if(!$blueprint->key_code){
            throw new \Exception('Key code is required');
        }

        $blueprint->key_id = $this->findOrCreateKey($blueprint);

        // Data vault
        $blueprint->vlt_id =$this->findOrCreateVault($blueprint);


        # Object type
        $blueprint->ot_id = $this->findOrCreateOtype($blueprint);

        $blueprint->attributesFinalCfg = $this->addAttributes();
        $this->handleCustomFieldsConfig();
        $this->handleIndexesConfig();

        #update primary attribute id
        $this->DB()->query("UPDATE _obj_types SET prim_attr_id={$blueprint->prim_attr_id} WHERE id={$blueprint->ot_id}");

        $this->createMigration();

        return $blueprint;
    }

}
