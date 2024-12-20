<?php

namespace Verba\Blueprints;

use Verba\Base;
use Verba\Blueprints\create\Request;
use Verba\Lang;


abstract class AbstractBlueprintService extends Base implements BlueprintServiceInterface
{
    protected AbstractBlueprint|null $blueprint = null;

    protected $migration = [];

    const MIGRATION_PADDING = "\t\t";

    public function __construct(AbstractBlueprint $blueprint)
    {
        $this->blueprint = $blueprint;
    }

    protected function addAttributes(): array
    {
        $tblAttributes = $this->DB()->table('_obj_attributes');
        $attrsFinalConfig = [];
        /**
         * @var \Verba\Mod\Otype $modOtype
         */
        $modOtype = \Verba\_mod('otype');

        foreach ($this->blueprint->attributes as $row ) {
            $template = null;
            if(isset($row['_']['template'])){
                $template = $row['_']['template'];
            }

            if($template) {
                $template = new $template();
                $row = $template->apply($row);
            }

            if(!isset($row['ot_iid'])){
                $row['ot_iid'] = $this->blueprint->ot_id;
            }

            $fxs = $row['_']['fxs'] ?? null;

            $migration = $row['_']['migration'] ?? null;

            $index = $row['_']['index'] ?? null;

            unset($row['_']);

            $attr_id = $tblAttributes->insert($row)->getInsertId();

            $attrsFinalConfig[] = array_merge(
                [
                    'attr_id' => $attr_id,
                ],
                $row
            );

            if($row['attr_code'] == 'id'){
                $this->blueprint->prim_attr_id = $attr_id;
            }

            if(is_array($fxs) && count($fxs)){
                foreach ($fxs as $fx) {
                    if(is_array($fx)){
                        $args = $fx[1] ?? [];
                        $className = $fx[0];
                    }
                    $attrFx = new $className($this->DB(), $attr_id, $this->blueprint->ot_id);
                    call_user_func_array([$attrFx, 'run'], $args);
                }
            }

            if ($row['lcd'] == 1) {
                $langs = Lang::getUsedLC();
            } else {
                $langs = [''];
            }

            foreach($langs as $lang) {
                if($migration === false) {
                    continue;
                }

                $migrationField = new MigrationField();
                $migrationField->field_name = $row['attr_code'] . (!$lang ? '' : '_'.$lang);
                $migrationField->field_type = $modOtype->getColumnTypeForAttr(
                    $row['form_element'] ?? 'text', $row['data_type']
                )['dbtype'];

                if(!empty($migration)) {
                    $migrationField->applyConfigDirect($migration);
                }
                $migrationField->attr_config = $row;

                $this->addToMigration($migrationField);

                if($index) {
                    $migrationIndex = new MigrationIndex();
                    $migrationIndex->name = 'idx_' . $migrationField->field_name;
                    $migrationIndex->columns = [$migrationField->field_name];
                    if(is_array($index)){
                        $migrationIndex->applyConfigDirect($index);
                    }
                    $this->addToMigration($migrationIndex);
                }
            }
        }

        return $attrsFinalConfig;
    }

    protected function handleCustomFieldsConfig()
    {
        foreach ($this->blueprint->customFields as $row ) {
            $migrationField = new MigrationField();
            $migrationField->applyConfigDirect($row);
            $this->addToMigration($migrationField);
        }
    }

    protected function handleIndexesConfig()
    {
        foreach ($this->blueprint->indexes as $row ) {
            $migrationField = new MigrationIndex();
            $migrationField->applyConfigDirect($row);
            $this->addToMigration($migrationField);
        }
    }

    protected function createMigration(): string
    {
        $tpl = file_get_contents(__DIR__.'/create/create_table_migration.php.dist');

        $migrationCode = $this->generateMigrationCode($this->blueprint);

        $className = 'BlueprintCreate'.ucfirst($this->result->ot_code).'Table';

        $tpl = str_replace(
            [
                '$className',
                '$migrationCode'
            ],
            [
                $className,
                $migrationCode
            ], $tpl);

        $outputPath = $_ENV['PWD'].'/App/db/migrations/'
            . date('YmdHis').'_blueprint_create_'.$this->result->ot_code.'_table.php';
        $savedir = dirname($outputPath);
        if(!is_writable($savedir)){
            throw new \Exception('Directory '.$savedir.' is not writable. Unable to create blueprint migration');
        }

        $sr = file_put_contents($outputPath, $tpl);
        chown($outputPath, '1000');
        chmod($outputPath, 0777);
        if(!$sr){
            throw new \Exception('Unable to save migration file. Last error: '.error_get_last()['message']);
        }
        return $outputPath;
    }

    public function generateMigrationCode(BlueprintInterface $blueprint)
    {
        $r = '';

        $r .= '$this->table(\''. $blueprint->tableName .'\')' . PHP_EOL;

        /**
         * @var MigrationField|MigrationIndex $migrationEntity
         */

        foreach ($this->migration as $migrationEntity) {
            $r .= $migrationEntity->toMigrationCode();
        }

        $r .= '->create();' . PHP_EOL;

        return $r;
    }

    public function addToMigration(MigrationField|MigrationIndex $migrationField)
    {
        $this->migration[] = $migrationField;
    }

    protected function findOrCreateKey($blueprint)
    {
        $stmt = $this->DB()->query('SELECT key_id FROM _keys WHERE key_id_code = \''.$blueprint->key_code.'\'');
        $key_id = $stmt->fetchColumn();
        if(!$key_id) {
            $key_id = $this->DB()->table('_keys')
                ->insert([
                    'key_id_code' => $blueprint->key_code,
                    'inherit_id' => $blueprint->key_base_id
                ])->getInsertId();
        }
        return $key_id;
    }

    protected function findOrCreateVault($blueprint)
    {
        $stmt = $this->DB()->query('SELECT vlt_id FROM _obj_data_vaults WHERE `object` = \''.$blueprint->tableName.'\'');
        $vlt_id = $stmt->fetchColumn();

        if(!$vlt_id) {
            $vlt_id = $this->DB()->table("_obj_data_vaults")
                ->insert([
                    'scheme' => 'mysql',
                    'object' => $blueprint->tableName,
                    'ot_id' => 13,
                    'key_id' => 0
                ])->getInsertId();
        }

        return $vlt_id;
    }

    protected function findOrCreateOtype($blueprint)
    {
        $stmt = $this->DB()->query('SELECT id FROM _obj_types WHERE ot_code = \''.$blueprint->ot_code.'\'');
        $ot_id = $stmt->fetchColumn();
        if(!$ot_id) {

            $ot_id = $this->DB()->table('_obj_types')
                ->insert([
                    'base' => $blueprint->ot_base_id,
                    'ot_code' => $blueprint->ot_code,
                    'role' => $blueprint->ot_role,
                    'base_key' => $blueprint->key_id,
                    'prim_attr_id' => 0,
                    'title_ru' => $blueprint->ot_ru,
                    'title_ua' => $blueprint->ot_ua,
                    'title_en' => $blueprint->ot_en,
                    'vlt_id' => $blueprint->vlt_id
                ])->getInsertId();

        }

        return $ot_id;
    }
}
