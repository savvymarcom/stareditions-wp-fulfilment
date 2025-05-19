<?php

namespace SavvyWebFulfilment;

class Autoloader
{
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload($class)
    {
        // Only autoload classes from this plugin's namespace
        if (strpos($class, __NAMESPACE__) !== 0) {
            return;
        }

        $class = str_replace(__NAMESPACE__ . '\\', '', $class);
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file  = plugin_dir_path(__DIR__) . 'includes/' . $class . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
