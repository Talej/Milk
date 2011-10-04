<?php

    /**
     * This is the bare minimum that is required in this file to get Milk up and running.
     * You may also want to load additional files/libraries or implement your URL
     * rewriting etc here (for example to map /myfile.html to the myfile module)
     */

    define('MILK_PATH', dirname(__FILE__));
    include(MILK_PATH . '/milk/milk.php');

    MilkLauncher::loadConfig();
    MilkLauncher::load(MILK_BASE_DIR, 'util', 'compat.php');
    MilkLauncher::load(MILK_BASE_DIR, 'util', 'tools.php');
    MilkLauncher::load(MILK_BASE_DIR, 'util', 'useragent.php');
    MilkLauncher::http_virtualise();

    if (preg_match('/milk(js|css)\/([a-f0-9]{32})\.(js|css)/', $_SERVER['PHP_SELF'], $m)) {
        MilkLauncher::load(MILK_BASE_DIR, 'util', 'tools.php');
        MilkLauncher::load(MILK_BASE_DIR, 'util', 'compress.php');

        $compress = new FLQCompress($m[1]);
        if (!$compress->output($m[2])) {
            // throw an error 404 or similar
        }
        exit;
    } else {
        $milk = new MilkLauncher('Home');
        $milk->module->run();
    }
