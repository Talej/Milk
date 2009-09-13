<?php

    /**
     * This is the bare minimum that is required in this file to get Milk up and running.
     * You may also want to load additional files/libraries or implement your URL
     * rewriting etc here (for example to map /myfile.html to the myfile module)
     */

    define('MILK_PATH', dirname($_SERVER['SCRIPT_FILENAME']));
    include(MILK_PATH . '/milk/milk.php');

    MilkLauncher::loadConfig();

    $milk = new MilkLauncher('Home');
    $milk->module->run();
