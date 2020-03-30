<?php

namespace modules\modules\admin;

use m\functions;
use m\module;
use m\view;
use m\config;
use m\core;
use m\i18n;
use libraries\pclzip\PclZip;

class update_list extends module {

    public function _init()
    {
        $modules = [];

        $dir = config::get('root_path') . '/modules/';

        $files = array_values(array_diff(scandir($dir), ['.', '..']));

        if (empty($files) || !is_array($files))
            $this->redirect('/' . $this->config->admin_panel_alias . '/modules/shop');

        foreach ($files as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'zip')
                continue;

            $archive = new PclZip($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($name, ['i18n','modules'])) {
                continue;
            }

            chdir($dir);

            if (($archive->extract(PCLZIP_OPT_PATH, $name) == 0 || !is_dir($dir . $name)) && $this->destroy_tmp($dir . $name))
                continue;

            /**
             * Trying to read an array of files in extracted archive
             */
            $_files = array_values(array_diff(scandir($dir . $name), ['.', '..']));

            /**
             * Returns error if a file `module.json` is absent
             */
            if ((empty($_files) || empty($_files['0']) || !is_file($dir . $name . '/' . $_files['0'] . '/module.json')) && $this->destroy_tmp($dir . $name))
                continue;
            /**
             * Trying to read a data from `module.json` to archive
             */
            $module_json = @json_decode(@file_get_contents($dir . $name . '/' . $_files['0'] . '/module.json'), true);

            /**
             * Returns error if a data from `module.json` is absent or isn't an array
             */
            if (empty($module_json) || !is_array($module_json) && $this->destroy_tmp($dir . $name))
                continue;

            /**
             * Checking if an array from `module.json` consist of important parameters
             */
            $important_parameters = ['title', 'description', 'icon', 'version', 'date', 'author', 'compatible_cores', 'price'];
            $empty_params = [];
            foreach ($important_parameters as $important_parameter)
                if (!isset($module_json[$important_parameter]))
                    $empty_params[] = $important_parameter;

            /**
             * Returns error if some of important parameters absent in `module.json`
             */
            if (!empty($empty_params) && $this->destroy_tmp($dir . $name))
                continue;

            if ($this->destroy_tmp($dir . $name)) {

                foreach ((array)$module_json['compatible_cores'] as $core) {

                    if (!isset($modules[$core]))
                        $modules[$core] = [];

                    $module_json['archive_link'] = empty($module_json['price']) ? '//' . $this->site->host . '/modules/' . $file : 'mailto:' . $module_json['author'];
                    $modules[$core][$module_json['name'] . $module_json['author']] = $module_json;
                }

            }
        }

        if (!empty($modules)) {

            foreach ($modules as $core => $_modules) {

                $_modules = array_values($_modules);

                file_put_contents($dir . 'modules.' . $core . '.json', json_encode($_modules));
            }
        }

        $this->redirect('/' . $this->config->admin_panel_alias . '/modules/shop');
    }

    function destroy_tmp($path)
    {
        if ($path == config::get('root_path') . '/modules/' || $path == config::get('root_path') . '/modules')
            return false;

        return functions::delete_recursively($path);
    }
}