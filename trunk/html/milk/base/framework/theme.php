<?php

    class MilkTheme extends MilkFramework {

        static public function getTheme($theme) {
            static $themes = array();

            if (isset($themes[$theme])) {
                return $themes[$theme];
            } else if (
                ($theme == 'standard' && MilkLauncher::load(MILK_BASE_DIR, 'theme', $theme . '.php')) ||
                MilkLauncher::load(MILK_EXT_DIR, 'theme', $theme . '.php')
            ) {
                $classname = $theme;
                if (class_exists($classname) && is_subclass_of($classname, 'MilkTheme')) {
                    $class = new $classname();
                    $themes[$theme] = $class;
                    return $class;
                } else {
                    trigger_error('MilkTheme::getTheme() - Unable to find MilkTheme class for ' . $theme, E_USER_ERROR);
                    exit;
                }
            } else {
                trigger_error('MilkTheme::getTheme() - Unable to locate theme file for ' . $theme, E_USER_ERROR);
                exit;
            }

            return FALSE;
        }
    }
