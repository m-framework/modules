<?php

namespace modules\modules\admin;

use m\functions;
use m\module;
use m\view;
use m\config;
use m\core;
use m\i18n;
use libraries\pclzip\PclZip;

class upload extends module {

    protected $js = ['/js/upload_module.js'];
    protected $css = ['/css/upload.css'];

    public function _init()
    {
        if (!empty($_FILES['module'])) {
            header("Content-type: application/json; charset=utf-8");
            core::out(json_encode($this->upload_module()));
        }

        view::set('content', $this->view->upload->prepare());
    }

    public function upload_module()
    {
        $arr = ['error' => i18n::get('Error') . ': ' . i18n::get('Sorry, can\'t save a module')];

        if (!empty($_FILES['module']['error']) || empty($_FILES['module']['size']) || empty($_FILES['module']['name']))
            return $arr;

        $dir = config::get('root_path') . '/modules/';

        if (is_file($dir . $_FILES['module']['name']))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('A module with the same name already exists. Try to use actual module version in file name')];

        $name = pathinfo($_FILES['module']['name'], PATHINFO_FILENAME);

        /**
         * Returns an error if uploading goes wrong
         */
        if (!move_uploaded_file($_FILES['module']['tmp_name'], $dir . $name . '.zip')) {
            return $arr;
        }

        /**
         * Trying to open uploaded archive
         */
        $archive = new PclZip($name . '.zip');

        /*
         * Open directory with modules archives
         */
        chdir($dir);

        /**
         * Returns error if archive extracting fails or directory with the same name don't created
         */
        if (($archive->extract(PCLZIP_OPT_PATH, $name) == 0 || !is_dir($dir . $name)) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('We can\'t to extract your file like archive')];

        /**
         * Trying to read an array of files in extracted archive
         */
        $files = array_values(array_diff(scandir($dir . $name), ['.', '..']));

        /**
         * Returns error if a file `module.json` is absent
         */
        if ((empty($files) || empty($files['0']) || !is_file($dir . $name . '/' . $files['0'] . '/module.json')) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('Uploaded archive don\'t consist of needed data')];

        /**
         * Trying to read a data from `module.json` to archive
         */
        $module_json = @json_decode(@file_get_contents($dir . $name . '/' . $files['0'] . '/module.json'), true);

        /**
         * Returns error if a data from `module.json` is absent or isn't an array
         */
        if (empty($module_json) || !is_array($module_json) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('Uploaded archive don\'t consist of needed data')];

        /**
         * Checking if an array from `module.json` consist of important parameters
         */
        $important_parameters = ['name', 'description', 'icon', 'version', 'date', 'author', 'compatible_cores', 'price'];
        $empty_params = [];
        foreach ($important_parameters as $important_parameter)
            if (!isset($module_json[$important_parameter]))
                $empty_params[] = $important_parameter;
        // TODO: use some casual function like `array_diff` or smth. else

        /**
         * Returns error if some of important parameters absent in `module.json`
         */
        if (!empty($empty_params) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('There are absents important parameters in module file') . '`module.json`'];

        /**
         * All is fine. Deleting only extracted temporary directory (leave an archive present in modules directory).
         */
        if ($this->destroy_tmp($dir . $name, 1))
            return ['success' => i18n::get('Your module') . ' `' . $module_json['name'] . '` ' . i18n::get('successfully uploaded to modules shop. It will appear in modules list after checking by administration.'),];

    }

    function destroy_tmp($path, $only_dir = null)
    {
        if ($path == config::get('root_path') . '/modules/' || $path == config::get('root_path') . '/modules')
            return false;

        return functions::delete_recursively($path) && (is_file($path . '.zip') && empty($only_dir) ? unlink($path . '.zip') : true);
    }
}