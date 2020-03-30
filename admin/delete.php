<?php

namespace modules\modules\admin;

use m\functions;
use m\module;
use m\view;
use m\registry;
use m\config;
use m\core;
use m\i18n;

class delete extends module {

    public function _init()
    {
        if (in_array($this->alias, ['delete','modules']) || !$this->user->is_admin())
            core::redirect('/' . $this->config->admin_panel_alias);

        $modules_path = config::get('root_path') . '/m-framework/modules';

        $modules = array_diff(scandir($modules_path), ['.', '..']);

        $protected = ['modules','sites','pages','users'];

        if (!empty($modules))
            foreach ($modules as $module) {

                if ($module !== $this->alias || !is_dir($modules_path . '/' . $module) || in_array($protected, $module))
                    continue;

                functions::delete_recursively($modules_path . '/' . $module);
            }

        core::redirect('/' . $this->config->admin_panel_alias . '/modules');
    }
}