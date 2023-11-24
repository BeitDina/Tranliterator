<?php
/**
*
* @package Tranliterator
* @version $Id: trans.php,v 1.0.2 2023/11/24 16:52:12 orynider Exp $
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
		
		if ($this->begin_offset == 0 && $trup[$this->end_offset] == "SILLUK") // whole pasuk
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
			} // end while
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
			} // end while
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


function ereg_repl($pattern, $replacement, $string) 
{ 
	return preg_replace('/'.$pattern.'/', $replacement, $string); 
}

/**
*
*/
function data_repl($candidates = null, $replacements = null, $data)
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
function mesagehandler($msg_title, $msg_text, $l_notify, $l_return_index = "index.php") 
{ 

	// Try to not call the adm page data...
	print '<!DOCTYPE html>';
	print '<html dir="ltr">';
	print '<head><meta charset="UTF-8" />';
	print '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';
	print '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
	print '<meta name="apple-mobile-web-app-capable" content="yes" />';
	print '<meta name="apple-mobile-web-app-status-bar-style" content="blue" />';
	print '<title>' . $msg_title . '</title>';
	print '<style type="text/css">{ margin: 0; padding: 0; } html { font-size: 100%; height: 100%; margin-bottom: 1px; background-color: #E4EDF0; } body { font-family: -apple-system, BlinkMacSystemFont, Roboto, "Lucida Grande", "Segoe UI", Arial, Helvetica, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; color: #536482; background: #E4EDF0; font-size: 62.5%; margin: 0; } ';
	print 'a:link, a:active, a:visited { color: #006688; text-decoration: none; } a:hover { color: #DD6900; text-decoration: underline; } ';
	print '#wrap { padding: 0 20px 15px 20px; min-width: 615px; } #page-header { text-align: right; height: 40px; } #page-footer { clear: both; font-size: 1em; text-align: center; } ';
	print '.panel { margin: 4px 0; background-color: #FFFFFF; border: solid 1px  #A9B8C2; } ';
	print '#errorpage #page-header a { font-weight: bold; line-height: 6em; } #errorpage #content { padding: 10px; } #errorpage #content h1 { line-height: 1.2em; margin-bottom: 0; color: #DF075C; } ';
	print '#errorpage #content div { margin-top: 20px; margin-bottom: 5px; border-bottom: 1px solid #CCCCCC; padding-bottom: 5px; color: #333333; font: bold 1.2em; font-family: -apple-system, BlinkMacSystemFont, Roboto, "Lucida Grande", "Segoe UI", Arial, Helvetica, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; text-decoration: none; line-height: 120%; text-align: left; } \n';
	print '</style>';
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
	// On fatal error E_USER_ERROR shoud stop the execution or exit the function.
	exit;
}

/**
* Error and message handler, call with trigger_error if reqd
*/
function errorhandler($err_no, $msg_text, $err_file, $err_line)
{
	global $phpExt, $root_path;
	
	// Do not send 200 OK, but service unavailable on errors
	switch($err_no)
	{ 
		case 0:				
			$l_notify = "Unknown PHP Error";
		break;
		case 1:   
			$l_notify = "Php Error";
		break;
		case 2:   
			$l_notify = "Php Warning";
		break;
		case 4:   
			$l_notify = "Parsing Error";
		break;
		case 8:   
			$l_notify = "Php Notice";
		break;
		case 16:   
			$l_notify = "Core Error";
		break;
		case 32:  
			$l_notify = "Core Warning";
		break;
		case 64:  
			$l_notify = "Compile Error";
		break;
		case 128:  
			$l_notify = "Compile Warning";
		break;
		case 256: 
			$l_notify = "Php User Error";
		break;
		case 512: 
			$l_notify = "Php User Warning";
		case 1024: 
			$l_notify = "Php User Notice";
		break;
		case 2048:
			$l_notify = 'PHP Strict';
		break;
		default:
			$l_notify = 'Service Unavailable';
		break;	
	}
	
	switch ($err_no)
	{
		case E_NOTICE:
		case E_WARNING:
			
			print "\n". $l_notify .': '. $msg_text . "\n";
			
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
			print '<style type="text/css">{ margin: 0; padding: 0; } html { font-size: 100%; height: 100%; margin-bottom: 1px; background-color: #E4EDF0; } body { font-family: -apple-system, BlinkMacSystemFont, Roboto, "Lucida Grande", "Segoe UI", Arial, Helvetica, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; color: #536482; background: #E4EDF0; font-size: 62.5%; margin: 0; } ';
			print 'a:link, a:active, a:visited { color: #006688; text-decoration: none; } a:hover { color: #DD6900; text-decoration: underline; } ';
			print '#wrap { padding: 0 20px 15px 20px; min-width: 615px; } #page-header { text-align: right; height: 40px; } #page-footer { clear: both; font-size: 1em; text-align: center; } ';
			print '.panel { margin: 4px 0; background-color: #FFFFFF; border: solid 1px  #A9B8C2; } ';
			print '#errorpage #page-header a { font-weight: bold; line-height: 6em; } #errorpage #content { padding: 10px; } #errorpage #content h1 { line-height: 1.2em; margin-bottom: 0; color: #DF075C; } ';
			print '#errorpage #content div { margin-top: 20px; margin-bottom: 5px; border-bottom: 1px solid #CCCCCC; padding-bottom: 5px; color: #333333; font: bold 1.2em -apple-system, BlinkMacSystemFont, Roboto, "Lucida Grande", "Segoe UI", Arial, Helvetica, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", sans-serif; text-decoration: none; line-height: 120%; text-align: left; } \n';
			print '</style>';
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
if(!function_exists('ereg'))            { function ereg($pattern, $subject, &$matches = []) { return preg_match('/'.$pattern.'/', $subject, $matches); } }
if(!function_exists('eregi'))           { function eregi($pattern, $subject, &$matches = []) { return preg_match('/'.$pattern.'/i', $subject, $matches); } }
if(!function_exists('ereg_replace'))    { function ereg_replace($pattern, $replacement, $string) { return preg_replace('/'.$pattern.'/', $replacement, $string); } }
if(!function_exists('eregi_replace'))   { function eregi_replace($pattern, $replacement, $string) { return preg_replace('/'.$pattern.'/i', $replacement, $string); } }
if(!function_exists('split'))           { function split($pattern, $subject, $limit = -1) { return preg_split('/'.$pattern.'/', $subject, $limit); } }
if(!function_exists('spliti'))          { function spliti($pattern, $subject, $limit = -1) { return preg_split('/'.$pattern.'/i', $subject, $limit); } }
//if(!function_exists('explode')) 					{ function explode($delimiters = null, $input = "") { return explode_split($delimiters, $input); } }

/*
* DEBUG AND ERROR HANDLING
*/
set_error_handler('errorhandler');
set_exception_handler("mesagehandler");
// end new trup code

$isOpera = 0;
$isFirefox = 0;
$origHebrew = "";

$l_about_title = 'About Transliterator';
$l_about_desc = 'Transliterator is a mechanism offered as-is to support customers for the purpose of transliterating from Hebrew Alphabet into other alphabets. Was started by <a href="https://github.com/joshwaxman/transliterate">Joshua Waxman</a> in 2006.';
$l_notify = 'You can read at <a href="https://github.com/BeitDina/Transliterator/">github.com/beitdina/Transliterator</a> more about it.<br/> Working on PHP '. PHP_VERSION .' on '. PHP_OS .'.';

//
// Show copyrights
//
if (isset($_REQUEST['copy']))
{
	mesagehandler($l_about_title, $l_about_desc, $l_notify, $root_path . 'index.' . $phpExt);
}

function PostExtendedASCIIToIntermediate($t, $f = 'hebrew')
{
	$t = preg_replace("< >", "BOUNDARY SPACE BOUNDARY ", $t);
	$t = preg_replace("<,>", "BOUNDARY COMMA BOUNDARY ", $t);
	$t = preg_replace("<->", "BOUNDARY DASH BOUNDARY ", $t);
	$t = preg_replace("<\.>", "BOUNDARY PERIOD BOUNDARY ", $t);

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
	/* Vowels */
	if ($f === 'hebrew')
	{	
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


function PostToIntermediate($t, $f)
{
	$t = preg_replace("< >", "BOUNDARY SPACE BOUNDARY ", $t);
	$t = preg_replace("<,>", "BOUNDARY COMMA BOUNDARY ", $t);
	$t = preg_replace("<->", "BOUNDARY DASH BOUNDARY ", $t);
	$t = preg_replace("<\.>", "BOUNDARY PERIOD BOUNDARY ", $t);
	
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
	/* Vowels */
	if ($f === 'hebrew')
	{
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
	$t = preg_replace("<;>", "BOUNDARY SEMICOLON BOUNDARY ", $t);
	$t = urldecode($t);
	return $t;
}


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


function CleanUpPunctuation($t)
{
	$t = preg_replace("<BOUNDARY>", "", $t);
	$t = preg_replace("<COMMA >", ",", $t);
	$t = preg_replace("<DASH >", "-", $t);
	$t = preg_replace("<SEMICOLON >", ";", $t);
	$t = preg_replace("<PERIOD >", ".", $t);
	$t = preg_replace("< >", "", $t);
	$t = preg_replace("<SPACE>", " ", $t);
	return $t;
}

function FileContentToUnicode($t, $f)
{
	global $root_path, $phpExt;
	switch($f)
	{ 
		case 'aramaic':
			include_once($root_path . 'schemas/arc.' . $phpExt);		
		break;
		case 'hebrew':   
		default:
			include_once($root_path . 'schemas/heb.' . $phpExt);
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
	
	// do not double letters in general
	$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);

	$t = preg_replace("<".HOLAM_VAV.">", "uo", $t);
	$t = preg_replace("<".HOLAM_MEM.">", "mo", $t);
	$t = preg_replace("<".HOLAM_LAMED.">", "lo", $t);
	$t = preg_replace("<".HOLAM_BHET.">", "vo", $t);	
	$t = preg_replace("<".HOLAM_TAV.">", "to", $t);
	$t = preg_replace("<".HOLAM_RESH.">", "ro", $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "ѐ", $t);	
	
	/* Font Frendly */
	$t = preg_replace("<".ALEPH.">", "ʾ", $t);
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
	$t = preg_replace("<".MEM_SOFIT.">", "ɱ", $t);
	$t = preg_replace("<".NUN.">", "n", $t);
	$t = preg_replace("<".NUN_SOFIT.">", "ɳ", $t);
	$t = preg_replace("<".SAMECH.">", "s", $t);
	$t = preg_replace("<".AYIN.">", "ʿ", $t);
	$t = preg_replace("<".PEI.">", "p", $t);
	$t = preg_replace("<".PHEI_SOFIT.">", "ph", $t);
	$t = preg_replace("<".TZADI_SOFIT.">", "ts", $t); //&#351;
	$t = preg_replace("<".TZADI.">", "tz", $t);
	$t = preg_replace("<".KUF.">", "q", $t);
	$t = preg_replace("<".RESH.">", "r", $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "sā", $t);
	$t = preg_replace("<".SHIN.">", "sh", $t);
	$t = preg_replace("<".SIN.">", "s", $t);
	$t = preg_replace("<".SHIN_NO_DOT.">", "(sh)", $t);	
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "th", $t);	
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
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);	
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


function AshkenazicTransliteration($t, $f)
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
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "ѐ", $t);	
	
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
	$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "sā", $t);
	$t = preg_replace("<".SHIN.">", "sh", $t);
	$t = preg_replace("<".SIN.">", "s", $t);
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "s", $t);	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "a", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "o", $t);
		$t = preg_replace("<".KAMETZ.">", "a", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "e", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "a", $t);
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
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);		
	}
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function SefardicTransliteration($t, $f)
{	
	// do not double letters in general
	$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);

	$t = preg_replace("<".HOLAM_VAV.">", "uō", $t);
	$t = preg_replace("<".HOLAM_MEM.">", "mō", $t);
	$t = preg_replace("<".HOLAM_LAMED.">", "lō", $t);
	$t = preg_replace("<".HOLAM_BHET.">", "vō", $t);
	$t = preg_replace("<".HOLAM_TAV.">", "tō", $t);
	$t = preg_replace("<".HOLAM_RESH.">", "rō", $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "ѐ", $t);	
	
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
	$t = preg_replace("<".HOLAM_VAV.">", "uō", $t);
	$t = preg_replace("<".ZED.">", "z", $t);
	$t = preg_replace("<".CHET.">", "ḥ", $t); // h dot
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
	$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "sā", $t);
	$t = preg_replace("<".SHIN.">", "sh", $t);
	$t = preg_replace("<".SIN.">", "s", $t);
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "t", $t);
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "a", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "a", $t);
		$t = preg_replace("<".KAMETZ.">", "a", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "a", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "e", $t);
		$t = preg_replace("<".PATACH.">", "e", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "ə", $t);
		$t = preg_replace("<".SHEVA.">", "ə", $t);
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
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);	 
	}
	$t = preg_replace("<ֹsh>", "osh", $t);
	$t = preg_replace("<ֹr>", "or", $t);
	$t = preg_replace("<ֹt>", "ot", $t);
	$t = preg_replace("<mosheh>", "Mosheh", $t);
	$t = preg_replace("<əə>", "ə", $t);	
	$t = preg_replace("<yisəraeel>", "YisəraeEel", $t);	
	$t = preg_replace("<iīsîrāeeīl>", "IīsârāeEīl", $t);
	$t = preg_replace("<yəerədəen>", "Iəerədəen", $t);
	$t = preg_replace("< yi>", " iy·", $t);
	$t = preg_replace("< ue>", " ue·", $t);
	$t = preg_replace("< uə>", " uə·", $t);
	$t = preg_replace("< he>", " he·", $t);
	$t = preg_replace("< bə>", " bə·", $t);
	$t = preg_replace("<bə·ā>", "bəā", $t);
	$t = preg_replace("< ha>", " ha·", $t);
	$t = preg_replace("< bə·eyn>", " bəein", $t);
	$t = preg_replace("<bəaaa>", "bəa·aa", $t);
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function AcademicTransliteration($t, $f)
{	
	// do not double letters in general
	$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	$t = preg_replace("<".HOLAM_VAV.">", "uō", $t);
	$t = preg_replace("<".HOLAM_MEM.">", "mō", $t);
	$t = preg_replace("<".HOLAM_LAMED.">", "lō", $t);
	$t = preg_replace("<".HOLAM_BHET.">", "vō", $t);
	$t = preg_replace("<".HOLAM_TAV.">", "tō", $t);
	$t = preg_replace("<".HOLAM_RESH.">", "rō", $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "uѐ", $t);	
	
	/* Consonants */
	$t = preg_replace("<".ALEPH.">", "ʾ", $t);
	$t = preg_replace("<".BET.">", "b", $t);
	$t = preg_replace("<".BHET.">", "ḇ", $t);
	$t = preg_replace("<".GIMEL.">", "g", $t);
	$t = preg_replace("<".GHIMEL.">", "ḡ", $t);
	$t = preg_replace("<".DALED.">", "d", $t);
	$t = preg_replace("<".DHALED.">", "ḏ", $t);
	$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
	$t = preg_replace("<".HEH.">", "h", $t);
	$t = preg_replace("<".HEH.">", "h", $t);
	$t = preg_replace("<".VAV.">", "w", $t);
	$t = preg_replace("<".ZED.">", "z", $t);
	$t = preg_replace("<".CHET.">", "ḥ", $t);
	$t = preg_replace("<".TET.">", "th", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "i", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "(y)", $t);
	$t = preg_replace("<".YUD.">", "y", $t);
	$t = preg_replace("<".KAF.">", "k", $t);
	$t = preg_replace("<".KHAF_SOFIT.">", "ḵ", $t);
	$t = preg_replace("<".LAMED.">", "l", $t);
	$t = preg_replace("<".MEM.">", "m", $t);
	$t = preg_replace("<".MEM_SOFIT.">", "ɱ", $t);
	$t = preg_replace("<".NUN.">", "n", $t);
	$t = preg_replace("<".NUN_SOFIT.">", "ɳ", $t);
	$t = preg_replace("<".SAMECH.">", "s", $t);
	$t = preg_replace("<".AYIN.">", "ʿ", $t);
	$t = preg_replace("<".PEI.">", "p", $t);
	$t = preg_replace("<".PHEI_SOFIT.">", "p̄", $t);
	$t = preg_replace("<".TZADI_SOFIT.">", "ţ̄", $t);
	$t = preg_replace("<".TZADI.">", "ţ", $t);
	$t = preg_replace("<".KUF.">", "q", $t);
	$t = preg_replace("<".RESH.">", "r", $t);
	$t = preg_replace("<".SHIN.">", "š", $t);
	$t = preg_replace("<".SIN.">", "ś", $t);	
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "ṯ", $t);	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "ŏ", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "ā", $t);
		$t = preg_replace("<".KAMETZ.">", "ā", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "ə", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "<sup>ē</sup>", $t);
		$t = preg_replace("<".PATACH.">", "ē", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "ə", $t);
		$t = preg_replace("<".SHEVA.">", "ə", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "ă", $t);
		$t = preg_replace("<".SEGOL.">", "ę", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "ê", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "ē", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "ī", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "ī", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "ō", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ō", $t);
		$t = preg_replace("<".MAPIQ.">", "ō", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "ū", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);	 
		$t = preg_replace("<ֹ >", "ó", $t);
		$t = preg_replace("<ֹ>", "ō", $t);		
	}
	/* Second Step */
	$t = preg_replace("<ֹş>", "ōş", $t);
	$t = preg_replace("<ֹr>", "ōr", $t);
	$t = preg_replace("<ֹt>", "ōt", $t);
	$t = preg_replace("<ֹp>", "p", $t);
	$t = preg_replace("<ֹe>", "eé", $t);
	$t = preg_replace("<ōā>", "ā", $t);
	$t = preg_replace("<ōō>", "ō", $t);
	$t = preg_replace("<ōî>", "î", $t);
	$t = preg_replace("<ōī>", "ī", $t);
	$t = preg_replace("<ōî>", "î", $t);
	$t = preg_replace("<mōşęh>", "Mōşęh", $t);
	$t = preg_replace("<ââ>", "â", $t);	
	$t = preg_replace("<iīsârāeél>", "IīsârāeEél", $t);	
	$t = preg_replace("<iīsîrāeeīl>", "IīsârāeEīl", $t);
	$t = preg_replace("<iâērâdâéɳ>", "Iâērâdâéɳ", $t);
	$t = preg_replace("< iī>", " iī·", $t);
	$t = preg_replace("< uē>", " uē·", $t);
	$t = preg_replace("< uâ>", " uî·", $t);
	$t = preg_replace("< hē>", " hē·", $t);
	$t = preg_replace("< bâ>", " bî·", $t);
	$t = preg_replace("<bî·ā>", "bâā", $t);
	$t = preg_replace("< hā>", " hā·", $t);
	$t = preg_replace("< bî·éiɳ>", " bâéiɳ", $t);
	$t = preg_replace("<bâāaā>", "bîā·aā", $t);
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

/*
* Michigan Claremont or Bash
*/
function MichiganClaremontTranslit($t, $f)
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
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "Ѐ", $t);	
	
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
	$t = preg_replace("<".TET.">", "+", $t);
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
	$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "$"."Ə", $t);
	$t = preg_replace("<".SHIN.">", "$", $t);
	$t = preg_replace("<".SIN.">", "&", $t);
	$t = preg_replace("<".TAV.">", "T.", $t);
	$t = preg_replace("<".THAV.">", "T.", $t);
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", ":F", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "F", $t);
		$t = preg_replace("<".KAMETZ.">", "F", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", ":A", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "A", $t);
		$t = preg_replace("<".PATACH.">", "A", $t);
		
		//Vowels
		$t = preg_replace("<".SHEVA_NACH.">", "Ə", $t);
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
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);	
	}
	$t = preg_replace("<".KAF.">", "K.", $t); //doble check
	$t = preg_replace("<".KHAF.">", "K", $t);	
	
	//Second Step;
	$t = preg_replace("<ֹş>", "ōş", $t);
	$t = preg_replace("<ֹr>", "ōr", $t);
	$t = preg_replace("<ֹt>", "ōt", $t);
	$t = preg_replace("<mōşęh>", "Mōşęh", $t);
	$t = preg_replace("<ââ>", "â", $t);	
	$t = preg_replace("<iīsârāeél>", "IīsârāeEél", $t);	
	$t = preg_replace("<iīsîrāeeīl>", "IīsârāeEīl", $t);
	$t = preg_replace("<iâērâdâéɳ>", "Iâērâdâéɳ", $t);
	$t = preg_replace("< iī>", " iī·", $t);
	$t = preg_replace("< uē>", " uē·", $t);
	$t = preg_replace("< uâ>", " uî·", $t);
	$t = preg_replace("< hē>", " hē·", $t);
	$t = preg_replace("< bâ>", " bî·", $t);
	$t = preg_replace("<bî·ā>", "bâā", $t);
	$t = preg_replace("< hā>", " hā·", $t);
	$t = preg_replace("< bî·éiɳ>", " bâéiɳ", $t);
	$t = preg_replace("<bâāaā>", "bîā·aā", $t);
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function RomanianTransliteration($t, $f)
{
	// do not double letters in general
	$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	$t = preg_replace("<".HOLAM_VAV.">", "uō", $t);
	$t = preg_replace("<".HOLAM_MEM.">", "mō", $t);
	$t = preg_replace("<".HOLAM_LAMED.">", "lō", $t);
	$t = preg_replace("<".HOLAM_BHET.">", "vō", $t);
	$t = preg_replace("<".HOLAM_TAV.">", "tō", $t);
	$t = preg_replace("<".HOLAM_RESH.">", "rō", $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "uѐ", $t);	
	
	//Consonants
	$t = preg_replace("<".ALEPH.">", "e", $t);
	$t = preg_replace("<".BET.">", "b", $t);
	$t = preg_replace("<".BHET.">", "v", $t);
	$t = preg_replace("<".GIMEL.">", "g", $t);
	$t = preg_replace("<".GHIMEL.">", "g", $t);
	$t = preg_replace("<".DALED.">", "đ", $t);
	$t = preg_replace("<".DHALED.">", "d", $t);
	$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
	$t = preg_replace("<".HEH.">", "h", $t);
	$t = preg_replace("<".VAV.">", "u", $t);
	$t = preg_replace("<".ZED.">", "z", $t);
	$t = preg_replace("<".CHET.">", "ĥ", $t);
	$t = preg_replace("<".TET.">", "th", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "i", $t);
	$t = preg_replace("< ".YUD.">", " i", $t);
	$t = preg_replace("<".YUD.SHEVA.">", "iî", $t);
	$t = preg_replace("<".YUD.">", "y", $t);
	$t = preg_replace("<".KHAF_KAMETZ.">", "cā", $t);
	$t = preg_replace("<".KAF.">", "c", $t);
	$t = preg_replace("<".KHAF.">", "cî", $t);
	$t = preg_replace("<".KAF.SHEVA_NACH.">", "cîâ", $t);
	$t = preg_replace("<".KHAF_SOFIT.">", "k", $t);
	$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", "kâ", $t);
	$t = preg_replace("<".LAMED.">", "l", $t);
	$t = preg_replace("<".MEM.">", "m", $t);
	$t = preg_replace("<".MEM.SHEVA_NACH.">", "mî", $t);
	$t = preg_replace("<".MEM_SOFIT.">", "ɱ", $t);
	$t = preg_replace("<".NUN.">", "n", $t);
	$t = preg_replace("<".NUN_SOFIT.">", "ɳ", $t);
	$t = preg_replace("<".SAMECH.">", "s", $t);
	$t = preg_replace("<".AYIN.">", "a", $t);
	$t = preg_replace("<".PEI.">", "p", $t);
	$t = preg_replace("<".PHEI_SOFIT.">", "f", $t);
	$t = preg_replace("<".TZADI.">", "ţ", $t);
	$t = preg_replace("<".TZADI_SOFIT.">", "ţ", $t);
	$t = preg_replace("<".KUF.">", "q", $t);
	$t = preg_replace("<".RESH.">", "r", $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "şā", $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "şâ", $t);
	$t = preg_replace("<".SHIN.">", "ş", $t);
	$t = preg_replace("<".SIN.">", "s", $t);
	$t = preg_replace("<".SHIN_NO_DOT.">", "ş", $t);
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "t", $t);
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "ā", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "ā", $t);
		$t = preg_replace("<".KAMETZ.">", "ā", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "ā", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "ē", $t);
		$t = preg_replace("<".PATACH.">", "ē", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "î", $t);
		$t = preg_replace("<".SHEVA.">", "â", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "ă", $t);
		$t = preg_replace("<".SEGOL.">", "ę", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "é", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "é", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "ī", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "ī", $t);
		$t = preg_replace("<".HOLAM_HASHER.">", "ó", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "ō", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ō", $t);
		$t = preg_replace("<".MAPIQ.">", "ō", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "ū", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);	
	} 
	
	if ($f === 'aramaic') 
	{
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", "hę", $t);
		$t = preg_replace("<".PTHAHA_UP.">", "ä", $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", "ě", $t); 
		$t = preg_replace("<".PTHAHA_DOTTED.">", "ü", $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", "ů", $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", "ù", $t); 
		$t = preg_replace("<".ZQAPHA_DOTTED.">", "ā", $t); 
		$t = preg_replace("<".RBASA_UP.">", "à", $t);
		$t = preg_replace("<".RBASA_DOWN.">", "ē", $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", "ő", $t); 
		$t = preg_replace("<".ZLAMA_ANGULAR.">", "é", $t); 
		$t = preg_replace("<".ZLAMA_UP.">", "ò", $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", "y", $t); 
		$t = preg_replace("<".ZLAMA_DOTTED.">", "ī", $t); 
		$t = preg_replace("<".ESASA_UP.">", "ì", $t);
		$t = preg_replace("<".ESASA_DOWN.">", "ý", $t); 
		$t = preg_replace("<".RWAHA.">", "ō", $t);
		$t = preg_replace("<".FEMININE_DOT.">", "ą", $t); 
		$t = preg_replace("<".DALED.QUSHSHAYA.">", "dâ", $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", "dî", $t);
		$t = preg_replace("<".MEM.QUSHSHAYA.">", "mî", $t);
		$t = preg_replace("<".QUSHSHAYA.">", "â", $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", "qâ", $t); 
		$t = preg_replace("<".RUKKAKHA.">", "â", $t);
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", "å", $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", "ё", $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", "ū", $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", "ę", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", "ó", $t); 
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", "ё", $t); 
		$t = preg_replace("<".MUSIC.">", "#", $t);
		$t = preg_replace("<".BARREKH.">", "+", $t); 
		$t = preg_replace("<".MAQAF.">", "־", $t); 		
	}	
	
	//Line marks
	$t = preg_replace("<֤>", "'", $t);
	$t = preg_replace("<֙>", "'", $t);
	$t = preg_replace("<֜>", "'", $t);
	$t = preg_replace("<֠>", "'", $t);
	$t = preg_replace("<֔>", "", $t); //"remove"
	$t = preg_replace("<֛>", "'", $t);
	$t = preg_replace("<֗>", "ő", $t);
	
	//Second Step, Names, Places, first up, etc;
	$t = preg_replace("<ââ>", "â", $t);
	$t = preg_replace("<âü>", "ü", $t);
	$t = preg_replace("<âā>", "ā", $t);
	$t = preg_replace("<âē>", "ē", $t);
	$t = preg_replace("<âē>", "ē", $t);
	$t = preg_replace("< đü>", " đü·", $t);
	$t = preg_replace("<đâ >", "đ ", $t);
	$t = preg_replace("< iī>", " iī·", $t);
	$t = preg_replace("<·iī>", "·iī·", $t);
	$t = preg_replace("< iî>", " iî·", $t);
	$t = preg_replace("<·iî>", "·iî·", $t);
	$t = preg_replace("< iō>", " iō·", $t);
	$t = preg_replace("< uē>", " uē·", $t);
	$t = preg_replace("<uē·tē>", "uē·tē·", $t);
	$t = preg_replace("< uî>", " uî·", $t);
	$t = preg_replace("< uō>", " uō·", $t);
	$t = preg_replace("< uā>", " uā·", $t);
	$t = preg_replace("< bî>", " bî·", $t);
	$t = preg_replace("< bē>", " bē·", $t);
	$t = preg_replace("< uâ>", " uî·", $t);
	$t = preg_replace("< bâ>", " bî·", $t);
	$t = preg_replace("< bā>", " bā·", $t);
	$t = preg_replace("<bî·ē>", "bē", $t);
	$t = preg_replace("<uē·iîhi>", "uē·iî·hi", $t);
	$t = preg_replace("<uē·iî>", "uē·iî·", $t);
	$t = preg_replace("<īi'>", "ī·i'", $t);
	$t = preg_replace("<īi >", "ī·i ", $t);
	$t = preg_replace("<··>", "·", $t);
	$t = preg_replace("<iî·lādē'i>", "iâlādē'i", $t);
	$t = preg_replace("<iî·lādēi>", "iâlādē·i", $t);
	$t = preg_replace("<iāré'e>", "iâlādē·i", $t);
	$t = preg_replace("<eīişīi>", "eīişī·i", $t);
	$t = preg_replace("< bā·őe>", " bāőe", $t);	
	$t = preg_replace("< lā>", " lā·", $t);
	$t = preg_replace("< lē>", " lē·", $t);
	$t = preg_replace("< lé>", " lé·", $t);
	$t = preg_replace("< lę>", " lę·", $t);
	$t = preg_replace("< lѐ>", " lѐ·", $t);
	$t = preg_replace("< lī>", " lī·", $t);
	$t = preg_replace("< lî>", " lî·", $t);
	$t = ($f === 'aramaic') ? preg_replace("<bē·réh>", "bēré·hî", $t) : $t;
	$t = ($f === 'aramaic') ? preg_replace("< đâ>", " đî·", $t) : $t;
	$t = ($f === 'aramaic') ? preg_replace("<đâ >", "đ ", $t) : $t;
	$t = ($f === 'aramaic') ? preg_replace("<dâ >", "d ", $t) : $t;
	$t = preg_replace("<aēmīinādābâ>", "Aēmīinādāb", $t);
	$t = str_replace(" laē", " lî·aē", $t);
	$t = str_replace(" lAē", " lî·Aē", $t);
	$t = preg_replace("<lāvāɳ>", "Lāvāɳ", $t);
	$t = preg_replace("<lāvāɳ>", "Lāvāɳ", $t);
	$t = preg_replace("<".KHAF.">", "c", $t);
	$t = preg_replace("<îkā'>", "î·kā'", $t);
	$t = preg_replace("<îkā>", "î·kā", $t);
	$t = preg_replace("<iēaaēvîdūanīi>", "iē·aaēvâdūanī·i", $t);
	$t = preg_replace("<iîhuā'h>", "Iâhuāh", $t);	
	$t = preg_replace("<iîhuāh>", "Iâhuāh", $t);
	$t = preg_replace("<ebârāhām>", "Ebârāhām", $t);
	$t = preg_replace("<lnēĥşāun>", "lî·Nēĥşāun", $t);
	$t = preg_replace("<nēĥşuōn>", "Nēĥşuōn", $t);
	$t = preg_replace("<uîruōĥē eălōhiɱ>", "uî·Ruōĥē Eălōhiɱ", $t);
	$t = preg_replace("<mōşęh>", "Mōşęh", $t);
	$t = preg_replace("<iéşuōa>", "Iéşuōa", $t);	
	$t = preg_replace("<iīsârāeél>", "IīsârāeEél", $t);	
	$t = preg_replace("<iīsîrāeél>", "IīsârāeEīl", $t);
	$t = preg_replace("<iī·sîrāeél>", "IīsârāeEīl", $t);
	$t = preg_replace("<iōērîdéɳ>", "Iōērâdéɳ", $t);
	$t = preg_replace("<hē·iōērîđéɳ>", "hē·Iōērâđéɳ", $t);	
	$t = preg_replace("<iōērîđéɳ>", "Iōērâđéɳ", $t);  
	$t = preg_replace("<sēlmāun>", "Sēlmāun", $t);
	$t = str_replace(" lSē", " lî·Sē", $t);
	$t = preg_replace("< uē·uē>", " uē·uē·", $t);
	$t = preg_replace("<uē·iōó>", "uē·iōó·", $t);
	$t = preg_replace("<bî·ā>", "bâā", $t);	
	$t = preg_replace("<bā·r>", "bār", $t);
	$t = preg_replace("<bî·ā>", "bâā", $t);
	$t = preg_replace("< lé>", " lé·", $t);
	$t = preg_replace("< lē>", " lē·", $t);
	$t = preg_replace("< hē>", " hē·", $t);
	$t = preg_replace("<hē·r>", "hēr", $t);
	$t = preg_replace("<āaɱ>", "aɱ", $t);
	$t = preg_replace("<hāiîtāh>", "hāiî·tāh", $t);
	$t = preg_replace("< hā>", " hā·", $t);
	$t = preg_replace("<-'hē>", "-'hē·", $t);
	$t = preg_replace("<-hē>", "-hē·", $t);
	$t = preg_replace("<hāeō'hęl>", "hā·eō'hęl", $t);
	$t = preg_replace("<hāeāręţ>", "hā·eāręţ", $t);
	$t = preg_replace("<mşīiĥāe>", "Mâşīiĥāe", $t);
	$t = preg_replace("<đēuīiđâ>", "Đēuīiđ", $t);
	$t = preg_replace("< bî·éiɳ>", " bâéiɳ", $t);
	$t = preg_replace("< mī>", " mī·", $t);
	$t = preg_replace("<bâāaā>", "bîā·aā", $t);
	$t = preg_replace("<pōāerāɳ>", "Pōāerāɳ", $t);		
	$t = preg_replace("<aévęr hē·Iōērâdéɳ>", "Aévęr hē·Iōērâdéɳ", $t);
	$t = preg_replace("<ióeémęr>", "ió·eémęr", $t);
	$t = preg_replace("<iōeémęr>", "iō·eémęr", $t);
	$t = preg_replace("<lpērţ>", "lî·Pērâţ", $t);
	$t = preg_replace("<lzērĥ>", "lî·Zērâĥ", $t);
	$t = preg_replace("<lĥéţrāun>", "lî·Ĥéţrāun", $t);
	$t = preg_replace("<ĥéţruōn>", "Ĥéţruōn", $t);
	$t = preg_replace("<pērţ>", "Pērâţ", $t);
	$t = preg_replace("<tāmār>", "Tāmār", $t);
	$t = preg_replace("<erām>", "Erām", $t);
	$t = preg_replace("<eārām>", "Eārām", $t);
	$t = preg_replace("<laēmī·inādābâ>", "lî·Aēmīinādābâ", $t);
	$t = preg_replace("<bā·aārāvāh muֹl suōf>", "bā·Aārāvāh Muֹl Suōf", $t);
	$t = preg_replace("<tpęl>", "Tōpęl", $t);
	$t = preg_replace("<tîh>", "tâh", $t);
	$t = preg_replace("<lāvāɳ>", "Lāvāɳ", $t);
	$t = preg_replace("<ĥāţérōt>", "Ĥāţérōt", $t);
	$t = preg_replace("<iî·huāh>", "Iâhuāh", $t); //DIVINE NAME
	$t = preg_replace("<eălōh'iɱ>", "Eălōh'iɱ", $t); //Westmister Institute Punctuation for Leningrad Codex
	$t = preg_replace("<eălōhiɱ>", "Eălōhiɱ", $t); //Oxford University Simple Punctuation for Leningrad Codex
	$t = preg_replace("<eélāiu>", "eélāi·u", $t);
	$t = preg_replace("<eāvīiu>", "eāvīi·u", $t);
	$t = preg_replace("<eānīi>", "eānī·i", $t);
	$t = preg_replace("<şîmīi>", "şâmī·i", $t);
	$t = preg_replace("<aēmīi>", "aēmī·i", $t);
	$t = preg_replace("<rōeşīi>", "rōeşī·i", $t);
	$t = preg_replace("<eīmōuѐ>", "eīmō·uѐ", $t);
	$t = preg_replace("<eēvîrāhāɱ>", "Eēvîrāhāɱ", $t);
	$t = preg_replace("<iī·ţîĥāq>", "Iīţâĥāq", $t);
	$t = preg_replace("<iēaāqōv>", "Iēaāqōv", $t);	
	$t = preg_replace("<eél şēdāi>", "Eél Şēdāi", $t);
	$t = preg_replace("<şēdāi>", "Şēdāi", $t);
	$t = preg_replace("<nѐdēaîtīi>", "nѐ·dēaîtī·i", $t);
	$t = preg_replace("<sāeéhuō>", "sāeé·huō", $t);	
	$t = preg_replace("<lāhęɱ>", "lā·hęɱ", $t);
	$t = preg_replace("<pāerāɳ>", "Pāerāɳ", $t); 
	$t = preg_replace("<tōpęl>", "Tōpęl", $t);
	$t = preg_replace("<méĥōrév>", "mé·Ĥōrév", $t);
	$t = preg_replace("<ĥōrév>", "Ĥōrév", $t);
	$t = preg_replace("<cîĥō'ɱ>", "câĥō'ɱ", $t);	
	$t = preg_replace("<séaīir>", "Séaīir", $t); 
	$t = preg_replace("<qādéş>", "Qādéş", $t);
	$t = preg_replace("<bē·rînéaē>", "Bērânéaē", $t);	
	$t = preg_replace("<mēmîré'e>", "Mēmîré'e", $t);
	$t = preg_replace("<bî·eélōné'i>", "bî·Eélōné'i", $t);
	$t = preg_replace("<eălīişā'a>", "Eălīişā'a", $t);
	$t = preg_replace("<eălīişāa>", "Eălīişāa", $t);
	$t = preg_replace("<eălīişā>", "Eălīişā", $t);
	$t = preg_replace("<şuōnéőɱ>", "Şuōnéőɱ", $t);	
	$t = preg_replace("<rīvîqāh>", "Rīvâqāh", $t);
	$t = preg_replace("<bē·t>", "bēt", $t);
	$t = preg_replace("<bî·tuōeél>", "Bâtuōeél", $t);
	$t = preg_replace("<bē·t>", "bēt", $t);
	$t = preg_replace("<pōēdēɳ>", "Pōēdēɳ", $t);
	$t = preg_replace("<eārāɱ>", "Eārāɱ", $t);
	$t = preg_replace("<iīhuōdāe>", "Iīhuōdāe", $t);
		
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function HebrewAramaicTransliteration($t, $from, $to)
{
	// do not double letters in general
	$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	//Replace From  > To
	$t = preg_replace("<".HOLAM_VAV.">", TO_HOLAM_VAV, $t);
	$t = preg_replace("<"."uō".">", TO_HOLAM_VAV, $t);
	$t = preg_replace("<".HOLAM_MEM.">", TO_HOLAM_MEM, $t);
	$t = preg_replace("<"."mō".">", TO_HOLAM_MEM, $t);
	$t = preg_replace("<".HOLAM_LAMED.">", TO_HOLAM_LAMED, $t);
	$t = preg_replace("<"."lō".">", TO_HOLAM_LAMED, $t);
	$t = preg_replace("<".HOLAM_BHET.">", TO_HOLAM_BHET, $t);
	$t = preg_replace("<"."vō".">", TO_HOLAM_BHET, $t);
	$t = preg_replace("<".HOLAM_TAV.">", TO_HOLAM_TAV, $t);
	$t = preg_replace("<"."tō".">", TO_HOLAM_TAV, $t);
	$t = preg_replace("<".HOLAM_RESH.">", TO_HOLAM_RESH, $t);
	$t = preg_replace("<"."rō".">", TO_HOLAM_RESH, $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", TO_HOLAM_HASHER_VAV, $t);
	$t = preg_replace("<"."uѐ".">", TO_HOLAM_HASHER_VAV, $t);	
	
	//Consonants
	$t = preg_replace("<".ALEPH.">", TO_ALEPH, $t);
	$t = preg_replace("<"."e".">", TO_ALEPH, $t);
	$t = preg_replace("<".BET.">", TO_BET, $t);
	$t = preg_replace("<"."b".">", TO_BET, $t);
	$t = preg_replace("<".BHET.">", TO_BHET, $t);
	$t = preg_replace("<"."v".">", TO_BHET, $t);
	$t = preg_replace("<".GIMEL.">", TO_GIMEL, $t);
	$t = preg_replace("<"."g".">", TO_GIMEL, $t);
	$t = preg_replace("<".GHIMEL.">", TO_GHIMEL, $t);
	$t = preg_replace("<"."g".">", TO_GHIMEL, $t);
	$t = preg_replace("<".DALED.">", TO_DALED, $t);
	$t = preg_replace("<"."đ".">", TO_DALED, $t);
	$t = preg_replace("<".DHALED.">", TO_DHALED, $t);
	$t = preg_replace("<"."d".">", TO_DHALED, $t);
	$t = preg_replace("<".HEH_MAPIK.">", TO_HEH_MAPIK, $t);
	//$t = preg_replace("<"."h".">", TO_HEH_MAPIK, $t);
	$t = preg_replace("<".HEH.">", TO_HEH, $t);
	$t = preg_replace("<"."h".">", TO_HEH, $t);
	$t = preg_replace("<".VAV.">", TO_VAV, $t);
	$t = preg_replace("<"."u".">", TO_VAV, $t);
	$t = preg_replace("<".ZED.">", TO_ZED, $t);
	$t = preg_replace("<"."z".">", TO_ZED, $t);
	$t = preg_replace("<".CHET.">", TO_CHET, $t);
	$t = preg_replace("<"."ĥ".">", TO_CHET, $t);
	$t = preg_replace("<".TET.">", TO_TET, $t);
	$t = preg_replace("<"."th".">", TO_TET, $t);
	$t = preg_replace("<".YUD_PLURAL.">", TO_YUD_PLURAL, $t);
	$t = preg_replace("<"."i".">", TO_YUD_PLURAL, $t);
	$t = preg_replace("<".YUD.SHEVA.">", TO_YUD.SHEVA, $t);
	$t = preg_replace("<"."iî".">", TO_YUD.SHEVA, $t);
	$t = preg_replace("< ".YUD.">", TO_YUD, $t);
	$t = preg_replace("< "." i".">", TO_YUD, $t);
	$t = preg_replace("<".YUD.">", TO_YUD, $t);
	$t = preg_replace("<"."y".">", TO_YUD, $t);
	$t = preg_replace("<".KHAF_KAMETZ.">", TO_KHAF_KAMETZ, $t);
	$t = preg_replace("<"."cā".">", TO_KHAF_KAMETZ, $t);
	$t = preg_replace("<".KHAF.">", TO_KHAF, $t);
	$t = preg_replace("<"."cî".">", TO_KHAF, $t);
	$t = preg_replace("<".KAF.SHEVA_NACH.">", TO_KAF.SHEVA_NACH, $t);
	$t = preg_replace("<"."cîâ".">", TO_KAF.SHEVA_NACH, $t);
	$t = preg_replace("<".KHAF_SOFIT.">", TO_KHAF_SOFIT, $t);
	$t = preg_replace("<"."k".">", TO_KHAF_SOFIT, $t);
	$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", TO_KHAF_SOFIT.SHEVA, $t);
	$t = preg_replace("<"."kâ".">", TO_KHAF_SOFIT.SHEVA, $t);
	$t = preg_replace("<".KAF.">", TO_KAF, $t);
	$t = preg_replace("<"."c".">", TO_KAF, $t);
	$t = preg_replace("<".LAMED.">", TO_LAMED, $t);
	$t = preg_replace("<"."l".">", TO_LAMED, $t);
	$t = preg_replace("<".MEM.SHEVA_NACH.">", TO_MEM.SHEVA_NACH, $t);
	$t = preg_replace("<"."mî".">", TO_MEM.SHEVA_NACH, $t);
	$t = preg_replace("<".MEM_SOFIT.">", TO_MEM_SOFIT, $t);
	$t = preg_replace("<"."ɱ".">", TO_MEM_SOFIT, $t);
	$t = preg_replace("<".MEM.">", TO_MEM, $t);
	$t = preg_replace("<"."m".">", TO_MEM, $t);
	$t = preg_replace("<".NUN.">", TO_NUN, $t);
	$t = preg_replace("<"."n".">", TO_NUN, $t);
	$t = preg_replace("<".NUN_SOFIT.">", TO_NUN_SOFIT, $t);
	$t = preg_replace("<"."ɳ".">", TO_NUN_SOFIT, $t);
	$t = preg_replace("<".SAMECH.">", TO_SAMECH, $t);
	$t = preg_replace("<"."s".">", TO_SAMECH, $t);
	$t = preg_replace("<".AYIN.">", TO_AYIN, $t);
	$t = preg_replace("<"."a".">", TO_AYIN, $t);
	$t = preg_replace("<".PEI.">", TO_PEI, $t);
	$t = preg_replace("<"."p".">", TO_PEI, $t);
	$t = preg_replace("<".PHEI_SOFIT.">", TO_PHEI_SOFIT, $t);
	$t = preg_replace("<"."f".">", TO_PHEI_SOFIT, $t);
	$t = preg_replace("<".TZADI.">", TO_TZADI, $t);
	$t = preg_replace("<"."ţ".">", TO_TZADI, $t);
	$t = preg_replace("<".TZADI_SOFIT.">", TO_TZADI_SOFIT, $t);
	$t = preg_replace("<"."ţ".">", TO_TZADI_SOFIT, $t);
	$t = preg_replace("<".KUF.">", TO_KUF, $t);
	$t = preg_replace("<"."q".">", TO_KUF, $t);
	$t = preg_replace("<".RESH.">", TO_RESH, $t);
	$t = preg_replace("<"."r".">", TO_RESH, $t);
	$t = preg_replace("<".SHIN.">", TO_SHIN, $t);
	$t = preg_replace("<"."ş".">", TO_SHIN, $t);
	$t = preg_replace("<".SIN.">", TO_SIN, $t);
	$t = preg_replace("<"."s".">", TO_SIN, $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", TO_SHIN_SHIN_DOT_KAMETZ, $t);
	$t = preg_replace("<"."şā".">", TO_SHIN_SHIN_DOT_KAMETZ, $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", TO_SHIN_SHIN_DOT_SHEVA_NACH, $t);
	$t = preg_replace("<"."şâ".">", TO_SHIN_SHIN_DOT_SHEVA_NACH, $t);
	$t = preg_replace("<".SHIN_NO_DOT.">", TO_SHIN_NO_DOT, $t);
	$t = preg_replace("<"."ş".">", TO_SHIN_NO_DOT, $t);
	$t = preg_replace("<".TAV.">", TO_TAV, $t);
	$t = preg_replace("<"."t".">", TO_TAV, $t);
	$t = preg_replace("<".THAV.">", TO_THAV, $t);
	$t = preg_replace("<"."t".">", TO_THAV, $t);
	
	/* Vowels */
	if (($from !== 'hebrew') && ($from !== 'aramaic') && ($to === 'hebrew'))
	{
		$t = preg_replace("<"."ā".">", TO_CHATAF_KAMETZ, $t);;
		$t = preg_replace("<"."ā".">", TO_KAMETZ_KATAN, $t);
		$t = preg_replace("<"."ā".">", TO_KAMETZ, $t);
		$t = preg_replace("<"."ā".">", TO_CHATAF_PATACH, $t);
		$t = preg_replace("<"."ē".">", TO_PATACH_GANUV, $t);
		$t = preg_replace("<"."ē".">", TO_PATACH, $t);
		$t = preg_replace("<"."î".">", TO_SHEVA_NACH, $t);
		$t = preg_replace("<"."â".">", TO_SHEVA, $t);
		$t = preg_replace("<"."ă".">", TO_CHATAF_SEGOL, $t);
		$t = preg_replace("<"."ę".">", TO_SEGOL, $t);
		$t = preg_replace("<"."é".">", TO_TZEIREI_MALEI, $t);
		$t = preg_replace("<"."é".">", TO_TZEIREI_CHASER, $t);
		$t = preg_replace("<"."ī".">", TO_CHIRIK_MALEI, $t);
		$t = preg_replace("<"."ī".">", TO_CHIRIK_CHASER, $t);
		$t = preg_replace("<"."ó".">", TO_HOLAM_HASHER, $t);
		$t = preg_replace("<"."ō".">", TO_CHOLAM_MALEI, $t);
		$t = preg_replace("<"."ō".">", TO_CHOLAM_CHASER, $t);
		$t = preg_replace("<"."ō".">", TO_MAPIQ, $t);
		$t = preg_replace("<"."a".">", TO_METEG, $t);
		$t = preg_replace("<"."ū".">", TO_KUBUTZ, $t);
		$t = preg_replace("<"."'".">", TO_TIPEHA, $t); 
		$t = preg_replace("<"."'".">", TO_MERKHA, $t); 
		$t = preg_replace("<"."''".">", TO_MERKHA_KEFULA, $t);		
		$t = preg_replace("<"."´".">", TO_MUNAH, $t);		
		$t = preg_replace("<"."'".">", TO_ETNAHTA, $t);		
		$t = preg_replace("<"."^".">", TO_ATNAH_HAFUKH, $t); 	
		$t = preg_replace("<"."°".">", TO_YERAH_BEN_YOMO, $t);	
	}
	
	if (($from !== 'aramaic') && ($from !== 'hebrew') && ($to === 'aramaic')) 
	{
		$t = preg_replace("<"."hę".">", TO_RUKKAKHA_UP_ZLAMA_ANGULAR, $t);
		$t = preg_replace("<"."ä".">", TO_PTHAHA_UP, $t);
		$t = preg_replace("<"."ā".">", TO_PTHAHA_DOWN, $t);		 
		$t = preg_replace("<"."ü".">", TO_PTHAHA_DOTTED, $t); 
		$t = preg_replace("<"."ő".">", TO_ZQAPHA_UP, $t);
		$t = preg_replace("<"."ö".">", TO_ZQAPHA_DOWN, $t); 		 
		$t = preg_replace("<"."ù".">", TO_ZQAPHA_DOTTED, $t);
		$t = preg_replace("<"."à".">", TO_RBASA_UP, $t); 
		$t = preg_replace("<"."ē".">", TO_RBASA_DOWN, $t);
		$t = preg_replace("<"."ů".">", TO_RBASA_DOTTED, $t);  
		$t = preg_replace("<"."é".">", TO_ZLAMA_ANGULAR, $t); 
		$t = preg_replace("<"."ì".">", TO_ZLAMA_UP, $t);
		$t = preg_replace("<"."ī".">", TO_ZLAMA_DOWN, $t);		 
		$t = preg_replace("<"."ý".">", TO_ZLAMA_DOTTED, $t); 
		$t = preg_replace("<"."ó".">", TO_ESASA_UP, $t);
		$t = preg_replace("<"."ō".">", TO_ESASA_DOWN, $t);		
		$t = preg_replace("<"."y".">", TO_RWAHA, $t);
		$t = preg_replace("<"."ą".">", TO_FEMININE_DOT, $t);
		$t = preg_replace("<"."dâ".">", TO_DALED.QUSHSHAYA, $t);
		$t = preg_replace("<"."dî".">", TO_DHALED.QUSHSHAYA, $t);
		$t = preg_replace("<"."mî".">", TO_MEM.QUSHSHAYA, $t);
		$t = preg_replace("<"."â".">", TO_QUSHSHAYA, $t);		 
		$t = preg_replace("<"."qâ".">", TO_KUF.QUSHSHAYA, $t);
		$t = preg_replace("<"."â".">", TO_RUKKAKHA, $t);
		$t = preg_replace("<"."å".">", TO_VERTICAL_DOTS_UP, $t); 
		$t = preg_replace("<"."ё".">", TO_VERTICAL_DOTS_DOWN, $t); 
		$t = preg_replace("<"."ū".">", TO_THREE_DOTS_UP, $t);
		$t = preg_replace("<"."ę".">", TO_THREE_DOTS_DOWN, $t);		
		$t = preg_replace("<"."ī".">", TO_OBLIQUE_LINE_UP, $t);		
		$t = preg_replace("<"."ё".">", TO_OBLIQUE_LINE_DOWN, $t); 		
		$t = preg_replace("<"."#".">", TO_MUSIC, $t);
		$t = preg_replace("<"."+".">", TO_BARREKH, $t);		
		$t = preg_replace("<"."־".">", TO_MAQAF, $t); 		
	}	
	
	if (($from === 'aramaic') && ($to === 'hebrew')) 
	{
		$t = preg_replace("<".RUKKAKHA_UP_ZLAMA_ANGULAR.">", TO_SEGOL, $t);
		$t = preg_replace("<".PTHAHA_UP.">", TO_PTHAHA_UP, $t);
		$t = preg_replace("<".PTHAHA_DOWN.">", TO_PTHAHA_DOWN, $t);
		$t = preg_replace("<".PTHAHA_DOTTED.">", TO_PTHAHA_DOTTED, $t); 
		$t = preg_replace("<".ZQAPHA_UP.">", TO_ZQAPHA_UP, $t);
		$t = preg_replace("<".ZQAPHA_DOWN.">", TO_ZQAPHA_DOWN, $t);
		$t = preg_replace("<".ZQAPHA_DOTTED.">", TO_KAMETZ_KATAN, $t);
		$t = preg_replace("<".RBASA_UP.">", TO_PATACH, $t);
		$t = preg_replace("<".RBASA_DOWN.">", TO_PATACH_GANUV, $t); 
		$t = preg_replace("<".RBASA_DOTTED.">", TO_CHATAF_SEGOL, $t);  
		$t = preg_replace("<".ZLAMA_ANGULAR.">", TO_ZLAMA_ANGULAR, $t); 
		$t = preg_replace("<".ZLAMA_UP.">", TO_CHIRIK, $t);
		$t = preg_replace("<".ZLAMA_DOWN.">", TO_CHIRIK_MALEI, $t);	
		$t = preg_replace("<".ZLAMA_DOTTED.">", TO_CHIRIK, $t);  
		$t = preg_replace("<".ESASA_UP.">", TO_CHOLAM_MALEI, $t);
		$t = preg_replace("<".ESASA_DOWN.">", TO_ESASA_DOWN, $t);		
		$t = preg_replace("<".RWAHA.">", TO_CHIRIK, $t);
		$t = preg_replace("<".FEMININE_DOT.">", TO_SIN_DOT, $t);
		$t = preg_replace("<".DALED.QUSHSHAYA.">", TO_DALED.TO_SHEVA, $t);
		$t = preg_replace("<".DHALED.QUSHSHAYA.">", TO_DHALED.TO_SHEVA, $t);
		$t = preg_replace("<".MEM.TO_SHEVA.">", TO_MEM.TO_SHEVA, $t);
		$t = preg_replace("<".QUSHSHAYA.">", TO_SHEVA, $t);		
		$t = preg_replace("<".KUF.QUSHSHAYA.">", TO_KUF.TO_SHEVA, $t); 
		$t = preg_replace("<".RUKKAKHA.">", TO_SHEVA, $t);;
		$t = preg_replace("<".VERTICAL_DOTS_UP.">", TO_VERTICAL_DOTS_UP, $t);
		$t = preg_replace("<".VERTICAL_DOTS_DOWN.">", CHATAF_SEGOL, $t); 
		$t = preg_replace("<".THREE_DOTS_UP.">", TO_KUBUTZ, $t);
		$t = preg_replace("<".THREE_DOTS_DOWN.">", TO_SEGOL, $t);		
		$t = preg_replace("<".OBLIQUE_LINE_UP.">", TO_RAFE, $t);		
		$t = preg_replace("<".OBLIQUE_LINE_DOWN.">", TO_MERKHA, $t); 		
		$t = preg_replace("<".MUSIC.">", TO_MUSIC, $t);
		$t = preg_replace("<".BARREKH.">", TO_BARREKH, $t);		
		$t = preg_replace("<".MAQAF.">", TO_MAQAF, $t);		
	}
	
	/* Vowels */
	if (($from === 'hebrew') && ($to == 'aramaic'))
	{	
		global $root_path, $phpExt;
		include_once($root_path . 'schemas/to_arc.' . $phpExt);
		
		$t = preg_replace("<".SEGOL.">", TO_RUKKAKHA_UP_ZLAMA_ANGULAR, $t);
		//$t = preg_replace("<".PTHAHA_UP.">", TO_PTHAHA_UP, $t);
		//$t = preg_replace("<".PTHAHA_DOWN.">", TO_PTHAHA_DOWN, $t);
		//$t = preg_replace("<".PTHAHA_DOTTED.">", TO_PTHAHA_DOTTED, $t); 
		//$t = preg_replace("<".ZQAPHA_UP.">", TO_ZQAPHA_UP, $t);
		//$t = preg_replace("<".ZQAPHA_DOWN.">", TO_ZQAPHA_DOWN, $t);
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
		$t = preg_replace("<".SHEVA.">", TO_RUKKAKHA, $t);;
		//$t = preg_replace("<".VERTICAL_DOTS_UP.">", TO_VERTICAL_DOTS_UP, $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", TO_VERTICAL_DOTS_DOWN, $t); 
		$t = preg_replace("<".KUBUTZ.">", TO_THREE_DOTS_UP, $t);
		$t = preg_replace("<".SEGOL.">", TO_THREE_DOTS_DOWN, $t);		
		$t = preg_replace("<".RAFE.">", TO_OBLIQUE_LINE_UP, $t);		
		$t = preg_replace("<".MERKHA.">", TO_OBLIQUE_LINE_DOWN, $t); 		
		$t = preg_replace("<"."#".">", TO_MUSIC, $t);
		$t = preg_replace("<"."׃".">", '܀', $t);
		//$t = preg_replace("<".BARREKH.">", TO_BARREKH, $t);		
		//$t = preg_replace("<".MAQAF.">", TO_MAQAF, $t);	
	}		
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function AcademicSpirantization($t, $f)
{
	// do not double letters in general
	$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	$t = preg_replace("<".HOLAM_VAV.">", "wō", $t);
	$t = preg_replace("<".HOLAM_MEM.">", "mō", $t);
	$t = preg_replace("<".HOLAM_LAMED.">", "lō", $t);
	$t = preg_replace("<".HOLAM_BHET.">", "vō", $t);
	$t = preg_replace("<".HOLAM_TAV.">", "tō", $t);
	$t = preg_replace("<".HOLAM_RESH.">", "rō", $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "wѐ", $t);	
	
	//Consonants
	$t = preg_replace("<".ALEPH.">", "ʾ", $t);
	$t = preg_replace("<".BET.">", "b", $t);
	$t = preg_replace("<".BHET.">", "ḇ", $t);
	$t = preg_replace("<".GIMEL.">", "g", $t);
	$t = preg_replace("<".GHIMEL.">", "g̱", $t);
	$t = preg_replace("<".DALED.">", "d", $t);
	$t = preg_replace("<".DHALED.">", "ḏ", $t);
	$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
	$t = preg_replace("<".HEH.">", "h", $t);
	$t = preg_replace("<".VAV.">", "w", $t);
	$t = preg_replace("<".ZED.">", "z", $t);
	$t = preg_replace("<".CHET.">", "ḥ", $t);
	$t = preg_replace("<".TET.">", "ṭ", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "y", $t);
	$t = preg_replace("< ".YUD.">", "y", $t);
	$t = preg_replace("<".YUD.SHEVA.">", "iî", $t);
	$t = preg_replace("<".YUD.">", "y", $t);
	$t = preg_replace("<".KAF.">", "k", $t);
	$t = preg_replace("<".KAF.SHEVA_NACH.">", "kâ", $t);
	$t = preg_replace("<".KHAF_SOFIT.">", "ḵ", $t);
	$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", "ḵâ", $t);
	$t = preg_replace("<".LAMED.">", "l", $t);
	$t = preg_replace("<".MEM.">", "m", $t);
	$t = preg_replace("<".MEM.SHEVA_NACH.">", "mî", $t);
	$t = preg_replace("<".MEM_SOFIT.">", "ɱ", $t);
	$t = preg_replace("<".NUN.">", "n", $t);
	$t = preg_replace("<".NUN_SOFIT.">", "ɳ", $t);
	$t = preg_replace("<".SAMECH.">", "s", $t);
	$t = preg_replace("<".AYIN.">", "a", $t);
	$t = preg_replace("<".PEI.">", "p", $t);
	$t = preg_replace("<".PHEI_SOFIT.">", "f", $t);
	$t = preg_replace("<".TZADI.">", "ṣ", $t);
	$t = preg_replace("<".TZADI_SOFIT.">", "ṣ", $t);
	$t = preg_replace("<".KUF.">", "q", $t);
	$t = preg_replace("<".RESH.">", "r", $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "şâ", $t);
	$t = preg_replace("<".SHIN.">", "š", $t);
	$t = preg_replace("<".SIN.">", "ś", $t);
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "ṯ", $t);	
	
	if ($f === 'hebrew')
	{	
		/* Vowels āyw */
		$t = preg_replace("<".CHATAF_KAMETZ.">", "ŏ", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "ā", $t);
		$t = preg_replace("<".KAMETZ.">", "ā", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "â", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "ē", $t);
		$t = preg_replace("<".PATACH.">", "ē", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "ǝ", $t);
		$t = preg_replace("<".SHEVA.">", "ǝ", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "ĕ", $t);
		$t = preg_replace("<".SEGOL.">", "e", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "é", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "é", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "ī", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "ī", $t);
		$t = preg_replace("<".HOLAM_HASHER.">", "ó", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "ō", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ō", $t);
		$t = preg_replace("<".MAPIQ.">", "ō", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "ū", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);	
		$t = preg_replace("<ֹ >", "ő", $t);
		$t = preg_replace("<ֹ>", "ō", $t);
		
		//Line marks
		$t = preg_replace("<֤>", "'", $t);
		$t = preg_replace("<֙>", "'", $t);
		$t = preg_replace("<֜>", "'", $t);
		$t = preg_replace("<֠>", "'", $t);
		$t = preg_replace("<֔>", "", $t); //"remove"
		$t = preg_replace("<֛>", "'", $t);
		$t = preg_replace("<֗>", "ő", $t);
	}	
	
	//Second Step;
	$t = preg_replace("<".KHAF.">", "c", $t);
	$t = preg_replace("<־>", "-", $t);
	$t = preg_replace("<îkā'>", "î·kā'", $t);
	$t = preg_replace("<îkā>", "î·kā", $t);
	$t = preg_replace("<iēaaēvîdūanīi>", "iē·aaēvâdūanī·i", $t);
	$t = preg_replace("<ֹş>", "ōş", $t);
	$t = preg_replace("<ֹr>", "ōr", $t);
	$t = preg_replace("<ֹt>", "ōt", $t);
	$t = preg_replace("<ֹp>", "p", $t);
	$t = preg_replace("<ֹe>", "óe", $t);
	$t = preg_replace("<ōā>", "ā", $t);
	$t = preg_replace("<ōō>", "ō", $t);
	$t = preg_replace("<ōî>", "î", $t);
	$t = preg_replace("<ōī>", "ī", $t);
	$t = preg_replace("<ōî>", "î", $t);
	$t = preg_replace("<iîhuā'h>", "Iâhuāh", $t);	
	$t = preg_replace("<iîhuāh>", "Iâhuāh", $t);
	$t = preg_replace("<uîruōĥē eălōhiɱ>", "uî·Ruōĥē Eălōhiɱ", $t);
	$t = preg_replace("<mōşęh>", "Mōşęh", $t);
	$t = preg_replace("<ââ>", "â", $t);	
	$t = preg_replace("<iīsârāeél>", "IīsârāeEél", $t);	
	$t = preg_replace("<iīsîrāeél>", "IīsârāeEīl", $t);
	$t = preg_replace("<iōērîdéɳ>", "Iōērâdéɳ", $t);
	$t = preg_replace("< iī>", " iī·", $t);
	$t = preg_replace("<·iī>", "·iī·", $t);
	$t = preg_replace("< iî>", " iî·", $t);
	$t = preg_replace("<·iî>", "·iî·", $t);
	$t = preg_replace("< iō>", " iō·", $t);
	$t = preg_replace("< wē>", " wē·", $t);
	$t = preg_replace("<wē·tē>", "wē·tē·", $t);
	$t = preg_replace("< wî>", " wî·", $t);
	$t = preg_replace("< wō>", " wō·", $t);
	$t = preg_replace("< wā>", " wā·", $t);
	$t = preg_replace("< bî>", " bî·", $t);
	$t = preg_replace("< bē>", " bē·", $t);
	$t = preg_replace("< wâ>", " wî·", $t);
	$t = preg_replace("< bâ>", " bî·", $t);
	$t = preg_replace("< bā>", " bā·", $t);
	$t = preg_replace("< wē·wē>", " wē·wē·", $t);
	$t = preg_replace("<wē·iōó>", "uē·iōó·", $t);
	$t = preg_replace("<bî·ā>", "bâā", $t);	
	$t = preg_replace("<bā·r>", "bār", $t);
	$t = preg_replace("<bî·ā>", "bâā", $t);
	$t = preg_replace("< lé>", " lé·", $t);
	$t = preg_replace("< lē>", " lē·", $t);
	$t = preg_replace("< hē>", " hē·", $t);
	$t = preg_replace("<hē·r>", "hēr", $t);
	$t = preg_replace("<āaɱ>", "aɱ", $t);
	$t = preg_replace("<hāiîtāh>", "hāiî·tāh", $t);
	$t = preg_replace("< hā>", " hā·", $t);
	$t = preg_replace("<-'hē>", "-'hē·", $t);
	$t = preg_replace("<-hē>", "-hē·", $t);
	$t = preg_replace("<hāeō'hęl>", "hā·eō'hęl", $t);
	$t = preg_replace("<hāeāręţ>", "hā·eāręţ", $t);
	$t = preg_replace("< bî·éiɳ>", " bâéiɳ", $t);
	$t = preg_replace("< mī>", " mī·", $t);
	$t = preg_replace("<bâāaā>", "bîā·aā", $t);
	$t = preg_replace("<pōāerāɳ>", "Pōāerāɳ", $t);		
	$t = preg_replace("<aévęr hē·Iōērâdéɳ>", "Aévęr hē·Iōērâdéɳ", $t);
	$t = preg_replace("<ióeémęr>", "ió·eémęr", $t);
	$t = preg_replace("<iōeémęr>", "iō·eémęr", $t);
	$t = preg_replace("<wē·iîhi>", "wē·iî·hi", $t);
	$t = preg_replace("<wē·iî>", "wē·iî·", $t);
	$t = preg_replace("<īi'>", "ī·i'", $t);
	$t = preg_replace("<īi >", "ī·i ", $t);
	$t = preg_replace("<iî·lādē'i>", "iâlādē'i", $t);
	$t = preg_replace("<iî·lādēi>", "iâlādē·i", $t);
	$t = preg_replace("<iāré'e>", "iâlādē·i", $t);
	$t = preg_replace("<eīişīi>", "eīişī·i", $t);
	$t = preg_replace("< bā·őe>", " bāőe", $t);	
	$t = preg_replace("< lā>", " lā·", $t);
	$t = preg_replace("< lē>", " lē·", $t);
	$t = preg_replace("< lé>", " lé·", $t);
	$t = preg_replace("< lę>", " lę·", $t);
	$t = preg_replace("< lѐ>", " lѐ·", $t);
	$t = preg_replace("< lī>", " lī·", $t);
	$t = preg_replace("< lî>", " lî·", $t);
	$t = preg_replace("<bā·aārāvāh muֹl suōf>", "bā·Aārāvāh Muֹl Suōf", $t);
	$t = preg_replace("<tpęl>", "Tōpęl", $t);
	$t = preg_replace("<tîh>", "tâh", $t);
	$t = preg_replace("<lāvāɳ>", "Lāvāɳ", $t);
	$t = preg_replace("<ĥāţérōt>", "Ĥāţérōt", $t);
	$t = preg_replace("<iî·huāh>", "Iâhuāh", $t); //DIVINE NAME
	$t = preg_replace("<eălōh'iɱ>", "Eălōh'iɱ", $t); //Westmister Institute Punctuation for Leningrad Codex
	$t = preg_replace("<eălōhiɱ>", "Eălōhiɱ", $t); //Oxford University Simple Punctuation for Leningrad Codex
	$t = preg_replace("<eélāiu>", "eélāi·u", $t);
	$t = preg_replace("<eāvīiu>", "eāvīi·u", $t);
	$t = preg_replace("<eānīi>", "eānī·i", $t);
	$t = preg_replace("<şîmīi>", "şâmī·i", $t);
	$t = preg_replace("<aēmīi>", "aēmī·i", $t);
	$t = preg_replace("<rōeşīi>", "rōeşī·i", $t);
	$t = preg_replace("<eīmōuѐ>", "eīmō·uѐ", $t);
	$t = preg_replace("<eēvîrāhāɱ>", "Eēvîrāhāɱ", $t);
	$t = preg_replace("<iī·ţîĥāq>", "Iīţâĥāq", $t);
	$t = preg_replace("<iēaāqōv>", "Iēaāqōv", $t);	
	$t = preg_replace("<eél şēdāi>", "Eél Şēdāi", $t);
	$t = preg_replace("<şēdāi>", "Şēdāi", $t);
	$t = preg_replace("<nѐdēaîtīi>", "nѐ·dēaîtī·i", $t);
	$t = preg_replace("<sāeéhuō>", "sāeé·huō", $t);	
	$t = preg_replace("<lāhęɱ>", "lā·hęɱ", $t);
	$t = preg_replace("<pāerāɳ>", "Pāerāɳ", $t); 
	$t = preg_replace("<tōpęl>", "Tōpęl", $t);
	$t = preg_replace("<méĥōrév>", "mé·Ĥōrév", $t);
	$t = preg_replace("<ĥōrév>", "Ĥōrév", $t);
	$t = preg_replace("<cîĥō'ɱ>", "câĥō'ɱ", $t);	
	$t = preg_replace("<séaīir>", "Séaīir", $t); 
	$t = preg_replace("<qādéş>", "Qādéş", $t);
	$t = preg_replace("<bē·rînéaē>", "Bērânéaē", $t);	
	$t = preg_replace("<mēmîré'e>", "Mēmîré'e", $t);
	$t = preg_replace("<bî·eélōné'i>", "bî·Eélōné'i", $t);
	$t = preg_replace("<eălīişā'a>", "Eălīişā'a", $t);
	$t = preg_replace("<eălīişāa>", "Eălīişāa", $t);
	$t = preg_replace("<eălīişā>", "Eălīişā", $t);
	$t = preg_replace("<şuōnéőɱ>", "Şuōnéőɱ", $t);	
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function RomanioteTransliteration($t, $f)
{	
	// do not double letters in general
	$GEMINATE_CANDIDATES = "/(?<hiriqYod>|ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	$t = preg_replace("<".HOLAM_VAV.">", "uō", $t);
	$t = preg_replace("<".HOLAM_MEM.">", "μō", $t);
	$t = preg_replace("<".HOLAM_LAMED.">", "λō", $t);
	$t = preg_replace("<".HOLAM_BHET.">", "ϐō", $t);
	$t = preg_replace("<".HOLAM_TAV.">", "τō", $t);
	$t = preg_replace("<".HOLAM_RESH.">", "ρō", $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "uѐ", $t);	
	
	/* Consonants */
	$t = preg_replace("<".ALEPH.">", "α", $t);
	$t = preg_replace("<".BET.">", "β", $t);
	$t = preg_replace("<".BHET.">", "ϐ", $t);
	$t = preg_replace("<".GIMEL.">", "γ", $t);
	$t = preg_replace("<".GHIMEL.">", "γ", $t);
	$t = preg_replace("<".DALED.">", "δ", $t);
	$t = preg_replace("<".DHALED.">", "ð", $t);
	$t = preg_replace("<".HEH_MAPIK.">", "ε", $t);
	$t = preg_replace("<".HEH.">", "x", $t);
	$t = preg_replace("<".VAV.">", "υ", $t); //ϝ
	$t = preg_replace("<".ZED.">", "ζ", $t);
	$t = preg_replace("<".CHET.">", "η", $t);
	$t = preg_replace("<".TET.">", "τ", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "ι", $t);
	$t = preg_replace("<".YUD.">", "ι", $t);
	$t = preg_replace("<".KAF.">", "χ", $t);
	$t = preg_replace("<".KHAF_SOFIT.">", "ϰ", $t);
	$t = preg_replace("<".LAMED.">", "λ", $t);
	$t = preg_replace("<".MEM.">", "μ", $t);
	$t = preg_replace("<".MEM_SOFIT.">", "μ", $t);
	$t = preg_replace("<".NUN.">", "ν", $t);
	$t = preg_replace("<".NUN_SOFIT.">", "ν", $t);
	$t = preg_replace("<".SAMECH.">", "ξ", $t);
	$t = preg_replace("<".AYIN.">", "ο", $t);
	$t = preg_replace("<".PEI.">", "φ", $t);
	$t = preg_replace("<".PHEI_SOFIT.">", "φ̄", $t);
	$t = preg_replace("<".TZADI_SOFIT.">", "ϻ̄", $t);
	$t = preg_replace("<".TZADI.">", "ϻ", $t);
	$t = preg_replace("<".KUF.">", "κ", $t);
	$t = preg_replace("<".RESH.">", "ρ", $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "Σά", $t);
	$t = preg_replace("<".SHIN.">", "Σ", $t);
	$t = preg_replace("<".SIN.">", "σ", $t);	
	$t = preg_replace("<".TAV.">", "θ", $t);
	$t = preg_replace("<".THAV.">", "τ", $t);
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "ŏ", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "ā", $t);
		$t = preg_replace("<".KAMETZ.">", "ά", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "૩", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "ͣ", $t);
		$t = preg_replace("<".PATACH.">", "α", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "ἐ", $t);
		$t = preg_replace("<".SHEVA.">", "ἑ", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "૩", $t);
		$t = preg_replace("<".SEGOL.">", "ę", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "ε", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "ē", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "ί", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "ϊ", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "ω", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ω", $t);
		$t = preg_replace("<".MAPIQ.">", "ω", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "ου", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);
		$t = preg_replace("<ֹ >", "ó", $t);
		$t = preg_replace("<ֹ>", "ō", $t);	 	
	}
	/* Second Step */
	$t = preg_replace("<mōΣęx>", "MōΣęx", $t);
	$t = preg_replace("<φωαδαν>", "Φωαδαν", $t);
	$t = preg_replace("<tōφęλ>", "Tōφęλ", $t);
	$t = preg_replace("<λάϐάν>", "Λάϐάν", $t);
	$t = preg_replace("<ηəϻεrōτ>", "Ηəϻεrōτ", $t);
	$t = preg_replace("<ιίϻεηάκ>", "Ιιϻεηάκ", $t);
	$t = preg_replace("<α૩lōε'ιμ>", "Α૩lōε'ιμ", $t);
	$t = preg_replace("<εε>", "ε", $t);	
	$t = preg_replace("<ιίσεράαελ>", "Ιισεράαελ", $t);	
	$t = preg_replace("<ιωαρεδεν>", "Ιωαρεδεν", $t);
	$t = preg_replace("< ιī>", " ιī·", $t);
	$t = preg_replace("< ϝē>", " ϝē·", $t);
	$t = preg_replace("< ϝε>", " ϝε·", $t);
	$t = preg_replace("< ϝἐ>", " ϝἐ·", $t);
	$t = preg_replace("< ϝα>", " ϝα·", $t);
	$t = preg_replace("< ϝω>", " ϝω·", $t);
	$t = preg_replace("< υἐ>", " υἐ·", $t);
	$t = preg_replace("< xə>", " xə·", $t);
	$t = preg_replace("< xα>", " xα·", $t);
	$t = preg_replace("< βε>", " βε·", $t);
	$t = preg_replace("< βα>", " βα·", $t);
	$t = preg_replace("< βά>", " βά·", $t);
	$t = preg_replace("<βε·ιν>", "βειν", $t);
	$t = preg_replace("<βε·α>", "βεα", $t);
	$t = preg_replace("<βά·ρά>", "βάρά", $t);
	$t = preg_replace("<μι>", "μι·", $t);
	$t = preg_replace("< xα>", " xα·", $t);
	$t = preg_replace("< xά>", " xά·", $t);
	$t = preg_replace("<··>", "·", $t);
	$t = preg_replace("< βî·éiɳ>", " βâéiɳ", $t);
	$t = preg_replace("<βâāaā>", "βîā·aā", $t);	
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function UkrainianTransliteration($t, $f)
{
	// do not double letters in general
	$GEMINATE_CANDIDATES = "(ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	$t = preg_replace("<".HOLAM_VAV.">", "уо", $t);
	$t = preg_replace("<".HOLAM_MEM.">", "мо", $t);
	$t = preg_replace("<".HOLAM_LAMED.">", "ло", $t);
	$t = preg_replace("<".HOLAM_BHET.">", "во", $t);
	$t = preg_replace("<".HOLAM_TAV.">", "то", $t);
	$t = preg_replace("<".HOLAM_RESH.">", "ро", $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "ѐ", $t);	
	
	//Consonants
	$t = preg_replace("<".ALEPH.">", "е", $t);
	$t = preg_replace("<".BET.">", "б", $t);
	$t = preg_replace("<".BHET.">", "в", $t);
	$t = preg_replace("<".GIMEL.">", "г", $t);
	$t = preg_replace("<".GHIMEL.">", "ґ", $t);
	$t = preg_replace("<".DALED.">", "д", $t);
	$t = preg_replace("<".DHALED.">", "д", $t);
	$t = preg_replace("<".HEH_MAPIK.">", "х", $t);
	$t = preg_replace("<".HEH.">", "х", $t);
	$t = preg_replace("<".VAV.">", "у", $t);
	$t = preg_replace("<".ZED.">", "з", $t);
	$t = preg_replace("<".CHET.">", "ч", $t);
	$t = preg_replace("<".TET.">", "ҭ", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "і", $t);
	$t = preg_replace("<".YUD_PLURAL.YUD.">", "ї", $t);
	$t = preg_replace("<".YUD.SHEVA.">", "я", $t);
	$t = preg_replace("<".YUD.">", "и", $t);
	$t = preg_replace("<".KAF.">", "к", $t);
	$t = preg_replace("<".KAF.SHEVA_NACH.">", "кьъ", $t);
	$t = preg_replace("<".KHAF_SOFIT.">", "к", $t);
	$t = preg_replace("<".KHAF_SOFIT.SHEVA.">", "кь", $t);
	$t = preg_replace("<".LAMED.">", "л", $t);
	$t = preg_replace("<".MEM.">", "м", $t);
	$t = preg_replace("<".MEM.SHEVA_NACH.">", "мь", $t);
	$t = preg_replace("<".MEM_SOFIT.">", "ӎ", $t);
	$t = preg_replace("<".NUN.">", "н", $t);
	$t = preg_replace("<".NUN_SOFIT.">", "ӊ", $t);
	$t = preg_replace("<".SAMECH.">", "с", $t);
	$t = preg_replace("<".AYIN.">", "а", $t);
	$t = preg_replace("<".PEI.">", "п", $t);
	$t = preg_replace("<".PHEI_SOFIT.">", "ф", $t);
	$t = preg_replace("<".TZADI.">", "ц", $t);
	$t = preg_replace("<".TZADI_SOFIT.">", "ц", $t);
	$t = preg_replace("<".KUF.">", "q", $t);
	$t = preg_replace("<".RESH.">", "р", $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_KAMETZ.">", "ша", $t);
	$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "щь", $t);
	$t = preg_replace("<".SHIN.">", "щ", $t);
	$t = preg_replace("<".SIN.">", "ш", $t);
	$t = preg_replace("<".TAV.">", "т", $t);
	$t = preg_replace("<".THAV.">", "т", $t);
	
	/* Vowels */
	if ($f === 'hebrew')
	{
		$t = preg_replace("<".CHATAF_KAMETZ.">", "ā", $t);
		$t = preg_replace("<".KAMETZ_KATAN.">", "ā", $t);
		$t = preg_replace("<".KAMETZ.">", "ā", $t);
		$t = preg_replace("<".CHATAF_PATACH.">", "ā", $t);
		$t = preg_replace("<".PATACH_GANUV.">", "ӭ", $t);
		$t = preg_replace("<".PATACH.">", "ӭ", $t);
		$t = preg_replace("<".SHEVA_NACH.">", "ь", $t);
		$t = preg_replace("<".SHEVA.">", "ъ", $t);
		$t = preg_replace("<".CHATAF_SEGOL.">", "ѫ", $t);
		$t = preg_replace("<".SEGOL.">", "ӗ", $t);
		$t = preg_replace("<".TZEIREI_MALEI.">", "ѐ", $t);
		$t = preg_replace("<".TZEIREI_CHASER.">", "ѐ", $t);
		$t = preg_replace("<".CHIRIK_MALEI.">", "ī", $t);
		$t = preg_replace("<".CHIRIK_CHASER.">", "ī", $t);
		$t = preg_replace("<".HOLAM_HASHER.">", "ѐ", $t);
		$t = preg_replace("<".CHOLAM_MALEI.">", "о", $t);
		$t = preg_replace("<".CHOLAM_CHASER.">", "ō", $t);
		$t = preg_replace("<".MAPIQ.">", "ō", $t);
		$t = preg_replace("<".METEG.">", "a", $t);
		$t = preg_replace("<".KUBUTZ.">", "ū", $t);
		$t = preg_replace("<".TIPEHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA.">", "'", $t); 
		$t = preg_replace("<".MERKHA_KEFULA.">", "''", $t);	
		$t = preg_replace("<".MUNAH.">", "´", $t);	
		$t = preg_replace("<".ETNAHTA.">", "'", $t); 
		$t = preg_replace("<".ATNAH_HAFUKH.">", "^", $t); 
		$t = preg_replace("<".YERAH_BEN_YOMO.">", "°", $t);		
	}
	
	//Second Step;
	$t = preg_replace("<ֹв>", "ōв", $t);
	$t = preg_replace("<ֹр>", "ōр", $t);
	$t = preg_replace("<".KHAF.">", "к", $t);
	$t = preg_replace("<ōā>", "ā", $t);
	$t = preg_replace("<ōō>", "ō", $t);
	$t = preg_replace("<ōь>", "ь", $t);
	$t = preg_replace("<ōь>", "ī", $t);
	$t = preg_replace("<ōь>", "ь", $t);
	$t = preg_replace("<ъъ>", "ъ", $t);
	$t = preg_replace("<бьтуōеѐл>", "Бьтуōеѐл", $t);
	$t = preg_replace("<іīшьрāеѐл>", "Іīшьрāеѐл", $t);
	$t = preg_replace("<мощӗх>", "Мощӗх", $t);
	$t = preg_replace("<пāерāӊ>", "Пāерāӊ", $t);
	$t = preg_replace("<топӗл>", "Топӗл", $t);
	$t = preg_replace("<лāвāӊ>", "Лāвāӊ", $t);
	$t = preg_replace("<чāцѐрот>", "Чāцѐрот", $t);
	$t = preg_replace("<іōӭрьдѐӊ>", "Іōӭрьдѐӊ", $t);
	$t = preg_replace("< хāеāaрӗц>", " хāEāaрӗц", $t);
	$t = preg_replace("<аӭд qāдѐщ>", "аӭд Qāдѐщ", $t);	
	$t = preg_replace("< iь>", " iь·", $t);
	$t = preg_replace("<·iь>", "·iь·", $t);
	$t = preg_replace("< iь>", " iь·", $t);
	$t = preg_replace("<·iь>", "·iь·", $t);
	$t = preg_replace("< iō>", " iō·", $t);
	$t = preg_replace("< уӭ>", " уӭ·", $t);
	$t = preg_replace("< уē>", " уē·", $t);
	$t = preg_replace("< уь>", " уь·", $t);
	$t = preg_replace("< уō>", " уō·", $t);
	$t = preg_replace("< уā>", " уā·", $t);
	$t = preg_replace("< bь>", " bь·", $t);
	$t = preg_replace("< bē>", " bē·", $t);
	$t = preg_replace("< уâ>", " уь·", $t);
	$t = preg_replace("< бā>", " бā·", $t);
	$t = preg_replace("< бӭ>", " бӭ·", $t);
	$t = preg_replace("<бӭ·т>", "бӭт", $t);
	$t = preg_replace("< бь>", " бь·", $t);
	$t = preg_replace("< bъ>", " bь·", $t);
	$t = preg_replace("< bā>", " bā·", $t);
	$t = preg_replace("<bь·ā>", "bъā", $t);	
	$t = preg_replace("<bā·r>", "bār", $t);
	$t = preg_replace("<bь·ā>", "bъā", $t);
	$t = preg_replace("< хӭ>", " хӭ·", $t);
	$t = preg_replace("<хӭ·р>", " хӭр", $t);
	$t = preg_replace("< мī>", " мī·", $t);
	$t = preg_replace("<пōӭдӭӊ>", "Пōӭдӭӊ", $t);
	$t = preg_replace("<еāрāӎ>", "Еāрāӎ", $t);	
	$t = preg_replace("< хā>", " хā·", $t);
	$t = preg_replace("< ль>", " ль·", $t);
	$t = preg_replace("<iōeéмęr>", "iō·eéмęr", $t);
	$t = preg_replace("<уē·iьhi>", "уē·iь·hi", $t);
	$t = preg_replace("<уē·iь>", "уē·iь·", $t);
	$t = preg_replace("<уӭ·іь>", "уӭ·iь·", $t);
	$t = preg_replace("<іьхуāх>", "ІЬХУĀХ", $t);
	$t = preg_replace("<еѫлохіӎ>", "Еѫлохіӎ", $t);	
	$t = preg_replace("<еѐлāіу>", "еѐлāі·у", $t);	
	$t = preg_replace("<еāнīі>", "еāнī·і", $t);
	$t = preg_replace("<щьмīі>", "щьмī·і", $t);
	$t = preg_replace("<лāхӗӎ>", "лā·хӗӎ", $t);
	$t = preg_replace("<нѐдӭаьтīі>", "нѐ·дӭаьтī·і", $t);	
	$t = preg_replace("<еӭвьрāхāӎ>", "Еӭвьрāхāӎ", $t);
	$t = preg_replace("<іī·цьчāq>", "Іīцьчāq", $t);
	$t = preg_replace("<іīцьчāq>", "Іīцьчāq", $t);	
	$t = preg_replace("<іīцьчāq>", "Іīцьчāq", $t);
	$t = preg_replace("<іӭаāqōв>", "Іӭаāqōв", $t);	
	$t = preg_replace("<еѐл щӭдāі>", "Еѐл Щӭдāі", $t);	
	$t = preg_replace("<щӭдāі>", "Щӭдāі", $t);
	$t = preg_replace("<бь·аѐвӗр>", "бь·Аѐвӗр", $t);
	$t = preg_replace("<бӭ·рьнѐаӭ>", "Бӭрьнѐаӭ", $t);
	$t = preg_replace("<щӭдāі>", "Щӭдāі", $t);	
	$t = preg_replace("<мѐчōрѐв>", "мѐ·Чōрѐв", $t);
	$t = preg_replace("<чōрѐв>", "Чōрѐв", $t);
	$t = preg_replace("<шѐаīір>", "Шѐаīір", $t);
	$t = preg_replace("<qāдѐщ>", "Qāдѐщ", $t);
	$t = preg_replace("<рīвьqāх>", "Рīвьqāх", $t);		
	$t = preg_replace("<Бьтуōеѐл>", "Бьтуōеѐл", $t);
	
	//Other line marks
	$t = preg_replace("<֤>", "'", $t);
	$t = preg_replace("<֙>", "'", $t);
	$t = preg_replace("<֜>", "'", $t);
	$t = preg_replace("<֠>", "'", $t);
	$t = preg_replace("<֔>", "", $t);
	$t = preg_replace("<֛>", "'", $t);
	$t = preg_replace("<֗>", "ő", $t);
	
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
	$len = count($words);
	//	print "len: " . $len . "--";
	for ($i = 0; $i < $len; $i++)
	{
		$letters = explode_split("SPACE", $words[$i]);
		$len2 = count($letters);
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
	$len = count($trup);
	$root = new tree_node(0, $len - 1);
	$root->generate_trup_tree();
	$root->print_trup_tree();
}

function generateTransliteration($sourcetext, $targetlang, $sourcelang, $isOpera = false)
{
	global $root_path, $phpExt;
	switch($targetlang)
	{ 
		case 'aramaic':
			include_once($root_path . 'schemas/to_arc.' . $phpExt);		
		break;
		case 'hebrew':   
		default:
			include_once($root_path . 'schemas/to_heb.' . $phpExt);
		break;	
	}
	switch($sourcelang)
	{ 
		case 'aramaic':
			include_once($root_path . 'schemas/arc.' . $phpExt);		
			generateNewTransliteration($sourcetext, $targetlang, $sourcelang, $isOpera);
		break;
		case 'hebrew':   
		default:
			include_once($root_path . 'schemas/heb.' . $phpExt);
			generateNewTransliteration($sourcetext, $targetlang, $sourcelang, $isOpera);
		break;	
	}
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
		$t2 = RomanianTransliteration($t, $f);
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
		$t2 = UkrainianTransliteration($t, $f);
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
