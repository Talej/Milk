<?php

    class MilkTheme extends MilkFramework {
        public $streams = array();

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

        public function put($key, $val) {
            if (count($this->streams) > 1) {
                $stream =& $this->streams[count($this->streams)-2];
            } else {
                $stream =& $this->streams[0];
            }
            if (!isset($stream[$key])) $stream[$key] = array();
            $stream[$key][] = $val;
        }

        public function get($key, $cast='string') {
            $i = count($this->streams)-1;
            if (isset($this->streams[$i][$key])) {
                $val = $this->streams[$i][$key];
                unset($this->streams[$i][$key]);
            } else {
                $val = array();
            }
            if ($cast == 'string') {
                return implode('', $val);
            } else {
                return $val;
            }
        }

        public function deliverChildren($ctrl) {
            foreach ($ctrl->controls as $control) {
                $control->deliver();
            }
        }
    }
