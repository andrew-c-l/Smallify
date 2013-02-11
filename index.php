<?php
/**
 * Global settings - Individual config. Just nice to have in a config file.
 */
define("DEBUG", true);

if(DEBUG) {
    // set debug settings
    ini_set("display_errors", "1");
    // Report all PHP errors (see changelog)
    error_reporting(E_ALL);
    ini_set('error_reporting', E_ALL);
} else {
    // deactivate error messages, debug info etc..
    // set debug settings
    ini_set("display_errors", "0");
    // Report all PHP errors (see changelog)
    error_reporting(0);
    ini_set('error_reporting', 0);
}


function buffer_include($file)
{
	ob_start();
	include $file;
	return ob_get_clean();
}

require_once('lib/Smallify.php');
$smallify = Smallify::getInstance();
$smallify->setCacheDir("cache/");

//$html_output = buffer_include('content.php');

//echo $html_output;
//echo $smallify->minify_html($html_output);
echo $smallify->shrink('content.php');
?>

<!-- 
<?php echo "Memory usage: " . (memory_get_usage(true) / 1024 / 1024) . " MB\r\n";  ?>
<?php echo "Peak memory usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB\r\n";  ?>
-->