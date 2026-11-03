<?php
header("Content-Type: text/plain");

echo "Loaded php.ini: " . php_ini_loaded_file() . PHP_EOL;
echo "upload_max_filesize: " . ini_get("upload_max_filesize") . PHP_EOL;
echo "post_max_size: " . ini_get("post_max_size") . PHP_EOL;
echo "max_file_uploads: " . ini_get("max_file_uploads") . PHP_EOL;