<?php

    class MilkTheme extends MilkFramework {
        public $streams = array();

        public function getTheme($ctrl) {
            static $themes = array();

            $theme = MilkTools::ifNull($ctrl->theme, $ctrl->module->theme);
            if (isset($themes[$theme])) {
                return $themes[$theme];
            } else if (
                ($theme == 'standard' && require_once(MilkTools::mkPath(MILK_BASE_DIR, 'theme', $theme . '.php'))) ||
                require_once(MilkTools::mkPath(MILK_EXT_DIR, 'theme', $theme . '.php'))
            ) {
                $classname = $theme . '_MilkTheme';
                if (class_exists($classname) && is_subclass_of($classname, 'MilkTheme')) {
                    $class = new $classname();
                    $themes[$theme] = $class;
                    $themes[$theme]->streams =& $this->streams;
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

        public function deliver($ctrl) {
            if ($theme = $this->getTheme($ctrl)) {
                array_push($this->streams, array());
                $ctrl->deliver($theme);
                foreach ($this->streams[count($this->streams)-1] as $key => $vals) {
                    foreach ($vals as $val) {
                        $this->put($key, $val);
                    }
                }
                array_pop($this->streams);
            }
        }

        public function deliverChildren($ctrl) {
            foreach ($ctrl->controls as $control) {
                $this->deliver($control);
            }
        }

        public function entitise($v) {
            return htmlentities($v, ENT_QUOTES, 'UTF-8');
        }

        public function includejs($js) {
            $this->put('includejs', $js);
        }

        public function includecss($css) {
            $this->put('includecss', $css);
        }

        public function includes() {
            $str = '';

            $jsfiles = array_unique($this->get('includejs', NULL));
            $cachedjs = FALSE;
            if ((!defined('CFG_DEBUG_ENABLED') || !CFG_DEBUG_ENABLED) && !empty($jsfiles)) {
                include_once(MilkTools::mkPath(MILK_BASE_DIR, 'util', 'compress.php'));

                $compress = new FLQCompress(FLQCOMPRESS_TYPE_JS, $jsfiles);
                if ($jscache = $compress->exec()) {
                    $str.= "<script type=\"text/javascript\" language=\"Javascript1.1\" src=\"" . $this->entitise($jscache) . "\"></script>\n";
                    $cachedjs = TRUE;
                }
            }
            if (!$cachedjs) {
                foreach ($jsfiles as $jsfile) {
                    $str.= "<script type=\"text/javascript\" language=\"Javascript1.1\" src=\"" . $this->entitise($jsfile) . "\"></script>\n";
                }
            }

            $cssfiles = array_unique($this->get('includecss', NULL));
            $cachedcss = FALSE;
            if ((!defined('CFG_DEBUG_ENABLED') || !CFG_DEBUG_ENABLED) && !empty($cssfiles)) {
                include_once(MilkTools::mkPath(MILK_BASE_DIR, 'util', 'compress.php'));

                $compress = new FLQCompress(FLQCOMPRESS_TYPE_CSS, $cssfiles);
                if ($csscache = $compress->exec()) {
                    $str.= "<link rel=\"stylesheet\" href=\"" . $this->entitise($csscache) . "\" type=\"text/css\" />\n";
                    $cachedcss = TRUE;
                }
            }
            if (!$cachedcss) {
                $cssstr = '';
                foreach ($cssfiles as $cssfile) {
                    if (!isset($first)) {
                        $str.= "<link rel=\"stylesheet\" href=\"" . $this->entitise($cssfile) . "\" type=\"text/css\" />\n";
                    } else {
                        $cssstr.= "@import url(\"" . $this->entitise($cssfile) . "\");\n";
                    }
                }
                if ($cssstr != '') {
                    $str.= "<style type=\"text/css\">" . $cssstr . "</style>\n";
                }
            }

            // fetch and compress the javascript
            $jsstr = $this->get('constructjs') . $this->get('connectjs') . $this->get('loadjs');
            if (!defined('CFG_DEBUG_ENABLED') || !CFG_DEBUG_ENABLED) {
                include_once(MilkTools::mkPath(MILK_BASE_DIR, 'dep', 'jsmin-1.1.1.php'));
                $jsstr = JSMin::minify($jsstr);
            }

            $str.= "<script type=\"text/javascript\" language=\"Javascript1.1\">\n"
                 . "function load() {"
                 . $jsstr
                 . "}"
                 . "</script>\n";

            return $str;
        }
    }
