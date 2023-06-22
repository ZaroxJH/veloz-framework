<?php

namespace Database\Migrations;

use Veloz\Database\Migrations\Migration;
use Veloz\Database\Migrations\Aedificator;

return new class extends Migration
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
        $this->aedificator->create(
            [
                'created_at' => [
                    'type' => 'timestamp',
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'updated_at' => [
                    'type' => 'timestamp',
                    'default' => 'CURRENT_TIMESTAMP',
                ],
            ]
        );
    }

    public function delete()
    {
        $this->aedificator->deleteTable();
    }

    public function setTable($name = null)
    {
        $this->aedificator->setTable($name);
    }
};