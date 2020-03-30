<?php

namespace modules\modules\models;

use m\model;

class modules_options extends model
{
    public $_table = 'modules_options';

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'language' => 'int',
        'module' => 'varchar',
        'parameter' => 'varchar',
        'value' => 'varchar',
    ];

    public function value_special()
    {
        $value = str_replace('~', '&#126;', $this->value);
        $value = str_replace('*', '&#42;', $value);

        return $value;
    }
}
