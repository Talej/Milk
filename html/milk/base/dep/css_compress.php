<?php

    function css_compress($buffer) {
        preg_match_all('/@import url\([\'"]?[^\)\'\"]+[\'"]?\);/', $buffer, $m, PREG_SET_ORDER);
        $prepend = '';
        foreach ($m as $import) {
            $prepend.= $import[0];
            $buffer = str_replace($import[0], '', $buffer);
        }
        $buffer = $prepend . $buffer;

        $buffer = str_replace('; ',';',str_replace(' }','}',str_replace('{ ','{',str_replace(array("\r\n","\r","\n","\t",'  ','    ','    '),"",preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!','',$buffer)))));

        return $buffer;
    }

