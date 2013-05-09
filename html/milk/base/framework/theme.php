<?php

    class MilkTheme extends MilkFramework {
        public $streams = array();
        public $mod;
        public $path;

        public function __construct($mod) {
            $this->addHook('init');
            $cb = array($this, 'init');
            $this->addHookHandler('init', $cb);
            $this->mod = $mod;
            $theme = str_replace('_MilkTheme', '', get_class($this));
            $this->path = MilkTools::mkPath(($theme == 'default' ? 'base' : 'ext'), 'theme', $theme);
        }

        public function getPath($ctrl) {
            if (str_replace('_MilkTheme', '', get_class($this)) == $ctrl->theme) {
                return $this->path;
            } else {
                $theme = ($ctrl->theme ? $ctrl->theme : 'default');
                return MilkTools::mkPath(($theme == 'default' ? 'base' : 'ext'), 'theme', $theme);
            }
        }

        public function init() { }

        public function getTheme($ctrl) {
            static $themes = array();

            $theme = MilkTools::ifNull($ctrl->theme, $ctrl->module->theme);
            if (isset($themes[$theme])) {
                return $themes[$theme];
            } else if (
                ($theme == 'default' && require_once(MilkTools::mkPath(MILK_BASE_DIR, 'theme', $theme, $theme . '.php'))) ||
                require_once(MilkTools::mkPath(MILK_EXT_DIR, 'theme', $theme, $theme . '.php'))
            ) {
                $classname = $theme . '_MilkTheme';
                if (class_exists($classname) && is_subclass_of($classname, 'MilkTheme')) {
                    $class = new $classname($this->mod);
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
                $theme->execHook('init');
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
            if ($v instanceof MilkControl) {
                $this->deliver($v);
                return $this->get('xhtml');
            } else if ($v instanceof MilkDateTime) {
                return $v->toString(MilkTools::ifNull($this->mod->config->get('DATETIME_FORMAT'), MilkTools::ifDef('CFG_DATETIME_FORMAT', NULL)));
            } else if ($v instanceof MilkDate) {
                return $v->toString(MilkTools::ifNull($this->mod->config->get('DATE_FORMAT'), MilkTools::ifDef('CFG_DATE_FORMAT', NULL)));
            } else if (is_object($v) && method_exists($v, 'toString')) {
                return $v->toString();
            } else {
                return htmlentities($v, ENT_QUOTES, 'UTF-8');
            }
        }

        public function includejs($js, $compress=TRUE) {
            if ($compress && strtolower(substr($js, 0, 4)) != 'http') {
                $this->put('cachejs', $js);
            } else {
                $this->put('includejs', $js);
            }
        }

        public function includecss($css, $media='screen') {
            if (in_array($media, array('all', 'screen', 'print'))) {
                $this->put($media . 'css', $css);
            } else {
                $this->put('includecss', array($css, $media));
            }
        }

        public function includes() {
            $str = '';

            $jsfiles = array();
            $cachefiles = array_unique($this->get('cachejs', NULL));
            $cachedjs = FALSE;
            if ((!defined('CFG_DEBUG_ENABLED') || !CFG_DEBUG_ENABLED) && !empty($cachefiles)) {
                include_once(MilkTools::mkPath(MILK_BASE_DIR, 'util', 'compress.php'));

                $compress = new FLQCompress(FLQCOMPRESS_TYPE_JS, $cachefiles);
                if ($jscache = $compress->exec()) {
                    $str.= "<script type=\"text/javascript\" language=\"Javascript1.1\" src=\"" . $this->entitise($jscache) . "\"></script>\n";
                    $cachedjs = TRUE;
                }
            } else {
                $jsfiles = $cachefiles;
            }


            $jsfiles = array_unique(array_merge($jsfiles, $this->get('includejs', NULL)));
            if (!empty($jsfiles)) {
                foreach ($jsfiles as $jsfile) {
                    if (is_array($jsfile)) list($jsfile,) = $jsfile;
                    $str.= "<script type=\"text/javascript\" language=\"Javascript1.1\" src=\"" . $this->entitise($jsfile) . "\"></script>\n";
                }
            }

            $medias = array('all', 'screen', 'print');
            foreach ($medias as $media) {
                if (isset($first)) unset($first);
                $cssfiles = array_unique($this->get($media . 'css', NULL));
                $cachedcss = FALSE;
                if ((!defined('CFG_DEBUG_ENABLED') || !CFG_DEBUG_ENABLED) && !empty($cssfiles)) {
                    include_once(MilkTools::mkPath(MILK_BASE_DIR, 'util', 'compress.php'));

                    $compress = new FLQCompress(FLQCOMPRESS_TYPE_CSS, $cssfiles);
                    if ($csscache = $compress->exec()) {
                        $str.= "<link rel=\"stylesheet\" href=\"" . $this->entitise($csscache) . "\" type=\"text/css\" media=\"" . $this->entitise($media) . "\">\n";
                        $cachedcss = TRUE;
                    }
                }
                if (!$cachedcss) {
                    $cssstr = '';
                    foreach ($cssfiles as $cssfile) {
                        if (!isset($first)) {
                            $str.= "<link rel=\"stylesheet\" href=\"" . $this->entitise($cssfile) . "\" type=\"text/css\" media=\"" . $this->entitise($media) . "\">\n";
                            $first = TRUE;
                        } else {
                            $cssstr.= "@import url(\"" . $this->entitise($cssfile) . "\");\n";
                        }
                    }
                    if ($cssstr != '') {
                        $str.= "<style type=\"text/css\" media=\"" . $this->entitise($media) . "\">" . $cssstr . "</style>\n";
                    }
                }
            }

            // non-compressable css files (non-standard media type)
            $cssfiles = array_unique($this->get('includecss', NULL));
            foreach ($cssfiles as $css) {
                list($cssfile, $media) = $css;
                $str.= "<link rel=\"stylesheet\" href=\"" . $this->entitise($cssfile) . "\" type=\"text/css\" media=\"" . $this->entitise($media) . "\">\n";
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

        public function getID($ctrl, $prefix='mlk-') {
            return $prefix . implode('-', (array)$ctrl->id);
        }

        public function jsControl($ctrl, $props=NULL) {
            $jsfile = MilkTools::mkPath($this->getPath($ctrl), 'js', strtolower(str_replace('_MilkControl', '', get_class($ctrl))) . '.js');
            if (file_exists(MilkTools::mkPath(MILK_DIR, $jsfile))) $this->includejs(MilkTools::mkPath('/milk', $jsfile));

            if (!$ctrl->strictConns) $props['strictConns'] = MilkTools::jsEncode(FALSE, JSTYPE_BOOL);
            if ($ctrl->savegroup)    $props['saveGroup']   = MilkTools::jsEncode($ctrl->savegroup);

            $str = 'Milk.add(' . MilkTools::jsEncode($ctrl->name) . ', '
                 . MilkTools::jsEncode($this->getID($ctrl, '')) . ', '
                 . MilkTools::jsEncode((array)$props, JSTYPE_HASH, FALSE) . ').init();';

            $this->put('constructjs', $str);

            if ($ctrl->connections) {
                $str = '';
                foreach ($ctrl->connections as $conn) {
                    $str.= ' connect(' . MilkTools::jsEncode($conn->signal) . ', '
                         . MilkTools::jsEncode($conn->dest instanceof MilkControl ? $this->getID($conn->dest, '') : $conn->dest) . ', '
                         . MilkTools::jsEncode($conn->slot) . ', '
                         . MilkTools::jsEncode($conn->args) . ');';
                }
                $str = ' with (Milk.get(' . MilkTools::jsEncode($this->getID($ctrl, '')) . ')) {' . $str . '}';

                $this->put('connectjs', $str);
            }
        }
    }
