<?php
/**
*
* @package Transliterator
* @version $Id: trans.php,v 1.0.6 2024/04/15 02:58:12 orynider Exp $
*
*/

//Acces check
if (!defined('IN_PORTAL') && (strpos($_SERVER['PHP_SELF'], "unit_test.php") <= 0)) { die("Direct acces not allowed! This file was accesed: ".$_SERVER['PHP_SELF']."."); }

//Constants
include($root_path . 'contants.' . $phpExt);

// new trup code
class tree_node
{
	var $left = NULL;
	var $right = NULL;

	var $begin_offset;
	var $end_offset;
	
	function __construct($begin, $end)
	{
		$this->begin_offset = $begin;
		$this->end_offset = $end;
	}
	function print_offset()
	{
		print $this->begin_offset;
		if (is_null($this->begin_offset))
		{	
			print "hello";
		}
	}

	function print_trup_tree()
	{
		global $t_without_trup;
		
		for ($i = $this->begin_offset; $i <= $this->end_offset; $i++)
		{
			if ($i != $this->begin_offset) // skip the first time
			{	
				print '.';
				print $t_without_trup[$i];
			}
		}
		
		if (!is_null($this->left) )
		{
			print "[";
			$this->left->print_trup_tree();
			print "]";
		}
		
		if (!is_null($this->right) )
		{
			print "[";
			$this->right->print_trup_tree();
			print "]";
		}
	}

	function generate_trup_tree()
	{
		global $trup;
		
		if (is_array($trup)) // whole pasuk
		{
			if ($trup[$this->end_offset] == "SILLUK")
			{
				// search for etnachta, if there is one
				$i = $this->end_offset - 1;
				while ($i >= 0)
				{
					if ($trup[$i] == "ETNACHTA")
					{
						$pos = $i;
						break;
					}
					else if ($trup[$i] == "ZAKEF_KATON" || $trup[$i] == "ZAKEF_GADOL" || $trup[$i] == "TIPCHA")
					{
						$pos = $i;
					}
					$i--;
				} // end while

				// now, $pos contains position of major dichotomy
				$this->left = new tree_node(0, $pos);
				$this->right= new tree_node($pos + 1, $this->end_offset);

				$this->left->generate_trup_tree();
				$this->right->generate_trup_tree();
			}
			else if ($trup[$this->end_offset] == "SILLUK" || $trup[$this->end_offset] == "ETNACHTA") // further division of silluk, or of etnachta
			{
				$i = $this->end_offset - 1;
				$pos = -1;
				while ($i >= $this->begin_offset)
				{
					// this will get us the earliest subdividing trup within this segment
					if ($trup[$i] == "ZAKEF_KATON" || $trup[$i] == "ZAKEF_GADOL" || $trup[$i] == "TIPCHA" || $trup[$i] == "SEGOLTA")
					{
						$pos = $i;
					}
					$i--;
				} 
				// end while
				if ($pos != -1) // we found some disjunctive accent
				{
					$this->left = new tree_node($this->begin_offset, $pos);
					$this->right= new tree_node($pos + 1, $this->end_offset);

					$this->left->generate_trup_tree();
					$this->right->generate_trup_tree();
				}
			}
			else if ($trup[$this->end_offset] == "ZAKEF_KATON")
			{
				$i = $this->end_offset - 1;
				$pos = -1;
				
				while ($i >= $this->begin_offset)
				{
					// this will get us the earliest subdividing trup within this segment
					if ($trup[$i] == "PASHTA" || $trup[$i] == "YETIV" || $trup[$i] == "REVII")
					{
						$pos = $i;
					}
					$i--;
				} 
				// end while
				if ($pos != -1) // we found some disjunctive accent
				{
					$this->left = new tree_node($this->begin_offset, $pos);
					$this->right= new tree_node($pos + 1, $this->end_offset);

					$this->left->generate_trup_tree();
					$this->right->generate_trup_tree();
				}
			}
		}
	}
}
 
/**
* @param string $file The filename to read the data from
*/
function read_file($filename, $line_num = false, $prev = 10, $next = 10, $sourcelang = 'hebrew')
{
    if (!is_file($filename)) 
	{
        return trigger_error("Error wile read_file() reads file `$filename`", E_USER_ERROR);
        return false;
    }	
	
	$contents = @file($filename);
	if ($contents === false)
	{
		trigger_error('Error reading file contents for <em>' . $filename . '</em>', E_USER_ERROR);
	}
    $content = false;
	/* */
	ob_start();
	highlight_file($filename);
	$content = ob_get_contents();
	ob_end_clean();	
	
	$lines  = explode("<br />", $content);
	$count = count($lines);	
	/* */
	if ($content === false)
	{
		$content = print_r($contents, true);
		$lines  = explode("\r\n", $content);
		$count = count($lines);
		
		$origfile = array();
		foreach ($contents as $lines => $line)
		{
			if (preg_match('@^(//|<\?|\?>|/\*|\*/|#)@', rtrim($line, "\r\n")))
			{
				$origfile[$lines] = $line;
			}
			$portion = explode("\t", $line, 2);
						
		}
		$last = $count - 1;
		$content = preg_replace('<Array>', '', $content);
		for($line = 0, $lines; $line < $count; $line++)		
		{		
			$content = str_replace('['.$line.']', '', $content);
		}
 		$content = str_replace('=>', '', $content);
		$content = str_replace('(', '', $content);
		$content = str_replace(')', '', $content);		
		$content = preg_replace('/\r\n/', '', $content);
	}
	else
	{	
		$last = $count - 1;
		$content = str_replace('<code>', '', $content);
		$content = str_replace('<span style="color: #000000">', '', $content);
		$content = str_replace("<br />", "\r\n", $content);
		$content = str_replace('</span>', '', $content);
		$content = str_replace('</code>', '', $content);
		$content = preg_replace('/\r\n/', '', $content);
	}
	$content = FileContentToUnicode($content, $sourcelang);
	return $content;
}

/*
* This explode_split()  is a function as explode() built function but on arrays of delimitors.
* explode() will now throw ValueError when separator parameter is given an empty string (""). Previously, explode() returned false instead. 
* A ValueError is thrown when the type of an argument is correct but the value of it is incorrect in PHP 8+ or later. 
*/      
function explode_split($delimiters = null, $input = "") 
{
		if($delimiters === null || !is_array($delimiters)) 
		{
			$delimiters = array("SPACE", $delimiters);
		}
		
		$query = "";
		
		foreach($delimiters as $delimiter) 
		{
			$query .= preg_quote($delimiter) . "SPACE";
		}
		$query = rtrim($query, "SPACE");
		if($query != "") 
		{
			$query = "(".$query.")";
			print $query . "\n";
			return preg_split("/(".$query.")/", $input);
			// $output = preg_split("/(@|vs)/", $input);
			// return $output;
		}
		return $input;
}

/**
*
*/
function ereg_repl($pattern, $replacement, $string) 
{ 
	return preg_replace('/'.$pattern.'/', $replacement, $string); 
}

/**
*
*/
function data_repl($data, $candidates = null, $replacements = null)
{		
	if($candidates === null || !is_array($candidates)) 
	{
		$candidates = array(ALEPH, BET, BHET, GIMEL, DALED, VAV, HOLAM_VAV, ZED, TET, YUD, KAF, KHAF_SOFIT, LAMED, MEM, HOLAM_MEM, NUN, SAMECH, PEI, TZADI, KUF, SHIN, SIN, TAV);
	}
	if($replacements === null || !is_array($replacements)) 
	{
		$replacements = array(ALEPH, BET, BHET, GIMEL, DALED, VAV, HOLAM_VAV, ZED, TET, YUD, KAF, KHAF_SOFIT, LAMED, MEM, HOLAM_MEM, NUN, SAMECH, PEI, TZADI, KUF, SHIN, SIN, TAV);
	}	
	$data = str_replace($candidates, $replacements, $data);	
	
		
	return $data;
} 
	
//Funtion redefinition ends
function mesagehandler($msg_title, $msg_text = 'Error', $l_notify = 'Generated by PHP.', $l_return_index = "index.php") 
{ 
	global $root_path;
	
	$l_return_index = '<a title="Return to index page" href="' . $root_path . $l_return_index.'">Return to index page</a>';
	
	// Try to not call the adm page data...
	print '<!DOCTYPE html />';
	print '<html dir="ltr">';
	print '<head><meta charset="UTF-8" />';
	print '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';
	print '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
	print '<meta name="apple-mobile-web-app-capable" content="yes" />';
	print '<meta name="apple-mobile-web-app-status-bar-style" content="blue" />';
	print '<title>' . $msg_title . '</title>';
	print '<style type="text/css">{ margin: 0; padding: 0; } html { font-size: 100%; height: 100%; margin-bottom: 1px; background-color: #e4edf0; } body { font-family: -apple-system, BlinkMacSystemFont, Roboto, "Lucida Grande", "Segoe UI", Arial, Helvetica, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; color: #536482; background: #e4edf0; font-size: 84.5%; margin: 0; } ';
	print 'a:link, a:active, a:visited { color: #006688; text-decoration: none; } a:hover { color: #dd6900; text-decoration: underline; } ';
	print '#wrap { padding: 0 20px 15px 20px; min-width: 615px; } #page-header { text-align: right; height: 40px; } #page-footer { clear: both; font-size: 1em; text-align: center; } ';
	print '.panel { margin: 4px 0; background-color: #ffffff; border: solid 1px  #a9b8c2; } ';
	print '#errorpage #page-header a { font-weight: bold; line-height: 6em; } #errorpage #content { padding: 10px; } #errorpage #content h1 { line-height: 1.2em; margin-bottom: 0; color: #df075c; } ';
	print '#errorpage #page-footer #content div { margin-top: 20px; margin-bottom: 5px; border-bottom: 1px solid #cdcdcd; padding-bottom: 5px; color: #434343; font: bold 1.2em; font-family: -apple-system, BlinkMacSystemFont, Roboto, "Lucida Grande", "Segoe UI", Arial, Helvetica, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; text-decoration: none; line-height: 120%; text-align: left; } \n';
	print '</style>';
	print '<!-- Load template *.css definition located in same folder -->';
	print '<link rel="stylesheet" href="'.$root_path.'index.css" type="text/css" />';
	print '</head>';
	print '<body id="page">';
	print '<div id="wrap">';
	print '	<div id="page-header">'. $l_return_index .'</div>';	
	print '	<div id="page-body">';
	print '	<div class="panel">';
	print '		<div id="content">';
	print '			<h1>' . $msg_title . '</h1>';
	print '			<div id="text">' . $msg_text . '</div>';
	print '			<div id="notify">' . $l_notify . '</div>';
	print '		</div>';
	print '	</div>';
	print '	</div>';
	print '</div>';
	print '	<div id="page-footer">Powered by <a href="https://github.com/beitdina/">Beit Dina Institute</a>';
	print '	</div>';
	print '</body>';
	print '</html>';
	
	// On fatal error E_USER_ERROR shoud stop the execution or exit the function.
	exit;
}

/**
* Error and message handler, call with trigger_error if reqd
*/
function errorhandler($err_no, $msg_text, $err_file = __FILE__, $err_line = __LINE__)
{
	global $phpExt, $root_path;
	
	// Do not send 200 OK, but service unavailable on errors
	switch($err_no)
	{ 
		case 0:				
			$l_notify = "Unknown PHP Error";
		break;	
		case 1: 				
			$l_notify = "E_ERROR (int) 	Fatal run-time errors. These indicate errors that can not be recovered from, such as a memory allocation problem. Execution of the script is halted.";
		break;
		case 2:				
			$l_notify = "E_WARNING (int) 	Run-time warnings (non-fatal errors). Execution of the script is not halted.";
		break;
		case 4:				
			$l_notify = "E_PARSE (int) 	Compile-time parse errors. Parse errors should only be generated by the parser.";
		break;
		case 8:				
			$l_notify = "E_NOTICE (int) 	Run-time notices. Indicate that the script encountered something that could indicate an error, but could also happen in the normal course of running a script.";
		break;
		case 16: 			
			$l_notify = "E_CORE_ERROR (int) 	Fatal errors that occur during PHP's initial startup. This is like an E_ERROR, except it is generated by the core of PHP.";
		break;
		case 32:			
			$l_notify = "E_CORE_WARNING (int) 	Warnings (non-fatal errors) that occur during PHP's initial startup. This is like an E_WARNING, except it is generated by the core of PHP.";
		break;
		case 64:			
			$l_notify = "E_COMPILE_ERROR (int) 	Fatal compile-time errors. This is like an E_ERROR, except it is generated by the Zend Scripting Engine.";
		break;
		case 128: 		
			$l_notify = "E_COMPILE_WARNING (int) 	Compile-time warnings (non-fatal errors). This is like an E_WARNING, except it is generated by the Zend Scripting Engine.";
		break;
		case 256:		
			$l_notify = "E_USER_ERROR (int) 	User-generated error message. This is like an E_ERROR, except it is generated in PHP code by using the PHP function trigger_error().";
		break;
		case 512:		
			$l_notify = "E_USER_WARNING (int) 	User-generated warning message. This is like an E_WARNING, except it is generated in PHP code by using the PHP function trigger_error().";
		break;
		case 1024: 		
			$l_notify = "E_USER_NOTICE (int) 	User-generated notice message. This is like an E_NOTICE, except it is generated in PHP code by using the PHP function trigger_error().";
		break;
		case 2048:		
			$l_notify = "E_STRICT (int) 	Enable to have PHP suggest changes to your code which will ensure the best interoperability and forward compatibility of your code.";
		break;
		case 4096:		
			$l_notify = "E_RECOVERABLE_ERROR (int) 	Catchable fatal error. It indicates that a probably dangerous error occurred, but did not leave the Engine in an unstable state. If the error is not caught by a user defined handle (see also set_error_handler()), the application aborts as it was an E_ERROR. ";
		break;
		case 8192:		
			$l_notify = "E_DEPRECATED (int) 	Run-time notices. Enable this to receive warnings about code that will not work in future versions.";
		break;
		case 16384: 	
			$l_notify = "E_USER_DEPRECATED (int) 	User-generated warning message. This is like an E_DEPRECATED, except it is generated in PHP code by using the PHP function trigger_error().";
		break;
		case 32767:	
			$l_notify = "E_ALL (int) 	All errors, warnings, and notices.";
		break;
		default:
			$l_notify = 'Service Unavailable';
		break;	
	}
	
	switch ($err_no)
	{
		case E_NOTICE:
		case E_WARNING:			
			print "\t". $l_notify .': '. $msg_text . " in file: " . basename($err_file) . " on line: " . $err_line . "\n";			
		break;
		
		case E_USER_ERROR:						
			$msg_title = 'User Error';			
			$l_return_index = '<a href="' . $root_path . '">Return to index page</a>';			
			
			//garbage_collection();			
			print '<!DOCTYPE html>';
			print '<html dir="ltr">';
			print '<head><meta charset="UTF-8" />';
			print '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';
			print '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
			print '<meta name="apple-mobile-web-app-capable" content="yes" />';
			print '<meta name="apple-mobile-web-app-status-bar-style" content="blue" />';
			print '<title>' . $msg_title . '</title>';
			print '<style type="text/css">{ margin: 0; padding: 0; } html { font-size: 100%; height: 100%; margin-bottom: 1px; background-color: #e4edf0; } body { font-family: -apple-system, BlinkMacSystemFont, Roboto, "Lucida Grande", "Segoe UI", Arial, Helvetica, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; color: #536482; background: #e4edf0; font-size: 62.5%; margin: 0; } ';
			print 'a:link, a:active, a:visited { color: #006688; text-decoration: none; } a:hover { color: #DD6900; text-decoration: underline; } ';
			print '#wrap { padding: 0 20px 15px 20px; min-width: 615px; } #page-header { text-align: right; height: 40px; } #page-footer { clear: both; font-size: 1em; text-align: center; } ';
			print '.panel { margin: 4px 0; background-color: #fefefe; border: solid 1px  #a9b8c2; } ';
			print '#errorpage #page-header a { font-weight: bold; line-height: 6em; } #errorpage #content { padding: 10px; } #errorpage #content h1 { line-height: 1.2em; margin-bottom: 0; color: #df075c; } ';
			print '#errorpage #content div { margin-top: 20px; margin-bottom: 5px; border-bottom: 1px solid #cdcdcd; padding-bottom: 5px; color: #434343; font: bold 1.2em -apple-system, BlinkMacSystemFont, Roboto, "Lucida Grande", "Segoe UI", Arial, Helvetica, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; text-decoration: none; line-height: 120%; text-align: left; } \n';
			print '</style>';
			print '<!-- Load template *.css definition located in same folder -->';
			print '<link rel="stylesheet" href="'.$root_path.'index.css" type="text/css" />';
			print '</head>';
			print '<body id="page">';
			print '<div id="wrap">';
			print '	<div id="page-header"><a title="'.$l_return_index.'"'.' href="#"> @ '.$l_return_index.'</a></div>';	
			print '	<div id="page-body">';
			print '	<div class="panel">';
			print '		<div id="content">';
			print '			<h1>' . $msg_title . '</h1>';
			print '			<div>' . $msg_text . '</div>';
			print $l_notify;
			print '		</div>';
			print '	</div>';
			print '	</div>';
			print '	<div id="page-footer">Powered by <a href="https://github.com/beitdina/">Beit Dina Institute</a>';
			print '	</div>';
			print '</div>';
			print '</body>';
			print '</html>';
			
			// force an exit here.
			exit;
			
			// Call admin page data...
		break;
		
		case E_USER_WARNING:
		case E_USER_NOTICE:			
			define('IN_ERROR_HANDLER', true);			
			
			$msg_title = 'User Notice';
			$l_return_index = '<a href="' . $root_path . '">Return to index page</a>';
			
			print "<html>\n<body>\n" . $msg_title . "\n<br /><br />\n" . $msg_text . "\n<br /><br />\n" . $l_return_index . "</body>\n</html>";
		break;
	}

	// If we notice an error not handled here we pass this back to PHP by returning false
	// This may not work for all php versions
	return false;
}

/*
* Funtions Redefinitions for PHP 7+ and 8+ and 9+
*/
if (!function_exists('ereg'))           		  { function ereg($pattern, $subject, &$matches = []) { return preg_match('/'.$pattern.'/', $subject, $matches); } }
if (!function_exists('eregi'))           	  { function eregi($pattern, $subject, &$matches = []) { return preg_match('/'.$pattern.'/i', $subject, $matches); } }
if (!function_exists('ereg_replace'))   { function ereg_replace($pattern, $replacement, $string) { return preg_replace('/'.$pattern.'/', $replacement, $string); } }
if (!function_exists('eregi_replace'))  { function eregi_replace($pattern, $replacement, $string) { return preg_replace('/'.$pattern.'/i', $replacement, $string); } }
if (!function_exists('split'))          		  { function split($pattern, $subject, $limit = -1) { return preg_split('/'.$pattern.'/', $subject, $limit); } }
if (!function_exists('spliti'))         		  { function spliti($pattern, $subject, $limit = -1) { return preg_split('/'.$pattern.'/i', $subject, $limit); } }
//if (!function_exists('explode')) 			  { function explode($delimiters = null, $input = "") { return explode_split($delimiters, $input); } }

/*
* jlibUG AND ERROR HANDLING
*/
set_error_handler('errorhandler');
set_exception_handler("mesagehandler");
// end new trup code

$isOpera = 0;
$isFirefox = 0;
$origHebrew = "";

$l_about_title = 'About Transliterator';
$l_about_desc = 'Transliterator is a mechanism offered as-is to support customers for the purpose of transliterating from Hebrew Alphabet into other alphabets. Was started by <a href="https://github.com/joshwaxman/transliterate">Joshua Waxman</a> in 2006.';
$l_notify = ' # Our Security Policy
<br />
To report a security issue, please check <a href="https://github.com/BeitDina/Transliterator/issues">issues</a> and post at  <a href="https://github.com/BeitDina/Transliterator/issues/new">new issue</a>.
<br />
The Beit Dina IT and Security Unit will respond within 5 working days of your reported issues.
<br />
We use GitHub Security Advisory to privately discuss and fix the issue.
<br />
You can read at <a href="https://github.com/BeitDina/Transliterator/">github.com/beitdina/Transliterator</a> more about it.
<br />
Working on PHP '. PHP_VERSION .' on '. PHP_OS .'.';

//
// Show copyrights
//
if (isset($_REQUEST['copy']))
{
	mesagehandler($l_about_title, $l_about_desc, $l_notify, 'index.' . $phpExt);
}

function PostExtendedASCIIToIntermediate($t, $f = 'hebrew')
{
	$t = preg_replace("< >", "SPACE ", $t);
	$t = preg_replace("<,>", "COMMA", $t);
	$t = preg_replace("<->", "DASH", $t);
	$t = preg_replace("<\.>", "PERIOD", $t);

	/* Vowels */
	if ($f === 'hebrew')
	{	
		$t = preg_replace("<ALEPH>", "ALEPH ", $t);
		$t = preg_replace("<BET_U>", "BET_UNKNOWN ", $t);
		$t = preg_replace("<GIMEL_U>", "GIMEL_UNKNOWN ", $t);
		$t = preg_replace("<DALED_U>", "DALED_UNKNOWN ", $t);
		$t = preg_replace("<HEH_U>", "HEH_UNKNOWN ", $t);
		$t = preg_replace("<VAV_U>", "VAV_UNKNOWN ", $t);
		$t = preg_replace("<ZED>", "ZED ", $t);
		$t = preg_replace("<CHET>", "CHET ", $t);
		$t = preg_replace("<TET>", "TET ", $t);
		$t = preg_replace("<YUD_U>", "YUD_UNKNOWN ", $t);
		$t = preg_replace("<KAF_U>", "KAF_UNKNOWN ", $t);
		$t = preg_replace("<KAF_S_U>", "KAF_SOFIT_UNKNOWN ", $t);
		$t = preg_replace("<LAMED>", "LAMED ", $t);
		$t = preg_replace("<MEM>", "MEM ", $t);
		$t = preg_replace("<MEM_S>", "MEM_SOFIT ", $t);
		$t = preg_replace("<NUN>", "NUN ", $t);
		$t = preg_replace("<NUN_S>", "NUN_SOFIT ", $t);
		$t = preg_replace("<SAMECH>", "SAMECH ", $t);
		$t = preg_replace("<AYIN>", "AYIN ", $t);
		$t = preg_replace("<PEI_U>", "PEI_UNKNOWN ", $t);
		$t = preg_replace("<PEI_S_U>", "PHEI_SOFIT", $t);
		$t = preg_replace("<TZADI>", "TZADI ", $t);
		$t = preg_replace("<TZADI_S>", "TZADI_SOFIT ", $t);
		$t = preg_replace("<KUF>", "KUF ", $t);
		$t = preg_replace("<RESH>", "RESH ", $t);
		$t = preg_replace("<SHIN_U>", "SHIN_UNKNOWN ", $t);
		$t = preg_replace("<TAV_U>", "TAV_UNKNOWN ", $t);
		
		
		$t = preg_replace("<SHEVA_U>", "SHEVA_UNKNOWN ", $t);
		$t = preg_replace("<CHATAF_SEGOL>", "CHATAF_SEGOL ", $t);
		$t = preg_replace("<CHATAF_PATACH>", "CHATAF_PATACH ", $t);
		$t = preg_replace("<CHATAF_KAMETZ>", "CHATAF_KAMETZ ", $t);
		$t = preg_replace("<CHIRIK_U>", "CHIRIK_UNKNOWN ", $t);
		$t = preg_replace("<TZEIREI_U>", "TZEIREI_UNKNOWN ", $t);
		$t = preg_replace("<SEGOL>", "SEGOL ", $t);
		$t = preg_replace("<PATACH_U>", "PATACH_UNKNOWN ", $t);
		$t = preg_replace("<KAMETZ>", "KAMETZ ", $t);
		$t = preg_replace("<CHOLAM_U>", "CHOLAM_UNKNOWN ", $t);
		$t = preg_replace("<KUBUTZ>", "KUBUTZ ", $t);
		$t = preg_replace("<DAGESH_U>", "DAGESH_UNKNOWN ", $t);
		$t = preg_replace("<SHIN_DOT>", "SHIN_DOT ", $t);
		$t = preg_replace("<SIN_DOT>", "SIN_DOT ", $t);
	}
	return $t;
}

/**
*
*/
function PostExtendedASCIIToEncodedUnicode($t, $f = 'hebrew')
{
	$t = preg_replace(ALEPH, "&#1488;", $t);
	$t = preg_replace(BET, "&#1489;", $t);
	$t = preg_replace(GIMEL, "&#1490;", $t);
	$t = preg_replace(DALED, "&#1491;", $t);
	$t = preg_replace(HEH, "&#1492;", $t);
	$t = preg_replace(VAV, "&#1493;", $t);
	$t = preg_replace(ZED, "&#1494;", $t);
	$t = preg_replace(CHET, "&#1495;", $t);
	$t = preg_replace(TET, "&#1496;", $t);
	$t = preg_replace(YUD, "&#1497;", $t);
	$t = preg_replace(KAF, "&#1499;", $t);
	$t = preg_replace(KAF_S, "&#1498;", $t);
	$t = preg_replace(LAMED, "&#1500;", $t);
	$t = preg_replace(MEM, "&#1502;", $t);
	$t = preg_replace(MEM_S, "&#1501;", $t);
	$t = preg_replace(NUN, "&#1504;", $t);
	$t = preg_replace(NUN_S, "&#1503;", $t);
	$t = preg_replace(SAMECH, "&#1505;", $t);
	$t = preg_replace(AYIN, "&#1506;", $t);
	$t = preg_replace(PEI, "&#1508;", $t);
	$t = preg_replace(PEI_S, "&#1507;", $t);
	$t = preg_replace(TZADI, "&#1510;", $t);
	$t = preg_replace(TZADI_S, "&#1509;", $t);
	$t = preg_replace(KUF, "&#1511;", $t);
	$t = preg_replace(RESH, "&#1512;", $t);
	$t = preg_replace(SHIN_U, "&#1513;", $t);
	$t = preg_replace(TAV_U, "&#1514;", $t);
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace(SHEVA_U, "&#1456;", $t);
		$t = preg_replace(CHATAF_SEGOL, "&#1457;", $t);
		$t = preg_replace(CHATAF_PATACH, "&#1458;", $t);
		$t = preg_replace(CHATAF_KAMETZ, "&#1459;", $t);
		$t = preg_replace(CHIRIK_U, "&#1460;", $t);
		$t = preg_replace(TZEIREI_U, "&#1461;", $t);
		$t = preg_replace(SEGOL, "&#1462;", $t);
		$t = preg_replace(PATACH_U, "&#1463;", $t);
		$t = preg_replace(KAMETZ, "&#1464;", $t);
		$t = preg_replace(CHOLAM_U, "&#1465;", $t);
		$t = preg_replace(KUBUTZ, "&#1467;", $t);
		$t = preg_replace(DAGESH_U, "&#1468;", $t);
		$t = preg_replace(SHIN_DOT, "&#1473;", $t);
		$t = preg_replace(SIN_DOT, "&#1474;", $t);
	}
	$t = urldecode($t);
	return $t;
}

/**
*
*/
function PostToIntermediate($t, $f)
{	
	$t = preg_replace("< >", "SPACE", $t);
	$t = preg_replace("<,>", "COMMA", $t);
	$t = preg_replace("<->", "DASH", $t);
	$t = preg_replace("<\.>", "PERIOD ", $t);
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		global $root_path, $phpExt;
		
		include_once($root_path . 'schemas/heb.' . $phpExt);
		
		$t = preg_replace("<&#1488;>", "ALEPH ", $t);
		$t = preg_replace("<&#1489;>", "BET_UNKNOWN ", $t);
		$t = preg_replace("<&#1490;>", "GIMEL_UNKNOWN ", $t);
		$t = preg_replace("<&#1491;>", "DALED_UNKNOWN ", $t);
		$t = preg_replace("<&#1492;>", "HEH_UNKNOWN ", $t);
		$t = preg_replace("<&#1493;>", "VAV_UNKNOWN ", $t);
		$t = preg_replace("<&#1494;>", "ZED ", $t);
		$t = preg_replace("<&#1495;>", "CHET ", $t);
		$t = preg_replace("<&#1496;>", "TET ", $t);
		$t = preg_replace("<&#1497;>", "YUD_UNKNOWN ", $t);
		$t = preg_replace("<&#1498;>", "KAF_SOFIT_UNKNOWN ", $t);
		$t = preg_replace("<&#1499;>", "KAF_UNKNOWN ", $t);
		$t = preg_replace("<&#1500;>", "LAMED ", $t);
		$t = preg_replace("<&#1501;>", "MEM_SOFIT ", $t);
		$t = preg_replace("<&#1502;>", "MEM ", $t);
		$t = preg_replace("<&#1503;>", "NUN_SOFIT ", $t);
		$t = preg_replace("<&#1504;>", "NUN ", $t);
		$t = preg_replace("<&#1505;>", "SAMECH ", $t);
		$t = preg_replace("<&#1506;>", "AYIN ", $t);
		$t = preg_replace("<&#1507;>", "PHEI_SOFIT ", $t);
		$t = preg_replace("<&#1508;>", "PEI_UNKNOWN ", $t);
		$t = preg_replace("<&#1509;>", "TZADI_SOFIT ", $t);
		$t = preg_replace("<&#1510;>", "TZADI ", $t);
		$t = preg_replace("<&#1511;>", "KUF ", $t);
		$t = preg_replace("<&#1512;>", "RESH ", $t);
		$t = preg_replace("<&#1513;>", "SHIN_UNKNOWN ", $t);
		$t = preg_replace("<&#1514;>", "TAV_UNKNOWN ", $t);
		
		// now for the nikud
		$t = preg_replace("<&#1456;>", "SHEVA_UNKNOWN ", $t);
		$t = preg_replace("<&#1457;>", "CHATAF_SEGOL ", $t);
		$t = preg_replace("<&#1458;>", "CHATAF_PATACH ", $t);
		$t = preg_replace("<&#1459;>", "CHATAF_KAMETZ ", $t);
		$t = preg_replace("<&#1460;>", "CHIRIK_UNKNOWN ", $t);
		$t = preg_replace("<&#1461;>", "TZEIREI_UNKNOWN ", $t);
		$t = preg_replace("<&#1462;>", "SEGOL ", $t);
		$t = preg_replace("<&#1464;>", "KAMETZ ", $t);
		$t = preg_replace("<&#1463;>", "PATACH_UNKNOWN ", $t);
		$t = preg_replace("<&#1465;>", "CHOLAM_UNKNOWN ", $t);
		$t = preg_replace("<&#1467;>", "KUBUTZ ", $t);
		$t = preg_replace("<&#1473;>", "SHIN_DOT ", $t);
		$t = preg_replace("<&#1474;>", "SIN_DOT ", $t);
		$t = preg_replace("<&#1468;>", "DAGESH_UNKNOWN ", $t);
		// trup code now
		$t = preg_replace("<&#1431;>", "REVII ", $t);
		$t = preg_replace("<&#1444;>", "MAHPACH ", $t);
		$t = preg_replace("<&#1433;>", "KADMA ", $t);
		$t = preg_replace("<&#1443;>", "MUNACH ", $t);
		$t = preg_replace("<&#1428;>", "ZAKEF_KATON ", $t);
		$t = preg_replace("<&#1429;>", "ZAKEF_GADOL ", $t);
		$t = preg_replace("<&#1445;>", "MERCHA ", $t);
		$t = preg_replace("<&#1430;>", "TIPCHA ", $t);
		$t = preg_replace("<&#1425;>", "ETNACHTA ", $t);
		$t = preg_replace("<&#1469;>", "METEG ", $t);
		$t = preg_replace("<&#1475;>", "SOF_PASUK ", $t);
		$t = preg_replace("<&#1435;>", "TEVIR ", $t);
		$t = preg_replace("<&#1447;>", "DARGA ", $t);
		$t = preg_replace("<&#1436;>", "GERESH ", $t);
		$t = preg_replace("<&#1438;>", "GERSHAYIM ", $t);
		$t = preg_replace("<&#1454;>", "ZARKA ", $t);
		$t = preg_replace("<&#1426;>", "SEGOLTA ", $t);
		$t = preg_replace("<&#1440;>", "TELISHA_KETANA ", $t);
		$t = preg_replace("<&#1449;>", "TELISHA_GEDOLA ", $t);
	}	
	// since until now expressions were escaped as in &#number; we only handle now
	$t = preg_replace("<;>", "SEMICOLON", $t);
	$t = urldecode($t);
	return $t;
}

/**
*
*/
function handleSpecials($t)
{
	// certain penultimately stressed words s/t mess up the
	// transliteration, which assumes ultimate stress. detecting
	// stress is a non-trivial matter, and so we handle this here
	// by listing the common words and fixing the mapping a bit
	// 1. mitzrAyma
	$t = ereg_repl("(". MEM ." ". CHIRIK_MALEI ." ". TZADI ." ". SHEVA_NACH ." ". RESH ." ". KAMETZ . YUD . ")" . "(" . SHEVA_NA . ")" . "(" . MEM ." ". KAMETZ ." ". HEH .")",
			"\\1 ". SHEVA_NACH ." \\3", $t);
	return $t;
}

// waxmanjo edit 20160828
// In the past, we made the assumption that the dagesh immediately followed the consonant
// However, I've now encountered dagesh after the nikkud instead of preceding it. Rather than deal with both cases,
// we will change one case into the other.
function flipDageshNikkud($t)
{
	$NIKUD = "(".PATACH."|".PATACH_GANUV."|".PATACH_UNKNOWN."|".CHATAF_PATACH."|".KAMETZ."|".CHATAF_KAMETZ."|".SHEVA."|".SHEVA_NACH."|".SHEVA_UNKNOWN."|".SEGOL."|".CHATAF_SEGOL."|".TZEIREI."|".TZEIREI_MALEI."|".TEZEIREI_CHASER."|".CHIRIK_MALEI."|".CHIRIK_CHASER."|".CHIRIK."|".CHOLAM_CHASER."|".CHOLAM_MALEI."|".CHOLAM."|".KUBUTZ.")";
	$t = preg_replace("<" . $NIKUD . DAGESH_UNKNOWN.">", DAGESH_UNKNOWN. "\\1", $t);
	return $t;
}

// waxmanjo edit 20160828
// In the past, we made the assumption that the shin or sin dot immediately followed the consonant shin/sin.
// However, I've now encountered the shin and sin dot after the nikkud instead of preceding it. Rather than deal with both cases,
// we will change one case into the other.
function flipShinSinDotNikkud($t)
{
	$NIKUD = "(".PATACH."|".PATACH_GANUV."|".PATACH_UNKNOWN."|".CHATAF_PATACH."|".KAMETZ."|".CHATAF_KAMETZ."|".SHEVA_UNKNOWN."|".SEGOL."|".CHATAF_SEGOL."|".TZEIREI_UNKNOWN."|".TZEIREI_MALEI."|".TEZEIREI_CHASER."|".CHIRIK_MALEI."|".CHIRIK_CHASER."|".CHIRIK_UNKNOWN."|".CHOLAM_CHASER."|".CHOLAM_MALEI."|".CHOLAM_UNKNOWN."|".KUBUTZ.")";

	$t = preg_replace("<".SHIN_UNKNOWN . $NIKUD . SHIN_DOT.">", SHIN_UNKNOWN . SHIN_DOT ."\\1", $t);
	$t = preg_replace("<".SHIN_UNKNOWN . $NIKUD . SIN_DOT.">", SHIN_UNKNOWN . SIN_DOT ."\\1", $t);
	return $t;
}

/**
*
*/
function ApplyRulesToIntermediateForm($t)
{
	// now that we have it in intermediate form
	// we want to perform some transformations	
	$t = flipDageshNikkud($t);
	$t = flipShinSinDotNikkud($t);	

	// first, arrive at correct shin/sin
	// and alas! dagesh can intervene between shin and shin dot, and same for sin
	$t = preg_replace("<DAGESH_UNKNOWN (SHIN_DOT|SIN_DOT)>", "\\1 DAGESH_UNKNOWN", $t);

	$t = preg_replace("<SHIN_UNKNOWN SHIN_DOT>", "SHIN ", $t);
	$t = preg_replace("<SHIN_UNKNOWN SIN_DOT>", "SIN ", $t);

	// then, handle heh/mapik heh
	$t = preg_replace("<HEH_UNKNOWN DAGESH_UNKNOWN>", "HEH_MAPIK", $t);
	$t = preg_replace("<HEH_UNKNOWN>", "HEH", $t);

	// vav cholam = cholam malei, every other cholam = chaser
	$t = preg_replace("<VAV_UNKNOWN CHOLAM_UNKNOWN>", "CHOLAM_MALEI", $t);
	$t = preg_replace("<CHOLAM_UNKNOWN>", "CHOLAM_CHASER", $t);

	// handle examples like tetzavveh:
	// vav_unknown dagesh_unknown vowel = vav_chazak vowel
	$NIKUD = "(PATACH|PATACH_GANUV|CHATAF_PATACH|KAMETZ|CHATAF_KAMETZ|SHEVA_NA|SHEVA_NACH|SHEVA_UNKNOWN|SEGOL|CHATAF_SEGOL|TZEIREI_UNKNOWN|TZEIREI_MALEI|TEZEIREI_CHASER|CHIRIK_MALEI|CHIRIK_CHASER|CHOLAM_CHASER|CHOLAM_MALEI)";
	$t = preg_replace("<VAV_UNKNOWN DAGESH_UNKNOWN $NIKUD>", "VAV_CHAZAK \\1", $t);

	// else - vav_unknown dagesh_unknown = SHURUK
	$t = preg_replace("<VAV_UNKNOWN DAGESH_UNKNOWN>", "SHURUK", $t);

	// remaining vav will be actual vav
	$t = preg_replace("<VAV_UNKNOWN>", "VAV", $t);

	// shva at the end of a word will always be shva nach
	$t = preg_replace("<SHEVA_UNKNOWN BOUNDARY>",
			"SHEVA_NACH BOUNDARY", $t);

	// BEGEDKEFET
	// then, handle begedkefet at the beginning of a word = plosive
	$t = preg_replace("<BOUNDARY ((BET|GIMEL|DALED|KAF|PEI|TAV)_UNKNOWN) DAGESH_UNKNOWN>", "BOUNDARY \\2", $t);

	// begedkefet followed by anything but dagesh is the fricative
	$BGDKFT_UNKNOWN = "(BET_UNKNOWN|GIMEL_UNKNOWN|DALED_UNKNOWN|KAF_UNKNOWN|PEI_UNKNOWN|TAV_UNKNOWN)";

	$t = preg_replace("<BET_UNKNOWN BOUNDARY>", "BHET BOUNDARY", $t);
	$t = preg_replace("<BET_UNKNOWN " . $NIKUD . ">", "BHET \\1", $t);

	$t = preg_replace("<GIMEL_UNKNOWN BOUNDARY>", "GIMEL_UNKNOWN BOUNDARY", $t);
	$t = preg_replace("<GIMEL_UNKNOWN " . $NIKUD . ">", "GHIMEL \\1", $t);

	$t = preg_replace("<DALED_UNKNOWN BOUNDARY>", "DHALED BOUNDARY", $t);
	$t = preg_replace("<DALED_UNKNOWN " . $NIKUD . ">", "DHALED \\1", $t);

	// handle any chaf sofit nikud at the end
	$t = preg_replace("<KAF_SOFIT_UNKNOWN $NIKUD BOUNDARY>", "KHAF_SOFIT \\1 BOUNDARY", $t);
	// maybe user forgot the sheva nach?
	$t = preg_replace("<KAF_SOFIT_UNKNOWN BOUNDARY>", "KHAF_SOFIT BOUNDARY", $t);

	$t = preg_replace("<KAF_UNKNOWN " . $NIKUD . ">", "KHAF \\1", $t);

	$t = preg_replace("<PEI_UNKNOWN BOUNDARY>", "PHEI BOUNDARY", $t);
	$t = preg_replace("<PEI_UNKNOWN " . $NIKUD . ">", "PHEI \\1", $t);

	$t = preg_replace("<TAV_UNKNOWN BOUNDARY>", "THAV BOUNDARY", $t);
	$t = preg_replace("<TAV_UNKNOWN " . $NIKUD . ">", "THAV \\1", $t);

	// then, handle patach ganuv vs. regular patach

	$t = preg_replace("<(AYIN|CHET|HEH_MAPIK) PATACH_UNKNOWN BOUNDARY>",
			"PATACH_GANUV \\1 BOUNDARY", $t);
	$t = preg_replace("<PATACH_UNKNOWN>", "PATACH", $t);


	// SHEVA:
	// shva after a gutteral will always be shva nach
	$t = preg_replace("<(ALEPH|HEH|CHET|AYIN) SHEVA_UNKNOWN>",
			"\\1 SHEVA_NACH", $t);


	// shva at beginning of word should be shva na
	// some of these, such as PHEI_UNKNOWN, are not possible, but it
	// is simpler to write
	$NON_GUTTERALS = "(B(H?)ET|G(H?)IMEL|D(H?)ALED|VAV(_UNKNOWN)?|ZED|TET|YUD(_UNKNOWN)?|K(H?)AF(_UNKNOWN)?|LAMED|MEM|NUN|SAMECH|P(H?)EI(_UNKNOWN)?|TZADI|KUF|RESH|S(H?)IN(_UNKNOWN)?|T(H?)AV(_UNKNOWN)?)";
	$t = preg_replace("<BOUNDARY " . $NON_GUTTERALS . " SHEVA_UNKNOWN>", "BOUNDARY \\1 SHEVA_NA", $t);


	// for geminates, we should first have satisfied begedkefet rules

	$GEMINATE_CANDIDATES = "(BET|GIMEL|DALED|VAV|ZED|TET|YUD|KAF|KAF_SOFIT|LAMED|MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . " DAGESH_UNKNOWN>", "\\1_CHAZAK", $t);

	// Generate yud chazak. Must handle this before tzeirei malei and chirik malei rules,
	// because a chirik followed by yud chazak is really a chirik chaser and dagesh chazak.
	$t = preg_replace("<YUD_UNKNOWN DAGESH_UNKNOWN>", "YUD_CHAZAK", $t);

	// TZEIREI MALEI/CHASER
	$t = preg_replace("<TZEIREI_UNKNOWN YUD_UNKNOWN>", "TZEIREI_MALEI", $t);
	$t = preg_replace("<TZEIREI_UNKNOWN>", "TZEIREI_CHASER", $t);

	// CHIRIK_MALEI/CHASER
	$t = preg_replace("<CHIRIK_UNKNOWN YUD_UNKNOWN>", "CHIRIK_MALEI", $t);
	$t = preg_replace("<CHIRIK_UNKNOWN>", "CHIRIK_CHASER", $t);

	// yud followed by nikud, except for patach ganuv, is a full yud
	// we must handle this rule AFTER patach ganuv to handle cases like mashiach
	$NIKUD_WO_GANUV = "(PATACH|CHATAF_PATACH|KAMETZ|CHATAF_KAMETZ|SHEVA_NA|SHEVA_NACH|SHEVA_UNKNOWN|SEGOL|CHATAF_SEGOL|TZEIREI_MALEI|TEZEIREI_CHASER|CHIRIK_MALEI|CHIRIK_CHASER|CHOLAM_CHASER|CHOLAM_MALEI)";
	$t = preg_replace("<YUD_UNKNOWN " . $NIKUD_WO_GANUV . ">", "YUD \\1", $t);

	// more shva_na/nach
	// *controversial: Short Vowel + non-plosive non geminate + shva should
	// be nach. problem is that some hold by shva merachef and
	// especially in the instance in which the dagesh disappears in yud and
	// mem. however, we will assume that they are simple nachs.
	$NON_FINAL_NON_PLOSIVES = "(ALEPH|BHET|GHIMEL|DHALED|HEH|VAV|ZED|CHET|TET|YUD|KHAF|LAMED|MEM|NUN|SAMECH|AYIN|PHEI|TZADI|KUF|RESH|SHIN|SIN|THAV)";
	$SHORT_VOWELS = "(PATACH|SEGOL|CHIRIK_CHASER|KUBUTZ)";
	$t = preg_replace("<" . $SHORT_VOWELS . $NON_FINAL_NON_PLOSIVES . "SHEVA_UNKNOWN>", "\\1 \\2 SHEVA_NACH", $t);


	// before apply shva na/nach for long vowels, handle shva nach in
	// consonant clusters
	// sheva_? + letter + chataf = nach
	$LETTER_AFTER_NACH = "(ALEPH|BET|HEH|VAV|ZED|CHET|TET|YUD|LAMED|MEM|NUN|SAMECH|AYIN|TZADI|KUF|RESH|SHIN|SIN)";
	$BEGEDKEFET_UNKNOWN = "((BET|GIMEL|DALED|KAF|PEI|TAV)_UNKNOWN)";
	$NAS = "(SHEVA_NA|CHATAF)";
	$VOWELS = "(PATACH|SEGOL|CHIRIK|KUBUTZ|CHOLAM|KAMETZ|TZEIREI)";

	$t = preg_replace("<SHEVA_UNKNOWN $LETTER_AFTER_NACH $NAS>", "SHEVA_NACH \\1 \\2", $t);
	$t = preg_replace("<SHEVA_UNKNOWN $BEGEDKEFET_UNKNOWN DAGESH_UNKNOWN $NAS>", "SHEVA_NACH \\2 \\3", $t);
	$t = preg_replace("<SHEVA_UNKNOWN $BEGEDKEFET_UNKNOWN DAGESH_UNKNOWN $VOWELS>", "SHEVA_NACH \\2 \\3", $t);



	// similarly, Long vowel + non-plosive non geminate + shva
	// should be na
	$LONG_VOWELS = "(KAMETZ|TZEIREI_MALEI|TZEIREI_CHASER|CHIRIK_MALEI|CHOLAM_CHASER|CHOLAM_MALEI|SHURUK)";
	// with an exception of e.g. ubhnei rather than ubhenei, not maintaining
	// sheva merachef
	$t = preg_replace("<BOUNDARY SHURUK" . $NON_FINAL_NON_PLOSIVES . "SHEVA_UNKNOWN>", "BOUNDARY SHURUK \\1 SHEVA_NACH", $t);
	$t = preg_replace("<".$LONG_VOWELS . $NON_FINAL_NON_PLOSIVES . "SHEVA_UNKNOWN>", "\\1 \\2 SHEVA_NA", $t);

	// back to begedkefet: handle shva nach begedkefet dagesh as non-geminate
	$t = preg_replace("<SHEVA_NACH ((BET|GIMEL|DALED|KAF|PEI|TAV)_UNKNOWN) DAGESH_UNKNOWN>", "SHEVA_NACH \\2", $t);

	// and then handle short vowel + begedkefet + dagesh --> geminate begedkefet
	$t = preg_replace("<". $SHORT_VOWELS . $BEGEDKEFET_UNKNOWN . "DAGESH_UNKNOWN>", "\\1 \\3_CHAZAK", $t);

	// some begedkefets, such as those followed by what were unknown
	// vowels/matres lectiones, have not yet been handled. handle them now

	$t = preg_replace("<BET_UNKNOWN>", "BHET", $t);
	$t = preg_replace("<GIMEL_UNKNOWN>", "GHIMEL", $t);
	$t = preg_replace("<DALED_UNKNOWN>", "DHALED", $t);
	$t = preg_replace("<KAF_UNKNOWN>", "KHAF", $t);
	$t = preg_replace("<PEI_UNKNOWN>", "PHEI", $t);
	$t = preg_replace("<TAV_UNKNOWN>", "THAV", $t);


	// chazak followed by sheva_unknown should make the shva into a na
	$t = preg_replace("<_CHAZAK SHEVA_UNKNOWN>", "_CHAZAK SHEVA_NA", $t);



	// handle certain yud_unknowns
	// yud at the end of a word, unhandled before, is a mere yud
	$t = preg_replace("<YUD_UNKNOWN BOUNDARY>", "YUD BOUNDARY", $t);
	// yud after a segol is unpronounced and is there to show plurality
	$t = preg_replace("<SEGOL YUD_UNKNOWN>", "SEGOL YUD_PLURAL", $t);
	// finally, otherwise unmarked yud_unknowns should be made known
	$t = preg_replace("<YUD_UNKNOWN>", "YUD", $t);



	// handle certain Divine names which are written differently than they
	// are pronounced
	$t = preg_replace("<YUD SHEVA_NA HEH VAV KAMETZ HEH>",
			"ALEPH CHATAF_PATACH DHALED CHOLAM_CHASER NUN KAMETZ YUD", $t);

	$t = preg_replace("<YUD HEH VAV KAMETZ HEH>",
			"ALEPH SHEVA_NACH DALED CHOLAM_CHASER NUN KAMETZ YUD", $t);
	
	// certain other leters besides PLURAL YUD are there for etymological
	// purposes. we can generally detect them as follows:
	// vowel + letter1 + no nikud + letter2
	// we will discard the letter which is an em haqeriya
	// if both are, discard the first of the two
	// thus, hu(w)`, we discard the vav in favor of the aleph
	// zo(`)t, we discard the ALEPH
	// ro(`)sh, we discard the ALEPH
	// but in betnching, yer`u with no sheva after the resh, we discard the aleph
	$EM_KERIYA = "(ALEPH|HEH|VAV|YUD)";
	$t = preg_replace("<" . $NIKUD . $EM_KERIYA . $EM_KERIYA . ">", "\\1 (\\2) \\3", $t);

	$NON_EM_KERIYA = "";
	/*
	 $t = preg_replace("<$NIKUD $EM_KERIYA $EM_KERIYA>", "\\1 (\\2) \\3", $t);
	 */
	
	// some work on kametz katon
	// 1) a stop-word - kol, bakol, lakol, lekhol, etc. that is, consider
	// 	morphology
	// This is by no means complete. For example, for a moment, we didn't handle shebechol because the bet chazak was not part of the pattern
	// . Maybe it would pay to create a stemmer here?
	// For now, consider on case by case basis and just look for this particular suffix.

	$t = preg_replace("<(BET|BHET|BET_CHAZAK|LAMED|KAF|KHAF) (SHEVA_NA KHAF) (KAMETZ) (LAMED BOUNDARY)>", "\\1 \\2 \\3_KATAN \\4", $t);
	$t = preg_replace("<(BET|BHET|BET_CHAZAK|LAMED|KAF|KHAF) (PATACH KAF_CHAZAK) (KAMETZ) (LAMED BOUNDARY)>", "\\1 \\2 \\3_KATAN \\4", $t);
	$t = preg_replace("<(MEM CHIRIK_CHASER KAF_CHAZAK) (KAMETZ) (LAMED BOUNDARY)>", "\\1 \\2_KATAN \\3", $t);
	$t = preg_replace("<(KAF) (KAMETZ) (LAMED BOUNDARY)>", "\\1 \\2_KATAN \\3", $t);

	// 1.5) catches the above rule much better
	//    kametz + consonant + boundary + dash --> kametz katan
	$LETTER_AFTER_KATAN = "(BHET|GHIMEL|DHALED|VAV|ZED|TET|KHAF_SOFIT|LAMED|MEM_SOFIT|NUN_SOFIT|SAMECH||TZADI_SOFIT|KUF|RESH|SHIN|SIN|THAV)";

	//	print $t;
	$t = preg_replace("<(KAMETZ)" . $LETTER_AFTER_KATAN . "(BOUNDARY DASH)>", "\\1_KATAN \\2 \\3", $t);


	// 2) Another common word - chochma and related forms
	// we are modifying from the incorrectly computed transliteration

	$t = preg_replace("<(CHET) (KAMETZ) (KHAF) SHEVA_NA (MEM) (KAMETZ|PATACH)>", "\\1 \\2_KATAN \\3 SHEVA_NACH \\4 \\5", $t);


	// 3) kametz + cons + chataf_kametz, that first kametz was katon
	$t = preg_replace("<(KAMETZ)" . $NON_FINAL_NON_PLOSIVES . "(CHATAF_KAMETZ)>",
			"\\1_KATAN \\2 \\3", $t);


	// 4) kametz katan generally results from reduction from cholam.
	//	various forms betray this reduction happened
	// 		one common form is kametz + cons + shva_nach + bgdkft_plosive
	//		because kametz is a long vowel and so in unstressed
	//		syllables it should be open.
	//		the problem is where it occurs in stressed syllables
	//		s.t. we will need to undo the damage we are about to cause

	$t = preg_replace("<(KAMETZ)" . $NON_FINAL_NON_PLOSIVES ."(SHEVA_NACH) (BET|GIMEL|DALED|KAF|PEI|TAV)>",
			"\\1_KATAN \\2 \\3 \\4", $t);


	return $t;
}

/**
*
*/
function CleanUpPunctuation($t)
{
	$t = preg_replace("<BOUNDARY>", "", $t);
	$t = preg_replace("<COMMA>", ",", $t);
	$t = preg_replace("<DASH>", "-", $t);
	$t = preg_replace("<SEMICOLON>", ";", $t);
	$t = preg_replace("<PERIOD>", ".", $t);
	$t = preg_replace("< >", "", $t);
	$t = preg_replace("<SPACE>", " ", $t);
	return $t;
}

/**
*
*/
function FileContentToUnicode($t, $f)
{
	global $root_path, $phpExt;
	switch($f)
	{ 
		case 'aramaic':
			if (!defined('ALEPH')) 
			{			
				include_once($root_path . 'schemas/arc.' . $phpExt);
			}	
		break;
		case 'hebrew':   
		default:
			if (!defined('ALEPH')) 
			{			
				include_once($root_path . 'schemas/heb.' . $phpExt);
			}
		break;	
	}	
	
	$t = str_replace("\u1488?", ALEPH, $t);
	$t = str_replace("\u1489?", BET, $t);
	$t = str_replace("\u1490?", GIMEL, $t);
	$t = str_replace("\u1491?", DALED, $t);
	$t = str_replace("\u1492?", HEH, $t);
	$t = str_replace("\u1493?", VAV, $t);
	$t = str_replace("\u1494?", ZED, $t);
	$t = str_replace("\u1495?", CHET, $t);
	$t = str_replace("\u1496?", TET, $t);
	$t = str_replace("\u1497?", YUD, $t);
	$t = str_replace("\u1498?", KAF_SOFIT, $t);
	$t = str_replace("\u1499?", KAF, $t);
	$t = str_replace("\u1500?", LAMED, $t);
	$t = str_replace("\u1501?", MEM_SOFIT, $t);
	$t = str_replace("\u1502?", MEM, $t);
	$t = str_replace("\u1503?", NUN_SOFIT, $t);
	$t = str_replace("\u1504?", NUN, $t);
	$t = str_replace("\u1505?", SAMECH, $t);
	$t = str_replace("\u1506?", AYIN, $t);
	$t = str_replace("\u1507?", PHEI_SOFIT, $t);
	$t = str_replace("\u1508?", PEI, $t);
	$t = str_replace("\u1509?", TZADI_SOFIT, $t);
	$t = str_replace("\u1510?", TZADI, $t);
	$t = str_replace("\u1511?", KUF, $t);
	$t = str_replace("\u1512?", RESH, $t);
	$t = str_replace("\u1513?", SHIN, $t);
	$t = str_replace("\u1514?", TAV, $t);
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		// now for the nikud
		$t = str_replace("\u1456?", SHEVA, $t);
		$t = str_replace("\u1457?", CHATAF_SEGOL, $t);
		$t = str_replace("\u1458?", CHATAF_PATACH, $t);
		$t = str_replace("\u1459?", CHATAF_KAMETZ, $t);
		$t = str_replace("\u1460?", CHIRIK, $t);
		$t = str_replace("\u1461?", TZEIREI, $t);
		$t = str_replace("\u1462?", SEGOL, $t);
		$t = str_replace("\u1464?", KAMETZ, $t);
		$t = str_replace("\u1463?", PATACH, $t);
		$t = str_replace("\u1465?", CHOLAM, $t);
		$t = str_replace("\u1467?", KUBUTZ, $t);
		$t = str_replace("\u1473?", SHIN_DOT, $t);
		$t = str_replace("\u1474?", SIN_DOT, $t);
		$t = str_replace("\u1468?", DAGESH, $t);
		
		/* * /
		$t = str_replace("\u1431?", REVII, $t);
		$t = str_replace("\u1444?", MAHPACH, $t);
		$t = str_replace("\u1433?", KADMA, $t);
		$t = str_replace("\u1443?", MUNACH, $t);
		$t = str_replace("\u1428?", ZAKEF_KATON, $t);
		$t = str_replace("\u1429?", ZAKEF_GADOL, $t);
		$t = str_replace("\u1445?", MERCHA, $t);
		$t = str_replace("\u1430?", TIPCHA, $t);
		$t = str_replace("\u1425?", ETNACHTA, $t);
		$t = str_replace("\u1469?", METEG, $t);
		$t = str_replace("\u1475?", SOF_PASUK, $t);
		$t = str_replace("\u1435?", TEVIR, $t);
		$t = str_replace("\u1447?", DARGA, $t);
		$t = str_replace("\u1436?", GERESH, $t);
		$t = str_replace("\u1438?", GERSHAYIM, $t);
		$t = str_replace("\u1454?", ZARKA, $t);
		$t = str_replace("\u1426?", SEGOLTA, $t);
		$t = str_replace("\u1440?", TELISHA_KETANA, $t);
		$t = str_replace("\u1449?", TELISHA_GEDOLA, $t);
		/* */
	}	
	$t = urldecode($t);
	return $t;
}

/*
Academic Font Friendly Function
*/
function AcademicFontFriendlyTransliteration($t, $f)
{
	if (($f === 'hebrew') || ($f === 'aramaic'))
	{	
		// do not double letters in general
		$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
		$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);

		$t = preg_replace("<".HOLAM_VAV.">", "uo", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "mo", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "lo", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "vo", $t);	
		$t = preg_replace("<".HOLAM_TAV.">", "to", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "ro", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "Ń", $t);	
		
		/* Font Frendly */
		$t = preg_replace("<".ALEPH.">", "Ęľ", $t);
		$t = preg_replace("<".BET.">", "b", $t);
		$t = preg_replace("<".BHET.">", "bh", $t);
		$t = preg_replace("<".GIMEL.">", "g", $t);
		$t = preg_replace("<".GHIMEL.">", "gh", $t);
		$t = preg_replace("<".DALED.">", "d", $t);
		$t = preg_replace("<".DHALED.">", "dh", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".VAV.">", "w", $t);
		$t = preg_replace("<".ZED.">", "z", $t);
		$t = preg_replace("<".CHET.">", "ch", $t); //$t = preg_replace("<".CHET.">", "&#295;", $t);
		$t = preg_replace("<".TET.">", "th", $t); //$t = preg_replace("<".TET.">", "&#335;", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "i", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "(y)", $t);
		$t = preg_replace("<".YUD.">", "y", $t);
		$t = preg_replace("<".KAF.">", "k", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "kh", $t);
		$t = preg_replace("<".LAMED.">", "l", $t);
		$t = preg_replace("<".MEM.">", "m", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "É±", $t);
		$t = preg_replace("<".NUN.">", "n", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "Éł", $t);
		$t = preg_replace("<".SAMECH.">", "s", $t);
		$t = preg_replace("<".AYIN.">", "Ęż", $t);
		$t = preg_replace("<".PEI.">", "p", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "ph", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "ts", $t); //&#351;
		$t = preg_replace("<".TZADI.">", "tz", $t);
		$t = preg_replace("<".KUF.">", "q", $t);
		$t = preg_replace("<".RESH.">", "r", $t);
		$t = preg_replace("<".SHIN_NO_DOT.">", "sh", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "sÄ", $t);
		$t = preg_replace("<".SHIN.">", "sh", $t);
		$t = preg_replace("<".SIN.">", "s", $t);
		$t = preg_replace("<".SHIN_NO_DOT.">", "(sh)", $t);	
		$t = preg_replace("<".TAV.">", "t", $t);
		$t = preg_replace("<".THAV.">", "th", $t);
	}	
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "&#335;", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "o", $t);
		$t = preg_replace("<".KAMETZ.">", "&#257;", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "&#259;", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "(a)", $t);
		$t = preg_replace("<".PATACH.">", "a", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "", $t);
		$t = preg_replace("<".SHEVA.">", "&#601;", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "&#277;", $t);
		$t = preg_replace("<".SEGOL.">", "e", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "&#234;", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "&#275;", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "&#238;", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "i", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "&#244;", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "&#333;", $t);
		$t = preg_replace("<".MAPIQ.">", "&#333;", $t);
		$t = preg_replace("<".SHURUK.">", "&#251;", $t); //
		$t = preg_replace("<".KUBUTZ.">", "u", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);	
	} 
	
	if ($f === 'aramaic') 
	{		
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", "hÄ™", $t);
		$t = preg_replace("<".PTHAHA_UP.">", "Ă¤", $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", "Ä›", $t); 
		$t = preg_replace("<".PTHAHA_DOTTED.">", "ĂĽ", $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", "ĹŻ", $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", "Ăą", $t); 
		$t = preg_replace("<".ZQAPHA_DOTTED.">", "Ä", $t); 
		$t = preg_replace("<".RBASA_UP.">", "Ă ", $t);
		$t = preg_replace("<".RBASA_DOWN.">", "Ä“", $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", "Ĺ‘", $t); 
		$t = preg_replace("<".ZLAMA_ANGULAR.">", "Ă©", $t); 
		$t = preg_replace("<".ZLAMA_UP.">", "Ă˛", $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", "y", $t); 
		$t = preg_replace("<".ZLAMA_DOTTED.">", "Ä«", $t); 
		$t = preg_replace("<".ESASA_UP.">", "Ă¬", $t);
		$t = preg_replace("<".ESASA_DOWN.">", "Ă˝", $t); 
		$t = preg_replace("<".RWAHA.">", "ĹŤ", $t);
		$t = preg_replace("<".FEMININE_DOT.">", "Ä…", $t); 
		$t = preg_replace("<".DALED.QUSHSHAYA.">", "dĂ˘", $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", "dĂ®", $t);
		$t = preg_replace("<".MEM.QUSHSHAYA.">", "mĂ®", $t);
		$t = preg_replace("<".QUSHSHAYA.">", "Ă˘", $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", "qĂ˘", $t); 
		$t = preg_replace("<".RUKKAKHA.">", "Ă˘", $t);
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", "ĂĄ", $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", "Ĺ«", $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", "Ä™", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", "Ăł", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".BARREKH.">", "\+", $t); 
		$t = preg_replace("<".MAQAF.">", "Öľ", $t);

		$t = preg_replace("<BOUNDARY>", "BOUNDARY", $t);
		$t = preg_replace("<COMMA>", ",", $t);
		$t = preg_replace("<DASH>", "-", $t);
		$t = preg_replace("<SEMICOLON>", ";", $t);
		$t = preg_replace("<PERIOD>", ".", $t);
		$t = preg_replace("< >", " ", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);	 		
	}	
	
	/* Second Step */ 
	/*	$t = preg_replace("<BOUNDARY COMMA BOUNDARY>", ",", $t);
	 $t = preg_replace("<COMMA>", ",", $t);
	 $t = preg_replace("<BOUNDARY DASH BOUNDARY>", "-", $t);
	 $t = preg_replace("<BOUNDARY SEMICOLON BOUNDARY>", ";", $t);
	 $t = preg_replace("<SEMICOLON>", ";", $t);
	 $t = preg_replace("< >", "", $t);
	 $t = preg_replace("<BOUNDARY>", " ", $t);
	 $t = preg_replace("<PERIOD>", ".", $t);
	 */	
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	$t = urldecode($t);
	return $t;
}

/**
*
*/
function AshkenazicTransliteration($t, $f, $l = 'en')
{	
	if (($f === 'hebrew') || ($f === 'aramaic'))
	{
		// do not double letters in general
		$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEITZADI|KUF|SIN|SHIN|TAV)";
		$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
		
		$t = preg_replace("<".HOLAM_VAV.">", "uo", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "mo", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "lo", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "vo", $t);	
		$t = preg_replace("<".HOLAM_TAV.">", "to", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "ro", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "Ń", $t);	
		
		/* default */
		$t = preg_replace("<".ALEPH.">", "e", $t);
		$t = preg_replace("<".BET.">", "b", $t);
		$t = preg_replace("<".BHET.">", "v", $t);
		$t = preg_replace("<".GIMEL.">", "g", $t);
		$t = preg_replace("<".GHIMEL.">", "g", $t);
		$t = preg_replace("<".DALED.">", "d", $t);
		$t = preg_replace("<".DHALED.">", "d", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".VAV.">", "v", $t);
		$t = preg_replace("<".ZED.">", "z", $t);
		$t = preg_replace("<".CHET.">", "ch", $t);
		$t = preg_replace("<".TET.">", "t", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "i", $t);
		$t = preg_replace("<".YUD.">", "y", $t);
		$t = preg_replace("<".KAF.">", "k", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "ch", $t);
		$t = preg_replace("<".LAMED.">", "l", $t);
		$t = preg_replace("<".MEM.">", "m", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "m", $t);
		$t = preg_replace("<".NUN.">", "n", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "n", $t);
		$t = preg_replace("<".SAMECH.">", "s", $t);
		$t = preg_replace("<".AYIN.">", "a", $t);
		$t = preg_replace("<".PEI.">", "p", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "f", $t);
		$t = preg_replace("<".TZADI.">", "tz", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "ts", $t);
		$t = preg_replace("<".KUF.">", "k", $t);
		$t = preg_replace("<".RESH.">", "r", $t);
		$t = preg_replace("<".SHIN_NO_DOT.">", "sh", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "sÄ", $t);
		$t = preg_replace("<".SHIN.">", "sh", $t);
		$t = preg_replace("<".SIN.">", "s", $t);
		$t = preg_replace("<".TAV.">", "t", $t);
		$t = preg_replace("<".THAV.">", "s", $t);	
	}	
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "a", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "o", $t);
		$t = preg_replace("<".KAMETZ.">", "a", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "e", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "Ä›", $t);
		$t = preg_replace("<".PATACH.">", "a", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "e", $t);
		$t = preg_replace("<".SHEVA.">", "'", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "e", $t);
		$t = preg_replace("<".SEGOL.">", "e", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "ei", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "ei", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "i", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "i", $t);
		$t = preg_replace("<".CHOLAM.">", "o", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "o", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "o", $t);
		$t = preg_replace("<".MAPIQ.">", "o", $t);
		$t = preg_replace("<".KUBUTZ.">", "u", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);		
	}
	
	if ($f === 'aramaic') 
	{		
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", "hÄ™", $t);
		$t = preg_replace("<".PTHAHA_UP.">", "Ă¤", $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", "Ä›", $t); 
		$t = preg_replace("<".PTHAHA_DOTTED.">", "ĂĽ", $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", "ĹŻ", $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", "Ăą", $t); 
		$t = preg_replace("<".ZQAPHA_DOTTED.">", "Ä", $t); 
		$t = preg_replace("<".RBASA_UP.">", "Ă ", $t);
		$t = preg_replace("<".RBASA_DOWN.">", "Ä“", $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", "Ĺ‘", $t); 
		$t = preg_replace("<".ZLAMA_ANGULAR.">", "Ă©", $t); 
		$t = preg_replace("<".ZLAMA_UP.">", "Ă˛", $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", "y", $t); 
		$t = preg_replace("<".ZLAMA_DOTTED.">", "Ä«", $t); 
		$t = preg_replace("<".ESASA_UP.">", "Ă¬", $t);
		$t = preg_replace("<".ESASA_DOWN.">", "Ă˝", $t); 
		$t = preg_replace("<".RWAHA.">", "ĹŤ", $t);
		$t = preg_replace("<".FEMININE_DOT.">", "Ä…", $t); 
		$t = preg_replace("<".DALED.QUSHSHAYA.">", "d'", $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", "d'", $t);
		$t = preg_replace("<".MEM.QUSHSHAYA.">", "m'", $t);
		$t = preg_replace("<".QUSHSHAYA.">", "'", $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", "q'", $t); 
		$t = preg_replace("<".RUKKAKHA.">", "'", $t);
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", "ĂĄ", $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", "Ĺ«", $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", "Ä™", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", "Ăł", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".BARREKH.">", "\+", $t); 
		$t = preg_replace("<".MAQAF.">", "Öľ", $t);

		$t = preg_replace("<BOUNDARY>", "BOUNDARY", $t);
		$t = preg_replace("<COMMA>", ",", $t);
		$t = preg_replace("<DASH>", "-", $t);
		$t = preg_replace("<SEMICOLON>", ";", $t);
		$t = preg_replace("<PERIOD>", ".", $t);
		$t = preg_replace("< >", " ", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);	 		
	}
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

/**
*
*/
function SefardicTransliteration($t, $f)
{	
	if (($f === 'hebrew') || ($f === 'aramaic'))
	{	
		// do not double letters in general
		$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
		$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);

		$t = preg_replace("<".HOLAM_VAV.">", "uĹŤ", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "mĹŤ", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "lĹŤ", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "vĹŤ", $t);
		$t = preg_replace("<".HOLAM_TAV.">", "tĹŤ", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "rĹŤ", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "Ń", $t);	
		
		$t = preg_replace("<".ALEPH.">", "e", $t);
		$t = preg_replace("<".BET.">", "b", $t);
		$t = preg_replace("<".BHET.">", "v", $t);
		$t = preg_replace("<".GIMEL.">", "g", $t);
		$t = preg_replace("<".GHIMEL.">", "g", $t);
		$t = preg_replace("<".DALED.">", "d", $t);
		$t = preg_replace("<".DHALED.">", "d", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".VAV.">", "u", $t);
		$t = preg_replace("<".HOLAM_VAV.">", "uĹŤ", $t);
		$t = preg_replace("<".ZED.">", "z", $t);
		$t = preg_replace("<".CHET.">", "á¸Ą", $t); // h dot
		$t = preg_replace("<".TET.">", "th", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "y", $t);
		$t = preg_replace("< ".YUD.">", " y", $t);
		$t = preg_replace("<".YUD.">", "y", $t);
		$t = preg_replace("<".KAF.">", "k", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "kh", $t);
		$t = preg_replace("<".LAMED.">", "l", $t);
		$t = preg_replace("<".MEM.">", "m", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "m", $t);
		$t = preg_replace("<".NUN.">", "n", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "n", $t);
		$t = preg_replace("<".SAMECH.">", "s", $t);
		$t = preg_replace("<".AYIN.">", "a", $t);
		$t = preg_replace("<".PEI.">", "p", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "f", $t);
		$t = preg_replace("<".TZADI.">", "tz", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "ts", $t);
		$t = preg_replace("<".KUF.">", "q", $t);
		$t = preg_replace("<".RESH.">", "r", $t);
		$t = preg_replace("<".SHIN_NO_DOT.">", "sh", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "sÄ", $t);
		$t = preg_replace("<".SHIN.">", "sh", $t);
		$t = preg_replace("<".SIN.">", "s", $t);
		$t = preg_replace("<".TAV.">", "t", $t);
		$t = preg_replace("<".THAV.">", "t", $t);
	}	
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "a", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "a", $t);
		$t = preg_replace("<".KAMETZ.">", "a", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "a", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "Ä›", $t);
		$t = preg_replace("<".PATACH.">", "e", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "É™", $t);
		$t = preg_replace("<".SHEVA.">", "É™", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "e", $t);
		$t = preg_replace("<".SEGOL.">", "e", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "e", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "e", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "i", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "i", $t);
		$t = preg_replace("<".CHOLAM.">", "o", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "o", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "o", $t);
		$t = preg_replace("<".MAPIQ.">", "o", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "u", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);	 
	}
	
	if ($f === 'aramaic') 
	{		
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", "hÄ™", $t);
		$t = preg_replace("<".PTHAHA_UP.">", "Ă¤", $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", "Ä›", $t); 
		$t = preg_replace("<".PTHAHA_DOTTED.">", "ĂĽ", $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", "ĹŻ", $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", "Ăą", $t); 
		$t = preg_replace("<".ZQAPHA_DOTTED.">", "Ä", $t); 
		$t = preg_replace("<".RBASA_UP.">", "Ă ", $t);
		$t = preg_replace("<".RBASA_DOWN.">", "Ä“", $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", "Ĺ‘", $t); 
		$t = preg_replace("<".ZLAMA_ANGULAR.">", "Ă©", $t); 
		$t = preg_replace("<".ZLAMA_UP.">", "Ă˛", $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", "y", $t); 
		$t = preg_replace("<".ZLAMA_DOTTED.">", "Ä«", $t); 
		$t = preg_replace("<".ESASA_UP.">", "Ă¬", $t);
		$t = preg_replace("<".ESASA_DOWN.">", "Ă˝", $t); 
		$t = preg_replace("<".RWAHA.">", "ĹŤ", $t);
		$t = preg_replace("<".FEMININE_DOT.">", "Ä…", $t); 
		$t = preg_replace("<".DALED.QUSHSHAYA.">", "dĂ˘", $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", "dĂ®", $t);
		$t = preg_replace("<".MEM.QUSHSHAYA.">", "mĂ®", $t);
		$t = preg_replace("<".QUSHSHAYA.">", "Ă˘", $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", "qĂ˘", $t); 
		$t = preg_replace("<".RUKKAKHA.">", "Ă˘", $t);
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", "ĂĄ", $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", "Ĺ«", $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", "Ä™", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", "Ăł", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".BARREKH.">", "\+", $t); 
		$t = preg_replace("<".MAQAF.">", "Öľ", $t);

		$t = preg_replace("<BOUNDARY>", "BOUNDARY", $t);
		$t = preg_replace("<COMMA>", ",", $t);
		$t = preg_replace("<DASH>", "-", $t);
		$t = preg_replace("<SEMICOLON>", ";", $t);
		$t = preg_replace("<PERIOD>", ".", $t);
		$t = preg_replace("< >", " ", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);	 		
	}
	
	$t = preg_replace("<Öąsh>", "osh", $t);
	$t = preg_replace("<Öąr>", "or", $t);
	$t = preg_replace("<Öąt>", "ot", $t);
	$t = preg_replace("<mosheh>", "Mosheh", $t);
	$t = preg_replace("<É™É™>", "É™", $t);	
	$t = preg_replace("<yisÉ™raeel>", "YisÉ™raeEel", $t);	
	$t = preg_replace("<iÄ«sĂ®rÄeeÄ«l>", "IÄ«sĂ˘rÄeEÄ«l", $t);
	$t = preg_replace("<yÉ™erÉ™dÉ™en>", "IÉ™erÉ™dÉ™en", $t);
	$t = preg_replace("< yi>", " iyÂ·", $t);
	$t = preg_replace("< ue>", " ueÂ·", $t);
	$t = preg_replace("< uÉ™>", " uÉ™Â·", $t);
	$t = preg_replace("< he>", " heÂ·", $t);
	$t = preg_replace("< bÉ™>", " bÉ™Â·", $t);
	$t = preg_replace("<bÉ™Â·Ä>", "bÉ™Ä", $t);
	$t = preg_replace("< ha>", " haÂ·", $t);
	$t = preg_replace("< bÉ™Â·eyn>", " bÉ™ein", $t);
	$t = preg_replace("<bÉ™aaa>", "bÉ™aÂ·aa", $t);
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

/**
*
*/
function AcademicTransliteration($t, $f)
{	
	/* Vowels */
	if ($f === 'romanian')
	{	
		//Basic Characters	
		$rom_dia_lc = array('ie', 'iĂ®', 'ĹźĹŁ', 'iu', 'ia', 'Ä', 'ce', 'kg', ' Ă®', 'Ă® ');
		$rom_dia_uc = array('Ie', 'IĂ®', 'ĹžĹŁ', 'Iu', 'Ia', 'Ä‚', 'Ce', 'Kg', ' ĂŽ', 'ĂŽ ');	
		$rom_lc = array('Đ°', 'b', 'v', 'h', 'g', 'd', 'e', 'Ă˘', 'z', 'i', 'y', 'j', 'k', 'l', 'm', 'É±', 'n', 'Éł', 'o', 'p', 'r', 's', 't', 'u', 'f', 'x', 'ĹŁ', 'c', 'Ĺź', 'Ă®', 'Ă˘');
		$rom_uc = array('A', 'B', 'V', 'H', 'G', 'D', 'E', 'Ă‚', 'Z', 'I', 'Y', 'J', 'K', 'L', 'M', 'ÓŽ', 'N', 'ÓŠ', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'Ĺ˘', 'C', 'Ĺž', 'ĂŽ', 'ĂŽ');
		
		$academic_dia_lc = array('ye', 'yi', 'ĹˇÄŤ', 'yu', 'ya', 'Ä', 'che', 'kg', ' É™', 'É™ ');
		$academic_dia_uc = array('Ye', 'Yi', 'Ĺ ÄŤ', 'Yu', 'Ya', 'Ä‚', 'Che', 'Kg', ' ĆŹ', 'ĆŹ ');	
		$academic_lc = array('Ęľ', 'b.', 'á¸‡', 'h', 'g', 'd', 'e', 'Ĺľ', 'z', 'y', 'i', 'j', 'k', 'l', 'm', 'm', 'n', 'n', 'Ęż', 'p', 'r', 'Ĺ›', 't', 'w', 'f', 'x', 'ĹŁ', 'ÄŤ', 'Ĺˇ', 'â€˛', 'Çť');
		$academic_uc = array('Ęľ', 'B.', 'á¸†', 'H', 'G', 'D', 'E', 'Ĺ˝', 'Z', 'Y', 'I', 'J', 'K', 'L', 'M', 'M', 'N', 'N', 'Ęż', 'P', 'R', 'Ĺš', 'T', 'W', 'F', 'X', 'Ĺ˘', 'ÄŚ', 'Ĺ ', 'â€˛', 'ĆŽ');
			
		$t = str_replace($rom_dia_lc, $academic_dia_lc, $t);
		$t = str_replace($rom_dia_uc, $academic_dia_uc, $t);
		
		$t = preg_replace("<BONDUARY>", " ", $t);		
		
		$t = str_replace($rom_lc, $academic_lc, $t);
		
		$t = preg_replace("<PERIOD>", "", $t);
		$t = preg_replace("<COMMA>", "", $t);
		$t = preg_replace("<SPACE>", "space", $t);
		
		$t = str_replace($rom_uc, $academic_uc, $t);
		
		$t = preg_replace("<space>", "SPACE", $t);
		$t = str_replace("B.ĘżWNDĘľRI", " ", $t);
		//Trasliteration specific	
	}
	
	if (($f === 'hebrew') || ($f === 'aramaic'))
	{	
		// do not double letters in general
		$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
		$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
		
		$t = preg_replace("<".HOLAM_VAV.">", "uĹŤ", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "mĹŤ", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "lĹŤ", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "vĹŤ", $t);
		$t = preg_replace("<".HOLAM_TAV.">", "tĹŤ", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "rĹŤ", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "uŃ", $t);	
		
		/* Consonants */
		$t = preg_replace("<".ALEPH.">", "Ęľ", $t);
		$t = preg_replace("<".BET.">", "b", $t);
		$t = preg_replace("<".BHET.">", "á¸‡", $t);
		$t = preg_replace("<".GIMEL.">", "g", $t);
		$t = preg_replace("<".GHIMEL.">", "á¸ˇ", $t);
		$t = preg_replace("<".DALED.">", "d", $t);
		$t = preg_replace("<".DHALED.">", "á¸Ź", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".VAV.">", "w", $t);
		$t = preg_replace("<".ZED.">", "z", $t);
		$t = preg_replace("<".CHET.">", "á¸Ą", $t);
		$t = preg_replace("<".TET.">", "th", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "i", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "(y)", $t);
		$t = preg_replace("<".YUD.">", "y", $t);
		$t = preg_replace("<".KAF.">", "k", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "á¸µ", $t);
		$t = preg_replace("<".LAMED.">", "l", $t);
		$t = preg_replace("<".MEM.">", "m", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "É±", $t);
		$t = preg_replace("<".NUN.">", "n", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "Éł", $t);
		$t = preg_replace("<".SAMECH.">", "s", $t);
		$t = preg_replace("<".AYIN.">", "Ęż", $t);
		$t = preg_replace("<".PEI.">", "p", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "pĚ„", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "ĹŁĚ„", $t);
		$t = preg_replace("<".TZADI.">", "ĹŁ", $t);
		$t = preg_replace("<".KUF.">", "q", $t);
		$t = preg_replace("<".RESH.">", "r", $t);		
		$t = preg_replace("<".SHIN.">", "Ĺˇ", $t);
		$t = preg_replace("<".SIN.">", "Ĺ›", $t);
		$t = preg_replace("<".SHIN_NO_DOT.">", "Ĺˇ", $t);		
		$t = preg_replace("<".TAV.">", "t", $t);
		$t = preg_replace("<".THAV.">", "áąŻ", $t);
	}	
	
	if ($f === 'hebrew')
	{	
		$t = preg_replace("<".CHATAF_KAMETZ.">", "ĹŹ", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "Ä", $t);
		$t = preg_replace("<".KAMETZ.">", "Ä", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "É™", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "<sup>Ä›</sup>", $t);
		$t = preg_replace("<".PATACH.">", "Ä“", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "É™", $t);
		$t = preg_replace("<".SHEVA.">", "É™", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "Ä", $t);
		$t = preg_replace("<".SEGOL.">", "Ä™", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "ĂŞ", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "Ä“", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "Ä«", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "Ä«", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "ĹŤ", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ĹŤ", $t);
		$t = preg_replace("<".MAPIQ.">", "ĹŤ", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "Ĺ«", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);	 

		$t = preg_replace("<BOUNDARY>", "BOUNDARY", $t);
		$t = preg_replace("<COMMA>", ",", $t);
		$t = preg_replace("<DASH>", "-", $t);
		$t = preg_replace("<SEMICOLON>", ";", $t);
		$t = preg_replace("<PERIOD>", ".", $t);
		$t = preg_replace("< >", " ", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);	
	}
	
	if ($f === 'aramaic') 
	{		
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", "hÄ™", $t);
		$t = preg_replace("<".PTHAHA_UP.">", "Ă¤", $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", "Ä›", $t); 
		$t = preg_replace("<".PTHAHA_DOTTED.">", "ĂĽ", $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", "ĹŻ", $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", "Ăą", $t); 
		$t = preg_replace("<".ZQAPHA_DOTTED.">", "Ä", $t); 
		$t = preg_replace("<".RBASA_UP.">", "Ă ", $t);
		$t = preg_replace("<".RBASA_DOWN.">", "Ä“", $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", "Ĺ‘", $t); 
		$t = preg_replace("<".ZLAMA_ANGULAR.">", "Ă©", $t); 
		$t = preg_replace("<".ZLAMA_UP.">", "Ă˛", $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", "y", $t); 
		$t = preg_replace("<".ZLAMA_DOTTED.">", "Ä«", $t); 
		$t = preg_replace("<".ESASA_UP.">", "Ă¬", $t);
		$t = preg_replace("<".ESASA_DOWN.">", "Ă˝", $t); 
		$t = preg_replace("<".RWAHA.">", "ĹŤ", $t);
		$t = preg_replace("<".FEMININE_DOT.">", "Ä…", $t); 
		$t = preg_replace("<".DALED.QUSHSHAYA.">", "dÉ™", $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", "dÉ™", $t);
		$t = preg_replace("<".MEM.QUSHSHAYA.">", "mÉ™", $t);
		$t = preg_replace("<".QUSHSHAYA.">", "É™", $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", "qÉ™", $t); 
		$t = preg_replace("<".RUKKAKHA.">", "É™", $t);
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", "ĂĄ", $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", "Ĺ«", $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", "Ä™", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", "Ăł", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".BARREKH.">", "\+", $t); 
		$t = preg_replace("<".MAQAF.">", "Öľ", $t);

		$t = preg_replace("<BOUNDARY>", "BOUNDARY", $t);
		$t = preg_replace("<COMMA>", ",", $t);
		$t = preg_replace("<DASH>", "-", $t);
		$t = preg_replace("<SEMICOLON>", ";", $t);
		$t = preg_replace("<PERIOD>", ".", $t);
		$t = preg_replace("< >", " ", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);	 		
	}	
	
	/* Second Step */
	$t = preg_replace("<ĹŤÄ>", "Ä", $t);
	$t = preg_replace("<ĹŤĹŤ>", "ĹŤ", $t);
	$t = preg_replace("<ĹŤÉ™>", "É™", $t);
	$t = preg_replace("<ĹŤÄ«>", "Ä«", $t);
	$t = preg_replace("<ĹŤĂ®>", "É™", $t);
	$t = preg_replace("<mĹŤĹźÄ™h>", "MĹŤĹźÄ™h", $t);
	$t = preg_replace("<Ă˘Ă˘>", "É™", $t);	
	$t = preg_replace("<iÄ«sÉ™rÄeĂ©l>", "IÄ«sÉ™rÄeEĂ©l", $t);	
	$t = preg_replace("<iÄ«sÉ™rÄeeÄ«l>", "IÄ«sÉ™rÄeEÄ«l", $t);
	$t = preg_replace("<iÉ™Ä“rÉ™dÉ™Ă©Éł>", "IÉ™Ä“rÉ™dÉ™Ă©Éł", $t);
	$t = preg_replace("< iÄ«>", " iÄ«Â·", $t);
	$t = preg_replace("< uÄ“>", " uÄ“Â·", $t);
	$t = preg_replace("< uĂ˘>", " uĂ®Â·", $t);
	$t = preg_replace("< hÄ“>", " hÄ“Â·", $t);
	$t = preg_replace("< bÉ™>", " bĂ®Â·", $t);
	$t = preg_replace("<bÉ™Â·Ä>", "bÉ™Ä", $t);
	$t = preg_replace("< hÄ>", " hÄÂ·", $t);
	$t = preg_replace("< bÉ™Â·Ă©iÉł>", " bÉ™Ă©iÉł", $t);
	$t = preg_replace("<bÉ™ÄaÄ>", "bÉ™ÄÂ·aÄ", $t);
	
	ExtractTrup();
	
	$t = CleanUpPunctuation($t);
	return $t;
}

/*
* Michigan Claremont or Bash
*/
function MichiganClaremontTranslit($t, $from)
{	
	if (($from === 'aramaic') || ($from === 'hebrew')) 
	{	
		// do not double letters in general
		$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
		$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
			
		$t = preg_replace("<".HOLAM_VAV.">", "WO", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "MO", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "LO", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "VO", $t);
		$t = preg_replace("<".HOLAM_TAV.">", "TO", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "RO", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "Đ€", $t);	
		
		//Consonants
		$t = preg_replace("<".ALEPH.">", ")", $t);
		$t = preg_replace("<".BET.">", "B.", $t);
		$t = preg_replace("<".BHET.">", "V", $t);
		$t = preg_replace("<".GIMEL.">", "G", $t);
		$t = preg_replace("<".GHIMEL.">", "G", $t);
		$t = preg_replace("<".DALED.">", "D", $t);
		$t = preg_replace("<".DHALED.">", "D", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "H", $t);
		$t = preg_replace("<".HEH.">", "H.", $t);
		$t = preg_replace("<".HEH.">", "H", $t);
		$t = preg_replace("<".VAV.">", "W", $t);
		$t = preg_replace("<".ZED.">", "Z", $t);
		$t = preg_replace("<".CHET.">", "X", $t);
		$t = preg_replace("<".TET.">", "\+", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "Y", $t);
		$t = preg_replace("< ".YUD.">", "Y.", $t);
		$t = preg_replace("<".YUD.">", "y", $t);
		$t = preg_replace("<".KAF.">", "K.", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "K", $t);
		$t = preg_replace("<".LAMED.">", "L", $t);
		$t = preg_replace("<".MEM.">", "M.", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "M", $t);
		$t = preg_replace("<".NUN.">", "N.", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "N", $t);
		$t = preg_replace("<".SAMECH.">", "S", $t);
		$t = preg_replace("<".AYIN.">", "(", $t);
		$t = preg_replace("<".PEI.">", "P.", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "P", $t);
		$t = preg_replace("<".TZADI.">", "TZ", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "C", $t);
		$t = preg_replace("<".KUF.">", "Q", $t);
		$t = preg_replace("<".RESH.">", "R", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "SE", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "$"."ĆŹ", $t);
		$t = preg_replace("<".SHIN.">", "$", $t);
		$t = preg_replace("<".SIN.">", "&", $t);
		$t = preg_replace("<".TAV.">", "T.", $t);
		$t = preg_replace("<".THAV.">", "T.", $t);
		$t = preg_replace("<".KAF.">", "K.", $t); //doble check
		$t = preg_replace("<".KHAF.">", "K", $t);
	}
	
	/* Vowels */
	if ($from === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", ":F", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "F", $t);
		$t = preg_replace("<".KAMETZ.">", "F", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", ":A", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "A", $t);
		$t = preg_replace("<".PATACH.">", "A", $t);
		
		//Vowels
		$t = preg_replace("<".SHEVA_NACH.">", "ĆŹ", $t);
		$t = preg_replace("<".SHEVA.">", ":", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", ":E", $t);
		$t = preg_replace("<".SEGOL.">", "E", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "\"", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "\"", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "I", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "I", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "O", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "O", $t);
		$t = preg_replace("<".MAPIQ.">", "O", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "U", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);	
	}	
	
	/* Vowels */
	if ($from == 'romanian')
	{	
		//Basic Characters	
		$rom_dia_lc = array('ie', 'iĂ®', 'ĹźĹŁ', 'iu', 'ia', 'Ä', 'ce', 'kg', ' Ă®', 'Ă® ');
		$rom_dia_uc = array('Ie', 'IĂ®', 'ĹžĹŁ', 'Iu', 'Ia', 'Ä‚', 'Ce', 'Kg', ' ĂŽ', 'ĂŽ ');	
		$rom_lc = array('Đ°', 'b', 'v', 'h', 'g', 'd', 'e', 'Ă˘', 'z', 'i', 'y', 'j', 'k', 'l', 'm', 'É±', 'n', 'Éł', 'o', 'p', 'r', 's', 't', 'u', 'f', 'x', 'ĹŁ', 'c', 'Ĺź', 'Ă®', 'Ă˘');
		$rom_uc = array('A', 'B', 'V', 'H', 'G', 'D', 'E', 'Ă‚', 'Z', 'I', 'Y', 'J', 'K', 'L', 'M', 'M', 'N', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'Ĺ˘', 'C', 'Ĺž', 'ĂŽ', 'ĂŽ');
		
		$mc_dia_lc = array('Je', 'Ji', 'Ĺ ÄŤ', 'Ju', 'Ja', 'Ä‚', 'Che', 'Kg', ' ĆŽ', 'ĆŽ ');
		$mc_dia_uc = array('Je', 'Ji', 'Ĺ ÄŤ', 'Ju', 'Ja', 'Ä‚', 'Che', 'Kg', ' ĆŽ', 'ĆŽ ');	
		$mc_lc = array(')', 'B.', 'V', 'H', 'G', 'D', 'E', 'Ĺ˝', 'Z', 'Y', 'I', 'J', 'K', 'L', 'M', 'M', 'N', 'N', '(', 'p', 'R', '&', 'T', 'U', 'F', 'X', 'C', 'ÄŚ', '$', 'â€˛', 'ĆŽ');
		$mc_uc = array(')', 'B.', 'V', 'H', 'G', 'D', 'E', 'Ĺ˝', 'Z', 'Y', 'I', 'J', 'K', 'L', 'M', 'M', 'N', 'N', '(', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'C', 'ÄŚ', 'Ĺ ', 'â€˛', 'ĆŽ');
			
		$t = str_replace($rom_dia_lc, $mc_dia_lc, $t);
		$t = str_replace($rom_dia_uc, $mc_dia_uc, $t);
		$t = preg_replace("<BONDUARY>", " ", $t);		
		$t = str_replace($rom_lc, $mc_lc, $t);
		$t = preg_replace("<PERIOD>", "", $t);
		$t = preg_replace("<COPPA>", "", $t);
		$t = preg_replace("<SPACE>", "space", $t);
		$t = str_replace($rom_uc, $mc_uc, $t);
		$t = preg_replace("<space>", "SPACE", $t);
		$t = str_replace("B.(UND)RI", " ", $t);
		//Trasliteration specific	
	}	
	
	//Second Step;
	$t = preg_replace("<ÖąĹź>", "ĹŤĹź", $t);
	$t = preg_replace("<Öąr>", "ĹŤr", $t);
	$t = preg_replace("<Öąt>", "ĹŤt", $t);
	$t = preg_replace("<mĹŤĹźÄ™h>", "MĹŤĹźÄ™h", $t);
	$t = preg_replace("<Ă˘Ă˘>", "Ă˘", $t);	
	$t = preg_replace("<iÄ«sĂ˘rÄeĂ©l>", "IÄ«sĂ˘rÄeEĂ©l", $t);	
	$t = preg_replace("<iÄ«sĂ®rÄeeÄ«l>", "IÄ«sĂ˘rÄeEÄ«l", $t);
	$t = preg_replace("<iĂ˘Ä“rĂ˘dĂ˘Ă©Éł>", "IĂ˘Ä“rĂ˘dĂ˘Ă©Éł", $t);
	$t = preg_replace("< iÄ«>", " iÄ«Â·", $t);
	$t = preg_replace("< uÄ“>", " uÄ“Â·", $t);
	$t = preg_replace("< uĂ˘>", " uĂ®Â·", $t);
	$t = preg_replace("< hÄ“>", " hÄ“Â·", $t);
	$t = preg_replace("< bĂ˘>", " bĂ®Â·", $t);
	$t = preg_replace("<bĂ®Â·Ä>", "bĂ˘Ä", $t);
	$t = preg_replace("< hÄ>", " hÄÂ·", $t);
	$t = preg_replace("< bĂ®Â·Ă©iÉł>", " bĂ˘Ă©iÉł", $t);
	$t = preg_replace("<bĂ˘ÄaÄ>", "bĂ®ÄÂ·aÄ", $t);
	$t = preg_replace("<Â·Â·>", "Â·", $t);
	
	ExtractTrup();
	
	$t = CleanUpPunctuation($t);
	return $t;
}

/**
*
*/
function RomanianTransliteration($t, $from, $to)
{
	/* Vowels */
	if (($from === 'hebrew') || ($from === 'aramaic'))
	{
		// do not double letters in general
		$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
		$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
		
		$t = preg_replace("<".HOLAM_VAV.">", "uĹŤ", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "mĹŤ", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "lĹŤ", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "vĹŤ", $t);
		$t = preg_replace("<".HOLAM_TAV.">", "tĹŤ", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "rĹŤ", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "uŃ", $t);	
		
		//Consonants
		$t = preg_replace("<".ALEPH.">", "e", $t);
		$t = preg_replace("<".BET.">", "b", $t);
		$t = preg_replace("<".BHET.">", "v", $t);
		$t = preg_replace("<".GIMEL.">", "g", $t);
		$t = preg_replace("<".GHIMEL.">", "g", $t);
		$t = preg_replace("<".DALED.">", "Ä‘", $t);
		$t = preg_replace("<".DHALED.">", "d", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".VAV.">", "u", $t);
		$t = preg_replace("<".ZED.">", "z", $t);
		$t = preg_replace("<".CHET.">", "ÄĄ", $t);
		$t = preg_replace("<".TET.">", "th", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "i", $t);
		$t = preg_replace("< ".YUD.">", " i", $t);
		$t = preg_replace("<".YUD.SHEVA.">", "iĂ®", $t);
		$t = preg_replace("<".YUD.">", "y", $t);
		$t = preg_replace("<".KHAF_KAMETZ.">", "cÄ", $t);
		$t = preg_replace("<".KAF.">", "c", $t);
		$t = preg_replace("<".KHAF.">", "cĂ®", $t);
		$t = preg_replace("<".KAF.SHEVA_NACH.">", "cĂ®Ă˘", $t);
		$t = preg_replace("<".KHAF.">", "c", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "k", $t);
		$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", "kĂ˘", $t);
		$t = preg_replace("<".LAMED.">", "l", $t);
		$t = preg_replace("<".MEM.">", "m", $t);
		$t = preg_replace("<".MEM.SHEVA_NACH.">", "mĂ®", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "É±", $t);
		$t = preg_replace("<".NUN.">", "n", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "Éł", $t);
		$t = preg_replace("<".SAMECH.">", "s", $t);
		$t = preg_replace("<".AYIN.">", "a", $t);
		$t = preg_replace("<".PEI.">", "p", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "f", $t);
		$t = preg_replace("<".TZADI.">", "ĹŁ", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "ĹŁ", $t);
		$t = preg_replace("<".KUF.">", "q", $t);
		$t = preg_replace("<".RESH.">", "r", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "ĹźÄ", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "ĹźĂ˘", $t);
		$t = preg_replace("<".SHIN.">", "Ĺź", $t);
		$t = preg_replace("<".SIN.">", "s", $t);
		$t = preg_replace("<".SHIN_NO_DOT.">", "Ĺź", $t);
		$t = preg_replace("<".TAV.">", "t", $t);
		$t = preg_replace("<".THAV.">", "t", $t);
	}	
	
	/* Vowels */
	if ($from === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "Ä", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "Ä", $t);
		$t = preg_replace("<".KAMETZ.">", "Ä", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "Ä", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "Ä›", $t);
		$t = preg_replace("<".PATACH.">", "Ä“", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "Ă®", $t);
		$t = preg_replace("<".SHEVA.">", "Ă˘", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "Ä", $t);
		$t = preg_replace("<".SEGOL.">", "Ä™", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "Ă©", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "Ă©", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "Ä«", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "Ä«", $t);
		$t = preg_replace("<".HOLAM_HASHER.">", "Ăł", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "ĹŤ", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ĹŤ", $t);
		$t = preg_replace("<".MAPIQ.">", "ĹŤ", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "Ĺ«", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);	
		
		$t = preg_replace("<BOUNDARY>", "BOUNDARY", $t);
		$t = preg_replace("<COMMA>", ",", $t);
		$t = preg_replace("<DASH>", "-", $t);
		$t = preg_replace("<SEMICOLON>", ";", $t);
		$t = preg_replace("<PERIOD>", ".", $t);
		$t = preg_replace("< >", " ", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);	
	} 
	
	if ($from === 'aramaic') 
	{
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", "hÄ™", $t);
		$t = preg_replace("<".PTHAHA_UP.">", "Ă¤", $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", "Ä›", $t); 
		$t = preg_replace("<".PTHAHA_DOTTED.">", "ĂĽ", $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", "ĹŻ", $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", "Ăą", $t); 
		$t = preg_replace("<".ZQAPHA_DOTTED.">", "Ä", $t); 
		$t = preg_replace("<".RBASA_UP.">", "Ă ", $t);
		$t = preg_replace("<".RBASA_DOWN.">", "Ä“", $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", "Ĺ‘", $t); 
		$t = preg_replace("<".ZLAMA_ANGULAR.">", "Ă©", $t); 
		$t = preg_replace("<".ZLAMA_UP.">", "Ă˛", $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", "y", $t); 
		$t = preg_replace("<".ZLAMA_DOTTED.">", "Ä«", $t); 
		$t = preg_replace("<".ESASA_UP.">", "Ă¬", $t);
		$t = preg_replace("<".ESASA_DOWN.">", "Ă˝", $t); 
		$t = preg_replace("<".RWAHA.">", "ĹŤ", $t);
		$t = preg_replace("<".FEMININE_DOT.">", "Ä…", $t); 
		$t = preg_replace("<".DALED.QUSHSHAYA.">", "dĂ˘", $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", "dĂ®", $t);
		$t = preg_replace("<".MEM.QUSHSHAYA.">", "mĂ®", $t);
		$t = preg_replace("<".QUSHSHAYA.">", "Ă˘", $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", "qĂ˘", $t); 
		$t = preg_replace("<".RUKKAKHA.">", "Ă˘", $t);
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", "ĂĄ", $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", "Ĺ«", $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", "Ä™", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", "Ăł", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".BARREKH.">", "\+", $t); 
		$t = preg_replace("<".MAQAF.">", "Öľ", $t);

		$t = preg_replace("<BOUNDARY>", "BOUNDARY", $t);
		$t = preg_replace("<COMMA>", ",", $t);
		$t = preg_replace("<DASH>", "-", $t);
		$t = preg_replace("<SEMICOLON>", ";", $t);
		$t = preg_replace("<PERIOD>", ".", $t);
		$t = preg_replace("< >", " ", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);	 		
	}	
	
	//Line marks
	$t = preg_replace("<Ö¤>", "'", $t);
	$t = preg_replace("<Ö™>", "'", $t);
	$t = preg_replace("<Öś>", "'", $t);
	$t = preg_replace("<Ö >", "'", $t);
	$t = preg_replace("<Ö”>", "", $t); //"remove"
	$t = preg_replace("<Ö›>", "'", $t);
	$t = preg_replace("<Ö—>", "Ĺ‘", $t);
	
	/* Vowels */
	if ($from == 'ukrainian')
	{	
		//Basic Characters
		$cyr_dia_lc = array('Ń”', 'Ń—', 'Ń‰', 'ŃŽ', 'ŃŹ', 'ŃŤ', 'Ń‡Đµ', 'ĐşŇ‘', ' Ń‹', 'Ń‹ ');
		$cyr_dia_uc = array('Đ„', 'Đ‡', 'Đ©', 'Đ®', 'ĐŻ', 'Đ­', 'Đ§Đµ', 'ĐšŇ‘', ' Đ«', 'Đ« ');
		$cyr_lc = array('Đ°', 'Đ±', 'Đ˛', 'Đł', 'Ň‘', 'Đ´', 'Đµ', 'Đ¶', 'Đ·', 'Đ¸', 'Ń–', 'Đą', 'Đş', 'Đ»', 'ĐĽ', 'Đ˝', 'Đľ', 'Đż', 'Ń€', 'Ń', 'Ń‚', 'Ń', 'Ń„', 'Ń…', 'Ń†', 'Ń‡', 'Ń', 'ŃŚ', 'Ń‹');
		$cyr_uc = array('Đ', 'Đ‘', 'Đ’', 'Đ“', 'Ň', 'Đ”', 'Đ•', 'Đ–', 'Đ—', 'Đ', 'Đ†', 'Đ™', 'Đš', 'Đ›', 'Đś', 'Đť', 'Đž', 'Đź', 'Đ ', 'Đˇ', 'Đ˘', 'ĐŁ', 'Đ¤', 'ĐĄ', 'Đ¦', 'Đ§', 'Đ¨', 'Đ¬', 'Đ«');
		
		$rom_dia_lc = array('ie', 'iĂ®', 'ĹźĹŁ', 'iu', 'ia', 'Ä', 'ce', 'kg', ' Ă®', 'Ă® ');
		$rom_dia_uc = array('Ie', 'IĂ®', 'ĹžĹŁ', 'Iu', 'Ia', 'Ä‚', 'Ce', 'Kg', ' ĂŽ', 'ĂŽ ');	
		$rom_lc = array('Đ°', 'b', 'v', 'h', 'g', 'd', 'e', 'Ă˘', 'z', 'i', 'y', 'j', 'c', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'x', 'ĹŁ', 'c', 'Ĺź', 'Ă®', 'Ă˘');
		$rom_uc = array('A', 'B', 'V', 'H', 'G', 'D', 'E', 'Ă‚', 'Z', 'I', 'Y', 'J', 'C', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'Ĺ˘', 'C', 'Ĺž', 'ĂŽ', 'ĂŽ');
		
		$lat_dia_lc = array('je', 'ji', 'ĹˇÄŤ', 'ju', 'ja', 'Ä', 'che', 'kg', ' Çť', 'Çť ');
		$lat_dia_uc = array('Je', 'Ji', 'Ĺ ÄŤ', 'Ju', 'Ja', 'Ä‚', 'Che', 'Kg', ' ĆŽ', 'ĆŽ ');	
		$lat_lc = array('a', 'b', 'v', 'h', 'g', 'd', 'e', 'Ĺľ', 'z', 'y', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'x', 'c', 'ÄŤ', 'Ĺˇ', 'â€˛', 'Çť');
		$lat_uc = array('A', 'B', 'V', 'H', 'G', 'D', 'E', 'Ĺ˝', 'Z', 'Y', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'C', 'ÄŚ', 'Ĺ ', 'â€˛', 'ĆŽ');
		
		$t = str_replace($cyr_dia_lc, $rom_dia_lc, $t);
		
		$t = preg_replace("<BONDUARY>", "", $t);
		
		$t = str_replace($cyr_dia_uc, $rom_dia_uc, $t);		
		
		$t = preg_replace("<BONDUARY>", "", $t);
		
		$t = str_replace($cyr_lc, $rom_lc, $t);
		
		$t = preg_replace("<BONDUARY>", "", $t);
		
		$t = str_replace($cyr_uc, $rom_uc, $t);
		
		$t = preg_replace("<Đ§ĐžĐśĐśĐ>", "COMMA", $t);
		$t = preg_replace("<ĐˇĐźĐĐ§Đ•>", " ", $t);
		$t = preg_replace("<Đ‘ĐžĐŁĐťĐ”ĐĐ Đ†>", "BONDUARY", $t);
		$t = preg_replace("<Đ‘ĐžĐŁĐťĐ”ĐĐ Đ>", "BONDUARY", $t);
		
		//Trasliteration specific	
	}		
	
	//Second Step, Names, Places, first up, etc;
	$t = preg_replace("<Ă˘Ă˘>", "Ă˘", $t);
	$t = preg_replace("<Ă˘ĂĽ>", "ĂĽ", $t);
	$t = preg_replace("<Ă˘Ä>", "Ä", $t);
	$t = preg_replace("<Ă˘Ä“>", "Ä“", $t);
	$t = preg_replace("<Ă˘Ä“>", "Ä“", $t);
	$t = preg_replace("< Ä‘ĂĽ>", " Ä‘ĂĽÂ·", $t);
	$t = preg_replace("<Ä‘Ă˘ >", "Ä‘ ", $t);
	$t = preg_replace("< iÄ«>", " iÄ«Â·", $t);
	$t = preg_replace("<Â·iÄ«>", "Â·iÄ«Â·", $t);
	$t = preg_replace("< iĂ®>", " iĂ®Â·", $t);
	$t = preg_replace("<Â·iĂ®>", "Â·iĂ®Â·", $t);
	$t = preg_replace("< iĹŤ>", " iĹŤÂ·", $t);
	$t = preg_replace("< uÄ“>", " uÄ“Â·", $t);
	$t = preg_replace("<uÄ“Â·tÄ“>", "uÄ“Â·tÄ“Â·", $t);
	$t = preg_replace("<SPACEuĂ®>", "SPACEuĂ®Â·", $t);
	$t = preg_replace("< uĂ®>", " uĂ®Â·", $t);
	$t = preg_replace("< uĹŤ>", " uĹŤÂ·", $t);
	$t = preg_replace("< uÄ>", " uÄÂ·", $t);
	$t = preg_replace("< bĂ®>", " bĂ®Â·", $t);
	$t = preg_replace("<SPACEbĂ®>", "SPACEbĂ®Â·", $t);
	$t = preg_replace("< bÄ“>", " bÄ“Â·", $t);
	$t = preg_replace("<SPACEbÄ“>", "SPACEbÄ“Â·", $t);
	$t = preg_replace("< uĂ˘>", " uĂ®Â·", $t);
	$t = preg_replace("< bĂ˘>", " bĂ®Â·", $t);
	$t = preg_replace("< bÄ>", " bÄÂ·", $t);
	$t = preg_replace("<SPACEbÄ>", "SPACEbÄÂ·", $t);
	$t = preg_replace("<bĂ®Â·Ä“>", "bÄ“", $t);
	$t = preg_replace("<uÄ“Â·iĂ®hi>", "uÄ“Â·iĂ®Â·hi", $t);
	$t = preg_replace("<uÄ“Â·iĂ®>", "uÄ“Â·iĂ®Â·", $t);
	$t = preg_replace("<Ä«i'>", "Ä«Â·i'", $t);
	$t = preg_replace("<Ä«i >", "Ä«Â·i ", $t);
	$t = preg_replace("<Â·Â·>", "Â·", $t);
	$t = preg_replace("<iĂ®Â·lÄdÄ“'i>", "iĂ˘lÄdÄ“'i", $t);
	$t = preg_replace("<iĂ®Â·lÄdÄ“i>", "iĂ˘lÄdÄ“Â·i", $t);
	$t = preg_replace("<iÄrĂ©'e>", "iĂ˘lÄdÄ“Â·i", $t);
	$t = preg_replace("<eÄ«iĹźÄ«i>", "eÄ«iĹźÄ«Â·i", $t);
	$t = preg_replace("< bÄÂ·Ĺ‘e>", " bÄĹ‘e", $t);	
	$t = preg_replace("< lÄ>", " lÄÂ·", $t);
	$t = preg_replace("< lÄ“>", " lÄ“Â·", $t);
	$t = preg_replace("< lĂ©>", " lĂ©Â·", $t);
	$t = preg_replace("< lÄ™>", " lÄ™Â·", $t);
	$t = preg_replace("< lŃ>", " lŃÂ·", $t);
	$t = preg_replace("< lÄ«>", " lÄ«Â·", $t);
	$t = preg_replace("< lĂ®>", " lĂ®Â·", $t);
	
	$t = ($from === 'aramaic') ? preg_replace("<bÄ“Â·rĂ©h>", "bÄ“rĂ©Â·hĂ®", $t) : $t;
	$t = ($from === 'aramaic') ? preg_replace("< Ä‘Ă˘>", " Ä‘Ă®Â·", $t) : $t;
	$t = ($from === 'aramaic') ? preg_replace("<Ä‘Ă˘ >", "Ä‘ ", $t) : $t;
	$t = ($from === 'aramaic') ? preg_replace("<dĂ˘ >", "d ", $t) : $t;
	
	$t = preg_replace("<aÄ“mÄ«inÄdÄbĂ˘>", "AÄ“mÄ«inÄdÄb", $t);
	$t = str_replace(" laÄ“", " lĂ®Â·aÄ“", $t);
	$t = str_replace(" lAÄ“", " lĂ®Â·AÄ“", $t);
	$t = preg_replace("<lÄvÄÉł>", "LÄvÄÉł", $t);
	$t = preg_replace("<lÄvÄÉł>", "LÄvÄÉł", $t);
	$t = preg_replace("<Ă®kÄ'>", "Ă®Â·kÄ'", $t);
	$t = preg_replace("<Ă®kÄ>", "Ă®Â·kÄ", $t);
	$t = preg_replace("<iÄ“aaÄ“vĂ®dĹ«anÄ«i>", "iÄ“Â·aaÄ“vĂ˘dĹ«anÄ«Â·i", $t);
	$t = preg_replace("<iĂ®huÄ'h>", "IĂ˘huÄh", $t);	
	$t = preg_replace("<iĂ®huÄh>", "IĂ˘huÄh", $t);
	$t = preg_replace("<ebĂ˘rÄhÄm>", "EbĂ˘rÄhÄm", $t);
	$t = preg_replace("<lnÄ“ÄĄĹźÄun>", "lĂ®Â·NÄ“ÄĄĹźÄun", $t);
	$t = preg_replace("<nÄ“ÄĄĹźuĹŤn>", "NÄ“ÄĄĹźuĹŤn", $t);
	$t = preg_replace("<uĂ®ruĹŤÄĄÄ“ eÄlĹŤhiÉ±>", "uĂ®Â·RuĹŤÄĄÄ“ EÄlĹŤhiÉ±", $t);
	$t = preg_replace("<mĹŤĹźÄ™h>", "MĹŤĹźÄ™h", $t);
	$t = preg_replace("<iĂ©ĹźuĹŤa>", "IĂ©ĹźuĹŤa", $t);	
	$t = preg_replace("<iÄ«sĂ˘rÄeĂ©l>", "IÄ«sĂ˘rÄeEĂ©l", $t);	
	$t = preg_replace("<iÄ«sĂ®rÄeĂ©l>", "IÄ«sĂ˘rÄeEÄ«l", $t);
	$t = preg_replace("<iÄ«Â·sĂ®rÄeĂ©l>", "IÄ«sĂ˘rÄeEÄ«l", $t);
	$t = preg_replace("<iĹŤÄ“rĂ®dĂ©Éł>", "IĹŤÄ“rĂ˘dĂ©Éł", $t);
	$t = preg_replace("<hÄ“Â·iĹŤÄ“rĂ®Ä‘Ă©Éł>", "hÄ“Â·IĹŤÄ“rĂ˘Ä‘Ă©Éł", $t);	
	$t = preg_replace("<iĹŤÄ“rĂ®Ä‘Ă©Éł>", "IĹŤÄ“rĂ˘Ä‘Ă©Éł", $t);  
	$t = preg_replace("<sÄ“lmÄun>", "SÄ“lmÄun", $t);
	$t = str_replace(" lSÄ“", " lĂ®Â·SÄ“", $t);
	$t = preg_replace("< uÄ“Â·uÄ“>", " uÄ“Â·uÄ“Â·", $t);
	$t = preg_replace("<uÄ“Â·iĹŤĂł>", "uÄ“Â·iĹŤĂłÂ·", $t);
	$t = preg_replace("<bĂ®Â·Ä>", "bĂ˘Ä", $t);	
	$t = preg_replace("<bÄÂ·r>", "bÄr", $t);
	$t = preg_replace("<bĂ®Â·Ä>", "bĂ˘Ä", $t);
	$t = preg_replace("< lĂ©>", " lĂ©Â·", $t);
	$t = preg_replace("< lÄ“>", " lÄ“Â·", $t);
	$t = preg_replace("<SPACEhÄ“>", "SPACEhÄ“Â·", $t);
	$t = preg_replace("< hÄ“>", " hÄ“Â·", $t);
	$t = preg_replace("<hÄ“Â·r>", "hÄ“r", $t);
	$t = preg_replace("<ÄaÉ±>", "aÉ±", $t);
	$t = preg_replace("<hÄiĂ®tÄh>", "hÄiĂ®Â·tÄh", $t);
	$t = preg_replace("<SPACEhÄ>", "SPACEhÄÂ·", $t);
	$t = preg_replace("<-'hÄ“>", "-'hÄ“Â·", $t);
	$t = preg_replace("<-hÄ“>", "-hÄ“Â·", $t);
	$t = preg_replace("<hÄeĹŤ'hÄ™l>", "hÄÂ·eĹŤ'hÄ™l", $t);
	$t = preg_replace("<hÄeÄrÄ™ĹŁ>", "hÄÂ·eÄrÄ™ĹŁ", $t);
	$t = preg_replace("<mĹźÄ«iÄĄÄe>", "MĂ˘ĹźÄ«iÄĄÄe", $t);
	$t = preg_replace("<Ä‘Ä“uÄ«iÄ‘Ă˘>", "ÄÄ“uÄ«iÄ‘", $t);
	$t = preg_replace("< bĂ®Â·Ă©iÉł>", " bĂ˘Ă©iÉł", $t);
	$t = preg_replace("< mÄ«>", " mÄ«Â·", $t);
	$t = preg_replace("<bĂ˘ÄaÄ>", "bĂ®ÄÂ·aÄ", $t);
	$t = preg_replace("<pĹŤÄerÄÉł>", "PĹŤÄerÄÉł", $t);		
	$t = preg_replace("<aĂ©vÄ™r hÄ“Â·IĹŤÄ“rĂ˘dĂ©Éł>", "AĂ©vÄ™r hÄ“Â·IĹŤÄ“rĂ˘dĂ©Éł", $t);
	$t = preg_replace("<iĂłeĂ©mÄ™r>", "iĂłÂ·eĂ©mÄ™r", $t);
	$t = preg_replace("<iĹŤeĂ©mÄ™r>", "iĹŤÂ·eĂ©mÄ™r", $t);
	$t = preg_replace("<lpÄ“rĹŁ>", "lĂ®Â·PÄ“rĂ˘ĹŁ", $t);
	$t = preg_replace("<lzÄ“rÄĄ>", "lĂ®Â·ZÄ“rĂ˘ÄĄ", $t);
	$t = preg_replace("<lÄĄĂ©ĹŁrÄun>", "lĂ®Â·Ä¤Ă©ĹŁrÄun", $t);
	$t = preg_replace("<ÄĄĂ©ĹŁruĹŤn>", "Ä¤Ă©ĹŁruĹŤn", $t);
	$t = preg_replace("<pÄ“rĹŁ>", "PÄ“rĂ˘ĹŁ", $t);
	$t = preg_replace("<tÄmÄr>", "TÄmÄr", $t);
	$t = preg_replace("<erÄm>", "ErÄm", $t);
	$t = preg_replace("<eÄrÄm>", "EÄrÄm", $t);
	$t = preg_replace("<laÄ“mÄ«Â·inÄdÄbĂ˘>", "lĂ®Â·AÄ“mÄ«inÄdÄbĂ˘", $t);
	$t = preg_replace("<bÄÂ·aÄrÄvÄh muÖąl suĹŤf>", "bÄÂ·AÄrÄvÄh MuÖąl SuĹŤf", $t);
	$t = preg_replace("<tpÄ™l>", "TĹŤpÄ™l", $t);
	$t = preg_replace("<tĂ®h>", "tĂ˘h", $t);
	$t = preg_replace("<lÄvÄÉł>", "LÄvÄÉł", $t);
	$t = preg_replace("<ÄĄÄĹŁĂ©rĹŤt>", "Ä¤ÄĹŁĂ©rĹŤt", $t);
	$t = preg_replace("<iĂ®Â·huÄh>", "IĂ˘huÄh", $t); //DIVINE NAME
	$t = preg_replace("<eÄlĹŤh'iÉ±>", "EÄlĹŤh'iÉ±", $t); //Westmister Institute Punctuation for Leningrad Codex
	$t = preg_replace("<eÄlĹŤhiÉ±>", "EÄlĹŤhiÉ±", $t); //Oxford University Simple Punctuation for Leningrad Codex
	$t = preg_replace("<eĂ©lÄiu>", "eĂ©lÄiÂ·u", $t);
	$t = preg_replace("<eÄvÄ«iu>", "eÄvÄ«iÂ·u", $t);
	$t = preg_replace("<eÄnÄ«i>", "eÄnÄ«Â·i", $t);
	$t = preg_replace("<ĹźĂ®mÄ«i>", "ĹźĂ˘mÄ«Â·i", $t);
	$t = preg_replace("<aÄ“mÄ«i>", "aÄ“mÄ«Â·i", $t);
	$t = preg_replace("<rĹŤeĹźÄ«i>", "rĹŤeĹźÄ«Â·i", $t);
	$t = preg_replace("<eÄ«mĹŤuŃ>", "eÄ«mĹŤÂ·uŃ", $t);
	$t = preg_replace("<eÄ“vĂ®rÄhÄÉ±>", "EÄ“vĂ®rÄhÄÉ±", $t);
	$t = preg_replace("<iÄ«Â·ĹŁĂ®ÄĄÄq>", "IÄ«ĹŁĂ˘ÄĄÄq", $t);
	$t = preg_replace("<iÄ“aÄqĹŤv>", "IÄ“aÄqĹŤv", $t);	
	$t = preg_replace("<eĂ©l ĹźÄ“dÄi>", "EĂ©l ĹžÄ“dÄi", $t);
	$t = preg_replace("<ĹźÄ“dÄi>", "ĹžÄ“dÄi", $t);
	$t = preg_replace("<nŃdÄ“aĂ®tÄ«i>", "nŃÂ·dÄ“aĂ®tÄ«Â·i", $t);
	$t = preg_replace("<sÄeĂ©huĹŤ>", "sÄeĂ©Â·huĹŤ", $t);	
	$t = preg_replace("<lÄhÄ™É±>", "lÄÂ·hÄ™É±", $t);
	$t = preg_replace("<pÄerÄÉł>", "PÄerÄÉł", $t); 
	$t = preg_replace("<tĹŤpÄ™l>", "TĹŤpÄ™l", $t);
	$t = preg_replace("<mĂ©ÄĄĹŤrĂ©v>", "mĂ©Â·Ä¤ĹŤrĂ©v", $t);
	$t = preg_replace("<ÄĄĹŤrĂ©v>", "Ä¤ĹŤrĂ©v", $t);
	$t = preg_replace("<cĂ®ÄĄĹŤ'É±>", "cĂ˘ÄĄĹŤ'É±", $t);	
	$t = preg_replace("<sĂ©aÄ«ir>", "SĂ©aÄ«ir", $t); 
	$t = preg_replace("<qÄdĂ©Ĺź>", "QÄdĂ©Ĺź", $t);
	$t = preg_replace("<bÄ“Â·rĂ®nĂ©aÄ“>", "BÄ“rĂ˘nĂ©aÄ“", $t);	
	$t = preg_replace("<mÄ“mĂ®rĂ©'e>", "MÄ“mĂ®rĂ©'e", $t);
	$t = preg_replace("<bĂ®Â·eĂ©lĹŤnĂ©'i>", "bĂ®Â·EĂ©lĹŤnĂ©'i", $t);
	$t = preg_replace("<eÄlÄ«iĹźÄ'a>", "EÄlÄ«iĹźÄ'a", $t);
	$t = preg_replace("<eÄlÄ«iĹźÄa>", "EÄlÄ«iĹźÄa", $t);
	$t = preg_replace("<eÄlÄ«iĹźÄ>", "EÄlÄ«iĹźÄ", $t);
	$t = preg_replace("<ĹźuĹŤnĂ©Ĺ‘É±>", "ĹžuĹŤnĂ©Ĺ‘É±", $t);	
	$t = preg_replace("<rÄ«vĂ®qÄh>", "RÄ«vĂ˘qÄh", $t);
	$t = preg_replace("<bÄ“Â·t>", "bÄ“t", $t);
	$t = preg_replace("<bĂ®Â·tuĹŤeĂ©l>", "BĂ˘tuĹŤeĂ©l", $t);
	$t = preg_replace("<bÄ“Â·t>", "bÄ“t", $t);
	$t = preg_replace("<pĹŤÄ“dÄ“Éł>", "PĹŤÄ“dÄ“Éł", $t);
	$t = preg_replace("<eÄrÄÉ±>", "EÄrÄÉ±", $t);
	$t = preg_replace("<iÄ«huĹŤdÄe>", "IÄ«huĹŤdÄe", $t);
		
	ExtractTrup();
	
	$t = CleanUpPunctuation($t);
	return $t;
}

/**
*
*/
function HebrewAramaicTransliteration($t, $from, $to)
{			
	/* Vowels */
	if (($from !== 'hebrew') && ($from !== 'aramaic') && (($to === 'hebrew') || ($to === 'aramaic')))
	{
		
		$t = preg_replace("<"."e".">", TO_ALEPH, $t);
		$t = preg_replace("<"."b".">", TO_BET, $t);
		$t = preg_replace("<"."v".">", TO_BHET, $t);
		$t = preg_replace("<"."g".">", TO_GIMEL, $t);
		$t = preg_replace("<"."g".">", TO_GHIMEL, $t);
		$t = preg_replace("<"."Ä‘".">", TO_DALED, $t);
		$t = preg_replace("<"."d".">", TO_DHALED, $t);
		$t = ($to === 'hebrew') ? preg_replace("<"."Du".">", TO_DHALED.TO_KUBUTZ, $t) : preg_replace("<"."Du".">", TO_DHALED.TO_QUSHSHAYA, $t);
		$t = preg_replace("<"."h".">", TO_HEH, $t);
		$t = preg_replace("<"."u".">", TO_VAV, $t);
		$t = preg_replace("<"."z".">", TO_ZED, $t);
		$t = preg_replace("<"."ÄĄ".">", TO_CHET, $t);
		$t = preg_replace("<"."th".">", TO_TET, $t);
		$t = preg_replace("<"."i".">", TO_YUD_PLURAL, $t);
		$t = preg_replace("<"."iĂ®".">", TO_YUD.TO_SHEVA, $t);
		$t = preg_replace("< "." i".">", TO_YUD, $t);
		$t = preg_replace("<"."y".">", TO_YUD, $t);
		$t = preg_replace("<"."cÄ".">", TO_KHAF_KAMETZ, $t);
		$t = preg_replace("<"."cĂ®".">", TO_KHAF, $t);
		$t = preg_replace("<"."cĂ®Ă˘".">", TO_KAF.TO_SHEVA_NACH, $t);
		$t = preg_replace("<"."k".">", TO_KHAF_SOFIT, $t);
		$t = preg_replace("<"."kĂ˘".">", TO_KHAF_SOFIT.TO_SHEVA, $t);
		$t = preg_replace("<"."c".">", TO_KAF, $t);
		$t = preg_replace("<"."l".">", TO_LAMED, $t);
		$t = preg_replace("<"."mĂ®".">", TO_MEM.TO_SHEVA_NACH, $t);
		$t = preg_replace("<"."É±".">", TO_MEM_SOFIT, $t);
		$t = preg_replace("<"."m".">", TO_MEM, $t);
		$t = preg_replace("<"."ĂŽn".">", TO_ALEPH.TO_SHEVA_NACH.TO_NUN_SOFIT, $t);
		$t = preg_replace("<"."Ă®n".">", TO_ALEPH.TO_SHEVA_NACH.TO_NUN_SOFIT, $t);
		$t = preg_replace("<"."n".">", TO_NUN, $t);
		$t = preg_replace("<"."Éł".">", TO_NUN_SOFIT, $t);
		$t = preg_replace("<"."s".">", TO_SAMECH, $t);
		$t = ($to === 'hebrew') ? preg_replace("<"."SPACEaSPACE".">", "SPACE".TO_ALEPH.TO_KAMETZ."SPACE", $t) : preg_replace("<"."SPACEaSPACE".">", "SPACE".TO_ALEPH."SPACE", $t);
		$t = preg_replace("<"."a".">", TO_AYIN, $t);
		$t = preg_replace("<"."o".">", TO_AYIN, $t);
		$t = preg_replace("<"."p".">", TO_PEI, $t);
		$t = preg_replace("<"."f".">", TO_PHEI_SOFIT, $t);
		$t = preg_replace("<"."ĹŁ".">", TO_TZADI, $t);
		$t = preg_replace("<"."ĹŁ".">", TO_TZADI_SOFIT, $t);
		$t = preg_replace("<"."q".">", TO_KUF, $t);
		$t = preg_replace("<"."r".">", TO_RESH, $t);
		$t = preg_replace("<"."Ĺź".">", TO_SHIN, $t);
		$t = preg_replace("<"."s".">", TO_SIN, $t);
		$t = preg_replace("<"."ĹźÄ".">", TO_SHIN_SHIN_DOT_KAMETZ, $t);
		$t = preg_replace("<"."ĹźĂ˘".">", TO_SHIN_SHIN_DOT_SHEVA_NACH, $t);
		$t = preg_replace("<"."Ĺź".">", TO_SHIN_NO_DOT, $t);
		$t = preg_replace("<"."t".">", TO_TAV, $t);
		$t = preg_replace("<"."t".">", TO_THAV, $t);
		
		$t = preg_replace("<BOUNDARY>", "Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", $t);		
		$t = preg_replace("<COMMA>", "Đ§ĐžĐśĐśĐ", $t);
		$t = preg_replace("<SPACE>", "ĐˇĐźĐĐ§Đ•", $t);		
		
		$t = preg_replace("<"."E".">", TO_ALEPH, $t);		
		$t = str_replace(array("BOUNDARY", "B"), array("Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", TO_BET), $t);
		$t = preg_replace("<"."V".">", TO_BHET, $t);		
		$t = preg_replace("<"."G".">", TO_GIMEL, $t);
		$t = preg_replace("<"."G".">", TO_GHIMEL, $t);
		$t = preg_replace("<"."Ä".">", TO_DALED, $t);
		$t = str_replace(array("BOUNDARY", "D"), array("Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", TO_DHALED), $t);
		$t = preg_replace("<"."H".">", TO_HEH, $t);
		$t = str_replace(array("BOUNDARY", "U"), array("Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", TO_VAV), $t);
		$t = preg_replace("<"."Z".">", TO_ZED, $t);
		$t = preg_replace("<"."Ä¤".">", TO_CHET, $t);
		$t = preg_replace("<"."Th".">", TO_TET, $t);
		$t = preg_replace("<"."I".">", TO_YUD_PLURAL, $t);
		$t = preg_replace("<"."IĂ®".">", TO_YUD.TO_SHEVA, $t);
		$t = str_replace(array("BOUNDARY", "Y"), array("Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", TO_YUD), $t);
		$t = preg_replace("<"."CĂ˘".">", TO_KHAF_KAMETZ, $t);
		$t = preg_replace("<"."CĂ®".">", TO_KHAF, $t);
		$t = preg_replace("<"."K".">", TO_KHAF_SOFIT, $t);
		$t = preg_replace("<"."KĂ‚".">", TO_KHAF_SOFIT.TO_SHEVA, $t);
		$t = preg_replace("<"."C".">", TO_KAF, $t);
		$t = preg_replace("<"."L".">", TO_LAMED, $t);
		$t = preg_replace("<"."MĂ®".">", TO_MEM.TO_SHEVA_NACH, $t);
		$t = preg_replace("<"."M".">", TO_MEM, $t);
		$t = preg_replace("<"."N".">", TO_NUN, $t);
		$t = str_replace(array("BOUNDARY", "N"), array("Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", TO_NUN), $t);
		$t = preg_replace("<"."S".">", TO_SAMECH, $t);
		$t = str_replace(array("BOUNDARY", "A"), array("Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", TO_AYIN), $t);
		$t = str_replace(array("BOUNDARY", "O"), array("Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", TO_AYIN), $t);
		$t = preg_replace("<"."P".">", TO_PEI, $t);
		$t = preg_replace("<"."F".">", TO_PHEI_SOFIT, $t);
		$t = preg_replace("<"."Ĺ˘".">", TO_TZADI, $t);
		$t = preg_replace("<"."Ĺ˘".">", TO_TZADI_SOFIT, $t);
		$t = preg_replace("<"."Q".">", TO_KUF, $t);
		$t = str_replace(array("BOUNDARY", "R"), array("Đ‘ĐžĐŁĐťĐ”ĐĐ Đ", TO_RESH), $t);
		$t = preg_replace("<"."Ĺž".">", TO_SHIN, $t);
		$t = preg_replace("<"."S".">", TO_SIN, $t);
		$t = preg_replace("<"."ĹžĂ˘".">", TO_SHIN_SHIN_DOT_KAMETZ, $t);
		$t = preg_replace("<"."ĹžĂ˘".">", TO_SHIN_SHIN_DOT_SHEVA_NACH, $t);
		$t = preg_replace("<"."Ĺž".">", TO_SHIN_NO_DOT, $t);
		$t = preg_replace("<"."T".">", TO_TAV, $t);
		$t = preg_replace("<"."T".">", TO_THAV, $t);
		
		$t = preg_replace("<Đ§ĐžĐśĐśĐ>", "COMMA", $t);
		$t = preg_replace("<ĐˇĐźĐĐ§Đ•>", "SPACE", $t);
		$t = preg_replace("<Đ‘ĐžĐŁĐťĐ”ĐĐ Đ>", "BONDUARY", $t);
		$t = preg_replace("<BONDUARY>", "", $t);
		$t = preg_replace("<×¤××¨×™×˘×“>", "×", $t);		
	}
	
	/* Vowels */
	if (($from !== 'ukrainian') && ($from !== 'romanian') && (($to === 'aramaic') || ($to === 'hebrew')))
	{		
		//Replace From  > To
		$t = preg_replace("<".HOLAM_VAV.">", TO_HOLAM_VAV, $t);
		$t = preg_replace("<".HOLAM_MEM.">", TO_HOLAM_MEM, $t);
		$t = preg_replace("<".HOLAM_LAMED.">", TO_HOLAM_LAMED, $t);
		$t = preg_replace("<".HOLAM_BHET.">", TO_HOLAM_BHET, $t);
		$t = preg_replace("<".HOLAM_TAV.">", TO_HOLAM_TAV, $t);
		$t = preg_replace("<".HOLAM_RESH.">", TO_HOLAM_RESH, $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", TO_HOLAM_HASHER_VAV, $t);
		
		//Consonants
		$t = preg_replace("<".ALEPH.">", TO_ALEPH, $t);
		$t = preg_replace("<".BET.">", TO_BET, $t);
		$t = preg_replace("<".BHET.">", TO_BHET, $t);
		$t = preg_replace("<".GIMEL.">", TO_GIMEL, $t);
		$t = preg_replace("<".GHIMEL.">", TO_GHIMEL, $t);
		$t = preg_replace("<".DALED.">", TO_DALED, $t);
		$t = preg_replace("<".DHALED.">", TO_DHALED, $t);
		$t = preg_replace("<".HEH_MAPIK.">", TO_HEH_MAPIK, $t);
		$t = preg_replace("<".HEH.">", TO_HEH, $t);
		$t = preg_replace("<".VAV.">", TO_VAV, $t);
		$t = preg_replace("<".ZED.">", TO_ZED, $t);
		$t = preg_replace("<".CHET.">", TO_CHET, $t);
		$t = preg_replace("<".TET.">", TO_TET, $t);
		$t = preg_replace("<".YUD_PLURAL.">", TO_YUD_PLURAL, $t);
		$t = preg_replace("<".YUD.SHEVA.">", TO_YUD.TO_SHEVA, $t);
		$t = preg_replace("< ".YUD.">", TO_YUD, $t);
		$t = preg_replace("<".YUD.">", TO_YUD, $t);
		$t = preg_replace("<".KHAF_KAMETZ.">", TO_KHAF_KAMETZ, $t);
		$t = preg_replace("<".KHAF.">", TO_KHAF, $t);
		$t = preg_replace("<".KAF.SHEVA_NACH.">", TO_KAF.SHEVA_NACH, $t);
		$t = preg_replace("<".KHAF_SOFIT.">", TO_KHAF_SOFIT, $t);
		$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", TO_KHAF_SOFIT.SHEVA, $t);
		$t = preg_replace("<".KAF.">", TO_KAF, $t);
		$t = preg_replace("<".LAMED.">", TO_LAMED, $t);
		$t = preg_replace("<".MEM.SHEVA_NACH.">", TO_MEM.SHEVA_NACH, $t);
		$t = preg_replace("<".MEM_SOFIT.">", TO_MEM_SOFIT, $t);
		$t = preg_replace("<".MEM.">", TO_MEM, $t);
		$t = preg_replace("<".NUN.">", TO_NUN, $t);
		$t = preg_replace("<".NUN_SOFIT.">", TO_NUN_SOFIT, $t);
		$t = preg_replace("<".SAMECH.">", TO_SAMECH, $t);
		$t = preg_replace("<".AYIN.">", TO_AYIN, $t);
		$t = preg_replace("<".PEI.">", TO_PEI, $t);
		$t = preg_replace("<".PHEI_SOFIT.">", TO_PHEI_SOFIT, $t);
		$t = preg_replace("<".TZADI.">", TO_TZADI, $t);
		$t = preg_replace("<".TZADI_SOFIT.">", TO_TZADI_SOFIT, $t);
		$t = preg_replace("<".KUF.">", TO_KUF, $t);
		$t = preg_replace("<".RESH.">", TO_RESH, $t);
		$t = preg_replace("<".SHIN.">", TO_SHIN, $t);
		$t = preg_replace("<".SIN.">", TO_SIN, $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", TO_SHIN_SHIN_DOT_KAMETZ, $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", TO_SHIN_SHIN_DOT_SHEVA_NACH, $t);
		$t = preg_replace("<".SHIN_NO_DOT.">", TO_SHIN_NO_DOT, $t);
		$t = preg_replace("<".TAV.">", TO_TAV, $t);
		$t = preg_replace("<".THAV.">", TO_THAV, $t);
	}	
	
	/* Vowels */
	if (($from !== 'hebrew') && ($from !== 'aramaic') && ($to === 'hebrew'))
	{	
		//Replace From  > To
		$t = preg_replace("<"."uĹŤ".">", TO_HOLAM_VAV, $t);
		$t = preg_replace("<"."mĹŤ".">", TO_HOLAM_MEM, $t);
		$t = preg_replace("<"."lĹŤ".">", TO_HOLAM_LAMED, $t);
		$t = preg_replace("<"."vĹŤ".">", TO_HOLAM_BHET, $t);
		$t = preg_replace("<"."tĹŤ".">", TO_HOLAM_TAV, $t);
		$t = preg_replace("<"."rĹŤ".">", TO_HOLAM_RESH, $t);
		$t = preg_replace("<"."uŃ".">", TO_HOLAM_HASHER_VAV, $t);
		
		$t = preg_replace("<"."Â·".">", "", $t);
		$t = preg_replace("<"."'".">", TO_TIPEHA, $t); 
		$t = preg_replace("<"."'".">", TO_MERKHA, $t); 
		$t = preg_replace("<"."''".">", TO_MERKHA_KEFULA, $t);		
		$t = preg_replace("<"."Â´".">", TO_MUNAH, $t);		
		$t = preg_replace("<"."'".">", TO_ETNAHTA, $t);		
		$t = preg_replace("<"."^".">", TO_ATNAH_HAFUKH, $t); 	
		$t = preg_replace("<"."Â°".">", TO_YERAH_BEN_YOMO, $t);	
		
		//Consonants
		$t = preg_replace("<"."e".">", TO_ALEPH, $t);
		$t = preg_replace("<"."EÄ".">", TO_ALEPH.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."EÄ".">", TO_ALEPH.TO_CHATAF_SEGOL, $t);
		$t = preg_replace("<"."b".">", TO_BET, $t);
		$t = preg_replace("<"."BÄ".">", TO_BET.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."v".">", TO_BHET, $t);
		$t = preg_replace("<"."VÄ".">", TO_BHET.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."g".">", TO_GIMEL, $t);
		$t = preg_replace("<"."GÄ".">", TO_GIMEL.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."g".">", TO_GHIMEL, $t);
		$t = preg_replace("<"."Ä‘".">", TO_DALED, $t);
		$t = preg_replace("<"."ÄÄ".">", TO_DALED.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."d".">", TO_DHALED, $t);
		$t = preg_replace("<"."DÄ".">", TO_DHALED.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."h".">", TO_HEH, $t);
		$t = preg_replace("<"."HÄ".">", TO_HEH.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."hÄ“".">", TO_HEH.TO_PATACH_GANUV, $t);
		$t = preg_replace("<"."u".">", TO_VAV, $t);
		$t = preg_replace("<"."UÄ".">", TO_VAV.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."ÄĄ".">", TO_CHET, $t);
		$t = preg_replace("<"."Ä¤Ä".">", TO_CHET.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."th".">", TO_TET, $t);
		$t = preg_replace("<"."IĹŤ".">", TO_YUD.TO_MAPIQ, $t);
		$t = preg_replace("<"."i".">", TO_YUD_PLURAL, $t);
		$t = preg_replace("<"."IÄ«".">", TO_YUD.TO_SHEVA, $t);
		$t = preg_replace("<"."iĂ®".">", TO_YUD.TO_SHEVA, $t);
		$t = preg_replace("< "." i".">", TO_YUD, $t);
		$t = preg_replace("<"."EÄ«".">", TO_ALEPH.TO_CHIRIK_MALEI, $t);
		$t = preg_replace("<"."y".">", TO_YUD, $t);
		$t = preg_replace("<"."cÄ".">", TO_KHAF_KAMETZ, $t);
		$t = preg_replace("<"."cĂ®".">", TO_KHAF, $t);
		$t = preg_replace("<"."cĂ®Ă˘".">", TO_KAF.TO_SHEVA_NACH, $t);
		$t = preg_replace("<"."k".">", TO_KHAF_SOFIT, $t);
		$t = preg_replace("<"."kĂ˘".">", TO_KHAF_SOFIT.TO_SHEVA, $t);
		$t = preg_replace("<"."c".">", TO_KAF, $t);
		$t = preg_replace("<"."LÄ".">", TO_LAMED.TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."l".">", TO_LAMED, $t);
		$t = preg_replace("<"."mĂ®".">", TO_MEM.TO_SHEVA_NACH, $t);
		$t = preg_replace("<"."MĹŤ".">", TO_MEM.TO_MAPIQ, $t);
		$t = preg_replace("<"."É±".">", TO_MEM_SOFIT, $t);
		$t = preg_replace("<"."m".">", TO_MEM, $t);
		$t = preg_replace("<"."mÄ“".">", TO_MEM.TO_PATACH_GANUV, $t);
		$t = preg_replace("<"."n".">", TO_NUN, $t);
		$t = preg_replace("<"."Éł".">", TO_NUN_SOFIT, $t);
		$t = preg_replace("<"."sĂ˘".">", TO_SIN.TO_SHEVA, $t);
		$t = preg_replace("<"."s".">", TO_SAMECH, $t);
		$t = preg_replace("<"."a".">", TO_AYIN, $t);
		$t = preg_replace("<"."PĹŤ".">", TO_PEI.TO_MAPIQ, $t);
		$t = preg_replace("<"."p".">", TO_PEI, $t);
		$t = preg_replace("<"."f".">", TO_PHEI_SOFIT, $t);
		$t = preg_replace("<"."ĹŁ".">", TO_TZADI, $t);
		$t = preg_replace("<"."ĹŁ ".">", TO_TZADI_SOFIT . " ", $t);
		$t = preg_replace("<"."q".">", TO_KUF, $t);
		$t = preg_replace("<"."r".">", TO_RESH, $t);
		$t = preg_replace("<"."ĹźÄ".">", TO_SHIN_SHIN_DOT_KAMETZ, $t);
		$t = preg_replace("<"."ĹźĂ˘".">", TO_SHIN_SHIN_DOT_SHEVA_NACH, $t);
		$t = preg_replace("<"."Ĺź".">", TO_SHIN_NO_DOT, $t);
		$t = preg_replace("<"."s".">", TO_SIN, $t);
		$t = preg_replace("<"."TĹŤ".">", TO_TAV.TO_MAPIQ, $t);
		$t = preg_replace("<"."t".">", TO_TAV, $t);
		$t = preg_replace("<"."t".">", TO_THAV, $t);
		$t = preg_replace("<"."z".">", TO_ZED, $t);
		$t = preg_replace("<"."ZÄ".">", TO_ZED.TO_CHATAF_KAMETZ, $t);
		
		$t = preg_replace("<"."Ä".">", TO_CHATAF_KAMETZ, $t);
		$t = preg_replace("<"."Ä".">", TO_KAMETZ_KATAN, $t);
		$t = preg_replace("<"."Ä".">", TO_KAMETZ, $t);
		$t = preg_replace("<"."Ä".">", TO_CHATAF_PATACH, $t);
		$t = preg_replace("<"."Ä“".">", TO_PATACH, $t);
		$t = preg_replace("<"."Ä›".">", TO_PATACH_GANUV, $t);
		$t = preg_replace("<"."Ă®".">", TO_SHEVA_NACH, $t);
		$t = preg_replace("<"."Ă˘".">", TO_SHEVA, $t);
		$t = preg_replace("<"."Ä".">", TO_CHATAF_SEGOL, $t);
		$t = preg_replace("<"."Ä™".">", TO_SEGOL, $t);
		$t = preg_replace("<"."Ă©".">", TO_TZEIREI_MALEI, $t);
		$t = preg_replace("<"."Ă©".">", TO_TZEIREI_CHASER, $t);
		$t = preg_replace("<"."Ä«".">", TO_CHIRIK_MALEI, $t);
		$t = preg_replace("<"."Ä«".">", TO_CHIRIK_CHASER, $t);
		$t = preg_replace("<"."Ăł".">", TO_HOLAM_HASHER, $t);
		$t = preg_replace("<"."ĹŤ".">", TO_CHOLAM_MALEI, $t);
		$t = preg_replace("<"."ĹŤ".">", TO_CHOLAM_CHASER, $t);
		$t = preg_replace("<"."ĹŤ".">", TO_MAPIQ, $t);
		$t = preg_replace("<"."a".">", TO_METEG, $t);
		$t = preg_replace("<"."Ĺ«".">", TO_KUBUTZ, $t);
	}				
	
	if (($from !== 'aramaic') && ($from !== 'hebrew') && ($to === 'aramaic')) 
	{
		$t = preg_replace("<"."EÄ".">", TO_ALEPH.TO_PTHAHA_DOWN, $t);
		$t = preg_replace("<"."EÄ".">", TO_ALEPH.TO_RBASA_DOTTED, $t);
		$t = preg_replace("<"."hÄ™".">", TO_RUKKAKHA_UP_ZLAMA_ANGULAR, $t);
		$t = preg_replace("<"."iĂ®".">", TO_YUD.TO_QUSHSHAYA, $t);
		$t = preg_replace("<"."Ă¤".">", TO_PTHAHA_UP, $t);
		$t = preg_replace("<"."Ä".">", TO_PTHAHA_DOWN, $t);		 
		$t = preg_replace("<"."ĂĽ".">", TO_PTHAHA_DOTTED, $t); 
		$t = preg_replace("<"."Ĺ‘".">", TO_ZQAPHA_UP, $t);
		$t = preg_replace("<"."Ă¶".">", TO_ZQAPHA_DOWN, $t); 		 
		$t = preg_replace("<"."Ăą".">", TO_ZQAPHA_DOTTED, $t);
		$t = preg_replace("<"."Ă ".">", TO_RBASA_UP, $t); 
		$t = preg_replace("<"."Ä“".">", TO_RBASA_DOWN, $t);
		$t = preg_replace("<"."ĹŻ".">", TO_RBASA_DOTTED, $t);  
		$t = preg_replace("<"."Ă©".">", TO_ZLAMA_ANGULAR, $t); 
		$t = preg_replace("<"."Ă¬".">", TO_ZLAMA_UP, $t);
		$t = preg_replace("<"."Ä«".">", TO_ZLAMA_DOWN, $t);		 
		$t = preg_replace("<"."Ă˝".">", TO_ZLAMA_DOTTED, $t); 
		$t = preg_replace("<"."Ăł".">", TO_ESASA_UP, $t);
		$t = preg_replace("<"."ĹŤ".">", TO_ESASA_DOWN, $t);		
		$t = preg_replace("<"."y".">", TO_RWAHA, $t);
		$t = preg_replace("<"."Ä…".">", TO_FEMININE_DOT, $t);
		$t = preg_replace("<"."dĂ˘".">", TO_DALED.TO_QUSHSHAYA, $t);
		$t = preg_replace("<"."dĂ®".">", TO_DHALED.TO_QUSHSHAYA, $t);
		$t = preg_replace("<"."mĂ®".">", TO_MEM.TO_QUSHSHAYA, $t);		 
		$t = preg_replace("<"."qĂ˘".">", TO_KUF.TO_QUSHSHAYA, $t);
		$t = preg_replace("<"."Ă®".">", TO_QUSHSHAYA, $t);
		$t = preg_replace("<"."Ă˘".">", TO_RUKKAKHA, $t);
		$t = preg_replace("<"."Ä".">", TO_THREE_DOTS_DOWN, $t);
		$t = preg_replace("<"."ĂĄ".">", TO_VERTICAL_DOTS_UP, $t); 
		$t = preg_replace("<"."Ń‘".">", TO_VERTICAL_DOTS_DOWN, $t); 
		$t = preg_replace("<"."Ĺ«".">", TO_THREE_DOTS_UP, $t);
		$t = preg_replace("<"."Ä™".">", TO_THREE_DOTS_DOWN, $t);		
		$t = preg_replace("<"."Ä«".">", TO_OBLIQUE_LINE_UP, $t);		
		$t = preg_replace("<"."Ń‘".">", TO_OBLIQUE_LINE_DOWN, $t); 		
		$t = preg_replace("<"."#".">", TO_MUSIC, $t);
		$t = preg_replace("<"."\+".">", TO_BARREKH, $t);		
		$t = preg_replace("<"."Öľ".">", TO_MAQAF, $t);
		$t = preg_replace("<Ü¦ÜÜŞÜťÜĄÜ•>", "Ü€", $t);		
	}	 

	//	 ÜźÝÜ¬Ý‚ÜµÜ’Ý‚ÜµÜ Ü•ÝÜťÜ ÜĽÜťÜ•Ý‚ÜÜżÜ¬Ý‚ÜąÜ— Ü•Ý‚ÝÜťÜąÜ«ÜÜżÜĄ ÜˇÜ«ÜĽÜťÜšÜµÜ Ü’Ý‚ÝÜ¸ÜŞÜąÜ— Ü•Ý‚ÝÜ•Ý‚Ü¸ÜÜĽÜťÜ•Ý‚ Ü’Ý‚ÝÜ¸ÜŞÜąÜ— Ü•ÝÜ˛ÜÜ’Ý‚ÜŞÜµÜ—ÜµÜˇ Ü€ 
	if (($from === 'aramaic') && ($to === 'hebrew')) 
	{
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", TO_SEGOL, $t);
		$t = preg_replace("<".PTHAHA_UP.">", TO_KAMETZ_KATAN, $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", TO_KAMETZ_KATAN, $t);
		$t = preg_replace("<".PTHAHA_DOTTED.">", TO_KAMETZ_KATAN, $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", TO_KAMETZ_KATAN, $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", TO_KAMETZ_KATAN, $t);
		$t = preg_replace("<".ZQAPHA_DOTTED.">", TO_KAMETZ_KATAN, $t);
		$t = preg_replace("<".RBASA_UP.">", TO_PATACH, $t);
		$t = preg_replace("<".RBASA_DOWN.">", TO_PATACH_GANUV, $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", TO_CHATAF_SEGOL, $t);  
		$t = preg_replace("<".ZLAMA_ANGULAR.">", TO_SEGOL, $t); //Ĺ« 
		$t = preg_replace("<".ZLAMA_UP.">", TO_CHIRIK, $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", TO_CHIRIK_MALEI, $t);	
		$t = preg_replace("<".ZLAMA_DOTTED.">", TO_CHIRIK, $t);  
		$t = preg_replace("<".ESASA_UP.">", TO_CHOLAM_MALEI, $t);
		$t = preg_replace("<".ESASA_DOWN.">", TO_KAMETZ_KATAN, $t);		
		$t = preg_replace("<".RWAHA.">", TO_HOLAM_HASHER, $t);
		$t = preg_replace("<".FEMININE_DOT.">", TO_SIN_DOT, $t);
		$t = preg_replace("<".DALED.QUSHSHAYA.">", TO_DALED.TO_SHEVA, $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", TO_DHALED.TO_SHEVA, $t);
		$t = preg_replace("<".MEM.TO_SHEVA.">", TO_MEM.TO_SHEVA, $t);
		$t = preg_replace("<".QUSHSHAYA.">", TO_SHEVA, $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", TO_KUF.TO_SHEVA, $t); 
		$t = preg_replace("<".RUKKAKHA.">", TO_SHEVA, $t);
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", TO_TZEIREI_CHASER, $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", TO_CHATAF_SEGOL, $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", TO_KUBUTZ, $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", TO_SEGOL, $t);		
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", TO_RAFE, $t);		
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", TO_MERKHA, $t); 		
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".BARREKH.">", "(+)", $t);		
		$t = preg_replace("<".MAQAF.">", TO_MAQAF, $t);		
	}
	
	/* Vowels */
	if (($from === 'hebrew') && ($to == 'aramaic'))
	{	
		$t = preg_replace("<".SEGOL.">", TO_RUKKAKHA_UP_ZLAMA_ANGULAR, $t);
		
		//$t = preg_replace("<".PTHAHA_UP.">", PTHAHA_UP, $t);
		//$t = preg_replace("<".PTHAHA_DOWN.">", PTHAHA_DOWN, $t);
		//$t = preg_replace("<".PTHAHA_DOTTED.">", PTHAHA_DOTTED, $t);
		//$t = preg_replace("<".ZQAPHA_UP.">", ZQAPHA_UP, $t);
		//$t = preg_replace("<".ZQAPHA_DOWN.">", ZQAPHA_DOWN, $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", TO_ZQAPHA_DOTTED, $t);
		$t = preg_replace("<".PATACH.">", TO_RBASA_UP, $t);
		$t = preg_replace("<".PATACH_GANUV.">", TO_RBASA_DOWN, $t); 
		$t = preg_replace("<".CHATAF_SEGOL.">", TO_RBASA_DOTTED, $t);  
		$t = preg_replace("<".KUBUTZ.">", TO_ZLAMA_ANGULAR, $t); 
		$t = preg_replace("<".CHIRIK.">", TO_ZLAMA_UP, $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", TO_ZLAMA_DOWN, $t);	
		$t = preg_replace("<".CHIRIK.">", TO_ZLAMA_DOTTED, $t);  
		$t = preg_replace("<".CHOLAM_MALEI.">", TO_ESASA_UP, $t);
		//$t = preg_replace("<".ESASA_DOWN.">", TO_ESASA_DOWN, $t);		
		$t = preg_replace("<".CHIRIK.">", TO_RWAHA, $t);
		$t = preg_replace("<".SIN_DOT.">", TO_FEMININE_DOT, $t);
		$t = preg_replace("<".DALED.SHEVA.">", TO_DALED.TO_QUSHSHAYA, $t);
		$t = preg_replace("<".DHALED.SHEVA.">", TO_DHALED.TO_QUSHSHAYA, $t);
		$t = preg_replace("<".MEM.SHEVA.">", TO_MEM.TO_QUSHSHAYA, $t);
		$t = preg_replace("<".SHEVA.">", TO_QUSHSHAYA, $t);		
		$t = preg_replace("<".KUF.SHEVA.">", TO_KUF.TO_QUSHSHAYA, $t); 
		$t = preg_replace("<".SHEVA.">", TO_RUKKAKHA, $t);
		//$t = preg_replace("<".VERTICAL_DOTS_UP.">", TO_VERTICAL_DOTS_UP, $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", TO_VERTICAL_DOTS_DOWN, $t); 
		$t = preg_replace("<".KUBUTZ.">", TO_THREE_DOTS_UP, $t);
		$t = preg_replace("<".SEGOL.">", TO_THREE_DOTS_DOWN, $t);		
		$t = preg_replace("<".RAFE.">", TO_OBLIQUE_LINE_UP, $t);		
		$t = preg_replace("<".MERKHA.">", TO_OBLIQUE_LINE_DOWN, $t); 		
		$t = preg_replace("<"."#".">", TO_MUSIC, $t);
		$t = preg_replace("<"."×".">", 'Ü€', $t);
		//$t = preg_replace("<".BARREKH.">", TO_BARREKH, $t);		
		//$t = preg_replace("<".MAQAF.">", TO_MAQAF, $t);	
	}
	
	//CleanUp
	$t = preg_replace("<Â·Â·>", "Â·", $t);
	$t = preg_replace("< >", " ", $t);			
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	
	return $t;
}

/**
*
*/
function AcademicSpirantization($t, $f)
{
	if (($f === 'hebrew') || ($f === 'aramaic'))
	{
		// do not double letters in general
		$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
		$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
		
		$t = preg_replace("<".HOLAM_VAV.">", "wĹŤ", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "mĹŤ", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "lĹŤ", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "vĹŤ", $t);
		$t = preg_replace("<".HOLAM_TAV.">", "tĹŤ", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "rĹŤ", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "wŃ", $t);	
		
		//Consonants
		$t = preg_replace("<".ALEPH.">", "Ęľ", $t);
		$t = preg_replace("<".BET.">", "b", $t);
		$t = preg_replace("<".BHET.">", "bĚ±", $t);
		$t = preg_replace("<".GIMEL.">", "g", $t);
		$t = preg_replace("<".GHIMEL.">", "gĚ±", $t);
		$t = preg_replace("<".DALED.">", "d", $t);
		$t = preg_replace("<".DHALED.">", "dĚ±", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
		$t = preg_replace("<".HEH.">", "h", $t);
		$t = preg_replace("<".VAV.">", "w", $t);
		$t = preg_replace("<".ZED.">", "z", $t);
		$t = preg_replace("<".CHET.">", "á¸Ą", $t);
		$t = preg_replace("<".TET.">", "áą­", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "y", $t);
		$t = preg_replace("< ".YUD.">", "y", $t);
		$t = preg_replace("<".YUD.SHEVA.">", "iÇť", $t);
		$t = preg_replace("<".YUD.">", "y", $t);
		$t = preg_replace("<".KAF.">", "k", $t);
		$t = preg_replace("<".KHAF.">", "c", $t);
		$t = preg_replace("<".KAF.SHEVA_NACH.">", "kÇť", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "kĚ±", $t);
		$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", "kĚ±Çť", $t);
		$t = preg_replace("<".LAMED.">", "l", $t);
		$t = preg_replace("<".MEM.">", "m", $t);
		$t = preg_replace("<".MEM.SHEVA_NACH.">", "mÇť", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "É±", $t);
		$t = preg_replace("<".NUN.">", "n", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "Éł", $t);
		$t = preg_replace("<".SAMECH.">", "s", $t);
		$t = preg_replace("<".AYIN.">", "a", $t);
		$t = preg_replace("<".PEI.">", "p", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "f", $t);
		$t = preg_replace("<".TZADI.">", "áąŁ", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "áąŁ", $t);
		$t = preg_replace("<".KUF.">", "q", $t);
		$t = preg_replace("<".RESH.">", "r", $t);
		$t = preg_replace("<".SHIN_NO_DOT.">", "Ĺˇ", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "ĹˇÇť", $t);
		$t = preg_replace("<".SHIN.">", "Ĺˇ", $t);
		$t = preg_replace("<".SIN.">", "Ĺ›", $t);
		$t = preg_replace("<".TAV.">", "t", $t);
		$t = preg_replace("<".THAV.">", "tĚ±", $t);	
	}
	
	if ($f === 'hebrew')
	{	
		/* Vowels Äyw */
		$t = preg_replace("<".CHATAF_KAMETZ.">", "ĹŹ", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "Ä", $t);
		$t = preg_replace("<".KAMETZ.">", "Ä", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "Çť", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "Ä›", $t);
		$t = preg_replace("<".PATACH.">", "Ä“", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "Çť", $t);
		$t = preg_replace("<".SHEVA.">", "Çť", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "Ä•", $t);
		$t = preg_replace("<".SEGOL.">", "e", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "Ă©", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "Ă©", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "Ä«", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "Ä«", $t);
		$t = preg_replace("<".HOLAM_HASHER.">", "Ăł", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "ĹŤ", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ĹŤ", $t);
		$t = preg_replace("<".MAPIQ.">", "ĹŤ", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "Ĺ«", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);	
		$t = preg_replace("<Öą >", "Ĺ‘", $t);
		$t = preg_replace("<Öą>", "ĹŤ", $t);
		
		//Line marks
		$t = preg_replace("<Ö¤>", "'", $t);
		$t = preg_replace("<Ö™>", "'", $t);
		$t = preg_replace("<Öś>", "'", $t);
		$t = preg_replace("<Ö >", "'", $t);
		$t = preg_replace("<Ö”>", "", $t); //"remove"
		$t = preg_replace("<Ö›>", "'", $t);
		$t = preg_replace("<Ö—>", "Ĺ‘", $t);
	}	
	
	if ($f === 'aramaic') 
	{		
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", "hÄ™", $t);
		$t = preg_replace("<".PTHAHA_UP.">", "Ă¤", $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", "Ä›", $t); 
		$t = preg_replace("<".PTHAHA_DOTTED.">", "ĂĽ", $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", "ĹŻ", $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", "Ăą", $t); 
		$t = preg_replace("<".ZQAPHA_DOTTED.">", "Ä", $t); 
		$t = preg_replace("<".RBASA_UP.">", "Ă ", $t);
		$t = preg_replace("<".RBASA_DOWN.">", "Ä“", $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", "Ĺ‘", $t); 
		$t = preg_replace("<".ZLAMA_ANGULAR.">", "Ă©", $t); 
		$t = preg_replace("<".ZLAMA_UP.">", "Ă˛", $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", "y", $t); 
		$t = preg_replace("<".ZLAMA_DOTTED.">", "Ä«", $t); 
		$t = preg_replace("<".ESASA_UP.">", "Ă¬", $t);
		$t = preg_replace("<".ESASA_DOWN.">", "Ă˝", $t); 
		$t = preg_replace("<".RWAHA.">", "ĹŤ", $t);
		$t = preg_replace("<".FEMININE_DOT.">", "Ä…", $t); 
		$t = preg_replace("<".DALED.QUSHSHAYA.">", "dÇť", $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", "dÇť", $t);
		$t = preg_replace("<".MEM.QUSHSHAYA.">", "mÇť", $t);
		$t = preg_replace("<".QUSHSHAYA.">", "Çť", $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", "qÇť", $t); 
		$t = preg_replace("<".RUKKAKHA.">", "Çť", $t);
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", "ĂĄ", $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", "Ĺ«", $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", "Ä™", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", "Ăł", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", "Ń‘", $t); 
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".BARREKH.">", "\+", $t); 
		$t = preg_replace("<".MAQAF.">", "Öľ", $t);

		$t = preg_replace("<BOUNDARY>", "BOUNDARY", $t);
		$t = preg_replace("<COMMA>", ",", $t);
		$t = preg_replace("<DASH>", "-", $t);
		$t = preg_replace("<SEMICOLON>", ";", $t);
		$t = preg_replace("<PERIOD>", ".", $t);
		$t = preg_replace("< >", " ", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);	 		
	}	
	
	//Second Step;
	$t = preg_replace("<Öľ>", "-", $t);
	$t = preg_replace("<ÇťkÄ'>", "ÇťÂ·kÄ'", $t);
	$t = preg_replace("<ÇťkÄ>", "Ă®ÇťÂ·kÄ", $t);
	$t = preg_replace("<iÄ“aaÄ“vÇťdĹ«anÄ«i>", "iÄ“Â·aaÄ“vÇťdĹ«anÄ«Â·i", $t);
	$t = preg_replace("<ÖąĹź>", "ĹŤĹź", $t);
	$t = preg_replace("<Öąr>", "ĹŤr", $t);
	$t = preg_replace("<Öąt>", "ĹŤt", $t);
	$t = preg_replace("<Öąp>", "p", $t);
	$t = preg_replace("<Öąe>", "Ăłe", $t);
	$t = preg_replace("<ĹŤÄ>", "Ä", $t);
	$t = preg_replace("<ĹŤĹŤ>", "ĹŤ", $t);
	$t = preg_replace("<ĹŤÇť>", "Çť", $t);
	$t = preg_replace("<ĹŤÄ«>", "Ä«", $t);
	$t = preg_replace("<ĹŤÇť>", "Çť", $t);
	$t = preg_replace("<iÇťhuÄ'h>", "IÇťhuÄh", $t);	
	$t = preg_replace("<iÇťhuÄh>", "IÇťhuÄh", $t);
	$t = preg_replace("<uÇťruĹŤÄĄÄ“ eÄlĹŤhiÉ±>", "uÇťÂ·RuĹŤÄĄÄ“ EÄlĹŤhiÉ±", $t);
	$t = preg_replace("<mĹŤĹˇÄ™h>", "MĹŤĹˇÄ™h", $t);
	$t = preg_replace("<ÇťÇť>", "Çť", $t);	
	$t = preg_replace("<iÄ«sÇťrÄeĂ©l>", "IÄ«sÇťrÄeEĂ©l", $t);	
	$t = preg_replace("<iÄ«sÇťrÄeĂ©l>", "IÄ«sÇťrÄeEÄ«l", $t);
	$t = preg_replace("<iĹŤÄ“rĂ®dĂ©Éł>", "IĹŤÄ“rĂ˘dĂ©Éł", $t);
	$t = preg_replace("< iÄ«>", " iÄ«Â·", $t);
	$t = preg_replace("<Â·iÄ«>", "Â·iÄ«Â·", $t);
	$t = preg_replace("< iÇť>", " iÇťÂ·", $t);
	$t = preg_replace("<Â·iÇť>", "Â·iÇťÂ·", $t);
	$t = preg_replace("< iĹŤ>", " iĹŤÂ·", $t);
	$t = preg_replace("< wÄ“>", " wÄ“Â·", $t);
	$t = preg_replace("<wÄ“Â·tÄ“>", "wÄ“Â·tÄ“Â·", $t);
	$t = preg_replace("< wÇť>", " wÇťÂ·", $t);
	$t = preg_replace("< wĹŤ>", " wĹŤÂ·", $t);
	$t = preg_replace("< wÄ>", " wÄÂ·", $t);
	$t = preg_replace("< bÇť>", " bÇťÂ·", $t);
	$t = preg_replace("< bÄ“>", " bÄ“Â·", $t);
	$t = preg_replace("< wÇť>", " wÇťÂ·", $t);
	$t = preg_replace("< bĂ˘>", " bÇťÂ·", $t);
	$t = preg_replace("< bÄ>", " bÄÂ·", $t);
	$t = preg_replace("< wÄ“Â·wÄ“>", " wÄ“Â·wÄ“Â·", $t);
	$t = preg_replace("<wÄ“Â·iĹŤĂł>", "uÄ“Â·iĹŤĂłÂ·", $t);
	$t = preg_replace("<bÇťÂ·Ä>", "bÇťÄ", $t);	
	$t = preg_replace("<bÄÂ·r>", "bÄr", $t);
	$t = preg_replace("<bÇťÂ·Ä>", "bÇťÄ", $t);
	$t = preg_replace("< lĂ©>", " lĂ©Â·", $t);
	$t = preg_replace("< lÄ“>", " lÄ“Â·", $t);
	$t = preg_replace("< hÄ“>", " hÄ“Â·", $t);
	$t = preg_replace("<hÄ“Â·r>", "hÄ“r", $t);
	$t = preg_replace("<ÄaÉ±>", "aÉ±", $t);
	$t = preg_replace("<hÄiÇťtÄh>", "hÄiÇťÂ·tÄh", $t);
	$t = preg_replace("< hÄ>", " hÄÂ·", $t);
	$t = preg_replace("<-'hÄ“>", "-'hÄ“Â·", $t);
	$t = preg_replace("<-hÄ“>", "-hÄ“Â·", $t);
	$t = preg_replace("<hÄeĹŤ'hÄ™l>", "hÄÂ·eĹŤ'hÄ™l", $t);
	$t = preg_replace("<hÄeÄrÄ™áąŁ>", "hÄÂ·eÄrÄ™áąŁ", $t);
	$t = preg_replace("< bÇťÂ·Ă©iÉł>", " bÇťĂ©iÉł", $t);
	$t = preg_replace("< mÄ«>", " mÄ«Â·", $t);
	$t = preg_replace("<bÇťÄaÄ>", "bÇťÄÂ·aÄ", $t);
	$t = preg_replace("<pĹŤÄerÄÉł>", "PĹŤÄerÄÉł", $t);		
	$t = preg_replace("<aĂ©vÄ™r hÄ“Â·IĹŤÄ“rĂ˘dĂ©Éł>", "AĂ©vÄ™r hÄ“Â·IĹŤÄ“rĂ˘dĂ©Éł", $t);
	$t = preg_replace("<iĂłeĂ©mÄ™r>", "iĂłÂ·eĂ©mÄ™r", $t);
	$t = preg_replace("<iĹŤeĂ©mÄ™r>", "iĹŤÂ·eĂ©mÄ™r", $t);
	$t = preg_replace("<wÄ“Â·iÇťhi>", "wÄ“Â·iÇťÂ·hi", $t);
	$t = preg_replace("<wÄ“Â·iÇť>", "wÄ“Â·iÇťÂ·", $t);
	$t = preg_replace("<Ä«i'>", "Ä«Â·i'", $t);
	$t = preg_replace("<Ä«i >", "Ä«Â·i ", $t);
	$t = preg_replace("<iÇťÂ·lÄdÄ“'i>", "iÇťlÄdÄ“'i", $t);
	$t = preg_replace("<iÇťÂ·lÄdÄ“i>", "iÇťlÄdÄ“Â·i", $t);
	$t = preg_replace("<iÄrĂ©'e>", "iÇťlÄdÄ“Â·i", $t);
	$t = preg_replace("<eÄ«iĹźÄ«i>", "eÄ«iĹźÄ«Â·i", $t);
	$t = preg_replace("< bÄÂ·Ĺ‘e>", " bÄĹ‘e", $t);	
	$t = preg_replace("< lÄ>", " lÄÂ·", $t);
	$t = preg_replace("< lÄ“>", " lÄ“Â·", $t);
	$t = preg_replace("< lĂ©>", " lĂ©Â·", $t);
	$t = preg_replace("< lÄ™>", " lÄ™Â·", $t);
	$t = preg_replace("< lŃ>", " lŃÂ·", $t);
	$t = preg_replace("< lÄ«>", " lÄ«Â·", $t);
	$t = preg_replace("< lÇť>", " lÇťÂ·", $t);
	$t = preg_replace("<bÄÂ·aÄrÄvÄh muÖąl suĹŤf>", "bÄÂ·AÄrÄvÄh MuÖąl SuĹŤf", $t);
	$t = preg_replace("<tpÄ™l>", "TĹŤpÄ™l", $t);
	$t = preg_replace("<tÇťh>", "tÇťh", $t);
	$t = preg_replace("<lÄvÄÉł>", "LÄvÄÉł", $t);
	$t = preg_replace("<ÄĄÄáąŁĂ©rĹŤt>", "Ä¤ÄáąŁĂ©rĹŤt", $t);
	$t = preg_replace("<iÇťÂ·huÄh>", "IÇťhuÄh", $t); //DIVINE NAME
	$t = preg_replace("<eÄlĹŤh'iÉ±>", "EÄlĹŤh'iÉ±", $t); //Westmister Institute Punctuation for Leningrad Codex
	$t = preg_replace("<eÄlĹŤhiÉ±>", "EÄlĹŤhiÉ±", $t); //Oxford University Simple Punctuation for Leningrad Codex
	$t = preg_replace("<eĂ©lÄiu>", "eĂ©lÄiÂ·u", $t);
	$t = preg_replace("<eÄvÄ«iu>", "eÄvÄ«iÂ·u", $t);
	$t = preg_replace("<eÄnÄ«i>", "eÄnÄ«Â·i", $t);
	$t = preg_replace("<ĹˇÇťmÄ«i>", "ĹˇÇťmÄ«Â·i", $t);
	$t = preg_replace("<aÄ“mÄ«i>", "aÄ“mÄ«Â·i", $t);
	$t = preg_replace("<rĹŤeĹˇÄ«i>", "rĹŤeĹˇÄ«Â·i", $t);
	$t = preg_replace("<eÄ«mĹŤuŃ>", "eÄ«mĹŤÂ·uŃ", $t);
	$t = preg_replace("<eÄ“vÇťrÄhÄÉ±>", "EÄ“vÇťrÄhÄÉ±", $t);
	$t = preg_replace("<iÄ«Â·áąŁÇťÄĄÄq>", "IÄ«áąŁÇťÄĄÄq", $t);
	$t = preg_replace("<iÄ“aÄqĹŤv>", "IÄ“aÄqĹŤv", $t);	
	$t = preg_replace("<eĂ©l ĹźÄ“dÄi>", "EĂ©l Ĺ Ä“dÄi", $t);
	$t = preg_replace("<ĹˇÄ“dÄi>", "Ĺ Ä“dÄi", $t);
	$t = preg_replace("<nŃdÄ“aÇťtÄ«i>", "nŃÂ·dÄ“aÇťtÄ«Â·i", $t);
	$t = preg_replace("<sÄeĂ©huĹŤ>", "sÄeĂ©Â·huĹŤ", $t);	
	$t = preg_replace("<lÄhÄ™É±>", "lÄÂ·hÄ™É±", $t);
	$t = preg_replace("<pÄerÄÉł>", "PÄerÄÉł", $t); 
	$t = preg_replace("<tĹŤpÄ™l>", "TĹŤpÄ™l", $t);
	$t = preg_replace("<mĂ©ÄĄĹŤrĂ©v>", "mĂ©Â·Ä¤ĹŤrĂ©v", $t);
	$t = preg_replace("<ÄĄĹŤrĂ©v>", "Ä¤ĹŤrĂ©v", $t);
	$t = preg_replace("<cÇťÄĄĹŤ'É±>", "cÇťÄĄĹŤ'É±", $t);	
	$t = preg_replace("<sĂ©aÄ«ir>", "SĂ©aÄ«ir", $t); 
	$t = preg_replace("<qÄdĂ©Ĺˇ>", "QÄdĂ©Ĺˇ", $t);
	$t = preg_replace("<bÄ“Â·rÇťnĂ©aÄ“>", "BÄ“rÇťnĂ©aÄ“", $t);	
	$t = preg_replace("<mÄ“mÇťrĂ©'e>", "MÄ“mÇťrĂ©'e", $t);
	$t = preg_replace("<bÇťÂ·eĂ©lĹŤnĂ©'i>", "bÇťÂ·EĂ©lĹŤnĂ©'i", $t);
	$t = preg_replace("<eÄlÄ«iĹˇÄ'a>", "EÄlÄ«iĹˇÄ'a", $t);
	$t = preg_replace("<eÄlÄ«iĹˇÄa>", "EÄlÄ«iĹˇÄa", $t);
	$t = preg_replace("<eÄlÄ«iĹˇÄ>", "EÄlÄ«iĹˇÄ", $t);
	$t = preg_replace("<ĹˇuĹŤnĂ©Ĺ‘É±>", "Ĺ uĹŤnĂ©Ĺ‘É±", $t);	
	
	ExtractTrup();
	
	$t = CleanUpPunctuation($t);
	return $t;
}

function RomanioteTransliteration($t, $f)
{	
	if ($f == 'romanian')
	{	
		
		$greek_dia_lc = array('Îµ', 'ÎşÎł', ' Îµ', 'Îµ', 'Îµ', 'Îµ ');
		$greek_dia_uc = array('Î•', 'ÎšÎł', ' Î•', 'Î•', 'Î•', 'Î• ');
		
		$rom_dia_lc = array('Ä', 'kg', ' Ă®', 'Ă˘', 'Ă®', 'Ă® ');
		$rom_dia_uc = array('Ä‚', 'Kg', ' ĂŽ', 'Ă‚', 'ĂŽ', 'ĂŽ ');	
		
		//Credit: ĐˇĐ°ŃĐ° ĐˇŃ‚Đ°ĐĽĐµĐ˝ĐşĐľĐ˛Đ¸Ń› <umpirsky@gmail.com>
		//@see http://en.wikipedia.org/wiki/Romanization_of_Greek
		$greek_lc = array('Î±', 'Î˛', 'Îł', 'Î´', 'Îµ', 'Î¶', 'Î·', 'Î¸', 'Îą', 'Îş', 'Î»', 'ÎĽ', 'Î˝', 'Îľ', 'Îż', 'Ď€', 'Ď', 'Ď', 'Ď„', 'Ď…', 'Ď†', 'Ď‡', 'Ď', 'Ď‰');
		$greek_uc = array('Î‘', 'Î’', 'Î“', 'Î”', 'Î•', 'Î–', 'Î—', 'Î', 'Î™', 'Îš', 'Î›', 'Îś', 'Îť', 'Îž', 'Îź', 'Î ', 'Îˇ', 'ÎŁ', 'Î¤', 'ÎĄ', 'Î¦', 'Î§', 'Î¨', 'Î©');
			
		$rom_lc = array('a', 'b', 'g', 'd', 'e', 'z', 'h', 'q', 'i', 'k', 'l', 'm', 'n', 'c', 'o', 'p', 'r', 's', 't', 'u', 'f', 'x', 'y', 'w');
		$rom_uc = array('A', 'B', 'G', 'D', 'E', 'Z', 'H', 'Q', 'I', 'K', 'L', 'M', 'N', 'C', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'Y', 'W');
		
		$lat_lc = array('a', 'b', 'g', 'd', 'e', 'z', 'h', 'q', 'i', 'k', 'l', 'm', 'n', 'c', 'o', 'p', 'r', 's', 't', 'u', 'f', 'x', 'y', 'w');
		$lat_uc = array('A', 'B', 'G', 'D', 'E', 'Z', 'H', 'Q', 'I', 'K', 'L', 'M', 'N', 'C', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'Y', 'W');
		
		$t = preg_replace("<BONDUARY>", "", $t);
		$t = preg_replace("<PERIOD>", "", $t);
		$t = preg_replace("<COPPA>", "", $t);
		$t = preg_replace("<SPACE>", "SPACE", $t);						
		$t = str_replace($rom_dia_lc, $greek_dia_lc, $t);	
		$t = str_replace($rom_dia_uc, $greek_dia_uc, $t);
		
		$t = str_replace($rom_lc, $greek_lc, $t);	
		$t = preg_replace("<SPACE>", "space", $t);
		$t = str_replace($rom_uc, $greek_uc, $t);
		$t = preg_replace("<space>", "SPACE", $t);
		$t = preg_replace("<Î’ÎźÎĄÎťÎ”Î‘ÎˇÎ¨>", "", $t);

		//Trasliteration specific		
	}

	if (($f == 'aramaic') || ($f == 'hebrew'))
	{
		// do not double letters in general
		$GEMINATE_CANDIDATES = "/(?<hiriqYod>|ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
		$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
		
		$t = preg_replace("<".HOLAM_VAV.">", "uĹŤ", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "ÎĽĹŤ", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "Î»ĹŤ", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "ĎĹŤ", $t);
		$t = preg_replace("<".HOLAM_TAV.">", "Ď„ĹŤ", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "ĎĹŤ", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "uŃ", $t);	
		
		/* Consonants */
		$t = preg_replace("<".ALEPH.">", "Î±", $t);
		$t = preg_replace("<".BET.">", "Î˛", $t);
		$t = preg_replace("<".BHET.">", "Ď", $t);
		$t = preg_replace("<".GIMEL.">", "Îł", $t);
		$t = preg_replace("<".GHIMEL.">", "Îł", $t);
		$t = preg_replace("<".DALED.">", "Î´", $t);
		$t = preg_replace("<".DHALED.">", "Ă°", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "Îµ", $t);
		$t = preg_replace("<".HEH.">", "x", $t);
		$t = preg_replace("<".VAV.">", "Ď…", $t); //Ďť
		$t = preg_replace("<".ZED.">", "Î¶", $t);
		$t = preg_replace("<".CHET.">", "Î·", $t);
		$t = preg_replace("<".TET.">", "Ď„", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "Îą", $t);
		$t = preg_replace("<".YUD.">", "Îą", $t);
		$t = preg_replace("<".KAF.">", "Ď‡", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "Ď°", $t);
		$t = preg_replace("<".LAMED.">", "Î»", $t);
		$t = preg_replace("<".MEM.">", "ÎĽ", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "ÎĽ", $t);
		$t = preg_replace("<".NUN.">", "Î˝", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "Î˝", $t);
		$t = preg_replace("<".SAMECH.">", "Îľ", $t);
		$t = preg_replace("<".AYIN.">", "Îż", $t);
		$t = preg_replace("<".PEI.">", "Ď†", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "Ď†Ě„", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "Ď»Ě„", $t);
		$t = preg_replace("<".TZADI.">", "Ď»", $t);
		$t = preg_replace("<".KUF.">", "Îş", $t);
		$t = preg_replace("<".RESH.">", "Ď", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "ÎŁÎ¬", $t);
		$t = preg_replace("<".SHIN.">", "ÎŁ", $t);
		$t = preg_replace("<".SIN.">", "Ď", $t);	
		$t = preg_replace("<".TAV.">", "Î¸", $t);
		$t = preg_replace("<".THAV.">", "Ď„", $t);
	}	
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "ĹŹ", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "Ä", $t);
		$t = preg_replace("<".KAMETZ.">", "Î¬", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "ŕ«©", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "ÍŁ", $t);
		$t = preg_replace("<".PATACH.">", "Î±", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "áĽ", $t);
		$t = preg_replace("<".SHEVA.">", "áĽ‘", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "ŕ«©", $t);
		$t = preg_replace("<".SEGOL.">", "Ä™", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "Îµ", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "Ä“", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "ÎŻ", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "ĎŠ", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "Ď‰", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "Ď‰", $t);
		$t = preg_replace("<".MAPIQ.">", "Ď‰", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "ÎżĎ…", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);
		
		$t = preg_replace("<Öą >", "Ăł", $t);
		$t = preg_replace("<Öą>", "ĹŤ", $t);	 	
	}
	
	/* Second Step */
	$t = preg_replace("<mĹŤÎŁÄ™x>", "MĹŤÎŁÄ™x", $t);
	$t = preg_replace("<Ď†Ď‰Î±Î´Î±Î˝>", "Î¦Ď‰Î±Î´Î±Î˝", $t);
	$t = preg_replace("<tĹŤĎ†Ä™Î»>", "TĹŤĎ†Ä™Î»", $t);
	$t = preg_replace("<Î»Î¬ĎÎ¬Î˝>", "Î›Î¬ĎÎ¬Î˝", $t);
	$t = preg_replace("<Î·É™Ď»ÎµrĹŤĎ„>", "Î—É™Ď»ÎµrĹŤĎ„", $t);
	$t = preg_replace("<ÎąÎŻĎ»ÎµÎ·Î¬Îş>", "Î™ÎąĎ»ÎµÎ·Î¬Îş", $t);
	$t = preg_replace("<Î±ŕ«©lĹŤÎµ'ÎąÎĽ>", "Î‘ŕ«©lĹŤÎµ'ÎąÎĽ", $t);
	$t = preg_replace("<ÎµÎµ>", "Îµ", $t);	
	$t = preg_replace("<ÎąÎŻĎÎµĎÎ¬Î±ÎµÎ»>", "Î™ÎąĎÎµĎÎ¬Î±ÎµÎ»", $t);	
	$t = preg_replace("<ÎąĎ‰Î±ĎÎµÎ´ÎµÎ˝>", "Î™Ď‰Î±ĎÎµÎ´ÎµÎ˝", $t);
	$t = preg_replace("< ÎąÄ«>", " ÎąÄ«Â·", $t);
	$t = preg_replace("< ĎťÄ“>", " ĎťÄ“Â·", $t);
	$t = preg_replace("< ĎťÎµ>", " ĎťÎµÂ·", $t);
	$t = preg_replace("< ĎťáĽ>", " ĎťáĽÂ·", $t);
	$t = preg_replace("< ĎťÎ±>", " ĎťÎ±Â·", $t);
	$t = preg_replace("< ĎťĎ‰>", " ĎťĎ‰Â·", $t);
	$t = preg_replace("< Ď…áĽ>", " Ď…áĽÂ·", $t);
	$t = preg_replace("< xÉ™>", " xÉ™Â·", $t);
	$t = preg_replace("< xÎ±>", " xÎ±Â·", $t);
	$t = preg_replace("< Î˛Îµ>", " Î˛ÎµÂ·", $t);
	$t = preg_replace("< Î˛Î±>", " Î˛Î±Â·", $t);
	$t = preg_replace("< Î˛Î¬>", " Î˛Î¬Â·", $t);
	$t = preg_replace("<Î˛ÎµÂ·ÎąÎ˝>", "Î˛ÎµÎąÎ˝", $t);
	$t = preg_replace("<Î˛ÎµÂ·Î±>", "Î˛ÎµÎ±", $t);
	$t = preg_replace("<Î˛Î¬Â·ĎÎ¬>", "Î˛Î¬ĎÎ¬", $t);
	$t = preg_replace("<ÎĽÎą>", "ÎĽÎąÂ·", $t);
	$t = preg_replace("< xÎ±>", " xÎ±Â·", $t);
	$t = preg_replace("< xÎ¬>", " xÎ¬Â·", $t);
	$t = preg_replace("<Â·Â·>", "Â·", $t);
	
	$t = preg_replace("< Î˛Ă®Â·Ă©iÉł>", " Î˛Ă˘Ă©iÉł", $t);
	$t = preg_replace("<Î˛Ă˘ÄaÄ>", "Î˛Ă®ÄÂ·aÄ", $t);	
	
	ExtractTrup();
	
	$t = CleanUpPunctuation($t);
	return $t;
}

function UkrainianTransliteration($t, $from, $to)
{
	// do not double letters in general
	$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	/* Vowels */
	if ($from === 'hebrew')
	{
		$t = preg_replace("<".HOLAM_VAV.">", "ŃĐľ", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "ĐĽĐľ", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "Đ»Đľ", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "Đ˛Đľ", $t);
		$t = preg_replace("<".HOLAM_TAV.">", "Ń‚Đľ", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "Ń€Đľ", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "Ń", $t);	
		
		//Consonants
		$t = preg_replace("<".ALEPH.">", "Đµ", $t);
		$t = preg_replace("<".BET.">", "Đ±", $t);
		$t = preg_replace("<".BHET.">", "Đ˛", $t);
		$t = preg_replace("<".GIMEL.">", "Đł", $t);
		$t = preg_replace("<".GHIMEL.">", "Ň‘", $t);
		$t = preg_replace("<".DALED.">", "Đ´", $t);
		$t = preg_replace("<".DHALED.">", "Đ´", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "Ń…", $t);
		$t = preg_replace("<".HEH.">", "Ń…", $t);
		$t = preg_replace("<".VAV.">", "Ń", $t);
		$t = preg_replace("<".ZED.">", "Đ·", $t);
		$t = preg_replace("<".CHET.">", "Ń‡", $t);
		$t = preg_replace("<".TET.">", "Ň­", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "Ń–", $t);
		$t = preg_replace("<".YUD_PLURAL.YUD.">", "Ń—", $t);
		$t = preg_replace("<".YUD.SHEVA.">", "ŃŹ", $t);
		$t = preg_replace("<".YUD.">", "Đ¸", $t);
		$t = preg_replace("<".KAF.">", "Đş", $t);
		$t = preg_replace("<".KAF.SHEVA_NACH.">", "ĐşŃŚŃŠ", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "Đş", $t);
		$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", "ĐşŃŚ", $t);
		$t = preg_replace("<".LAMED.">", "Đ»", $t);
		$t = preg_replace("<".MEM.">", "ĐĽ", $t);
		$t = preg_replace("<".MEM.SHEVA_NACH.">", "ĐĽŃŚ", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "ÓŽ", $t);
		$t = preg_replace("<".NUN.">", "Đ˝", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "ÓŠ", $t);
		$t = preg_replace("<".SAMECH.">", "Ń", $t);
		$t = preg_replace("<".AYIN.">", "Đ°", $t);
		$t = preg_replace("<".PEI.">", "Đż", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "Ń„", $t);
		$t = preg_replace("<".TZADI.">", "Ń†", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "Ń†", $t);
		$t = preg_replace("<".KUF.">", "q", $t);
		$t = preg_replace("<".RESH.">", "Ń€", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "ŃĐ°", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "Ń‰ŃŚ", $t);
		$t = preg_replace("<".SHIN.">", "Ń‰", $t);
		$t = preg_replace("<"."Ü«".">", "Ń‰", $t);
		$t = preg_replace("<".SIN.">", "Ń", $t);
		$t = preg_replace("<".TAV.">", "Ń‚", $t);
		$t = preg_replace("<".THAV.">", "Ń‚", $t);
		$t = preg_replace("<".KHAF.">", "Đş", $t);				
		
		$t = preg_replace("<".CHATAF_KAMETZ.">", "Ä", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "Ä", $t);
		$t = preg_replace("<".KAMETZ.">", "Ä", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "Ä", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "Ä›", $t);
		$t = preg_replace("<".PATACH.">", "Ó­", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "ŃŚ", $t);
		$t = preg_replace("<".SHEVA.">", "ŃŠ", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "Ń«", $t);
		$t = preg_replace("<".SEGOL.">", "Ó—", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "Ń", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "Ń", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "Ä«", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "Ä«", $t);
		$t = preg_replace("<".HOLAM_HASHER.">", "Ń", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "ĹŤ", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ĹŤ", $t);
		$t = preg_replace("<".MAPIQ.">", "ĹŤ", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "Ĺ«", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "Â´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "Â°", $t);		
	}
 
	/* Vowels */
	if ($from == 'aramaic')
	{			
		$t = preg_replace("<".HOLAM_VAV.">", "ŃĐľ", $t);
		$t = preg_replace("<".HOLAM_MEM.">", "ĐĽĐľ", $t);
		$t = preg_replace("<".HOLAM_LAMED.">", "Đ»Đľ", $t);
		$t = preg_replace("<".HOLAM_BHET.">", "Đ˛Đľ", $t);
		$t = preg_replace("<".HOLAM_TAV.">", "Ń‚Đľ", $t);
		$t = preg_replace("<".HOLAM_RESH.">", "Ń€Đľ", $t);
		$t = preg_replace("<".HOLAM_HASHER_VAV.">", "Ń", $t);	
		
		//Consonants
		$t = preg_replace("<".ALEPH.">", "Đµ", $t);
		$t = preg_replace("<".BET.">", "Đ±", $t);
		$t = preg_replace("<".BHET.">", "Đ˛", $t);
		$t = preg_replace("<".GIMEL.">", "Đł", $t);
		$t = preg_replace("<".GHIMEL.">", "Ň‘", $t);
		$t = preg_replace("<".DALED.">", "Đ´", $t);
		$t = preg_replace("<".DHALED.">", "Đ´", $t);
		$t = preg_replace("<".HEH_MAPIK.">", "Ń…", $t);
		$t = preg_replace("<".HEH.">", "Ń…", $t);
		$t = preg_replace("<".VAV.">", "Ń", $t);
		$t = preg_replace("<".ZED.">", "Đ·", $t);
		$t = preg_replace("<".CHET.">", "Ń‡", $t);
		$t = preg_replace("<".TET.">", "Ň­", $t);
		$t = preg_replace("<".YUD_PLURAL.">", "Ń–", $t);
		$t = preg_replace("<".YUD_PLURAL.YUD.">", "Ń—", $t);
		$t = preg_replace("<".YUD.SHEVA.">", "ŃŹ", $t);
		$t = preg_replace("<".YUD.">", "Đ¸", $t);
		$t = preg_replace("<".KAF.">", "Đş", $t);
		$t = preg_replace("<".KAF.SHEVA_NACH.">", "ĐşŃŚŃŠ", $t);
		$t = preg_replace("<".KHAF_SOFIT.">", "Đş", $t);
		$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", "ĐşŃŚ", $t);
		$t = preg_replace("<".LAMED.">", "Đ»", $t);
		$t = preg_replace("<".MEM.">", "ĐĽ", $t);
		$t = preg_replace("<".MEM.SHEVA_NACH.">", "ĐĽŃŚ", $t);
		$t = preg_replace("<".MEM_SOFIT.">", "ÓŽ", $t);
		$t = preg_replace("<".NUN.">", "Đ˝", $t);
		$t = preg_replace("<".NUN_SOFIT.">", "ÓŠ", $t);
		$t = preg_replace("<".SAMECH.">", "Ń", $t);
		$t = preg_replace("<".AYIN.">", "Đ°", $t);
		$t = preg_replace("<".PEI.">", "Đż", $t);
		$t = preg_replace("<".PHEI_SOFIT.">", "Ń„", $t);
		$t = preg_replace("<".TZADI.">", "Ń†", $t);
		$t = preg_replace("<".TZADI_SOFIT.">", "Ń†", $t);
		$t = preg_replace("<".KUF.">", "q", $t);
		$t = preg_replace("<".RESH.">", "Ń€", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "ŃĐ°", $t);
		$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "Ń‰ŃŚ", $t);
		$t = preg_replace("<".SHIN.">", "Ń‰", $t);
		$t = preg_replace("<"."Ü«".">", "Ń‰", $t);
		$t = preg_replace("<".SIN.">", "Ń", $t);
		$t = preg_replace("<".TAV.">", "Ń‚", $t);
		$t = preg_replace("<".THAV.">", "Ń‚", $t);
		
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", "Ó—", $t);
		//...
		$t = preg_replace("<".ZQAPHA_DOTTED.">", "Ä", $t);
		$t = preg_replace("<".RBASA_UP.">", "Ó­", $t);
		$t = preg_replace("<".RBASA_DOWN.">", "Ä›", $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", "Ń«", $t);  
		$t = preg_replace("<".ZLAMA_ANGULAR.">", "Ĺ«", $t); 
		$t = preg_replace("<".ZLAMA_UP.">", "Ä«", $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", "Ä«", $t);	
		$t = preg_replace("<".ZLAMA_DOTTED.">", "Ä«", $t);  
		$t = preg_replace("<".ESASA_UP.">", "ĹŤ", $t);
		//... $t = preg_replace("<".ESASA_DOWN.">", TO_ESASA_DOWN, $t);		
		$t = preg_replace("<".RWAHA.">", "Ä«", $t);
		$t = preg_replace("<".FEMININE_DOT.">", "Â°", $t);
		$t = preg_replace("<".DALED.QUSHSHAYA.">", "Đ´ŃŠ", $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", "Đ´ŃŠ", $t);
		$t = preg_replace("<".MEM.SHEVA.">", "ĐĽŃŠ", $t);
		$t = preg_replace("<".SHEVA.">", "ŃŠ", $t);		
		$t = preg_replace("<".KUF.SHEVA.">", "qŃŠ", $t); 
		$t = preg_replace("<".RUKKAKHA.">", "ŃŠ", $t);
		//.. $t = preg_replace("<".VERTICAL_DOTS_UP.">", TO_VERTICAL_DOTS_UP, $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", "Ń«", $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", "Ĺ«", $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", "Ó—", $t);		
		//$t = preg_replace("<".RAFE.">", "Ó­", $t);		
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", "'", $t); 		
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".'Ü€'.">", ".", $t);
		//$t = preg_replace("<".BARREKH.">", TO_BARREKH, $t);		
		//$t = preg_replace("<".MAQAF.">", TO_MAQAF, $t);	
	}
	
	/* Vowels */
	if ($from == 'romanian')
	{	
		//Basic Characters
		$cyr_dia_lc = array('Ń”', 'Ń—', 'Ń‰', 'ŃŽ', 'ŃŹ', 'ŃŤ', 'Ń‡Đµ', 'ĐşŇ‘', ' Ń‹', 'Ń‹ ');
		$cyr_dia_uc = array('Đ„', 'Đ‡', 'Đ©', 'Đ®', 'ĐŻ', 'Đ­', 'Đ§Đµ', 'ĐšŇ‘', ' Đ«', 'Đ« ');
		$cyr_lc = array('Đ°', 'Đ±', 'Đ˛', 'Đł', 'Ň‘', 'Đ´', 'Đµ', 'Đ¶', 'Đ·', 'Đ¸', 'Ń–', 'Đą', 'Đş', 'Đ»', 'ĐĽ', 'Đ˝', 'Đľ', 'Đż', 'Ń€', 'Ń', 'Ń‚', 'Ń', 'Ń„', 'Ń…', 'Ń†', 'Ń‡', 'Ń', 'ŃŚ', 'Ń‹');
		$cyr_uc = array('Đ', 'Đ‘', 'Đ’', 'Đ“', 'Ň', 'Đ”', 'Đ•', 'Đ–', 'Đ—', 'Đ', 'Đ†', 'Đ™', 'Đš', 'Đ›', 'Đś', 'Đť', 'Đž', 'Đź', 'Đ ', 'Đˇ', 'Đ˘', 'ĐŁ', 'Đ¤', 'ĐĄ', 'Đ¦', 'Đ§', 'Đ¨', 'Đ¬', 'Đ«');
		
		$rom_dia_lc = array('ie', 'iĂ®', 'ĹźĹŁ', 'iu', 'ia', 'Ä', 'ce', 'kg', ' Ă®', 'Ă® ');
		$rom_dia_uc = array('Ie', 'IĂ®', 'ĹžĹŁ', 'Iu', 'Ia', 'Ä‚', 'Ce', 'Kg', ' ĂŽ', 'ĂŽ ');	
		$rom_lc = array('Đ°', 'b', 'v', 'h', 'g', 'd', 'e', 'Ă˘', 'z', 'i', 'y', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'x', 'ĹŁ', 'c', 'Ĺź', 'Ă®', 'Ă˘');
		$rom_uc = array('A', 'B', 'V', 'H', 'G', 'D', 'E', 'Ă‚', 'Z', 'I', 'Y', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'Ĺ˘', 'C', 'Ĺž', 'ĂŽ', 'ĂŽ');
		
		$lat_dia_lc = array('je', 'ji', 'ĹˇÄŤ', 'ju', 'ja', 'Ä', 'che', 'kg', ' Çť', 'Çť ');
		$lat_dia_uc = array('Je', 'Ji', 'Ĺ ÄŤ', 'Ju', 'Ja', 'Ä‚', 'Che', 'Kg', ' ĆŽ', 'ĆŽ ');	
		$lat_lc = array('a', 'b', 'v', 'h', 'g', 'd', 'e', 'Ĺľ', 'z', 'y', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'x', 'c', 'ÄŤ', 'Ĺˇ', 'â€˛', 'Çť');
		$lat_uc = array('A', 'B', 'V', 'H', 'G', 'D', 'E', 'Ĺ˝', 'Z', 'Y', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'X', 'C', 'ÄŚ', 'Ĺ ', 'â€˛', 'ĆŽ');
			
		$t = str_replace($rom_dia_lc, $cyr_dia_lc, $t);
		$t = str_replace($rom_dia_uc, $cyr_dia_uc, $t);		
		$t = str_replace($rom_lc, $cyr_lc, $t);
		//$t = preg_replace("<PERIOD>", "", $t);
		//$t = preg_replace("<COPPA>", "", $t);
		//$t = preg_replace("<SPACE>", " ", $t);
		$t = str_replace($rom_uc, $cyr_uc, $t);
		$t = preg_replace("<Đ§ĐžĐśĐśĐ>", " ", $t);
		$t = preg_replace("<ĐˇĐźĐĐ§Đ•>", "SPACE", $t);
		$t = preg_replace("<Đ‘ĐžĐŁĐťĐ”ĐĐ Đ†>", " ", $t);
		$t = preg_replace("<ĐźĐ•Đ ĐĐžĐ”>", " ", $t);
		//Trasliteration specific
		
	}	
	
	//Second Step;
	$t = preg_replace("<ÖąĐ˛>", "ĹŤĐ˛", $t);
	$t = preg_replace("<ÖąŃ€>", "ĹŤŃ€", $t);
	$t = preg_replace("<ĹŤÄ>", "Ä", $t);
	$t = preg_replace("<ĹŤĹŤ>", "ĹŤ", $t);
	$t = preg_replace("<ĹŤŃŚ>", "ŃŚ", $t);
	$t = preg_replace("<ĹŤŃŚ>", "Ä«", $t);
	$t = preg_replace("<ĹŤŃŚ>", "ŃŚ", $t);
	$t = preg_replace("<ŃŠŃŠ>", "ŃŠ", $t);
	$t = preg_replace("<Đ±ŃŚŃ‚ŃĹŤĐµŃĐ»>", "Đ‘ŃŚŃ‚ŃĹŤĐµŃĐ»", $t);
	$t = preg_replace("<Ń–Ä«ŃŃŚŃ€ÄĐµŃĐ»>", "Đ†Ä«ŃŃŚŃ€ÄĐµŃĐ»", $t);
	$t = preg_replace("<ĐĽĐľŃ‰Ó—Ń…>", "ĐśĐľŃ‰Ó—Ń…", $t);
	$t = preg_replace("<ĐżÄĐµŃ€ÄÓŠ>", "ĐźÄĐµŃ€ÄÓŠ", $t);
	$t = preg_replace("<Ń‚ĐľĐżÓ—Đ»>", "Đ˘ĐľĐżÓ—Đ»", $t);
	$t = preg_replace("<Đ»ÄĐ˛ÄÓŠ>", "Đ›ÄĐ˛ÄÓŠ", $t);
	$t = preg_replace("<Ń‡ÄŃ†ŃŃ€ĐľŃ‚>", "Đ§ÄŃ†ŃŃ€ĐľŃ‚", $t);
	$t = preg_replace("<Ń–ĹŤÓ­Ń€ŃŚĐ´ŃÓŠ>", "Đ†ĹŤÓ­Ń€ŃŚĐ´ŃÓŠ", $t);
	$t = preg_replace("< Ń…ÄĐµÄaŃ€Ó—Ń†>", " Ń…ÄEÄaŃ€Ó—Ń†", $t);
	$t = preg_replace("<Đ°Ó­Đ´ qÄĐ´ŃŃ‰>", "Đ°Ó­Đ´ QÄĐ´ŃŃ‰", $t);	
	$t = preg_replace("< iŃŚ>", " iŃŚÂ·", $t);
	$t = preg_replace("<Â·iŃŚ>", "Â·iŃŚÂ·", $t);
	$t = preg_replace("< iŃŚ>", " iŃŚÂ·", $t);
	$t = preg_replace("<Â·iŃŚ>", "Â·iŃŚÂ·", $t);
	$t = preg_replace("< iĹŤ>", " iĹŤÂ·", $t);
	$t = preg_replace("< ŃÓ­>", " ŃÓ­Â·", $t);
	$t = preg_replace("< ŃÄ“>", " ŃÄ“Â·", $t);
	$t = preg_replace("< ŃŃŚ>", " ŃŃŚÂ·", $t);
	$t = preg_replace("< ŃĹŤ>", " ŃĹŤÂ·", $t);
	$t = preg_replace("< ŃÄ>", " ŃÄÂ·", $t);
	$t = preg_replace("< bŃŚ>", " bŃŚÂ·", $t);
	$t = preg_replace("< bÄ“>", " bÄ“Â·", $t);
	$t = preg_replace("< ŃĂ˘>", " ŃŃŚÂ·", $t);
	$t = preg_replace("< Đ±Ä>", " Đ±ÄÂ·", $t);
	$t = preg_replace("< Đ±Ó­>", " Đ±Ó­Â·", $t);
	$t = preg_replace("<Đ±Ó­Â·Ń‚>", "Đ±Ó­Ń‚", $t);
	$t = preg_replace("< Đ±ŃŚ>", " Đ±ŃŚÂ·", $t);
	$t = preg_replace("< bŃŠ>", " bŃŚÂ·", $t);
	$t = preg_replace("< bÄ>", " bÄÂ·", $t);
	$t = preg_replace("<bŃŚÂ·Ä>", "bŃŠÄ", $t);	
	$t = preg_replace("<bÄÂ·r>", "bÄr", $t);
	$t = preg_replace("<bŃŚÂ·Ä>", "bŃŠÄ", $t);
	$t = preg_replace("< Ń…Ó­>", " Ń…Ó­Â·", $t);
	$t = preg_replace("<Ń…Ó­Â·Ń€>", " Ń…Ó­Ń€", $t);
	$t = preg_replace("< ĐĽÄ«>", " ĐĽÄ«Â·", $t);
	$t = preg_replace("<ĐżĹŤÓ­Đ´Ó­ÓŠ>", "ĐźĹŤÓ­Đ´Ó­ÓŠ", $t);
	$t = preg_replace("<ĐµÄŃ€ÄÓŽ>", "Đ•ÄŃ€ÄÓŽ", $t);	
	$t = preg_replace("< Ń…Ä>", " Ń…ÄÂ·", $t);
	$t = preg_replace("< Đ»ŃŚ>", " Đ»ŃŚÂ·", $t);
	$t = preg_replace("< Đ»>", " Đ»Â·", $t);
	$t = preg_replace("<iĹŤeĂ©ĐĽÄ™r>", "iĹŤÂ·eĂ©ĐĽÄ™r", $t);
	$t = preg_replace("<ŃÄ“Â·iŃŚhi>", "ŃÄ“Â·iŃŚÂ·hi", $t);
	$t = preg_replace("<ŃÄ“Â·iŃŚ>", "ŃÄ“Â·iŃŚÂ·", $t);
	$t = preg_replace("<ŃÓ­Â·Ń–ŃŚ>", "ŃÓ­Â·iŃŚÂ·", $t);
	$t = preg_replace("<Ń–ŃŚŃ…ŃÄŃ…>", "Đ†Đ¬ĐĄĐŁÄ€ĐĄ", $t);
	$t = preg_replace("<ĐµŃ«Đ»ĐľŃ…Ń–ÓŽ>", "Đ•Ń«Đ»ĐľŃ…Ń–ÓŽ", $t);	
	$t = preg_replace("<ĐµŃĐ»ÄŃ–Ń>", "ĐµŃĐ»ÄŃ–Â·Ń", $t);	
	$t = preg_replace("<ĐµÄĐ˝Ä«Ń–>", "ĐµÄĐ˝Ä«Â·Ń–", $t);
	$t = preg_replace("<Ń‰ŃŚĐĽÄ«Ń–>", "Ń‰ŃŚĐĽÄ«Â·Ń–", $t);
	$t = preg_replace("<Đ»ÄŃ…Ó—ÓŽ>", "Đ»ÄÂ·Ń…Ó—ÓŽ", $t);
	$t = preg_replace("<Đ˝ŃĐ´Ó­Đ°ŃŚŃ‚Ä«Ń–>", "Đ˝ŃÂ·Đ´Ó­Đ°ŃŚŃ‚Ä«Â·Ń–", $t);	
	$t = preg_replace("<ĐµÓ­Đ˛ŃŚŃ€ÄŃ…ÄÓŽ>", "Đ•Ó­Đ˛ŃŚŃ€ÄŃ…ÄÓŽ", $t);
	$t = preg_replace("<Ń–Ä«Â·Ń†ŃŚŃ‡Äq>", "Đ†Ä«Ń†ŃŚŃ‡Äq", $t);
	$t = preg_replace("<Ń–Ä«Ń†ŃŚŃ‡Äq>", "Đ†Ä«Ń†ŃŚŃ‡Äq", $t);	
	$t = preg_replace("<Ń–Ä«Ń†ŃŚŃ‡Äq>", "Đ†Ä«Ń†ŃŚŃ‡Äq", $t);
	$t = preg_replace("<Ń–Ó­Đ°ÄqĹŤĐ˛>", "Đ†Ó­Đ°ÄqĹŤĐ˛", $t);	
	$t = preg_replace("<ĐµŃĐ» Ń‰Ó­Đ´ÄŃ–>", "Đ•ŃĐ» Đ©Ó­Đ´ÄŃ–", $t);	
	$t = preg_replace("<Ń‰Ó­Đ´ÄŃ–>", "Đ©Ó­Đ´ÄŃ–", $t);
	$t = preg_replace("<Đ±ŃŚÂ·Đ°ŃĐ˛Ó—Ń€>", "Đ±ŃŚcĐŃĐ˛Ó—Ń€", $t);
	$t = preg_replace("<Đ±Ó­Â·Ń€ŃŚĐ˝ŃĐ°Ó­>", "Đ‘Ó­Ń€ŃŚĐ˝ŃĐ°Ó­", $t);
	$t = preg_replace("<Ń‰Ó­Đ´ÄŃ–>", "Đ©Ó­Đ´ÄŃ–", $t);	
	$t = preg_replace("<ĐĽŃŃ‡ĹŤŃ€ŃĐ˛>", "ĐĽŃÂ·Đ§ĹŤŃ€ŃĐ˛", $t);
	$t = preg_replace("<Ń‡ĹŤŃ€ŃĐ˛>", "Đ§ĹŤŃ€ŃĐ˛", $t);
	$t = preg_replace("<ŃŃĐ°Ä«Ń–Ń€>", "Đ¨ŃĐ°Ä«Ń–Ń€", $t);
	$t = preg_replace("<qÄĐ´ŃŃ‰>", "QÄĐ´ŃŃ‰", $t);
	$t = preg_replace("<Ń€Ä«Đ˛ŃŚqÄŃ…>", "Đ Ä«Đ˛ŃŚqÄŃ…", $t);		
	$t = preg_replace("<Đ‘ŃŚŃ‚ŃĹŤĐµŃĐ»>", "Đ‘ŃŚŃ‚ŃĹŤĐµŃĐ»", $t);
	$t = preg_replace("<Đ°Ń«ĐĽÄ«Ń–Đ˝ÄĐ´ŃŠÄĐ±ŃŠ>", "ĐŃ«ĐĽÄ«Ń–Đ˝ÄĐ´ŃŠÄĐ±ŃŠ", $t);	 
 	$t = preg_replace("<ĐµÄŃ€ÄĐĽ>", "Đ•ÄŃ€ÄĐĽ", $t);
 	$t = preg_replace("<Đ˝Ń«Ń‡Ń‰ÄŃĐ˝>", "ĐťŃ«Ń‡Ń‰ÄŃĐ˝", $t);
 	$t = preg_replace("<Đ˝Ń«Ń‡Ń‰ŃĐľĐ˝>", "ĐťŃ«Ń‡Ń‰ŃĐľĐ˝", $t);	
 	$t = preg_replace("<ŃŃ«Đ»ĐĽÄŃĐ˝>", "ĐˇŃ«Đ»ĐĽÄŃĐ˝", $t);
	
	$t = preg_replace("<Â·Â·>", "Â·", $t);	
	
	//Other line marks
	$t = preg_replace("<Ö¤>", "'", $t);
	$t = preg_replace("<Ö™>", "'", $t);
	$t = preg_replace("<Öś>", "'", $t);
	$t = preg_replace("<Ö >", "'", $t);
	$t = preg_replace("<Ö”>", "", $t);
	$t = preg_replace("<Ö›>", "'", $t);
	$t = preg_replace("<Ö—>", "Ĺ‘", $t);
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	
	return $t;
}

$trup = null;
$t_with_trup = null;
$t_without_trup = null;

function ExtractTrup()
{
	global $trup;
	global $t_with_trup;
	$t = $t_with_trup;
	$words = explode_split("SPACE", $t);
	$len = is_array($words) ? @count($words) : 0;
	//	print "len: " . $len . "--";
	for ($i = 0; $i < $len; $i++)
	{
		$letters = explode_split("SPACE", $words[$i]);
		$len2 = is_array($letters) ? @count($letters) : 0;
		//		print  "lettercount: " . $len2;
		$firstTrup = null;
		for ($j = 0; $j < $len2; $j++)
		{
			//	print " ". $letters[$j];
			if($letters[$j] == "REVII")
			{
				$trup[] = "REVII";
				break;
			}
			else if ($letters[$j] == "MAHPACH")
			{
				if ($j = 1) // it is a yetiv
					$trup[] = "YETIV";
					else
						$trup[] = "MAHPACH";
						break;
			}
			else if ($letters[$j] == "KADMA")
			{
				if ($j = $len - 1 || $firstTrup == "KADMA") // last symbol or repetition
				{
					$trup[] = "PASHTA";
					break;
				}
				$firstTrup = "KADMA";
			}
			else if ($letters[$j] == "MUNACH")
			{
				$firstTrup = "MUNACH";
			}
			else if ($letters[$j] == "METEG")
			{
				$firstTrup = "SILLUK";
			}
			else if ($letters[$j] == "ZAKEF_KATON" || $letters[$j] == "ZAKEF_GADOL" || $letters[$j] == "MERCHA" || $letters[$j] == "TIPCHA" || $letters[$j] == "ETNACHTA" || $letters[$j] == "TEVIR" || $letters[$j] == "GERESH" || $letters[$j] == "GERSHAYIM" || $letters[$j] == "ZARKA" || $letters[$j] == "SEGOLTA" || $letters[$j] == "TELISHA_KETANA" || $letters[$j] == "TELISHA_GEDOLA")
			{
				$firstTrup = $letters[$j]; // supplanting previous trup
			}
			else if ($letters[$j] == "SOF_PASUK")
			{
				// do nothing;
			}

		} // end for on letters of word

		// now, for non-supplanted trup
		if (!is_null($firstTrup) )
			$trup[] = $firstTrup;

	} // end for on words in sentence
	print_r ($trup);
} // end function

function RemoveTrup($t)
{
	global $t_without_trup;
	// strip trup from input text
	$t = preg_replace("<REVII >", "", $t);
	$t = preg_replace("<MAHPACH >", "", $t);
	$t = preg_replace("<KADMA >", "", $t);
	$t = preg_replace("<MUNACH >", "", $t);
	$t = preg_replace("<ZAKEF_KATON >", "", $t);
	$t = preg_replace("<ZAKEF_GADOL >", "", $t);
	$t = preg_replace("<MERCHA >", "", $t);
	$t = preg_replace("<TIPCHA >", "", $t);
	$t = preg_replace("<ETNACHTA >", "", $t);
	$t = preg_replace("<METEG >", "", $t);
	$t = preg_replace("<SOF_PASUK >", "", $t);
	$t = preg_replace("<TEVIR >", "", $t);
	$t = preg_replace("<DARGA >", "", $t);
	$t = preg_replace("<GERESH >", "", $t);
	$t = preg_replace("<GERSHAYIM >", "", $t);
	$t = preg_replace("<ZARKA >", "", $t);
	$t = preg_replace("<SEGOLTA >", "", $t);
	$t = preg_replace("<TELISHA_KETANA >", "", $t);
	$t = preg_replace("<TELISHA_GEDOLA >", "", $t);

	return $t;
}


function chunker($t)
{
	// this function separated words into syllables
	// is operated grammatically, such that geminate consonants
	// close the previous syllable
	// END_SYL with be the marker for the end of a syllable
	$NON_FINAL_NON_PLOSIVES = "(".ALEPH."|".BHET."|".GHIMEL."|".DHALED."|".HEH."|".VAV."|".ZED."|".CHET."|".TET."|".YUD."|".KHAF."|".LAMED."|".MEM."|".NUN."|".SAMECH."|".AYIN."|".PHEI."|".TZADI."|".KUF."|".RESH."|".SHIN."|".SIN."|".THAV.")";

	$t = ereg_repl("NON_FINAL_NON_PLOSIVES (SHEVA_NACH)", "\\1 \\2 END_SYL", $t);
	$t = ereg_repl("NON_FINAL_NON_PLOSIVES (SHEVA_NACH)", "\\1 \\2 END_SYL", $t);
}

$root = null;

function generateAndPrintTrup()
{
	global $root;
	global $trup;
	global $t_without_trup;
	
	$len = is_array($trup) ? @count($trup) : 0;
	
	$root = new tree_node(0, $len - 1);
	$root->generate_trup_tree();
	$root->print_trup_tree();
}

function generateTransliteration($sourcetext, $targetlang, $sourcelang, $isOpera = false)
{
	global $root_path, $phpExt;
	
	switch($sourcelang)
	{
		case 'aramaic':
			if (!defined('ALEPH')) 
			{			
				include($root_path . 'schemas/arc.' . $phpExt);
			}				
		break;
		
		case 'ukrainian':
		case 'hungarian':					
		case 'romanian':					
			//$t1 = RomanianTransliteration($sourcetext, $sourcelang, 'romanian');
			//generateNewTransliteration($t1, $targetlang, 'romanian', $isOpera);
		break;
		
		case 'hebrew':		
		default:
			if (!defined('ALEPH')) 
			{			
				include($root_path . 'schemas/heb.' . $phpExt);
			}	
		break;	
	}
	switch($targetlang)
	{
		case 'aramaic':
			if (!defined('TO_ALEPH')) 
			{			
				include($root_path . 'schemas/to_arc.' . $phpExt);
			}		
		break;
		
		case 'ukrainian':
		case 'hungarian':
		case 'romanian':
		
		break;
		
		case 'hebrew':   
		default:
			if (!defined('TO_ALEPH')) 
			{			
				include($root_path . 'schemas/to_heb.' . $phpExt);
			}
		break;	
	}
	generateNewTransliteration($sourcetext, $targetlang, $sourcelang, $isOpera);
}

function generateNewTransliteration($sourcetext, $targetlang, $sourcelang, $isOpera = false)
{
	global $origHebrew, $_SERVER;
	
	$t = $sourcetext;
	$f = $sourcelang;	
	
	if (!empty($_SERVER["HTTP_USER_AGENT"]))
	{
		$origHebrew = $t;
		$t = "BOUNDARY " . PostToIntermediate($t, $f) . "BOUNDARY";
	}
	else
	{
		$origHebrew = PostExtendedASCIIToEncodedUnicode($t, $f);
		$t = "BOUNDARY " . PostExtendedASCIIToIntermediate($t) . "BOUNDARY";
	}

	$s = $sourcetext;
	
	global $t_with_trup, $log;
	$t_with_trup = $t;
	
	//$t = RemoveTrup($t);
	//$t = ApplyRulesToIntermediateForm($t);
	
	// print $t;
	// print $s;
	
	$target = $targetlang;	
	if (is_object($log))
	{
		$log->add_entry('Transliterated text (From: ' . $sourcelang . ' to: ' . $target . ' alphabet.)');
	}
	else
	{
		add_log_entry('Transliterated text (From: ' . $sourcelang . ' to: ' . $target . ' alphabet.)');
	}
	
	// AND here is the next step: change the intermediate text into transliteration
	// print "<p>";
	
	if ($target == "academic")
	{
		if (!empty($_SERVER["HTTP_USER_AGENT"]))
		{
			$t1 = AcademicTransliteration($t, $f);
			print $t1;

		}
		else // IE
		{
			print AcademicFontFriendlyTransliteration($t, $f);
		}
	}
	else if ($target == "academic_u")
	{
		$t1 = AcademicTransliteration($t, $f);
		print $t1;
	}
	else if ($target == "academic_ff")
	{
		print AcademicFontFriendlyTransliteration($t, $f);
	}
	else if ($target == "ashkenazic")
	{
		$t2 = AshkenazicTransliteration($t, $f);
		
		print $t2;
		global $t_without_trup;
		
		$t_without_trup = explode_split("SPACE", $t2);
		generateAndPrintTrup();
	}
	else if ($target == "romaniote")
	{
		$t2 = RomanioteTransliteration($t, $f);
		print $t2;
	}
	else if ($target == "sefardic")
	{
		$t2 = SefardicTransliteration($t, $f);
		print $t2;
	}
	else if ($target == "academic_s")
	{
		$t2 = AcademicSpirantization($t, $f);
		print $t2;
	}	
	else if ($target == "romanian")
	{
		$t2 = RomanianTransliteration($t, $f, $target);
		print $t2;
	}
	else if ($target == "hebrew")
	{
		$t2 = HebrewAramaicTransliteration($t, $f, $target);
		print $t2;
	}
	else if ($target == "aramaic")
	{
		$t2 = HebrewAramaicTransliteration($t, $f, $target);
		print $t2;
	}
	else if ($target == "ukrainian")
	{
		$t2 = UkrainianTransliteration($t, $f, $target);
		print $t2;
	}
	else if ($target == "mc")
	{
		$t2 = MichiganClaremontTranslit($t, $f);
		print $t2;
	}
	define('END_TIME', round((microtime(true) - BEGIN_TIME) * 1000, 1));	
}

?>
