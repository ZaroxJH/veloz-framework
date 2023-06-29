<?php

namespace Veloz\Database\Migrations;

class Migration
{
    public $aedificator;

    public function __construct()
    {
        $this->aedificator = new Aedificator();
        
        if (!$this->aedificator->init()) {
            exit;
        }
    }

    public function aedificator()
    {
        return $this->aedificator;
    }

    public function tableExists()
    {
        return $this->aedificator->tableExists();
    }

}