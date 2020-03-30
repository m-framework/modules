<?php

namespace modules\modules\admin;

use m\functions;
use m\module;
use m\view;
use m\registry;
use m\config;
use m\core;
use m\i18n;
use modules\files\models\files;

class install extends module {

    protected $js = ['/js/upload_module.js'];
    protected $css = ['/css/install.css'];

    public function _init()
    {
        if (!empty($_FILES['module'])) {
            header("Content-type: application/json; charset=utf-8");
            core::out(json_encode($this->upload_file()));
        }

        view::set('content', $this->view->install->prepare());
    }

    public function upload_file()
    {
        $arr = ['error' => i18n::get('Error') . ': ' . i18n::get('Sorry, can\'t install a module')];

        if (!empty($_FILES['module']['error']) || empty($_FILES['module']['size']) || empty($_FILES['module']['name']) || empty($this->user->profile)
            || !$this->user->is_admin())
            return $arr;

        $name = time() . '_' . pathinfo($_FILES['module']['name'], PATHINFO_FILENAME);

        $dir = config::get('root_path') . '/tmp/';
        $modules_path = config::get('root_path') . '/m-framework/modules/';

        if (!move_uploaded_file($_FILES['module']['tmp_name'], $dir . $name . '.zip') == true)
            return $arr;

        chdir($dir);

        $zip = new \ZipArchive;
        $zip->open($name . '.zip');
        $zip->extractTo($dir . $name);
        $zip->close();

        if (!is_dir($dir . $name) && $this->destroy_tmp($dir . $name)) {
            return ['error' => i18n::get('We can\'t to extract your file like archive')];
        }

        $files = array_values(array_diff(scandir($dir . $name), ['.', '..']));

        if (is_dir($modules_path . $files['0']) && !rename($modules_path . $files['0'], $modules_path . $files['0'] . '_' . time()))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('We can\'t to backup an existing module with same name. Try to remove it manually.')];

        if ((empty($files) || !is_array($files) || empty($files['0']) || !is_file($dir . $name . '/' . $files['0'] . '/module.json')) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('Uploaded archive don\'t consist of needed data')];

        $module_json = @json_decode(@file_get_contents($dir . $name . '/' . $files['0'] . '/module.json'), true);

        if (empty($module_json) && $this->destroy_tmp($dir . $name)) {
            return ['error' => i18n::get('Error') . ': ' . i18n::get('Uploaded archive don\'t consist of needed data')];
        }

        $important_parameters = ['name', 'description', 'icon', 'version', 'date', 'author', 'compatible_cores', 'price'];
        $empty_params = [];
        foreach ($important_parameters as $important_parameter) {
            if (!isset($module_json[$important_parameter])) {
                $empty_params[] = $important_parameter;
            }
        }

        // TODO: use some casual function like `array_diff` or smth. else

        if (!empty($empty_params) && $this->destroy_tmp($dir . $name)) {
            return [
                'empty_params' => $empty_params,
                'error' => i18n::get('Error') . ': ' . i18n::get('There are absents important parameters in module file') . '`module.json`'
            ];
        }

        if ((!is_array($module_json['compatible_cores']) || !in_array(core::get_version(), $module_json['compatible_cores'])) && $this->destroy_tmp($dir . $name)) {
            return ['error' => i18n::get('Error') . ': ' . i18n::get('This module don\'t compatible with current core version')];
        }

        $zip = new \ZipArchive;
        $zip->open($name . '.zip');
        $zip->extractTo($modules_path . $name);
        $zip->close();

        if (!is_dir($modules_path . $name) && $this->destroy_tmp($dir . $name)) {
            return ['success' => i18n::get('New module') . ' `' . $module_json['name'] . '` ' . i18n::get('successfully installed'),];
        }

        return $arr;
    }

    function destroy_tmp($path)
    {
        return functions::delete_recursively($path) && is_file($path . '.zip') ? unlink($path . '.zip') : false;
    }
}