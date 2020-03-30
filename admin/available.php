<?php

namespace modules\modules\admin;

use m\module;
use m\view;
use m\config;
use m\i18n;

class available extends module {

    public function _init()
    {
        $items = [];

        $modules_path = 'https://m-framework.com/m-framework/modules';

        $modules = array_diff(scandir($modules_path), array('.', '..'));

        if (!empty($modules))
            foreach ($modules as $module) {

                if (!is_dir($modules_path . '/' . $module))
                    continue;

                $module_files = array_diff(scandir($modules_path . '/' . $module), array('.', '..'));

                if (!in_array('admin', $module_files) || !in_array('module.json', $module_files)
                    || !is_file($modules_path . '/' . $module . '/module.json')
                    || !isset($this->view->content_table_row))
                    continue;

                $module_json = json_decode(file_get_contents($modules_path . '/' . $module . '/module.json'), true);

                if (empty($module_json) || !is_array($module_json)) {
                    continue;
                }

                $items[] = $this->view->available_row->prepare([
                    'module' => $module,
                    'icon' => empty($module_json['icon']) ? '' : $module_json['icon'],
                    'name' => i18n::get($module_json['name']),
                    'version' => empty($module_json['version']) ? '' : $module_json['version'],
                    'author' => empty($module_json['author']) ? '' : $module_json['author'],
                    'date' => empty($module_json['date']) ? '' : $module_json['date'],
                ]);
            }

        view::set('content', $this->view->content_table->prepare([
            'items' => implode('', $items),
        ]));
    }
}