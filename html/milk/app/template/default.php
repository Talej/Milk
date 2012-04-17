<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0; user-scalable=0.0;">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?php print flqentities($ctrl->title); ?></title>
    <?php print $this->includes(); ?>
</head>
<body onload="load()">
<?php print $this->get('xhtml'); ?>
</body>
</html>

