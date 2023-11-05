<?php
/**
*
* @package Tranliterator
* @version $Id: trans.php,v 1.1.1 2023/11/06 00:13:55 orynider Exp $
*
*/

//Acces check
if (!defined('IN_PORTAL') && (strpos($_SERVER['PHP_SELF'], "unit_test.php") <= 0)) { die("Direct acces not allowed! This file was accesed: ".$_SERVER['PHP_SELF']."."); }

//Constants
include($root_path . 'contants.' . $phpEx);

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
} // end class

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
			// Do not send 200 OK, but service unavailable on errors
			//send_status_line(503, 'Service Unavailable');

			//garbage_collection();

			// Try to not call the adm page data...

			print '<!DOCTYPE html>';
			print '<html dir="ltr">';
			print '<head><meta charset="UTF-8" />';
			print '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';
			print '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
			print '<meta name="apple-mobile-web-app-capable" content="yes" />';
			print '<meta name="apple-mobile-web-app-status-bar-style" content="blue" />';
			print '<title>' . $msg_title . '</title>';
			print '<style type="text/css">{ margin: 0; padding: 0; } html { font-size: 100%; height: 100%; margin-bottom: 1px; background-color: #E4EDF0; } body { font-family: "Lucida Grande", "Segoe UI", Helvetica, Arial, sans-serif; color: #536482; background: #E4EDF0; font-size: 62.5%; margin: 0; } ';
			print 'a:link, a:active, a:visited { color: #006688; text-decoration: none; } a:hover { color: #DD6900; text-decoration: underline; } ';
			print '#wrap { padding: 0 20px 15px 20px; min-width: 615px; } #page-header { text-align: right; height: 40px; } #page-footer { clear: both; font-size: 1em; text-align: center; } ';
			print '.panel { margin: 4px 0; background-color: #FFFFFF; border: solid 1px  #A9B8C2; } ';
			print '#errorpage #page-header a { font-weight: bold; line-height: 6em; } #errorpage #content { padding: 10px; } #errorpage #content h1 { line-height: 1.2em; margin-bottom: 0; color: #DF075C; } ';
			print '#errorpage #content div { margin-top: 20px; margin-bottom: 5px; border-bottom: 1px solid #CCCCCC; padding-bottom: 5px; color: #333333; font: bold 1.2em "Lucida Grande", "Segoe UI", Arial, Helvetica, sans-serif; text-decoration: none; line-height: 120%; text-align: left; } \n';
			print '</style>';
			print '</head>';
			print '<body id="page">';
			print '<div id="wrap">';
			print '	<div id="page-header">'.$l_return_index.'</div>';	
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

			//exit_handler();

			// On a fatal error (and E_USER_ERROR *is* fatal) we never want other scripts to continue and force an exit here.
			exit;
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
define('DEBUG', true); // [Admin Option] Show Footer debug stats - Actually set in phpBB/includes/constants.php
define('DEBUG_EXTRA', true); // [Admin Option] Show memory usage. Show link to full SQL debug report in footer. Beware, this makes the page slow to load. For debugging only.
define('INCLUDES', 'includes/'); //Main Includes folder
@ini_set('display_errors', '1');
//@error_reporting(E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
//@error_reporting(E_ALL & ~E_NOTICE); //Default error reporting in PHP 5.2+
error_reporting(E_ALL | E_NOTICE | E_STRICT);
@session_cache_expire (1440);
@set_time_limit (1500);
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
	mesagehandler($l_about_title, $l_about_desc, $l_notify, $root_path . 'index.' . $phpEx);
}

function PostHebrewExtendedASCIIToIntermediate($t)
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
	
	return $t;
}


function PostHebrewExtendedASCIIToEncodedUnicode($t)
{
	$t = preg_replace("<ALEPH>", "&#1488;", $t);
	$t = preg_replace("<BET_U>", "&#1489;", $t);
	$t = preg_replace("<GIMEL_U>", "&#1490;", $t);
	$t = preg_replace("<DALED_U>", "&#1491;", $t);
	$t = preg_replace("<HEH_U>", "&#1492;", $t);
	$t = preg_replace("<VAV_U>", "&#1493;", $t);
	$t = preg_replace("<ZED>", "&#1494;", $t);
	$t = preg_replace("<CHET>", "&#1495;", $t);
	$t = preg_replace("<TET>", "&#1496;", $t);
	$t = preg_replace("<YUD_U>", "&#1497;", $t);
	$t = preg_replace("<KAF_U>", "&#1499;", $t);
	$t = preg_replace("<KAF_S_U>", "&#1498;", $t);
	$t = preg_replace("<LAMED>", "&#1500;", $t);
	$t = preg_replace("<MEM>", "&#1502;", $t);
	$t = preg_replace("<MEM_S>", "&#1501;", $t);
	$t = preg_replace("<NUN>", "&#1504;", $t);
	$t = preg_replace("<NUN_S>", "&#1503;", $t);
	$t = preg_replace("<SAMECH>", "&#1505;", $t);
	$t = preg_replace("<AYIN>", "&#1506;", $t);
	$t = preg_replace("<PEI_U>", "&#1508;", $t);
	$t = preg_replace("<PEI_S_U>", "&#1507;", $t);
	$t = preg_replace("<TZADI>", "&#1510;", $t);
	$t = preg_replace("<TZADI_S>", "&#1509;", $t);
	$t = preg_replace("<KUF>", "&#1511;", $t);
	$t = preg_replace("<RESH>", "&#1512;", $t);
	$t = preg_replace("<SHIN_U>", "&#1513;", $t);
	$t = preg_replace("<TAV_U>", "&#1514;", $t);

	$t = preg_replace("<SHEVA_U>", "&#1456;", $t);
	$t = preg_replace("<CHATAF_SEGOL>", "&#1457;", $t);
	$t = preg_replace("<CHATAF_PATACH>", "&#1458;", $t);
	$t = preg_replace("<CHATAF_KAMETZ>", "&#1459;", $t);
	$t = preg_replace("<CHIRIK_U>", "&#1460;", $t);
	$t = preg_replace("<TZEIREI_U>", "&#1461;", $t);
	$t = preg_replace("<SEGOL>", "&#1462;", $t);
	$t = preg_replace("<PATACH_U>", "&#1463;", $t);
	$t = preg_replace("<KAMETZ>", "&#1464;", $t);
	$t = preg_replace("<CHOLAM_U>", "&#1465;", $t);
	$t = preg_replace("<KUBUTZ>", "&#1467;", $t);
	$t = preg_replace("<DAGESH_U>", "&#1468;", $t);
	$t = preg_replace("<SHIN_DOT>", "&#1473;", $t);
	$t = preg_replace("<SIN_DOT>", "&#1474;", $t);
	
	$t = urldecode($t);
	return $t;
}


function PostHebrewToIntermediate($t)
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

/*
Academic Font Friendly Function
*/
function AcademicFontFriendlyTransliteration($t)
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
	$t = preg_replace("<".SHIN.">", "sh", $t);
	$t = preg_replace("<".SIN.">", "s", $t);
	$t = preg_replace("<".SHIN_NO_DOT.">", "(sh)", $t);	
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "th", $t);
	
	/* Vowels */
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


function AshkenazicTransliteration($t)
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
	$t = preg_replace("<".SHIN.">", "sh", $t);
	$t = preg_replace("<".SIN.">", "s", $t);
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "s", $t);	
	
	/* Vowels */
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
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function SefardicTransliteration($t)
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
	$t = preg_replace("<".SHIN.">", "sh", $t);
	$t = preg_replace("<".SIN.">", "s", $t);
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "t", $t);
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

function AcademicTransliteration($t)
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
	
	/* Second Step */
	$t = preg_replace("<ֹş>", "ōş", $t);
	$t = preg_replace("<ֹr>", "ōr", $t);
	$t = preg_replace("<ֹt>", "ōt", $t);
	$t = preg_replace("<ֹp>", "p", $t);
	$t = preg_replace("<ֹe>", "eé", $t);
	$t = preg_replace("<ֹ >", "ó", $t);
	$t = preg_replace("<ֹ>", "ō", $t);
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
function MichiganClaremontTranslit($t)
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
	$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "$"."Ə", $t);
	$t = preg_replace("<".SHIN.">", "$", $t);
	$t = preg_replace("<".SIN.">", "&", $t);
	$t = preg_replace("<".TAV.">", "T.", $t);
	$t = preg_replace("<".THAV.">", "T.", $t);
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


function RomanianTransliteration($t)
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
	$t = preg_replace("<".DALED.">", "d", $t);
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
	$t = preg_replace("<".KAF.">", "c", $t);
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
	$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "şâ", $t);
	//$t = preg_replace("<".SHIN.SHIN_DOT.SHEVA_NACH.">", "şâ", $t); //??
	//$t = preg_replace("<".SHIN.SHEVA_NACH.SHIN_DOT.">", "şâ", $t); //??
	$t = preg_replace("<".SHIN.">", "ş", $t);
	$t = preg_replace("<".SIN.">", "s", $t);
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "t", $t);
	
	/* Vowels */
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
	
	//Line marks
	$t = preg_replace("<֤>", "'", $t);
	$t = preg_replace("<֙>", "'", $t);
	$t = preg_replace("<֜>", "'", $t);
	$t = preg_replace("<֠>", "'", $t);
	$t = preg_replace("<֔>", "", $t); //"remove"
	$t = preg_replace("<֛>", "'", $t);
	$t = preg_replace("<֗>", "ő", $t);
	
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
	$t = preg_replace("<ֹ >", "ő", $t);
	$t = preg_replace("<ֹ>", "ō", $t);
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
	$t = preg_replace("< bî·éiɳ>", " bâéiɳ", $t);
	$t = preg_replace("< mī>", " mī·", $t);
	$t = preg_replace("<bâāaā>", "bîā·aā", $t);
	$t = preg_replace("<pōāerāɳ>", "Pōāerāɳ", $t);		
	$t = preg_replace("<aévęr hē·Iōērâdéɳ>", "Aévęr hē·Iōērâdéɳ", $t);
	$t = preg_replace("<ióeémęr>", "ió·eémęr", $t);
	$t = preg_replace("<iōeémęr>", "iō·eémęr", $t);
	$t = preg_replace("<uē·iîhi>", "uē·iî·hi", $t);
	$t = preg_replace("<uē·iî>", "uē·iî·", $t);
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

function AcademicSpirantization($t)
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
	
	//Line marks
	$t = preg_replace("<֤>", "'", $t);
	$t = preg_replace("<֙>", "'", $t);
	$t = preg_replace("<֜>", "'", $t);
	$t = preg_replace("<֠>", "'", $t);
	$t = preg_replace("<֔>", "", $t); //"remove"
	$t = preg_replace("<֛>", "'", $t);
	$t = preg_replace("<֗>", "ő", $t);
	
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
	$t = preg_replace("<ֹ >", "ő", $t);
	$t = preg_replace("<ֹ>", "ō", $t);
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

function RomanioteTransliteration($t)
{	
	// do not double letters in general
	$GEMINATE_CANDIDATES = "/(?<hiriqYod>|ALEPH|BET|BHET|GIMEL|DALED|VAV|HOLAM_VAV|ZED|TET|YUD|KAF|KHAF_SOFIT|LAMED|MEM|HOLAM_MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	$t = preg_replace("<".HOLAM_VAV.">", "uō", $t);
	$t = preg_replace("<".HOLAM_MEM.">", "mō", $t);
	$t = preg_replace("<".HOLAM_LAMED.">", "lō", $t);
	$t = preg_replace("<".HOLAM_BHET.">", "vō", $t);
	$t = preg_replace("<".HOLAM_TAV.">", "tō", $t);
	$t = preg_replace("<".HOLAM_RESH.">", "rō", $t);
	$t = preg_replace("<".HOLAM_HASHER_VAV.">", "ѐ", $t);	
	
	/* Consonants */
	$t = preg_replace("<".ALEPH.">", "ά", $t);
	$t = preg_replace("<".BET.">", "β", $t);
	$t = preg_replace("<".BHET.">", "μπ", $t);
	$t = preg_replace("<".GIMEL.">", "γ", $t);
	$t = preg_replace("<".GHIMEL.">", "γκ", $t);
	$t = preg_replace("<".DALED.">", "δ", $t);
	$t = preg_replace("<".DHALED.">", "ντ", $t);
	$t = preg_replace("<".HEH_MAPIK.">", "χ", $t);
	$t = preg_replace("<".HEH.">", "h", $t);
	$t = preg_replace("<".HEH.">", "h", $t);
	$t = preg_replace("<".VAV.">", "β", $t);
	$t = preg_replace("<".ZED.">", "ζ", $t);
	$t = preg_replace("<".CHET.">", "ḥ", $t);
	$t = preg_replace("<".TET.">", "τ", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "ι", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "(γ)", $t);
	$t = preg_replace("<".YUD.">", "γι", $t);
	$t = preg_replace("<".KAF.">", "χ", $t);
	$t = preg_replace("<".KHAF_SOFIT.">", "κ", $t);
	$t = preg_replace("<".LAMED.">", "λ", $t);
	$t = preg_replace("<".MEM.">", "μ", $t);
	$t = preg_replace("<".MEM_SOFIT.">", "μ", $t);
	$t = preg_replace("<".NUN.">", "ν", $t);
	$t = preg_replace("<".NUN_SOFIT.">", "ν", $t);
	$t = preg_replace("<".SAMECH.">", "σ", $t);
	$t = preg_replace("<".AYIN.">", "ʿ", $t);
	$t = preg_replace("<".PEI.">", "φ", $t);
	$t = preg_replace("<".PHEI_SOFIT.">", "φ̄", $t);
	$t = preg_replace("<".TZADI_SOFIT.">", "ţ̄", $t);
	$t = preg_replace("<".TZADI.">", "ţ", $t);
	$t = preg_replace("<".KUF.">", "κ", $t);
	$t = preg_replace("<".RESH.">", "ρ", $t);
	$t = preg_replace("<".SHIN.">", "σσ", $t);
	$t = preg_replace("<".SIN.">", "σ", $t);	
	$t = preg_replace("<".TAV.">", "θ", $t);
	$t = preg_replace("<".THAV.">", "τ", $t);
	
	/* Vowels */
	$t = preg_replace("<".CHATAF_KAMETZ.">", "ŏ", $t);
	$t = preg_replace("<".KAMETZ_KATAN.">", "ā", $t);
	$t = preg_replace("<".KAMETZ.">", "α", $t);
	$t = preg_replace("<".CHATAF_PATACH.">", "ə", $t);
	$t = preg_replace("<".PATACH_GANUV.">", "<sup>ē</sup>", $t);
	$t = preg_replace("<".PATACH.">", "α", $t);
	$t = preg_replace("<".SHEVA_NACH.">", "ε", $t);
	$t = preg_replace("<".SHEVA.">", "ε", $t);
	$t = preg_replace("<".CHATAF_SEGOL.">", "ă", $t);
	$t = preg_replace("<".SEGOL.">", "ę", $t);
	$t = preg_replace("<".TZEIREI_MALEI.">", "ε", $t);
	$t = preg_replace("<".TZEIREI_CHASER.">", "ē", $t);
	$t = preg_replace("<".CHIRIK_MALEI.">", "ι", $t);
	$t = preg_replace("<".CHIRIK_CHASER.">", "ι", $t);
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
	
	/* Second Step */
	$t = preg_replace("<ֹş>", "ōş", $t);
	$t = preg_replace("<ֹr>", "ōr", $t);
	$t = preg_replace("<ֹt>", "ōt", $t);
	$t = preg_replace("<ֹp>", "p", $t);
	$t = preg_replace("<ֹe>", "eé", $t);
	$t = preg_replace("<ֹ >", "ó", $t);
	$t = preg_replace("<ֹ>", "ō", $t);
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

function UkrainianTransliteration($t)
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
	$t = preg_replace("<".SHIN_SHIN_DOT_SHEVA_NACH.">", "щь", $t);
	$t = preg_replace("<".SHIN.">", "щ", $t);
	$t = preg_replace("<".SIN.">", "ш", $t);
	$t = preg_replace("<".TAV.">", "т", $t);
	$t = preg_replace("<".THAV.">", "т", $t);
	
	/* Vowels */
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
	$t = preg_replace("< бь>", " бь·", $t);
	$t = preg_replace("< bъ>", " bь·", $t);
	$t = preg_replace("< bā>", " bā·", $t);
	$t = preg_replace("<bь·ā>", "bъā", $t);	
	$t = preg_replace("<bā·r>", "bār", $t);
	$t = preg_replace("<bь·ā>", "bъā", $t);
	$t = preg_replace("< хӭ>", " хӭ·", $t);
	$t = preg_replace("<хӭ·р>", " хӭр", $t);
	$t = preg_replace("< хā>", " хā·", $t);
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
		if (! is_null($firstTrup) )
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

function generateTransliteration($sourcetext, $targetlang, $isFirefox = false, $isOpera = false)
{
	global $origHebrew, $_SERVER;
	
	$t = $sourcetext;
		
	if (!empty($_SERVER["HTTP_USER_AGENT"]))
	{
		$origHebrew = $t;
		$t = "BOUNDARY " . PostHebrewToIntermediate($sourcetext) . "BOUNDARY";
	}
	else
	{
		$origHebrew = PostHebrewExtendedASCIIToEncodedUnicode($t);
		$t = "BOUNDARY " . PostHebrewExtendedASCIIToIntermediate($sourcetext) . "BOUNDARY";
	}
	
	$s = $sourcetext;

	global $t_with_trup;
	$t_with_trup = $t;
	$t = RemoveTrup($t);
	$t = ApplyRulesToIntermediateForm($t);

	//	print $t;
	//	print $s;

	// AND here is the next step: change the intermediate code into
	// transliteration
	//	print "<p>";

	$target = $targetlang;
	if ($target=="academic")
	{
		if (!empty($_SERVER["HTTP_USER_AGENT"]))
		{
			$t1 = AcademicTransliteration($t);
			print $t1;

		}
		else // IE
		{
			print AcademicFontFriendlyTransliteration($t);
		}
	}
	else if ($target == "academic_u")
	{
		$t1 = AcademicTransliteration($t);
		print $t1;
	}
	else if ($target == "academic_ff")
	{
		print AcademicFontFriendlyTransliteration($t);
	}
	else if ($target == "ashkenazic")
	{
		$t2 = AshkenazicTransliteration($t);
		print $t2;
		global $t_without_trup;
		$t_without_trup = explode_split("SPACE", $t2);
		generateAndPrintTrup();
	}
	else if ($target == "romaniote")
	{
		$t2 = RomanioteTransliteration($t);
		print $t2;
	}
	else if ($target == "sefardic")
	{
		$t2 = SefardicTransliteration($t);
		print $t2;
	}
	else if ($target == "academic_s")
	{
		$t2 = AcademicSpirantization($t);
		print $t2;
	}	
	else if ($target == "romanian")
	{
		$t2 = RomanianTransliteration($t);
		print $t2;
	}
	else if ($target == "ukrainian")
	{
		$t2 = UkrainianTransliteration($t);
		print $t2;
	}
	else if ($target == "mc")
	{
		$t2 = MichiganClaremontTranslit($t);
		print $t2;
	}
}

/*
 KAMETZ
 PATACH
 PATACH_UNKOWN
 SEGOL
 TZEIREI_CHASER
 TZEIREI_MALEI
 TZEIREI_UNKNOWN
 TZEIREI_UNKNOWN
 CHIRIK_CHASER
 CHIRIK_MALEI
 CHIRIK_UNKNOWN
 CHOLAM_CHASER
 CHOLAM_MALEI
 CHOLAM_UNKNOWN
 KUBUTZ
 SHURUK
 SHEVA_UNKNOWN
 SHEVA_NA
 SHEVA_NACH
 CHATAF_PATACH
 CHATAF_KAMETZ
 CHATAF_SEGOL
 PATACH_GANUV

 ALEPH
 BET
 BHET
 BET_UNKNOWN
 GIMEL
 GHIMEL
 GIMEL_UNKNOWN
 DALED
 DHALED
 DALED_UNKNOWN
 HEH
 HEH_MAPIK
 HEH_UNKNOWN
 VAV
 ZED
 CHET
 TET
 YUD
 KAF
 KAF_SOFIT
 KHAF
 KHAF-SOFIT
 KAF_UNKNOWN
 KAF_SOFIT_UNKNOWN
 LAMED
 MEM
 MEM_SOFIT
 NUN
 NUN_SOFIT
 SAMECH
 AYIN
 PEI
 PEI_SOFIT
 PHEI
 PHEI_SOFIT
 PEI_UNKNOWN
 PEI_SOFIT_UNKNOWN
 TZADI
 TZADI_SOFIT
 KUF
 RESH
 SHIN
 SIN
 SHIN_UNKNOWN
 TAV
 THAV
 TAV_UNKNOWN
 */
?>
