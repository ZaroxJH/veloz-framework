<?php

namespace Veloz\Database\Seeders;

use Veloz\Database\Migrations\Aedificator;

class Seeder
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

    public function clearTable()
    {
        return $this->aedificator->clearTable();
    }

    public function error()
    {
        return $this->aedificator->databaseError;
    }

    public function hasContent()
    {
        return $this->aedificator->hasContent();
    }

    public function tableExists()
    {
        return $this->aedificator->tableExists();
    }

}