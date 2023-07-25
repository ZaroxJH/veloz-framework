<?php

namespace Database\Seeders;

use Veloz\Database\Seeders\Seeder;
use Veloz\Database\Migrations\Aedificator;

return new class extends Seeder
{
    public $aedificator;

    public function __construct()
    {
        parent::__construct();
        $this->aedificator = parent::aedificator();
    }

    /**
     * Contains the table data
     */
    public function create()
    {
        $this->aedificator->fill_table(
            [
                
            ]
        );
    }

    public function setTable($name = null)
    {
        $this->aedificator->setTable($name);
    }
};