<?php

namespace Verba\Blueprints;

use Verba\Blueprints\create\Request;


class UpdateService extends AbstractBlueprintService
{
    static function initRequest($blueprint): BlueprintServiceRequestInterface
    {
        return $blueprint->getUpdateRequest();
    }

    public function run(): BlueprintServiceResultInterface
    {
        $this->result = new Result();
        $result = $this->doCreate();
        $migrationPath = $this->createMigration();
        return $this->result;
    }

    protected function doCreate(): Result
    {
        if(!$this->request->validate()){
            throw new \Exception('Otype create request is not set');
        }

        $tableName =
            $this->result->tableName = $this->request->tableName;

        $ot_code =
            $this->result->ot_code = $this->request->ot_code;

        // Create object type base key
        if(!$this->request->key_code){
            throw new \Exception('Key code is required');
        }

        $this->result->key_code = $this->request->key_code;
        $this->result->key_id = $this->DB()->query('SELECT key_id FROM _keys WHERE key_id_code = \''.$this->request->key_code.'\'')
            ->fetchColumn();
        if (!$this->result->key_id) {
            $this->result->key_id = $this->DB()
                ->table('_keys')
                    ->insert([
                        'key_id_code' => $this->request->key_code,
                        'inherit_id' => $this->request->key_base_id
                    ])->getInsertId();
        }

        // Create data vault record
        $stmt = $this->DB()->query('SELECT vlt_id FROM _obj_data_vaults WHERE `object` = \''.$tableName.'\'');
        $this->result->vlt_id = $stmt->fetchColumn();

        if(!$this->result->vlt_id) {
            $this->result->vlt_id = $this->DB()->table("_obj_data_vaults")
                ->insert([
                    'scheme' => 'mysql',
                    'object' => $tableName,
                    'ot_id' => 13,
                    'key_id' => 0
                ])->getInsertId();
        }

        # Create object type
        $stmt = $this->DB()->query('SELECT id FROM _obj_types WHERE ot_code = \''.$this->request->ot_code.'\'');
        $this->result->ot_id = $stmt->fetchColumn();
        if(!$this->result->ot_id) {

            $this->result->ot_id = $this->DB()->table('_obj_types')
                ->insert([
                    'base' => $this->request->ot_base_id,
                    'ot_code' => $ot_code,
                    'role' => $this->request->ot_role,
                    'base_key' => $this->result->key_id,
                    'prim_attr_id' => 0,
                    'title_ru' => $this->request->ot_ru,
                    'title_ua' => $this->request->ot_ua,
                    'title_en' => $this->request->ot_en,
                    'vlt_id' => $this->result->vlt_id
                ])->getInsertId();

        }

        $this->result->attributesFinalCfg = $this->addAttributes();
        $this->handleCustomFieldsConfig();
        $this->handleIndexesConfig();

        $this->DB()->query("UPDATE _obj_types SET prim_attr_id={$this->result->prim_attr_id} WHERE id={$this->result->ot_id}");

        return $this->result;
    }

}
