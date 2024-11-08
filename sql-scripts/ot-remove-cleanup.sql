-- select * from _obj_attributes GROUP BY form_element
DELETE from _obj_attributes WHERE ot_iid > 478;
DELETE FROM _ath_links Where p_ot_id = 7 AND p_iid NOT IN (SELECT attr_id FROM _obj_attributes);
DELETE FROM _athp_foreignid WHERE set_id NOT IN (SELECT set_id FROM _ath_links);
DELETE FROM _obj_links_rules
       WHERE
           p_ot_id NOT IN (SELECT id FROM _obj_types)
              OR
           ch_ot_id NOT IN (SELECT id FROM _obj_types);
