<?php
$filename = "index.php";
$output = [];
$return = 0;

exec("php -l " . $filename, $output, $return);

echo "<pre>";
echo implode("\n", $output);
echo "\nReturn code: " . $return;
echo "</pre>";
