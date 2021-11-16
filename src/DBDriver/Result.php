<?php

namespace Verba\DBDriver;

interface Result {

    public function getNumRows();

    public function fetchRow($arrayType='ASSOC_ARRAY');

    public function getValue($numRow,$numField);

    public function getFirstValue();

    public function getFirstRow();

    public function getMultiArray($arrayType='ASSOC_ARRAY');

    public function setCursor($position=0);

    public function getAffectedRows();

    public function getInsertId();

    public function free();
}
