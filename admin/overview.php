<?php

namespace modules\modules\admin;

use m\module;
use m\view;
use m\registry;
use m\config;
use m\i18n;
use m\functions;
use modules\modules\models\modules_sequence;

class overview extends module {

    protected $css = [
        '/css/overview.css'
    ];

    public function _init()
    {
        $items = [];

        $modules_sequence = modules_sequence::call_static()->s(['*'], [], [10000])->all();
        $sequence = [];

        if (!empty($modules_sequence)) {
            foreach ($modules_sequence as $module_sequence) {
                $sequence[$module_sequence['module']] = $module_sequence['sequence'];
            }
        }

        $this->process_modules_dir('/m-framework/modules/', $sequence, $items);

        if (config::get('application_path')) {
            $this->process_modules_dir(config::get('application_path') . 'modules/', $sequence, $items);
        }

        ksort($items);

        view::set('content', $this->view->overview->prepare([
            'items' => implode('', $items),
        ]));
    }

    private function process_modules_dir($path, $sequence, &$items)
    {
        $modules = array_diff(scandir(config::get('root_path') . $path), ['.', '..']);

        $protected = ['modules','sites','pages','users'];

        if (!empty($modules)) {
            foreach ($modules as $module) {

                if (!is_dir(config::get('root_path') . $path . $module)
                    || !is_file(config::get('root_path') . $path . $module . '/module.json'))
                    continue;

                $module_json = json_decode(file_get_contents(config::get('root_path') . $path . $module . '/module.json'), true);

                $n = functions::zerofill(empty($sequence[$module]) ? count($items) + 1 : $sequence[$module], 4);

                i18n::init($path . $module . '/admin/i18n/');

                $view_name = in_array($module, $protected) ? 'overview_item_protected' : 'overview_item';

                $items[$n . '_' . $module] = $this->view->{$view_name}->prepare([
                    'address' => '/' . $this->config->admin_panel_alias . '/' . $module,
                    'icon' => empty($module_json['icon']) ? '' : $module_json['icon'],
                    'title' => i18n::get($module_json['title']),
                    'name' => $module_json['name'],
                    'author' => $module_json['author'],
                    'version' => $module_json['version'],
                    'date' => $module_json['date'],
                    'module' => $module,
                ]);
            }
        }
    }
}