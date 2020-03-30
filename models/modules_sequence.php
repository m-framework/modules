<?php

namespace modules\modules\models;

use m\model;
use m\cache;
use m\registry;
use modules\pages\models\pages;

class modules_sequence extends model
{
    public $_table = 'modules_sequence';
    public $__id = 'module';
    public $_sort = ['sequence' => 'ASC'];

    protected $fields = [
        'module' => 'varchar',
        'sequence' => 'int',
    ];
}
