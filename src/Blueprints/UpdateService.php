<?php

namespace Verba\Blueprints;

class UpdateService extends AbstractBlueprintService
{
    public function run(): BlueprintInterface
    {
        $blueprint = $this->blueprint;

        if(!$blueprint->validate()) {
            throw new \Exception('Otype blueprint validation failed');
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
