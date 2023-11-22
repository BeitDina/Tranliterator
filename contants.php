<?php
/**
*
* @package Transliterator
* @version $Id: constants.php,v 1.1.1 2023/10/18 12:21:14 orynider Exp $
*
*/

//Acces check
if (!defined('IN_PORTAL') && (strpos($_SERVER['PHP_SELF'], "unit_test.php") <= 0)) { die("Direct acces not allowed! This file was accesed: ".$_SERVER['PHP_SELF']."."); }

// Include common scripts.
date_default_timezone_set('Asia/Jerusalem'); // We have to set something or else PHP will complain.
/** The time when the script began to be executed. */
define('BEGIN_TIME', microtime(true));
//Definitions
define('TRANS_VERSION', "v.1.0.2"); // version...
define('SPACE', 'SPACE'); //&nbsp;

?>
