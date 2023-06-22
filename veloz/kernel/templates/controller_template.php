<?php

namespace App\Controllers;

use Veloz\Core\Controller;

class DummyController extends Controller
{
    protected array $data = [];

    public function index(): bool|string
    {
        return $this->render();
    }

    private function render(): bool|string
    {
        return $this->view('dummyview', [
            $this->data,
        ]);
    }
}
