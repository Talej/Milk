<?php

    /**
     * User agent detection library
     *
     * This library provides functionality for detecting browsers, operating systems
     * and handheld device types
     *
     * $Id$
     *
     * @package core
     * @author Michael Little <michael@fliquidstudios.com>
     * @version 1.0
     */

    /**
     * FLQUserAgent class
     *
     * This class parses the given user agent string
     * to extract various components for browser detections
     * and other functionality
     *
     * @package core
     */
    class FLQUserAgent {
        public $agentstr;
        public $arch;
        public $os;
        public $osver;
        public $eng;
        public $engver;
        public $app;
        public $appver;
        public $appmajver;
        public $appminver;

        /**
         * constructor function for the FLQUserAgent class
         *
         * @access public
         * @return void
         * @param string $agentstr optional param, the agent string to use. If not passed will default to $_SERVER['HTTP_USER_AGENT']
         */
        function __construct($agentstr=NULL) {
            $this->agentstr = MilkTools::ifNull($agentstr, $_SERVER['HTTP_USER_AGENT']);

            if ($parts = $this->parse()) {
                $this->arch      = $parts['arch'];
                $this->os        = $parts['os'];
                $this->osver     = $parts['osver'];
                $this->eng       = $parts['eng'];
                $this->engver    = $parts['engver'];
                $this->app       = $parts['app'];
                $this->appver    = $parts['appver'];
                $this->appmajver = $parts['appmajver'];
                $this->appminver = $parts['appminver'];
            }
        }

        /**
         * parse() is the internal agent string parser
         *
         * This method uses the objects agentstr property to parse
         * and capture the various properties of the user agent
         * include architecture, OS, engine and application (browser).
         *
         * @access protected
         * @return mixed an associative array of parts on success, FALSE on failure
         */
        protected function parse() {
            $parts = array(
                'arch'      => NULL,
                'os'        => NULL,
                'osver'     => NULL,
                'eng'       => NULL,
                'engver'    => NULL,
                'app'       => NULL,
                'appver'    => NULL,
                'appmajver' => NULL,
                'appminver' => NULL
            );

            if (preg_match('/Mozilla\/[0-9\.]+\s*\([^;]+;\s*([^\s]+)\s([0-9\.]+);\s*(([^\s]+)[^;]+)/i', $this->agentstr, $m)) {
                // Matches IE 6 (and other versions ?)
                // 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; .NET CLR 3.0.04506.648)
                $parts['arch'] = $m[4];
                $parts['os'] = $m[3];
                $parts['eng'] = $m[1];
                $parts['engver'] = $m[2];
                $parts['app'] = $m[1];
                $parts['appver'] = $m[2];
                $parts['appmajver'] = $m[2];
            } else if (preg_match('/Mozilla\/[0-9\.]+\s\(([^;]+);\s*[^;];\s*([^;]+);[^\)]+\)\s*([^\s]+)\/([0-9]+)\s*(?:[^\s]*\s+)?([^\s]+)\/(([0-9]+)\.([0-9\.\-]+)).*/i', $this->agentstr, $m)) {
                //Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.3) Gecko/2008092510 Ubuntu/8.04 (hardy) Firefox/3.0.3
                // matches firefox agent string. For example:
                // Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3
                // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9) Gecko/2008061015 Firefox/3.0
                // Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8 (.NET CLR 3.5.30729)
                $parts['arch'] = $m[1];
                $parts['os'] = $m[2];
                // TODO: How to get osver
                $parts['eng'] = $m[3];
                $parts['engver'] = $m[4];
                $parts['app'] = $m[5];
                $parts['appver'] = $m[6];
                $parts['appmajver'] = $m[7];
                $parts['appminver'] = $m[8];
            } else if (preg_match('/Mozilla\/[0-9\.]+\s\(([^;]+);\s*[^;];\s*([^;]+);[^\)]+\)\s*([^\/]+)\/([0-9\.]+)[^\)]*\)\s*([^\/]+)\/(([0-9]+)\.([0-9\.]+))\s*(([^\/]+)\/(([0-9]+)\.([0-9\.]+)))?/i', $this->agentstr, $m)) {
                // Matches chrome
                // Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/0.3.154.9 Safari/525.19
                // Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Version/3.1.2 Safari/525.21
                $parts['arch'] = $m[1];
                $parts['os'] = $m[2];
                $parts['eng'] = $m[3];
                $parts['engver'] = $m[4];
                if (strtolower($m[5]) == 'chrome') {
                    $parts['app'] = $m[5];
                    $parts['appver'] = $m[6];
                    $parts['appmajver'] = $m[7];
                    $parts['appminver'] = $m[8];
                } else {
                    $parts['app'] = $m[10];
                    $parts['appver'] = $m[6];
                    $parts['appmajver'] = $m[7];
                    $parts['appminver'] = $m[8];
                }
//             } else if (preg_match('//', $this->agentstr, $m)) {
                // Matches Konqueror
                // Mozilla/5.0 (compatible; Konqueror/3.5; Linux) KHTML/3.5.9 (like Gecko) (Kubuntu)
            } else if (preg_match('/([^\/]+)\/(([0-9]+)\.([0-9\.]+))\s*\((([^\s]+)[^;]+)[^\)]+\)(\s*([^\/]+)\/([0-9\.]+))?/i', $this->agentstr, $m)) {
                // Matches opera
                // Opera/9.62 (Windows NT 5.1; U; en) Presto/2.1.1
                $parts['arch'] = $m[6];
                $parts['os'] = $m[5];
                if (isset($m[8])) $parts['eng'] = $m[8];
                if (isset($m[9])) $parts['engver'] = $m[9];
                $parts['app'] = $m[1];
                $parts['appver'] = $m[2];
                $parts['appmajver'] = $m[3];
                $parts['appminver'] = $m[4];
            }

            return $parts;
        }

        /**
         * isApp() is used for comparing the application or browser
         *
         * Compare the application name (e.g. Firefox, MSIE etc)
         * and optionally the version number. This method is a wrapper around the is() method
         *
         * @access public
         * @return bool TRUE if the application matches, FALSE otherwise
         * @param string $app the application name to compare
         * @param string $appver optional param, the version number to compare.
         *                       Can be major version (e.g. 3), minor version (e.g. 0.2) or full version (e.g. 3.0.2)
         * @param string $verop optional param, the version comparison type. Defaults to =.
         *                      Can be any of <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne
         */
        public function isApp($app, $appver=NULL, $verop=NULL) {
            return $this->is($app, $appver, NULL, NULL, NULL, NULL, NULL, $verop);
        }

        /**
         * isEng() is used for comparing the browser engine (e.g. Gecko)
         *
         * Compare the engine name and optionally the version number.
         * This method is a wrapper around the is() method
         *
         * @access public
         * @return bool TRUE if the application matches, FALSE otherwise
         * @param string $eng the engine name to compare
         * @param string $engver optional param, the version number to compare.
         *                       Can be major version (e.g. 3), minor version (e.g. 0.2) or full version (e.g. 3.0.2)
         * @param string $verop optional param, the version comparison type. Defaults to =.
         *                      Can be any of <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne
         */
        public function isEng($eng, $engver=NULL, $verop=NULL) {
            return $this->is(NULL, NULL, $eng, $engver, NULL, NULL, NULL, $verop);
        }

        /**
         * isOS() is used for comparing the operating system
         *
         * Compare the OS name and optionally the version number.
         * This method is a wrapper around the is() method
         *
         * @access public
         * @return bool TRUE if the application matches, FALSE otherwise
         * @param string $os the OS name to compare
         * @param string $osver optional param, the version number to compare.
         *                       Can be major version (e.g. 3), minor version (e.g. 0.2) or full version (e.g. 3.0.2)
         * @param string $verop optional param, the version comparison type. Defaults to =.
         *                      Can be any of <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne
         */
        public function isOS($os, $osver=NULL, $verop=NULL) {
            return $this->is(NULL, NULL, NULL, NULL, $os, $osver, NULL, $verop);
        }

        /**
         * is() is used for comparing all components of the user agent
         *
         * Compare the application, engine, OS and architecture. The PHP function
         * version_compare() is used for all version comparisons.
         *
         * @see http://www.php.net/version_compare
         * @access public
         * @return bool TRUE if the application matches, FALSE otherwise
         * @param string $app the application name to compare
         * @param string $appver optional param, the version number to compare.
         *                       Can be major version (e.g. 3), minor version (e.g. 0.2) or full version (e.g. 3.0.2)
         * @param string $eng optional param, the engine name to compare
         * @param string $engver optional param, the version number to compare.
         *                       Can be major version (e.g. 3), minor version (e.g. 0.2) or full version (e.g. 3.0.2)
         * @param string $os optional param, the OS name to compare
         * @param string $osver optional param, the version number to compare.
         *                       Can be major version (e.g. 3), minor version (e.g. 0.2) or full version (e.g. 3.0.2)
         * @param string $arch optional param, the system architecture
         * @param string $verop optional param, the version comparison type. Defaults to =.
         *                      Can be any of <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne
         */
        public function is($app=NULL, $appver=NULL, $eng=NULL, $engver=NULL, $os=NULL, $osver=NULL, $arch=NULL, $verop=NULL) {
            if (!in_array($verop, array('<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'))) $verop = '=';
            if ($app !== NULL && strtolower($app) != strtolower($this->app)) {
                return FALSE;
            }
            if ($appver !== NULL && !version_compare($this->appver, $appver, $verop) && !version_compare($this->appmajver, $appver, $verop) && !version_compare($this->appminver, $appver, $verop)) {
                return FALSE;
            }
            if ($eng !== NULL && strtolower($eng) != strtolower($this->eng)) {
                return FALSE;
            }
            if ($engver !== NULL && !version_compare($this->engver, $engver, $verop)) {
                return FALSE;
            }
            if ($os !== NULL && strtolower($os) != strtolower($this->os)) {
                return FALSE;
            }
            if ($osver !== NULL && !version_compare($this->osver, $osver, $verop)) {
                return FALSE;
            }
            if ($arch !== NULL && strtolower($arch) != strtolower($arch)) {
                return FALSE;
            }

            return TRUE;
        }
    }
