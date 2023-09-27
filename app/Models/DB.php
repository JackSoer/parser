<?php

namespace App\Models;

use Illuminate\Database\Capsule\Manager;

class DB
{
    protected $capsule;

    public function __construct($config)
    {
        $this->capsule = new Manager();
        $this->capsule->addConnection($config);

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }
}
