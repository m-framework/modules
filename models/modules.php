<?php

namespace modules\modules\models;

use m\core;
use m\model;
use m\config;
use m\module;

class modules extends model
{
    private static $client_classes;

    public static function get_modules_paths()
    {
        $arr = [];

        $modules_path = config::get('root_path') . '/m-framework/modules';

        $modules = array_diff(scandir($modules_path), ['.', '..']);

        if (empty($modules)) {
            return $arr;
        }

        foreach ($modules as $module) {

            if (!is_dir($modules_path . '/' . $module) || !is_file($modules_path . '/' . $module . '/module.json')) {
                continue;
            }

            $arr[$module] = $modules_path . '/' . $module;
        }

        return $arr;
    }

    public static function get_client_classes($path = null)
    {
        if (!empty(static::$client_classes)) {
            return static::$client_classes;
        }

        $arr = [];


        $modules_path = config::get('root_path') . '/m-framework/modules';

        if (!empty($path) && is_dir(config::get('root_path') . $path)) {
            $modules_path = config::get('root_path') . $path;
        }

        $modules = array_diff(scandir($modules_path), ['.', '..']);

        if (empty($modules)) {
            return $arr;
        }

        foreach ($modules as $module) {

            if (!is_dir($modules_path . '/' . $module) || !is_dir($modules_path . '/' . $module . '/client')) {
                continue;
            }

            $client_modules = array_diff(scandir($modules_path . '/' . $module . '/client'), ['.', '..']);

            if (empty($client_modules)) {
                continue;
            }

            foreach ($client_modules as $client_module) {

                if (mb_strtolower(pathinfo($client_module, PATHINFO_EXTENSION), 'UTF-8') !== 'php') {
                    continue;
                }

                $client_module = pathinfo($client_module, PATHINFO_FILENAME);

                $module_class_name = 'modules\\' . $module . '\\client\\' . $client_module;

                if (!property_exists($module_class_name, '_name')) {
                    continue;
                }

                if (empty($arr[$module])) {
                    $arr[$module] = [];
                }

                $arr[$module][$module_class_name] = empty($module_class_name::$_name) ? $client_module : $module_class_name::$_name;
            }
        }

        // is_dir(config::get('root_path') . config::get('application_path') . 'modules/'

        if (!empty($path)) {
            static::$client_classes = $arr;
            return $arr;
        }

        $modules_path = config::get('root_path') . config::get('application_path') . 'modules/';

        $modules = array_diff(scandir($modules_path), ['.', '..']);

        if (empty($modules)) {
            static::$client_classes = $arr;
            return $arr;
        }

        foreach ($modules as $module) {

            if (!is_dir($modules_path . '/' . $module) || !is_dir($modules_path . '/' . $module . '/client')) {
                continue;
            }

            $client_modules = array_diff(scandir($modules_path . '/' . $module . '/client'), ['.', '..']);

            if (empty($client_modules)) {
                continue;
            }

            foreach ($client_modules as $client_module) {

                if (mb_strtolower(pathinfo($client_module, PATHINFO_EXTENSION), 'UTF-8') !== 'php') {
                    continue;
                }

                $client_module = pathinfo($client_module, PATHINFO_FILENAME);

                $module_class_name = 'modules\\' . $module . '\\client\\' . $client_module;

                if (!property_exists($module_class_name, '_name')) {
                    continue;
                }

                if (empty($arr[$module])) {
                    $arr[$module] = [];
                }

                $arr[$module][$module_class_name] = empty($module_class_name::$_name) ? $client_module : $module_class_name::$_name;
            }
        }


        static::$client_classes = $arr;

        return $arr;
    }
}
