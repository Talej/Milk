<?php

    /**
     * This library provides caching, compression (minification and gzip encoding)
     * and concatination functionality for javascript and css files
     *
     * $Id$
     *
     * @package core
     * @author Michael Little <michael@fliquidstudios.com>
     * @version 1.0
     */

    define('FLQCOMPRESS_TYPE_JS',   'js');
    define('FLQCOMPRESS_TYPE_CSS', 'css');

    define('FLQCOMPRESS_CACHEDIR', MilkTools::mkPath(MILK_APP_DIR, 'cache'));

    /**
     * The FLQCompress class compresses, concatinates and caches Javascript and CSS
     * files and then serves them either gzip encoded or unencoded based on the
     * user agent and accepted encodings
     *
     * This class uses the JSMin and Smart Optimizer CSS libraries for minification
     *
     * @package core
     */
    class FLQCompress {
        protected $type;
        protected $files = array();

        /**
         * Constructor method for FLQCompress class
         *
         * @access public
         * @param string $type A valid FLQCompress type (FLQCOMPRESS_TYPE_JS, FLQCOMPRESS_TYPE_CSS)
         * @param mixed $files an array or string of files
         */
        function __construct($type, $files=NULL) {
            $this->type = $type;
            if (is_array($files)) {
                $this->files = $files;
            } else if (is_string($files)) {
                $this->files[] = $files;
            }
        }

        /**
         * addFile() is used to add a new file to the list of compression files
         *
         * @access public
         * @return void
         * @param string $file the file path to add
         */
        public function addFile($file) {
            $this->files[] = $file;
        }

        /**
         * getHash() generates a unique hash from the list of compression files
         *
         * The hash generated is used to uniquely identify collections of files
         * and create/load/refresh the cache files
         *
         * @access protected
         * @return string the hash string generated
         */
        protected function getHash() {
            return md5(implode('', $this->files));
        }

        /**
         * cacheExists() checks if a cache file for a hash exists
         *
         * @access public
         * @return mixed the cache file path on success, FALSE if no cache exists
         * @param string $hash optional param, the hash string. If not specified the
         *                     library attempts to find one based on the files
         * @param bool $gz optional param, whether to check for a gzipped cache file
         */
        public function cacheExists($hash=NULL, $gz=FALSE) {
            if ($hash === NULL) $hash = $this->getHash();
            $cachefile = $this->cacheFile($hash) . ($gz ? '.gz' : '');

            if (file_exists($cachefile) && is_readable($cachefile)) {
                return $cachefile;
            }

            return FALSE;
        }

        /**
         * cacheFile() gets the path of a cache file using a hash
         *
         * @access public
         * @return string the cache file path
         * @param string $hash the hash string to find cache for
         */
        public function cacheFile($hash) {
            return MilkTools::mkPath(FLQCOMPRESS_CACHEDIR, $hash . '.' . $this->type);
        }

        /**
         * webPath() gets the web path of a cache file using a hash
         *
         * @access public
         * @return string the web file path
         * @param string $hash the hash string to get the path for
         */
        public function webPath($hash) {
            return '/milk' . $this->type . '/' . $hash . '.' . $this->type;
        }

        /**
         * compress() generates a new cache for the specified files
         *
         * The files will be run through JSMin or CSS Compress for minification,
         * they will then be concatted into a single file and a gzipped cache version
         * will be generated
         *
         * @access public
         * @return the web path to the cache file on success, FALSE on failure
         */
        public function compress() {
            $hash = $this->getHash();
            $error = FALSE;
            switch ($this->type) {
                case FLQCOMPRESS_TYPE_JS:
                    include_once(MilkTools::mkPath(MILK_BASE_DIR, 'dep', 'jsmin-1.1.1.php'));
                    $cb = array('JSMin', 'minify');
                    break;

                case FLQCOMPRESS_TYPE_CSS:
                    include_once(MilkTools::mkPath(MILK_BASE_DIR, 'dep', 'css_compress.php'));
                    $cb = 'css_compress';
                    break;

                default:
                    trigger_error('FLQCompress::compress() - Unknown file type to compress', E_USER_ERROR);
            }

            $cachefile = $this->cacheFile($hash);
            if ($fp = fopen($cachefile, 'w+')) {
                $gzdata = '';
                foreach ($this->files as $file) {
                    $webfile = MilkTools::mkPath($_SERVER['DOCUMENT_ROOT'], $file);
                    if (file_exists($webfile) && is_readable($webfile)) {
                        if ($data = call_user_func($cb, file_get_contents($webfile))) {
                            $gzdata.= $data;

                            if (!fwrite($fp, $data)) {
                                $error = TRUE;
                                break;
                            }
                        }
                    }
                }
                fclose($fp);

                if (!$error && !file_put_contents($cachefile . '.gz', gzencode($gzdata))) {
                    $error = TRUE;
                }
            }

            if ($error) {
                @unlink($cachefile);
                @unlink($cachefile . '.gz');
            } else {
                return $this->webPath($hash);
            }

            return FALSE;
        }

        /**
         * hasChangedSince() uses the modified time of files to check if they have changed
         *
         * @access public
         * @return bool TRUE if any of the files have changes, FALSE otherwise
         * @param int $time a timestamp to check the files against
         */
        public function hasChangedSince($time) {
            foreach ($this->files as $file) {
                $webfile = MilkTools::mkPath($_SERVER['DOCUMENT_ROOT'], $file);
                if (!is_readable($webfile) || filemtime($webfile) > $time) {
                    return TRUE;
                }
            }

            return FALSE;
        }

        /**
         * output() sets all appropriate headers and outputs compressed content
         *
         * If no cache exists or the cache is expired a new cache will be generated.
         * All Content-Type and Cache related headers are set in here. gzip compressed
         * files are output if the browser supports them
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param string $hash the hash to output cached content for
         */
        public function output($hash) {
            if ($file = $this->cacheExists($hash)) {
                $type = substr($file, strrpos($file, '.')+1);
                $mtime = filemtime($file);
                $expires = MilkTools::ifDef('CFG_CACHE_EXPIRE', 86400*7); // default 7 days

                ini_set('zlib.output_compression', 'Off');
                HTTP::setStatus(200);
                HTTP::setIMS($mtime);
                header('Pragma:');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
                header('Cache-Control: max-age=' . $expires . ', private');
                switch ($type) {
                    case FLQCOMPRESS_TYPE_JS:  header('Content-Type: text/javascript'); break;
                    case FLQCOMPRESS_TYPE_CSS: header('Content-Type: text/css');        break;
                    default:
                        trigger_error('FLQCompress::output() - Unable to find Content-Type for ' . $type, E_USER_ERROR);
                }

                if (
                    stristr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') &&
                    (!strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0') || strstr($_SERVER['HTTP_USER_AGENT'], 'SV1')) &&
                    file_exists($file . '.gz')
                ) {
                    header("Content-Encoding: gzip");
                    print file_get_contents($file . '.gz');
                    return TRUE;
                } else {
                    print file_get_contents($file);
                    return TRUE;
                }
            }

            return FALSE;
        }

        /**
         * exec() determines if cache files are valid and regenerates cache if they are not
         *
         * @access public
         * @return string the cache file web path
         */
        public function exec() {
            if (($hash = $this->getHash()) && ($file = $this->cacheExists())) {
                $mtime = filemtime($file);
                if (!$this->hasChangedSince($mtime)) {
                    return $this->webPath($hash);
                } else {
                    @unlink($file);
                }
            }

            // generate new cache
            return $this->compress();
        }
    }
