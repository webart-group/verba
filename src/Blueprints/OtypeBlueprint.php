<?php

namespace Verba\Blueprints;


class OtypeBlueprint extends AbstractBlueprint
{
    public $ot_code;
    public $key_code;
    public $key_base_id;
    public $ot_base_id;
    public $ot_ru;
    public $ot_ua;
    public $ot_en;
    public $tableName;
    public $ot_role;

    public $attributes = [];
    public $customFields = [];

    public $indexes = [];

    public $links = [];

    public function __construct(array $cfg = null)
    {
          if ($cfg) {
              $this->app
          }
    }

    public function validate(): bool
    {
        return (bool)$this->ot_code;
    }

    public function setAttributes(array $attrs): self
    {
        $this->attributes = $attrs;
        return $this;
    }

    public function setCustomFields(array $fields): self
    {
        $this->customFields = $fields;
        return $this;
    }

    public function setIndexes(array $indexes): self
    {
        $this->indexes = $indexes;
        return $this;
    }

    public function setLinks(array $links): self
    {
        $this->links = $links;
        return $this;
    }
}
