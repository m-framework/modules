<?php

namespace modules\modules\admin;

use m\module;
use m\core;
use m\view;
use m\i18n;
use m\registry;
use modules\admin\admin\overview_data;
use modules\sites\models\sites;

class options extends module
{
    public function _init()
    {
        if (empty($this->get->options)) {
            core::redirect('/' . $this->conf->admin_panel_alias . '/modules');
        }

        $module_path = $this->conf->root_path . '/m-framework/modules/' . $this->get->options;
        $i18n_path = '/m-framework/modules/' . $this->get->options . '/admin/i18n/';
        $json_path = $module_path . '/module.json';

//        if (!is_dir($module_path) || !is_file($json_path)) {
//            core::redirect('/' . $this->conf->admin_panel_alias . '/modules');
//        }

        if (is_dir($this->conf->root_path . $i18n_path)) {
            i18n::init($i18n_path);
        }

        if (is_file($json_path)) {
            $module_json = json_decode(file_get_contents($json_path), true);
        }

        view::set('page_title', '<h1><i class="fa fa-cog"></i> *Edit module options* ' .
            (empty($module_json) || empty($module_json['title']) ? '' : '`' . i18n::get($module_json['title']) . '`') . '</h1>');
        registry::set('title', i18n::get('Edit module options'));

        registry::set('breadcrumbs', [
            '/' . $this->conf->admin_panel_alias . '/modules' => '*Modules*',
            '' => '*Edit module options*'
        ]);

        view::set('content', overview_data::items(
            'modules\modules\models\modules_options',
            [
                'parameter' => i18n::get('Parameter'),
                'value' => i18n::get('Value'),
            ],
            ['module' => $this->get->options, 'language' => $this->language_id],
            $this->view->options_overview,
            $this->view->options_overview_item,
            [
                'module' => $this->get->options,
            ]
        ));

        view::set_css($this->module_path . '/css/options_overview.css');
    }
}
