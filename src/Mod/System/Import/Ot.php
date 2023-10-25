<?php

namespace Verba\Mod\System\Import;

use Verba\Block\Json;
use Verba\Hive;
use Verba\Model;
use Verba\QueryMaker;
use function Verba\_oh;

class Ot extends Json
{
    public function build()
    {
        GLOBAL $S;

        $keepOriginalOtId = (bool)$this->request->getParams('keep_original_id');

        $source = $this->request->getParams('source');
        $destination = $this->request->getParams('destination');
        $log = [];
        /**
         * @var $S Hive
         */
        $dbFrom = $S->DbConnect($source);

        $dbTo = $S->DB();
        $dbTo->query("USE ".$destination['database']);
        $dbTo->query("SET autocommit=0");
        $dbTo->query("START TRANSACTION");

        $otToCopy = $this->request->getParam('ott');

        $sqlr = $dbFrom->query("SELECT * FROM `_obj_types` WHERE ot_code = '".$otToCopy."'");

        $srcOtypeData = $sqlr->fetchRow();

        $srcOtId = $srcOtypeData['id'];

        $vltData = $dbFrom->query("SELECT * FROM `_obj_data_vaults` WHERE vlt_id = '".$srcOtypeData['vlt_id']."'")->fetchRow();

        $keyData = $dbFrom->query("SELECT * FROM `_keys` WHERE key_id = '".$srcOtypeData['base_key']."'")->fetchRow();

        try{
            // KEY
            $existsKeySqlr = $dbTo->query("SELECT * FROM `_keys` WHERE `key_id_code` = '".$keyData['key_id_code']."'");
            $existsKey = $existsKeySqlr->getNumRows() ? $existsKeySqlr->fetchRow() : null;

            if(!$existsKey){
                if(!empty($keyData['inherit_id'])){
                    throw new \Exception('Key with not zerro inherit_id  not supports');
                }

                $sqlr = $dbTo->query("INSERT INTO `_keys` (`key_id_code`, `inherit_id`, `description`) 
                    VALUES ('".$keyData['key_id_code']."', '".$keyData['inherit_id']."', '".$keyData['description']."')"
                );

                $key_id = $sqlr->getInsertId();
            } else {
                $key_id = $existsKey['key_id'];
            }
            // VLT
            $_vlt = _oh('data_vault');
            $qm = new QueryMaker($_vlt, false, true);
            $qm->addWhere($vltData['object'], 'object');
            $q = $qm->getQuery();
            $sqlr = $qm->run();
            if(!$sqlr->getNumRows()){
                $vltAe = $_vlt->initAddEdit();
                unset(
                    $vltData['vlt_id'],
                );

                $vltAe->setGettedData($vltData);
                $vltAe->addedit_object();
                $vlt_id = $vltAe->getIID();
            }else{
                $existsVlt = $sqlr->fetchRow();
                $vlt_id = $existsVlt['vlt_id'];
            }

            //OTYPE
            /**
             * @var Model $otype
             */
            $otype = _oh('otype');
            $_attr = _oh('ot_attribute');
            $_ah = _oh('ah');

            $qm = new QueryMaker($otype, false, true);
            $qm->addWhere($srcOtypeData['ot_code'], 'ot_code');

            $sqlr = $qm->run();
            if($sqlr->getNumRows() > 0){
                $ot_id = $sqlr->fetchRow()['id'];
            }

            $otAe = $otype->initAddEdit();

            if(false === $keepOriginalOtId){
                unset(
                    $srcOtypeData['id'],
                );
            }

            if($srcOtypeData['handler'] == 'baseObject' || $srcOtypeData['handler'] == '\Model'){
                $srcOtypeData['handler'] = '';
            }

            $srcOtypeData['base_key'] = $key_id;
            $srcOtypeData['vlt_id'] = $vlt_id;

            if(!isset($ot_id)){
                $log[] = "OT is missed";
                $otAe->setGettedData($srcOtypeData);
                $otAe->addedit_object();
                $ot_id = $otAe->getIID();
            }

            $prim_attr_id = 0;
            //ATTRIBUTES
            $srcAttributesQ = $dbFrom->query("SELECT * FROM `_obj_attributes` WHERE ot_iid = '".$srcOtId."'");
            while ($srcAttr = $srcAttributesQ->fetchRow()) {

                $attrAe = $_attr->initAddEdit();

                $srcAttrId = $srcAttr['attr_id'];

                unset($srcAttr['attr_id']);
                $srcAttr['ot_iid'] = $ot_id;

                if($srcAttr['form_element'] == 'picupload'){
                    $srcAttr['form_element'] = 'picture';
                }

                $srcAttr['title']['en'] = $srcAttr['title_en'];
                $srcAttr['title']['ua'] = $srcAttr['title_ua'];
                $srcAttr['title']['ru'] = $srcAttr['title_ru'];

                $srcAttr['annotation']['en'] = $srcAttr['annotation_en'];
                $srcAttr['annotation']['ua'] = $srcAttr['annotation_ua'];
                $srcAttr['annotation']['ru'] = $srcAttr['annotation_ru'];

                $attrAe->setGettedData($srcAttr);
                $attrAe->addedit_object();

                $attr_id = $attrAe->getIID();

                if($srcAttr['attr_code'] == 'id'){
                    $prim_attr_id = $attr_id;
                }
                //$dbTo->query("COMMIT");
                //ATH
                $athsR = $dbFrom->query("SELECT * FROM `_ath_links`  WHERE p_ot_id = '".$_attr->getOtId()."' && p_iid = '".$srcAttrId."'");
                if($athsR->getNumRows()) {
                    while ($athLink = $athsR->fetchRow()) {
                        $athData = $dbFrom->query("SELECT * FROM _ath WHERE ah_id = '".$athLink['ch_iid']."'")
                            ->fetchRow();



                        $existsAthR = $dbTo->query("SELECT * FROM _ath WHERE ah_name = '".$athData['ah_name']."' 
                        && `ah_type` = '".$athData['ah_type']."'");

                        if(!$existsAthR->getNumRows()) {
                            $log[] = 'AH '.$athData['ah_name'].' not found in destination DB';
                            continue;
                        }

                        $existsAth = $existsAthR->fetchRow();
                        $ath_id = $existsAth['ah_id'];

                        $dbTo->query("INSERT INTO _ath_links 
                            (`p_ot_id`, `p_iid`, `ch_ot_id`, `ch_iid`,`rule_alias`, `priority`, `logic`, `cfg`) VALUES 
                            ('".$_attr->getOtId()."', '".$attr_id."', '".$_ah->getOtId()."', '".$ath_id."'
                            , '".$athData['rule_alias']."', '".$athData['priority']."', '".$athData['logic']."','".$athData['cfg']."')
                        ");

                        if($existsAth['check_params'] == 1) {
                            $log[] = 'Ath '.$existsAth['ah_name'].', attr [' . $attr_id . ']  need to check params';
                        }
                    }
                }
            }

            // Primary attr update
            if($prim_attr_id){
                $dbTo->query("UPDATE _obj_types SET prim_attr_id = '".$prim_attr_id."' WHERE id = '".$ot_id."'");
            }

            //OT Properties
            $otype_prop = _oh('otype_prop');
            if(!$otype_prop){
                $otPropsSrcSqlr = $dbFrom->query("SELECT * FROM `_obj_props`  WHERE ot_iid = '".$srcOtId."'");
                while($otPropData = $otPropsSrcSqlr->fetchRow()) {
                    $log[] = 'OT prop adding '.$otPropData['code'];
                    $dbTo->query("INSERT INTO _obj_props 
                            (`ot_id`, `key_id`, `ot_iid`, `created`,`owner`, `code`, `type`, `title_ru`,
                             `title_en`, `title_ua`, `value`, `inheritable`, `priority`
                            ) VALUES 
                            ('".$otype_prop->getID()."', '".$otype_prop->getBaseKey()."', '".$ot_id."', '".$otPropData['created']."'
                            , '".$otPropData['owner']."', '".$otPropData['code']."', '".$otPropData['type']."','".$otPropData['title_ru']."'
                            , '".$otPropData['title_en']."', '".$otPropData['title_ua']."', '".$otPropData['value']."', '".$otPropData['inheritable']."'
                            , '".$otPropData['priority']."'
                            )");
                }
            }

            // OT links
            $otLinksSqlr = $dbFrom->query("SELECT * FROM `_obj_links_rules`  WHERE ch_ot_id = '".$srcOtId."' || p_ot_id = '".$srcOtId."'");
            while($otL = $otLinksSqlr->fetchRow()) {
                $log[] = 'OT link adding '.var_export($otL, true);

                if($otL['p_ot_id'] == $srcOtId){
                    $p_ot_id = $srcOtId;
                }else{
                    $sqlr = $dbFrom->query("SELECT * FROM _obj_types WHERE id = '".$otL['p_ot_id']."'");
                    $pOtData = $sqlr->fetchRow();
                    $existsPotData = $dbTo->query("SELECT * FROM _obj_types WHERE ot_code = '".$pOtData['ot_code']."'")
                        ->fetchRow();
                    if(!$existsPotData){
                        $log[] = 'Linked p OT not found ';
                        continue;
                    }

                    $p_ot_id = $existsPotData['id'];
                }

                if($otL['ch_ot_id'] == $srcOtId){
                    $ch_ot_id = $srcOtId;
                }else{
                    $sqlr = $dbFrom->query("SELECT * FROM _obj_types WHERE id = '".$otL['ch_ot_id']."'");
                    $chOtData = $sqlr->fetchRow();
                    $existsChOtData = $dbTo->query("SELECT * FROM _obj_types WHERE ot_code = '".$chOtData['ot_code']."'")
                        ->fetchRow();
                    if(!$existsChOtData){
                        $log[] = 'Linked ch OT not found ';
                        continue;
                    }

                    $ch_ot_id = $existsPotData['id'];
                }

                $dbTo->query("INSERT INTO _obj_links_rules 
                            (`priority`, `p_ot_id`, `ch_ot_id`, `rule`,`statement`, `links_table`, `db`, `del_links_only`,
                             `alias`
                            ) VALUES 
                            ('".$otL['priority']."', '".$p_ot_id."', '".$ch_ot_id."', '".$otL['rule']."'
                            , '".$otL['statement']."', '".$otL['links_table']."', '".$otL['db']."','".$otL['del_links_only']."'
                            , '".$otL['alias']."'
                            )");
            }

            $dbTo->query("COMMIT");

        }catch (\Exception $exception){
            $dbTo->query("ROLLBACK");
            $log[] = $exception->getMessage();
            $log[] = $exception->getTraceAsString();
        }

        $this->content = var_export($log, true);
        return $this->content;
    }
}
