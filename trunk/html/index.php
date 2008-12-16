<?php


    include($_SERVER['DOCUMENT_ROOT'] . '/milk/milk.php');

    $milk = new MilkLauncher('Home');
    $milk->module->run();