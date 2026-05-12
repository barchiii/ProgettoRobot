<?php
echo '<pre>';
echo 'PHP version: ' . PHP_VERSION . "\n";
echo 'php.ini path: ' . php_ini_loaded_file() . "\n";
echo 'Extra ini files: ' . php_ini_scanned_files() . "\n";
echo 'OS: ' . PHP_OS . "\n";
echo '</pre>';