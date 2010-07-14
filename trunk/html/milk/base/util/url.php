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
            $data = self::requestInfo();
            $url = new FLQURL();
            $url->protocol  = $data['scheme'];
            $url->host      = $data['host'];
            $url->port      = $data['port'];
            $url->path      = substr(@$_SERVER['REQUEST_URI'], 0, strcspn(@$_SERVER['REQUEST_URI'], '?'));
            $url->arguments = self::parseArgs(@$_SERVER['QUERY_STRING']);

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
                $req = self::fromRequest();
                $parts['protocol'] = (isset($tmp['protocol']) ? $tmp['protocol'] : $req->protocol);
                $parts['host']     = (isset($tmp['host']) ? $tmp['host'] : $req->host);
                $parts['port']     = (isset($tmp['port']) ? $tmp['port'] : $req->port);
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
            $host = self::requestInfo();
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
            $url = $this->toAbsoluteString();
            header("Location: {$url}");
            exit;
        }

        static public function requestInfo() {
            $info = array(0 => NULL, 1 => NULL, 2 => NULL, 'scheme' => NULL, 'host' => NULL, 'port' => NULL);
            $forwarded_host = (array_key_exists('HTTP_X_FORWARDED_HOST', $_SERVER) ? substr($_SERVER['HTTP_X_FORWARDED_HOST'], 0, ifnot(strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ','),strlen($_SERVER['HTTP_X_FORWARDED_HOST']))) : '');

            // get the host
            if (preg_match('/^([^:]+)/i', $forwarded_host, $match)) {
                $info['host'] = $match[1];
                $info[1]      = $match[1];
            } else if (preg_match('/^([^:]+)/i', $_SERVER['HTTP_HOST'], $match)) {
                $info['host'] = $match[1];
                $info[1]      = $match[1];
            }

            // get the port
            if (preg_match('/:([0-9]+)$/', $forwarded_host, $match)) {
                $info['port'] = $match[1];
                $info[2]      = $match[1];
            } else if (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) && preg_match('/^https/i', $_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $info['port'] = 443;
                $info[2]      = 443;
            } else if (preg_match('/:([0-9]+)$/', $_SERVER['HTTP_HOST'], $match)) {
                $info['port'] = $match[1];
                $info[2]      = $match[1];
            } else if (array_key_exists('SERVER_PORT', $_SERVER)) {
                $info['port'] = $_SERVER['SERVER_PORT'];
                $info[2]      = $_SERVER['SERVER_PORT'];
            }

            // get the scheme
            if (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) && preg_match('/^https?/i', $_SERVER['HTTP_X_FORWARDED_PROTO'], $match)) {
                $info['scheme'] = strtolower($match[0]);
                $info[0]        = strtolower($match[0]);
            } else if ((array_key_exists('SSL', $_ENV) && strtobool($_ENV['SSL'])) || (array_key_exists('HTTPS', $_ENV) && strtobool($_ENV['HTTPS']))) {
                $info['scheme'] = 'https';
                $info[0]        = 'https';
            } else if ($info['port'] == 443) { // If port is 443 assume SSL
                $info['scheme'] = 'https';
                $info[0]        = 'https';
            } else {
                $info['scheme'] = 'http';
                $info[0]        = 'http';
            }

            return $info;
        }
    }
    
