<?php

    /**
     * The FLQURL class allows for easy construction and deconstruction of URLs
     *
     * @package core
     */
    class FLQURL
    {
        public $protocol;
        public $host;
        public $port;
        public $path;
        public $arguments;
        public $anchor;

        function __construct($url=NULL) {
            if ($url !== NULL) $this->set($url);
            if (!is_array($this->arguments)) $this->arguments = array();
        }

        public static function fromRequest() {
            $data = HTTP::requestInfo();
            $url = new URL();
            $url->protocol  = $data['scheme'];
            $url->host      = $data['host'];
            $url->port      = $data['port'];
            $url->path      = substr($_SERVER['REQUEST_URI'], 0, strcspn($_SERVER['REQUEST_URI'], '?'));
            $url->arguments = self::parseArgs($_SERVER['QUERY_STRING']);

            return $url;
        }

        protected function parse($url) {
            $parts = array(
                'protocol' => NULL,
                'host'     => NULL,
                'port'     => NULL,
                'path'     => NULL,
                'args'     => NULL,
                'anchor'   => NULL
            );
            if ($tmp = parse_url($url)) {
                if (isset($tmp['protocol'])) $parts['protocol'] = $tmp['protocol'];
                if (isset($tmp['host']))     $parts['host']     = $tmp['host'];
                if (isset($tmp['port']))     $parts['port']     = $tmp['port'];
                if (isset($tmp['path']))     $parts['path']     = $tmp['path'];
                if (isset($tmp['query']))    $parts['args']     = self::parseArgs($tmp['query']);
                if (isset($tmp['fragment'])) $parts['anchor']   = $tmp['fragment'];
            }

            return $parts;
        }

        public function set($url) {
            if ($parts = $this->parse($url)) {
                $this->protocol  = $parts['protocol'];
                $this->host      = $parts['host'];
                $this->port      = $parts['port'];
                $this->path      = $parts['path'];
                $this->arguments = $parts['args'];
                $this->anchor    = $parts['anchor'];
            }
        }

        public function addArgument($key, $val, $force=FALSE) {
            if (!isset($this->arguments[$key]) || $force) {
                $this->arguments[$key] = $val;

                return TRUE;
            }

            return FALSE;
        }

        public function delArgument($key) {
            if (isset($this->arguments[$key])) {
                unset($this->arguments[$key]);

                return TRUE;
            }

            return FALSE;
        }

        public static function argsToString(&$args) {
            return http_build_query($args);
        }

        public function parseArgs($str) {
            $args = array();
            parse_str($str, $args);

            return $args;
        }

        public function isThisHost() {
            $host = HTTP::requestInfo();
            if (($host['host'] == $this->host && $host['port'] == $this->port) || $this->host === NULL) {
                if ($host['scheme'] == $this->protocol || $this->protocol === NULL) {
                    return TRUE;
                }
            }

            return FALSE;
        }

        public function toAbsoluteString() {
            $url = $this->protocol . '://' . $this->host;
            if ($this->protocol == 'http' && $this->port != 80 || $this->protocol == 'https' && $this->port != 443) {
                $url.= ':' . $this->port;
            }
            $url.= $this->toRelativeString();

            return $url;
        }

        public function toRelativeString() {
            $url = $this->path;
            $queryString = self::argsToString($this->arguments);
            if ($queryString != '') $url.= '?' . $queryString;
            if ($this->anchor) $url.= $this->anchor;

            return $url;
        }

        public function toString() {
            return ($this->isThisHost() ? $this->toRelativeString() : $this->toAbsoluteString());
        }

        public function redirect() {
            $url = $this->toString();
            header("Location: {$url}");
            exit;
        }
    }
