<?php

    class MilkTools {

        static public function ifNull($arg1, $arg2) {
            $args = func_get_args();
            foreach ($args as $arg) {
                if ($arg !== NULL) return $arg;
            }

            return NULL;
        }

        static public function ifDef($const, $default) {
            assert('is_string($const)');

            if (define($const)) return constant($const);

            return $default;
        }

        static public function mkPath($file) {
            return MilkLauncher::mkPath(func_get_args());
        }
    }
