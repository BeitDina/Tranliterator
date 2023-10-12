<?php
/**
*
* @package Tranliterator
* @version $Id: trans.php,v 1.0.2 2023/10/12 7:04:08 orynider Exp $
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
	function tree_node($begin, $end)
	{
		$this->begin_offset = $begin;
		$this->end_offset = $end;
	}
	function print_offset()
	{
		echo $this->begin_offset;
		if (is_null($this->begin_offset))
		{	
			echo "hello";
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
				echo $t_without_trup[$i];
			}
		}
		
		if (!is_null($this->left) )
		{
			echo "[";
			$this->left->print_trup_tree();
			echo "]";
		}
		
		if (!is_null($this->right) )
		{
			echo "[";
			$this->right->print_trup_tree();
			echo "]";
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
* Funtions 
*/        
function explode_split($delimiters = null, $input="") 
{
		if($delimiters === null || !is_array($delimiters)) 
		{
			$delimiters = array("SPACE", $delimiters);
		}
		
		$query = "";
		//die(print_R($delimiters));
		foreach($delimiters as $delimiter) 
		{
			$query .= preg_quote($delimiter) . "SPACE";
		}
		$query = rtrim($query, "SPACE");
		if($query != "") 
		{
			$query="(".$query.")";
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

//Funtion redefinition ends
function mesagehandler($msg_title, $msg_text, $l_notify, $l_return_index = "index.php") 
{ 
			// Do not send 200 OK, but service unavailable on errors
			//send_status_line(503, 'Service Unavailable');

			//garbage_collection();

			// Try to not call the adm page data...

			print '<!DOCTYPE html>';
			print '<html dir="ltr">';
			print '<head>';
			print '<meta charset="UTF-8">';
			print '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
			print '<title>' . $msg_title . '</title>';
			print '<style type="text/css">{ margin: 0; padding: 0; } html { font-size: 100%; height: 100%; margin-bottom: 1px; background-color: #E4EDF0; } body { font-family: "Lucida Grande", "Segoe UI", Helvetica, Arial, sans-serif; color: #536482; background: #E4EDF0; font-size: 62.5%; margin: 0; } ';
			print 'a:link, a:active, a:visited { color: #006699; text-decoration: none; } a:hover { color: #DD6900; text-decoration: underline; } ';
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
* Funtions Redefinitions for PHP 7+ and 8+
*/
if(!function_exists('ereg'))            { function ereg($pattern, $subject, &$matches = []) { return preg_match('/'.$pattern.'/', $subject, $matches); } }
if(!function_exists('eregi'))           { function eregi($pattern, $subject, &$matches = []) { return preg_match('/'.$pattern.'/i', $subject, $matches); } }
if(!function_exists('ereg_replace'))    { function ereg_replace($pattern, $replacement, $string) { return preg_replace('/'.$pattern.'/', $replacement, $string); } }
if(!function_exists('eregi_replace'))   { function eregi_replace($pattern, $replacement, $string) { return preg_replace('/'.$pattern.'/i', $replacement, $string); } }
if(!function_exists('split'))           { function split($pattern, $subject, $limit = -1) { return preg_split('/'.$pattern.'/', $subject, $limit); } }
if(!function_exists('spliti'))          { function spliti($pattern, $subject, $limit = -1) { return preg_split('/'.$pattern.'/i', $subject, $limit); } }
if(!function_exists('explode')) 		{ function explode($delimiters = null, $input = "") { return explode_split($delimiters, $input); } }

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
$l_notify = 'You can read at <a href="https://github.com/BeitDina/Tranliterator/">github.com/beitdina/Trasliterator</a> more about it.';

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

	//	echo $t;
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

function AcademicTransliteration($t)
{
	//Definitions https://github.com/symbl-cc/symbl-data 
	//Backup: https://github.com/anio/unicode-table-data/blob/95d28cae674791b18798e5cdb846bbffde017097/loc/de/symbols/0500.txt#L200C3-L200C3
	$ALEPH = 'א';
	$BHET = $BET = 'ב';
	$GHIMEL = $GIMEL = 'ג';
	$DHALED = $DALED = 'ד';
	$HEH_MAPIK = $HEH = 'ה';
	$VAV = 'ו';
	$ZED = 'ז';
	$CHET = 'ח';
	$TET = 'ט';
	$YUD_PLURAL = $YUD = 'י';
	$KAF_SOFIT = $KHAF_SOFIT = 'ך';
	$KAF = 'כ';
	$LAMED = 'ל';
	$MEM_SOFIT = 'ם';
	$MEM = 'מ';
	$NUN_SOFIT = 'ן';
	$NUN = 'נ';
	$SAMECH = 'ס';
	$AYIN = 'ע';
	$PHEI_SOFIT = 'ף';
	$PEI = 'פ';
	$TZADI_SOFIT = 'ץ';
	$TZADI = 'צ';
	$KUF = 'ק';
	$RESH = 'ר';
	$SIN = $SHIN = 'ש';
	$THAV = $TAV = 'ת';
	/*
	DAGESH_LETTER: return 'דגש\שורוק'
	Niqqud.KAMATZ: return 'קמץ'
	Niqqud.PATAKH: return 'פתח'
	Niqqud.TZEIRE: return 'צירה'
	Niqqud.SEGOL: return 'סגול'
	Niqqud.SHVA: return 'שוא'
	Niqqud.HOLAM: return 'חולם'
	Niqqud.KUBUTZ: return 'קובוץ'
	Niqqud.HIRIK: return 'חיריק'
	Niqqud.REDUCED_KAMATZ: return 'חטף-קמץ'
	Niqqud.REDUCED_PATAKH: return 'חטף-פתח'
	Niqqud.REDUCED_SEGOL: return 'חטף-סגול'
	SHIN_SMALIT: return 'שין-שמאלית'
	SHIN_YEMANIT: return 'שין-ימנית'
	*/
	
	$SHEVA_NACH = $SHEVA = 'ְ'; //SHVA = '\u05B0'
	$CHATAF_SEGOL = 'ֱ'; //REDUCED_SEGOL = '\u05B1'
	$CHATAF_PATACH = 'ֲ'; //REDUCED_PATAKH = '\u05B2'
	$CHATAF_KAMETZ = 'ֳ'; //REDUCED_KAMATZ = '\u05B3'
	$CHIRIK_MALEI = $CHIRIK_CHASER = $CHIRIK_U = 'ִ'; //HIRIK = '\u05B4'
	$TZEIREI_MALEI = $TZEIREI_CHASER = $TZEIREI = 'ֵ'; //TZEIRE = '\u05B5'
	$SEGOL = 'ֶ'; //SEGOL = '\u05B6'  
	$PATACH_GANUV = '׆'; //\u05C6: Hebräisches Satzzeichen Nun Hafucha || 05C6: Hebräisches Interpunktions-Nonne Hafukha
	$PATACH = 'ַ'; //PATAKH = '\u05B7'; 
	$KAMETZ_KATAN = 'ׇ'; //\u05C7: Hebräisches Zeichen Kametz Katan || 05C7: Hebräischer Punkt Qamats Qatan
	$KAMETZ = 'ָ'; //KAMATZ = '\u05B8'; 
	$CHOLAM_CHASER = 'ֺ';//For Wav
	$HOLAM_HASHER = 'ֹֹ'; //HOLAM HASHER for Wav 
	$CHOLAM_MALEI = $CHOLAM = 'ֹֹ';//HOLAM = '\u05B9'
	$HOLAM_MEM = 'מֹ'; //  מֹ מֹ
	$HOLAM_VAV = 'וֺ'; //
	$METEG = 'ֽ'; //METEG = '\u05BD'
	$MAPIQ = 'ּ'; //u05BC
	$MAQAF = '־'; //u05BE
	$RAFE = 'ֿ'; //u05BF
	$KUBUTZ = 'ֻ'; //KUBUTZ = '\u05BB' 
	$SHURUK = $DAGESH = 'ּ'; //SHURUK = '\u05BC' //or: DAGESH_LETTER = '\u05bc'
	$SHIN_DOT = 'ׁ';  //SHIN_YEMANIT = '\u05c1' &#x05C1 in BabelMap
	$SIN_DOT = 'ׂ'; //SHIN_SMALIT = '\u05c2' &#x05C2 in BabelMap	
	$TIPEHA = '֖'; //U+0596 HEBREW ACCENT TIPEHA : tarha, me'ayla ~ mayla
	$MERKHA = '֥'; //U+05A5 HEBREW ACCENT MERKHA : yored
	$MERKHA_KEFULA = '֦'; //U+05A6 HEBREW ACCENT MERKHA KEFULA	
	$MUNAH = '֣'; //U+05A3 HEBREW ACCENT MUNAH		
	$ETNAHTA = '֑'; //U+0591 HEBREW ACCENT ETNAHTA : atnah
	$ATNAH_HAFUKH = '֢'; //U+05A2 HEBREW ACCENT ATNAH HAFUKH
	$YERAH_BEN_YOMO = '֪'; //U+05AA HEBREW ACCENT YERAH BEN YOMO : galgal
	
	$GEMINATE_CANDIDATES = "($ALEPH|$BET|$BHET|$GIMEL|$DALED|$VAV|$HOLAM_VAV|$ZED|$TET|$YUD|$KAF|$KHAF_SOFIT|$LAMED|$MEM|$HOLAM_MEM|$NUN|$SAMECH|$PEI|$TZADI|$KUF|$SHIN$SHIN_DOT|$SHIN$SIN_DOT|$TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);

	$t = preg_replace("<".$ALEPH.">", "ʾ", $t);
	$t = preg_replace("<".$BET.">", "b", $t);
	$t = preg_replace("<".$BHET.">", "ḇ", $t);
	$t = preg_replace("<".$GIMEL.">", "g", $t);
	$t = preg_replace("<".$GHIMEL.">", "ḡ", $t);
	$t = preg_replace("<".$DALED.">", "d", $t);
	$t = preg_replace("<".$DHALED.">", "ḏ", $t);
	$t = preg_replace("<".$HEH_MAPIK.">", "h", $t);
	$t = preg_replace("<".$HEH.">", "h", $t);
	$t = preg_replace("<".$VAV.">", "w", $t);
	$t = preg_replace("<".$ZED.">", "z", $t);
	$t = preg_replace("<".$CHET.">", "ḥ", $t);
	$t = preg_replace("<".$TET.">", "ṭ", $t);
	$t = preg_replace("<".$YUD_PLURAL.">", "(y)", $t);
	$t = preg_replace("<".$YUD.">", "y", $t);
	$t = preg_replace("<".$KAF.">", "k", $t);
	$t = preg_replace("<".$KHAF_SOFIT."?>", "ḵ", $t);
	$t = preg_replace("<".$LAMED.">", "l", $t);
	$t = preg_replace("<".$MEM_SOFIT ."?>", "m", $t);
	$t = preg_replace("<".$NUN_SOFIT."?>", "n", $t);
	$t = preg_replace("<".$SAMECH.">", "s", $t);
	$t = preg_replace("<".$AYIN.">", "ʿ", $t);
	$t = preg_replace("<".$PEI.">", "p", $t);
	$t = preg_replace("<".$PHEI_SOFIT."?>", "p̄", $t);
	$t = preg_replace("<".$TZADI_SOFIT."?>", "ţ̄", $t);
	$t = preg_replace("<".$KUF.">", "q", $t);
	$t = preg_replace("<".$RESH.">", "r", $t);
	$t = preg_replace("<".$SHIN.">", "s", $t);
	$t = preg_replace("<".$SHIN.$SHIN_DOT.">", "š", $t);
	$t = preg_replace("<".$SHIN.$SIN_DOT.">", "ś", $t);	
	$t = preg_replace("<".$TAV.">", "t", $t);
	$t = preg_replace("<".$THAV.">", "ṯ", $t);
	$t = preg_replace("<".$CHATAF_KAMETZ.">", "ŏ", $t);
	$t = preg_replace("<".$KAMETZ_KATAN.">", "o", $t);
	$t = preg_replace("<".$KAMETZ.">", "ā", $t);
	$t = preg_replace("<".$CHATAF_PATACH.">", "ă", $t);
	$t = preg_replace("<".$PATACH_GANUV.">", "<sup>a</sup>", $t);
	$t = preg_replace("<".$PATACH.">", "a", $t);
	$t = preg_replace("<".$SHEVA_NACH.">", "", $t);
	$t = preg_replace("<".$SHEVA.">", "ə", $t);
	$t = preg_replace("<".$CHATAF_SEGOL.">", "ĕ", $t);
	$t = preg_replace("<".$SEGOL.">", "e", $t);
	$t = preg_replace("<".$TZEIREI_MALEI.">", "ê", $t);
	$t = preg_replace("<".$TZEIREI_CHASER.">", "ē", $t);
	$t = preg_replace("<".$CHIRIK_MALEI.">", "î", $t);
	$t = preg_replace("<".$CHIRIK_CHASER.">", "i", $t);
	$t = preg_replace("<".$CHOLAM_MALEI.">", "ô", $t);
	$t = preg_replace("<".$CHOLAM_CHASER.">", "ō", $t);
	$t = preg_replace("<".$SHURUK.">", "û", $t);
	$t = preg_replace("<".$KUBUTZ.">", "u", $t);
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}


function AcademicFontFriendlyTransliteration($t)
{
	$GEMINATE_CANDIDATES = "(".ALEPH."|".BET."|".GIMEL."|".DALED."|".VAV."|".ZED."|".TET."|".YUD."|".KAF."|".KAF_SOFIT."|".LAMED."|".MEM."|".NUN."|".SAMECH."|".PEI."|".TZADI."|".KUF."|".SHIN."|".SIN."|".TAV.")";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1 \\1", $t);
	
	$t = preg_replace("<".ALEPH.">", "`", $t);
	$t = preg_replace("<".BET.">", "b", $t);
	$t = preg_replace("<".BHET.">", "bh", $t);
	$t = preg_replace("<".GIMEL.">", "g", $t);
	$t = preg_replace("<".GHIMEL.">", "gh", $t);
	$t = preg_replace("<".DALED.">", "d", $t);
	$t = preg_replace("<".DHALED.">", "dh", $t);
	$t = preg_replace("<".HEH_MAPIK.">", "h", $t);
	$t = preg_replace("<".HEH.">", "h", $t);
	$t = preg_replace("<".VAV.">", "w", $t);
	$t = preg_replace("<".ZED.">", "z", $t);
	$t = preg_replace("<".CHET.">", "h", $t); 	//$t = preg_replace("<".CHET.">", "&#295;", $t);
	$t = preg_replace("<".TET.">", "o", $t); //$t = preg_replace("<".TET.">", "&#335;", $t);
	$t = preg_replace("<".YUD_PLURAL.">", "(y)", $t);
	$t = preg_replace("<".YUD.">", "y", $t);
	$t = preg_replace("<".KAF.">", "k", $t);
	$t = preg_replace("<".KHAF_SOFIT."?>", "kh", $t);
	$t = preg_replace("<".LAMED.">", "l", $t);
	$t = preg_replace("<".MEM_SOFIT."?>", "m", $t);
	$t = preg_replace("<".NUN_SOFIT."?>", "n", $t);
	$t = preg_replace("<".SAMECH.">", "s", $t);
	$t = preg_replace("<".AYIN.">", "'", $t);
	$t = preg_replace("<".PEI.">", "p", $t);
	$t = preg_replace("<".PHEI_SOFIT."?>", "ph", $t);
	$t = preg_replace("<".TZADI_SOFIT."?>", "&#351;", $t);
	$t = preg_replace("<".KUF.">", "q", $t);
	$t = preg_replace("<".RESH.">", "r", $t);
	$t = preg_replace("<".SHIN.">", "&#353;", $t);
	$t = preg_replace("<".SIN.">", "&#347;", $t);
	$t = preg_replace("<".TAV.">", "t", $t);
	$t = preg_replace("<".THAV.">", "th", $t);
	$t = preg_replace("<".CHATAF_KAMETZ.">", "&#335;", $t);
	$t = preg_replace("<".KAMETZ_KATAN.">", "o", $t);
	$t = preg_replace("<".KAMETZ.">", "&#257;", $t);
	$t = preg_replace("<".CHATAF_PATACH.">", "&#259;", $t);
	$t = preg_replace("<".PATACH_GANUV.">", "<sup>a</sup>", $t);
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
	$t = preg_replace("<".SHURUK.">", "&#251;", $t); //
	$t = preg_replace("<".KUBUTZ.">", "u", $t);
	 	
	/*	$t = preg_replace("<BOUNDARY COMMA BOUNDARY>", ",", $t);
	 $t = preg_replace("<COMMA>", ",", $t);
	 $t = preg_replace("<BOUNDARY DASH BOUNDARY>", "-", $t);
	 $t = preg_replace("<BOUNDARY SEMICOLON BOUNDARY>", ";", $t);
	 $t = preg_replace("<SEMICOLON>", ";", $t);
	 $t = preg_replace("< >", "", $t);
	 $t = preg_replace("<BOUNDARY>", " ", $t);
	 $t = preg_replace("<PERIOD>", ".", $t);
	 */
	$t = CleanUpPunctuation($t);
	return $t;
}

function AshkenazicTransliteration($t)
{
	//Definitions https://github.com/symbl-cc/symbl-data 
	//Backup: https://github.com/anio/unicode-table-data/blob/95d28cae674791b18798e5cdb846bbffde017097/loc/de/symbols/0500.txt#L200C3-L200C3
	$ALEPH = 'א';
	$BHET = $BET = 'ב';
	$GHIMEL = $GIMEL = 'ג';
	$DHALED = $DALED = 'ד';
	$HEH_MAPIK = $HEH = 'ה';
	$VAV = 'ו';
	$ZED = 'ז';
	$CHET = 'ח';
	$TET = 'ט';
	$YUD_PLURAL = $YUD = 'י';
	$KHAF_SOFIT = 'ך';
	$KAF = 'כ';
	$LAMED = 'ל';
	$MEM_SOFIT = 'ם';
	$MEM = 'מ';
	$NUN_SOFIT = 'ן';
	$NUN = 'נ';
	$SAMECH = 'ס';
	$AYIN = 'ע';
	$PHEI_SOFIT = 'ף';
	$PEI = 'פ';
	$TZADI_SOFIT = 'ץ';
	$TZADI = 'צ';
	$KUF = 'ק';
	$RESH = 'ר';
	$SHIN = 'ש';
	$THAV = $TAV = 'ת';
	/*
	DAGESH_LETTER: return 'דגש\שורוק'
	Niqqud.KAMATZ: return 'קמץ'
	Niqqud.PATAKH: return 'פתח'
	Niqqud.TZEIRE: return 'צירה'
	Niqqud.SEGOL: return 'סגול'
	Niqqud.SHVA: return 'שוא'
	Niqqud.HOLAM: return 'חולם'
	Niqqud.KUBUTZ: return 'קובוץ'
	Niqqud.HIRIK: return 'חיריק'
	Niqqud.REDUCED_KAMATZ: return 'חטף-קמץ'
	Niqqud.REDUCED_PATAKH: return 'חטף-פתח'
	Niqqud.REDUCED_SEGOL: return 'חטף-סגול'
	SHIN_SMALIT: return 'שין-שמאלית'
	SHIN_YEMANIT: return 'שין-ימנית'
	*/
	
	$SHEVA_NACH = $SHEVA = 'ְ'; //SHVA = '\u05B0'
	$CHATAF_SEGOL = 'ֱ'; //REDUCED_SEGOL = '\u05B1'
	$CHATAF_PATACH = 'ֲ'; //REDUCED_PATAKH = '\u05B2'
	$CHATAF_KAMETZ = 'ֳ'; //REDUCED_KAMATZ = '\u05B3'
	$CHIRIK_MALEI = $CHIRIK_CHASER = $CHIRIK_U = 'ִ'; //HIRIK = '\u05B4'
	$TZEIREI_MALEI = $TZEIREI_CHASER = $TZEIREI = 'ֵ'; //TZEIRE = '\u05B5'
	$SEGOL = 'ֶ'; //SEGOL = '\u05B6'  
	$PATACH_GANUV = '׆'; //\u05C6: Hebräisches Satzzeichen Nun Hafucha || 05C6: Hebräisches Interpunktions-Nonne Hafukha
	$PATACH = 'ַ'; //PATAKH = '\u05B7'; 
	$KAMETZ_KATAN = 'ׇ'; //\u05C7: Hebräisches Zeichen Kametz Katan || 05C7: Hebräischer Punkt Qamats Qatan
	$KAMETZ = 'ָ'; //KAMATZ = '\u05B8'; 
	$CHOLAM_CHASER = 'ֺ';//For Wav
	$HOLAM_HASHER = 'ֹֹ'; //HOLAM HASHER for Wav 
	$CHOLAM_MALEI = $CHOLAM = 'ֹֹ';//HOLAM = '\u05B9'
	$HOLAM_MEM = 'מֹ'; //  מֹ מֹ
	$HOLAM_VAV = 'וֺ'; //
	$METEG = 'ֽ'; //METEG = '\u05BD'
	$MAPIQ = 'ּ'; //u05BC
	$MAQAF = '־'; //u05BE
	$RAFE = 'ֿ'; //u05BF
	$KUBUTZ = 'ֻ'; //KUBUTZ = '\u05BB' 
	$SHURUK = $DAGESH = 'ּ'; //SHURUK = '\u05BC' //or: DAGESH_LETTER = '\u05bc'
	$SHIN_DOT = 'ׁ';  //SHIN_YEMANIT = '\u05c1' &#x05C1 in BabelMap
	$SIN_DOT = 'ׂ'; //SHIN_SMALIT = '\u05c2' &#x05C2 in BabelMap	
	$TIPEHA = '֖'; //U+0596 HEBREW ACCENT TIPEHA : tarha, me'ayla ~ mayla
	$MERKHA = '֥'; //U+05A5 HEBREW ACCENT MERKHA : yored
	$MERKHA_KEFULA = '֦'; //U+05A6 HEBREW ACCENT MERKHA KEFULA	
	$MUNAH = '֣'; //U+05A3 HEBREW ACCENT MUNAH		
	$ETNAHTA = '֑'; //U+0591 HEBREW ACCENT ETNAHTA : atnah
	$ATNAH_HAFUKH = '֢'; //U+05A2 HEBREW ACCENT ATNAH HAFUKH
	$YERAH_BEN_YOMO = '֪'; //U+05AA HEBREW ACCENT YERAH BEN YOMO : galgal

	// do not double letters in general
	$GEMINATE_CANDIDATES = "($ALEPH|$BET|$BHET|$GIMEL|$DALED|$VAV|$HOLAM_VAV|$ZED|$TET|$YUD|$KAF|$KHAF_SOFIT|$LAMED|$MEM|$HOLAM_MEM|$NUN|$SAMECH|$PEI|$TZADI|$KUF|$SHIN$SHIN_DOT|$SHIN$SIN_DOT|$TAV)";

	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	$t = preg_replace("<".$ALEPH.">", "e", $t);
	$t = preg_replace("<".$BET.">", "b", $t);
	$t = preg_replace("<".$BHET.">", "v", $t);
	$t = preg_replace("<".$GIMEL.">", "g", $t);
	$t = preg_replace("<".$GHIMEL.">", "g", $t);
	$t = preg_replace("<".$DALED.">", "d", $t);
	$t = preg_replace("<".$DHALED.">", "d", $t);
	$t = preg_replace("<".$HEH_MAPIK.">", "h", $t);
	$t = preg_replace("<".$HEH."BOUNDARY>", "h", $t);
	$t = preg_replace("<".$HEH.">", "h", $t);
	$t = preg_replace("<".$VAV.">", "v", $t);
	$t = preg_replace("<".$HOLAM_VAV.">", "vo", $t);
	$t = preg_replace("<".$ZED.">", "z", $t);
	$t = preg_replace("<".$CHET.">", "ch", $t);
	$t = preg_replace("<".$TET.">", "t", $t);
	$t = preg_replace("<".$YUD_PLURAL.">", "i", $t);
	$t = preg_replace("<".$YUD.">", "y", $t);
	$t = preg_replace("<".$KAF.">", "k", $t);
	$t = preg_replace("<".$KHAF_SOFIT.">", "ch", $t);
	$t = preg_replace("<".$LAMED.">", "l", $t);
	$t = preg_replace("<".$MEM.">", "m", $t);
	$t = preg_replace("<".$MEM_SOFIT.">", "m", $t);
	$t = preg_replace("<".$HOLAM_MEM.">", "mo", $t);
	$t = preg_replace("<".$NUN.">", "n", $t);
	$t = preg_replace("<".$NUN_SOFIT.">", "n", $t);
	$t = preg_replace("<".$SAMECH.">", "s", $t);
	$t = preg_replace("<".$AYIN.">", "a", $t);
	$t = preg_replace("<".$PEI.">", "p", $t);
	$t = preg_replace("<".$PHEI_SOFIT.">", "f", $t);
	$t = preg_replace("<".$TZADI.">", "tz", $t);
	$t = preg_replace("<".$TZADI_SOFIT.">", "ts", $t);
	$t = preg_replace("<".$KUF.">", "k", $t);
	$t = preg_replace("<".$RESH.">", "r", $t);
	$t = preg_replace("<".$SHIN.$SHIN_DOT.">", "sh", $t);
	$t = preg_replace("<".$SHIN.$SIN_DOT.">", "s", $t);
	$t = preg_replace("<".$TAV.">", "t", $t);
	$t = preg_replace("<".$THAV.">", "s", $t);
	$t = preg_replace("<".$CHATAF_KAMETZ.">", "a", $t);
	$t = preg_replace("<".$KAMETZ_KATAN.">", "o", $t);
	$t = preg_replace("<".$KAMETZ.">", "a", $t);
	$t = preg_replace("<".$CHATAF_PATACH.">", "e", $t);
	$t = preg_replace("<".$PATACH_GANUV.">", "a", $t);
	$t = preg_replace("<".$PATACH.">", "a", $t);
	$t = preg_replace("<".$SHEVA_NACH.">", "e", $t);
	$t = preg_replace("<".$SHEVA.">", "'", $t);
	$t = preg_replace("<".$CHATAF_SEGOL.">", "e", $t);
	$t = preg_replace("<".$SEGOL.">", "e", $t);
	$t = preg_replace("<".$TZEIREI_MALEI.">", "ei", $t);
	$t = preg_replace("<".$TZEIREI_CHASER.">", "ei", $t);
	$t = preg_replace("<".$CHIRIK_MALEI.">", "i", $t);
	$t = preg_replace("<".$CHIRIK_CHASER.">", "i", $t);
	$t = preg_replace("<".$CHOLAM.">", "o", $t);
	$t = preg_replace("<".$CHOLAM_MALEI.">", "o", $t);
	$t = preg_replace("<".$CHOLAM_CHASER.">", "o", $t);
	$t = preg_replace("<".$SHURUK.">", "e", $t);
	$t = preg_replace("<".$KUBUTZ.">", "u", $t);
	$t = preg_replace("<".$TIPEHA.">", "'", $t); 
	$t = preg_replace("<".$MERKHA.">", "'", $t); 
	$t = preg_replace("<".$MERKHA_KEFULA.">", "''", $t);	
	$t = preg_replace("<".$MUNAH.">", "'", $t);		
	$t = preg_replace("<".$ETNAHTA.">", "´", $t); 
	$t = preg_replace("<".$ATNAH_HAFUKH.">", "^", $t); 
	$t = preg_replace("<".$YERAH_BEN_YOMO.">", "°", $t); 
	
	ExtractTrup();
	$t = CleanUpPunctuation($t);
	return $t;
}

function SefardicTransliteration($t)
{
	//Definitions https://github.com/symbl-cc/symbl-data 
	//Backup: https://github.com/anio/unicode-table-data/blob/95d28cae674791b18798e5cdb846bbffde017097/loc/de/symbols/0500.txt#L200C3-L200C3
	$ALEPH = 'א';
	$BHET = $BET = 'ב';
	$GHIMEL = $GIMEL = 'ג';
	$DHALED = $DALED = 'ד';
	$HEH_MAPIK = $HEH = 'ה';
	$VAV = 'ו';
	$ZED = 'ז';
	$CHET = 'ח';
	$TET = 'ט';
	$YUD_PLURAL = $YUD = 'י';
	$KHAF_SOFIT = 'ך';
	$KAF = 'כ';
	$LAMED = 'ל';
	$MEM_SOFIT = 'ם';
	$MEM = 'מ';
	$NUN_SOFIT = 'ן';
	$NUN = 'נ';
	$SAMECH = 'ס';
	$AYIN = 'ע';
	$PHEI_SOFIT = 'ף';
	$PEI = 'פ';
	$TZADI_SOFIT = 'ץ';
	$TZADI = 'צ';
	$KUF = 'ק';
	$RESH = 'ר';
	$SHIN = 'ש';
	$THAV = $TAV = 'ת';

	
	$SHEVA_NACH = $SHEVA = 'ְ'; //SHVA = '\u05B0'
	$CHATAF_SEGOL = 'ֱ'; //REDUCED_SEGOL = '\u05B1'
	$CHATAF_PATACH = 'ֲ'; //REDUCED_PATAKH = '\u05B2'
	$CHATAF_KAMETZ = 'ֳ'; //REDUCED_KAMATZ = '\u05B3'
	$CHIRIK_MALEI = $CHIRIK_CHASER = $CHIRIK_U = 'ִ'; //HIRIK = '\u05B4'
	$TZEIREI_MALEI = $TZEIREI_CHASER = $TZEIREI = 'ֵ'; //TZEIRE = '\u05B5'
	$SEGOL = 'ֶ'; //SEGOL = '\u05B6'  
	$PATACH_GANUV = '׆'; //\u05C6: Hebräisches Satzzeichen Nun Hafucha || 05C6: Hebräisches Interpunktions-Nonne Hafukha
	$PATACH = 'ַ'; //PATAKH = '\u05B7'; 
	$KAMETZ_KATAN = 'ׇ'; //\u05C7: Hebräisches Zeichen Kametz Katan || 05C7: Hebräischer Punkt Qamats Qatan
	$KAMETZ = 'ָ'; //KAMATZ = '\u05B8'; 
	$CHOLAM_CHASER = 'ֺ';//For Wav
	$HOLAM_HASHER = 'ֹֹ'; //HOLAM HASHER for Wav 
	$CHOLAM_MALEI = $CHOLAM = 'ֹֹ';//HOLAM = '\u05B9'
	$HOLAM_MEM = 'מֹ'; //  מֹ מֹ
	$HOLAM_VAV = 'וֺ'; //
	$METEG = 'ֽ'; //METEG = '\u05BD'
	$MAPIQ = 'ּ'; //u05BC
	$MAQAF = '־'; //u05BE
	$RAFE = 'ֿ'; //u05BF
	$KUBUTZ = 'ֻ'; //KUBUTZ = '\u05BB' 
	$SHURUK = $DAGESH = 'ּ'; //SHURUK = '\u05BC' //or: DAGESH_LETTER = '\u05bc'
	$SHIN_DOT = 'ׁ';  //SHIN_YEMANIT = '\u05c1' &#x05C1 in BabelMap
	$SIN_DOT = 'ׂ'; //SHIN_SMALIT = '\u05c2' &#x05C2 in BabelMap	
	$TIPEHA = '֖'; //U+0596 HEBREW ACCENT TIPEHA : tarha, me'ayla ~ mayla
	$MERKHA = '֥'; //U+05A5 HEBREW ACCENT MERKHA : yored
	$MERKHA_KEFULA = '֦'; //U+05A6 HEBREW ACCENT MERKHA KEFULA	
	$MUNAH = '֣'; //U+05A3 HEBREW ACCENT MUNAH		
	$ETNAHTA = '֑'; //U+0591 HEBREW ACCENT ETNAHTA : atnah
	$ATNAH_HAFUKH = '֢'; //U+05A2 HEBREW ACCENT ATNAH HAFUKH
	$YERAH_BEN_YOMO = '֪'; //U+05AA HEBREW ACCENT YERAH BEN YOMO : galgal
	
	// do not double letters in general
	$GEMINATE_CANDIDATES = "($ALEPH|$BET|$BHET|$GIMEL|$DALED|$VAV|$HOLAM_VAV|$ZED|$TET|$YUD|$KAF|$KHAF_SOFIT|$LAMED|$MEM|$HOLAM_MEM|$NUN|$SAMECH|$PEI|$TZADI|$KUF|$SHIN$SHIN_DOT|$SHIN$SIN_DOT|$TAV)";
	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	$t = preg_replace("<".$ALEPH.">", "e", $t);
	$t = preg_replace("<".$BET.">", "b", $t);
	$t = preg_replace("<".$BHET.">", "v", $t);
	$t = preg_replace("<".$GIMEL.">", "g", $t);
	$t = preg_replace("<".$GHIMEL.">", "g", $t);
	$t = preg_replace("<".$DALED.">", "d", $t);
	$t = preg_replace("<".$DHALED.">", "d", $t);
	$t = preg_replace("<".$HEH_MAPIK.">", "h", $t);
	$t = preg_replace("<".$HEH."BOUNDARY>", "h", $t);
	$t = preg_replace("<".$HEH.">", "h", $t);
	$t = preg_replace("<".$VAV.">", "u", $t);
	$t = preg_replace("<".$HOLAM_VAV.">", "uō", $t);
	$t = preg_replace("<".$ZED.">", "z", $t);
	$t = preg_replace("<".$CHET.">", "ḥ", $t); // h dot
	$t = preg_replace("<".$TET.">", "th", $t);
	$t = preg_replace("<".$YUD_PLURAL.">", "y", $t);
	$t = preg_replace("< ".$YUD.">", " y", $t);
	$t = preg_replace("<".$YUD.">", "y", $t);
	$t = preg_replace("<".$KAF.">", "k", $t);
	$t = preg_replace("<".$KHAF_SOFIT.">", "kh", $t);
	$t = preg_replace("<".$LAMED.">", "l", $t);
	$t = preg_replace("<".$MEM.">", "m", $t);
	$t = preg_replace("<".$MEM_SOFIT.">", "m", $t);
	$t = preg_replace("<".$NUN.">", "n", $t);
	$t = preg_replace("<".$NUN_SOFIT.">", "n", $t);
	$t = preg_replace("<".$SAMECH.">", "s", $t);
	$t = preg_replace("<".$AYIN.">", "a", $t);
	$t = preg_replace("<".$PEI.">", "p", $t);
	$t = preg_replace("<".$PHEI_SOFIT.">", "f", $t);
	$t = preg_replace("<".$TZADI.">", "tz", $t);
	$t = preg_replace("<".$TZADI_SOFIT.">", "ts", $t);
	$t = preg_replace("<".$KUF.">", "q", $t);
	$t = preg_replace("<".$RESH.">", "r", $t);
	$t = preg_replace("<".$SHIN.$SHIN_DOT.">", "sh", $t);
	$t = preg_replace("<".$SHIN.$SIN_DOT.">", "s", $t);
	$t = preg_replace("<".$TAV.">", "t", $t);
	$t = preg_replace("<".$THAV.">", "t", $t);
	$t = preg_replace("<".$CHATAF_KAMETZ.">", "a", $t);
	$t = preg_replace("<".$KAMETZ_KATAN.">", "a", $t);
	$t = preg_replace("<".$KAMETZ.">", "a", $t);
	$t = preg_replace("<".$CHATAF_PATACH.">", "a", $t);
	$t = preg_replace("<".$PATACH_GANUV.">", "e", $t);
	$t = preg_replace("<".$PATACH.">", "e", $t);
	$t = preg_replace("<".$SHEVA_NACH.">", "ə", $t);
	$t = preg_replace("<".$SHEVA.">", "ə", $t);
	$t = preg_replace("<".$CHATAF_SEGOL.">", "e", $t);
	$t = preg_replace("<".$SEGOL.">", "e", $t);
	$t = preg_replace("<".$TZEIREI_MALEI.">", "e", $t);
	$t = preg_replace("<".$TZEIREI_CHASER.">", "e", $t);
	$t = preg_replace("<".$CHIRIK_MALEI.">", "i", $t);
	$t = preg_replace("<".$CHIRIK_CHASER.">", "i", $t);
	$t = preg_replace("<".$CHOLAM.">", "o", $t);
	$t = preg_replace("<".$CHOLAM_MALEI.">", "o", $t);
	$t = preg_replace("<".$CHOLAM_CHASER.">", "o", $t);
	$t = preg_replace("<".$HOLAM_MEM.">", "mō", $t);
	$t = preg_replace("<".$METEG.">", "a", $t);
	$t = preg_replace("<".$SHURUK.">", "ə", $t);
	$t = preg_replace("<".$KUBUTZ.">", "u", $t);
	$t = preg_replace("<".$TIPEHA.">", "'", $t); 
	$t = preg_replace("<".$MERKHA.">", "'", $t); 
	$t = preg_replace("<".$MERKHA_KEFULA.">", "''", $t);	
	$t = preg_replace("<".$MUNAH.">", "'", $t);		
	$t = preg_replace("<".$ETNAHTA.">", "´", $t); 
	$t = preg_replace("<".$ATNAH_HAFUKH.">", "^", $t); 
	$t = preg_replace("<".$YERAH_BEN_YOMO.">", "°", $t); 
	
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
function MichiganClaremontTranslit($t)
{
	//Definitions https://github.com/symbl-cc/symbl-data 
	//Backup: https://github.com/anio/unicode-table-data/blob/95d28cae674791b18798e5cdb846bbffde017097/loc/de/symbols/0500.txt#L200C3-L200C3
	$ALEPH = 'א';
	$BHET = $BET = 'ב';
	$GHIMEL = $GIMEL = 'ג';
	$DHALED = $DALED = 'ד';
	$HEH_MAPIK = $HEH = 'ה';
	$VAV = 'ו';
	$ZED = 'ז';
	$CHET = 'ח';
	$TET = 'ט';
	$YUD_PLURAL = $YUD = 'י';
	$KHAF_SOFIT = 'ך';
	$KAF = 'כ';
	$LAMED = 'ל';
	$MEM_SOFIT = 'ם';
	$MEM = 'מ';
	$NUN_SOFIT = 'ן';
	$NUN = 'נ';
	$SAMECH = 'ס';
	$AYIN = 'ע';
	$PHEI_SOFIT = 'ף';
	$PEI = 'פ';
	$TZADI_SOFIT = 'ץ';
	$TZADI = 'צ';
	$KUF = 'ק';
	$RESH = 'ר';
	$SHIN = 'ש';
	$THAV = $TAV = 'ת';
	/*
	DAGESH_LETTER: return 'דגש\שורוק'
	Niqqud.KAMATZ: return 'קמץ'
	Niqqud.PATAKH: return 'פתח'
	Niqqud.TZEIRE: return 'צירה'
	Niqqud.SEGOL: return 'סגול'
	Niqqud.SHVA: return 'שוא'
	Niqqud.HOLAM: return 'חולם'
	Niqqud.KUBUTZ: return 'קובוץ'
	Niqqud.HIRIK: return 'חיריק'
	Niqqud.REDUCED_KAMATZ: return 'חטף-קמץ'
	Niqqud.REDUCED_PATAKH: return 'חטף-פתח'
	Niqqud.REDUCED_SEGOL: return 'חטף-סגול'
	SHIN_SMALIT: return 'שין-שמאלית'
	SHIN_YEMANIT: return 'שין-ימנית'
	*/
	
	$SHEVA_NACH = $SHEVA = 'ְ'; //SHVA = '\u05B0'
	$CHATAF_SEGOL = 'ֱ'; //REDUCED_SEGOL = '\u05B1'
	$CHATAF_PATACH = 'ֲ'; //REDUCED_PATAKH = '\u05B2'
	$CHATAF_KAMETZ = 'ֳ'; //REDUCED_KAMATZ = '\u05B3'
	$CHIRIK_MALEI = $CHIRIK_CHASER = $CHIRIK_U = 'ִ'; //HIRIK = '\u05B4'
	$TZEIREI_MALEI = $TZEIREI_CHASER = $TZEIREI = 'ֵ'; //TZEIRE = '\u05B5'
	$SEGOL = 'ֶ'; //SEGOL = '\u05B6'  
	$PATACH_GANUV = '׆'; //\u05C6: Hebräisches Satzzeichen Nun Hafucha || 05C6: Hebräisches Interpunktions-Nonne Hafukha
	$PATACH = 'ַ'; //PATAKH = '\u05B7'; 
	$KAMETZ_KATAN = 'ׇ'; //\u05C7: Hebräisches Zeichen Kametz Katan || 05C7: Hebräischer Punkt Qamats Qatan
	$KAMETZ = 'ָ'; //KAMATZ = '\u05B8'; 
	$CHOLAM_CHASER = 'ֺ';//For Wav
	$HOLAM_HASHER = 'ֹֹ'; //HOLAM HASHER for Wav 
	$CHOLAM_MALEI = $CHOLAM = 'ֹֹ';//HOLAM = '\u05B9'
	$HOLAM_MEM = 'מֹ'; //  מֹ מֹ
	$HOLAM_VAV = 'וֺ'; //
	$METEG = 'ֽ'; //METEG = '\u05BD'
	$MAPIQ = 'ּ'; //u05BC
	$MAQAF = '־'; //u05BE
	$RAFE = 'ֿ'; //u05BF
	$KUBUTZ = 'ֻ'; //KUBUTZ = '\u05BB' 
	$SHURUK = $DAGESH = 'ּ'; //SHURUK = '\u05BC' //or: DAGESH_LETTER = '\u05bc'
	$SHIN_DOT = 'ׁ';  //SHIN_YEMANIT = '\u05c1' &#x05C1 in BabelMap
	$SIN_DOT = 'ׂ'; //SHIN_SMALIT = '\u05c2' &#x05C2 in BabelMap	
	$TIPEHA = '֖'; //U+0596 HEBREW ACCENT TIPEHA : tarha, me'ayla ~ mayla
	$MERKHA = '֥'; //U+05A5 HEBREW ACCENT MERKHA : yored
	$MERKHA_KEFULA = '֦'; //U+05A6 HEBREW ACCENT MERKHA KEFULA	
	$MUNAH = '֣'; //U+05A3 HEBREW ACCENT MUNAH		
	$ETNAHTA = '֑'; //U+0591 HEBREW ACCENT ETNAHTA : atnah
	$ATNAH_HAFUKH = '֢'; //U+05A2 HEBREW ACCENT ATNAH HAFUKH
	$YERAH_BEN_YOMO = '֪'; //U+05AA HEBREW ACCENT YERAH BEN YOMO : galgal
	
	// do not double letters in general
	$GEMINATE_CANDIDATES = "($ALEPH|$BET|$BHET|$GIMEL|$DALED|$VAV|$HOLAM_VAV|$ZED|$TET|$YUD|$KAF|$KHAF_SOFIT|$LAMED|$MEM|$HOLAM_MEM|$NUN|$SAMECH|$PEI|$TZADI|$KUF|$SHIN$SHIN_DOT|$SHIN$SIN_DOT|$TAV)";

	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);

	//Consonants
	$t = preg_replace("<".$ALEPH.">", ")", $t);
	$t = preg_replace("<".$BET.">", "B.", $t);
	$t = preg_replace("<".$BHET.">", "V", $t);
	$t = preg_replace("<".$GIMEL.">", "G", $t);
	$t = preg_replace("<".$GHIMEL.">", "G", $t);
	$t = preg_replace("<".$DALED.">", "D", $t);
	$t = preg_replace("<".$DHALED.">", "D", $t);
	$t = preg_replace("<".$HEH_MAPIK.">", "H", $t);
	$t = preg_replace("<".$HEH."BOUNDARY>", "H.", $t);
	$t = preg_replace("<".$HEH.">", "H", $t);
	$t = preg_replace("<".$VAV.">", "W", $t);
	$t = preg_replace("<".$HOLAM_VAV.">", "WO", $t);
	$t = preg_replace("<".$ZED.">", "Z", $t);
	$t = preg_replace("<".$CHET.">", "X", $t);
	$t = preg_replace("<".$TET.">", "+", $t);
	$t = preg_replace("<".$YUD_PLURAL.">", "Y", $t);
	$t = preg_replace("< ".$YUD.">", "Y.", $t);
	$t = preg_replace("<".$YUD.">", "y", $t);
	$t = preg_replace("<".$KAF.">", "K.", $t);
	$t = preg_replace("<".$KHAF_SOFIT.">", "K", $t);
	$t = preg_replace("<".$LAMED.">", "L", $t);
	$t = preg_replace("<".$MEM.">", "M.", $t);
	$t = preg_replace("<".$MEM_SOFIT.">", "M", $t);
	$t = preg_replace("<".$HOLAM_MEM.">", "MO", $t);
	$t = preg_replace("<".$NUN.">", "N.", $t);
	$t = preg_replace("<".$NUN_SOFIT.">", "N", $t);
	$t = preg_replace("<".$SAMECH.">", "S", $t);
	$t = preg_replace("<".$AYIN.">", "(", $t);
	$t = preg_replace("<".$PEI.">", "P.", $t);
	$t = preg_replace("<".$PHEI_SOFIT.">", "P", $t);
	$t = preg_replace("<".$TZADI.">", "TZ", $t);
	$t = preg_replace("<".$TZADI_SOFIT.">", "C", $t);
	$t = preg_replace("<".$KUF.">", "Q", $t);
	$t = preg_replace("<".$RESH.">", "R", $t);
	$t = preg_replace("<".$SHIN.$SHIN_DOT.">", "$", $t);
	$t = preg_replace("<".$SHIN.$SIN_DOT.">", "&", $t);
	$t = preg_replace("<".$TAV.">", "T.", $t);
	$t = preg_replace("<".$THAV.">", "T.", $t);
	$t = preg_replace("<".$CHATAF_KAMETZ.">", ":F", $t);
	$t = preg_replace("<".$KAMETZ_KATAN.">", "F", $t);
	$t = preg_replace("<".$KAMETZ.">", "F", $t);
	$t = preg_replace("<".$CHATAF_PATACH.">", ":A", $t);
	$t = preg_replace("<".$PATACH_GANUV.">", "A", $t);
	$t = preg_replace("<".$PATACH.">", "A", $t);
	$t = preg_replace("<".$SHEVA_NACH.">", "â", $t);
	$t = preg_replace("<".$SHEVA.">", ":", $t);
	$t = preg_replace("<".$CHATAF_SEGOL.">", ":E", $t);
	$t = preg_replace("<".$SEGOL.">", "E", $t);
	$t = preg_replace("<".$TZEIREI_MALEI.">", "\"", $t);
	$t = preg_replace("<".$TZEIREI_CHASER.">", "\"", $t);
	$t = preg_replace("<".$CHIRIK_MALEI.">", "I", $t);
	$t = preg_replace("<".$CHIRIK_CHASER.">", "I", $t);
	$t = preg_replace("<".$CHOLAM_MALEI.">", "O", $t);
	$t = preg_replace("<".$CHOLAM_CHASER.">", "O", $t);
	$t = preg_replace("<".$METEG.">", "a", $t);
	$t = preg_replace("<".$SHURUK.">", "W.", $t);
	$t = preg_replace("<".$KUBUTZ.">", "U", $t);
	$t = preg_replace("<".$TIPEHA.">", "'", $t); 
	$t = preg_replace("<".$MERKHA.">", "'", $t); 
	$t = preg_replace("<".$MERKHA_KEFULA.">", "''", $t);	
	$t = preg_replace("<".$MUNAH.">", "'", $t);		
	$t = preg_replace("<".$ETNAHTA.">", "´", $t); 
	$t = preg_replace("<".$ATNAH_HAFUKH.">", "^", $t); 
	$t = preg_replace("<".$YERAH_BEN_YOMO.">", "°", $t); 
	
	//Vowels	
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
	//Definitions https://github.com/symbl-cc/symbl-data 
	//Backup: https://github.com/anio/unicode-table-data/blob/95d28cae674791b18798e5cdb846bbffde017097/loc/de/symbols/0500.txt#L200C3-L200C3
	$ALEPH = 'א';
	$BHET = $BET = 'ב';
	$GHIMEL = $GIMEL = 'ג';
	$DHALED = $DALED = 'ד';
	$HEH_MAPIK = $HEH = 'ה';
	$VAV = 'ו';
	$ZED = 'ז';
	$CHET = 'ח';
	$TET = 'ט';
	$YUD_PLURAL = $YUD = 'י';
	$KHAF_SOFIT = 'ך';
	$KAF = 'כ';
	$LAMED = 'ל';
	$MEM_SOFIT = 'ם';
	$MEM = 'מ';
	$NUN_SOFIT = 'ן';
	$NUN = 'נ';
	$SAMECH = 'ס';
	$AYIN = 'ע';
	$PHEI_SOFIT = 'ף';
	$PEI = 'פ';
	$TZADI_SOFIT = 'ץ';
	$TZADI = 'צ';
	$KUF = 'ק';
	$RESH = 'ר';
	$SHIN = 'ש';
	$THAV = $TAV = 'ת';
	/*
	DAGESH_LETTER: return 'דגש\שורוק'
	Niqqud.KAMATZ: return 'קמץ'
	Niqqud.PATAKH: return 'פתח'
	Niqqud.TZEIRE: return 'צירה'
	Niqqud.SEGOL: return 'סגול'
	Niqqud.SHVA: return 'שוא'
	Niqqud.HOLAM: return 'חולם'
	Niqqud.KUBUTZ: return 'קובוץ'
	Niqqud.HIRIK: return 'חיריק'
	Niqqud.REDUCED_KAMATZ: return 'חטף-קמץ'
	Niqqud.REDUCED_PATAKH: return 'חטף-פתח'
	Niqqud.REDUCED_SEGOL: return 'חטף-סגול'
	SHIN_SMALIT: return 'שין-שמאלית'
	SHIN_YEMANIT: return 'שין-ימנית'
	*/
	
	$SHEVA_NACH = $SHEVA = 'ְ'; //SHVA = '\u05B0'
	$CHATAF_SEGOL = 'ֱ'; //REDUCED_SEGOL = '\u05B1'
	$CHATAF_PATACH = 'ֲ'; //REDUCED_PATAKH = '\u05B2'
	$CHATAF_KAMETZ = 'ֳ'; //REDUCED_KAMATZ = '\u05B3'
	$CHIRIK_MALEI = $CHIRIK_CHASER = $CHIRIK_U = 'ִ'; //HIRIK = '\u05B4'
	$TZEIREI_MALEI = $TZEIREI_CHASER = $TZEIREI = 'ֵ'; //TZEIRE = '\u05B5'
	$SEGOL = 'ֶ'; //SEGOL = '\u05B6'  
	$PATACH_GANUV = '׆'; //\u05C6: Hebräisches Satzzeichen Nun Hafucha || 05C6: Hebräisches Interpunktions-Nonne Hafukha
	$PATACH = 'ַ'; //PATAKH = '\u05B7'; 
	$KAMETZ_KATAN = 'ׇ'; //\u05C7: Hebräisches Zeichen Kametz Katan || 05C7: Hebräischer Punkt Qamats Qatan
	$KAMETZ = 'ָ'; //KAMATZ = '\u05B8'; 
	$CHOLAM_CHASER = 'ֺ';//For Wav
	$HOLAM_HASHER = 'ֹֹ'; //HOLAM HASHER for Wav 
	$CHOLAM_MALEI = $CHOLAM = 'ֹֹ';//HOLAM = '\u05B9'
	$HOLAM_MEM = 'מֹ'; //  מֹ מֹ
	$HOLAM_VAV = 'וֺ'; //
	$METEG = 'ֽ'; //METEG = '\u05BD'
	$MAPIQ = 'ּ'; //u05BC
	$MAQAF = '־'; //u05BE
	$RAFE = 'ֿ'; //u05BF
	$KUBUTZ = 'ֻ'; //KUBUTZ = '\u05BB' 
	$SHURUK = $DAGESH = 'ּ'; //SHURUK = '\u05BC' //or: DAGESH_LETTER = '\u05bc'
	$SHIN_DOT = 'ׁ';  //SHIN_YEMANIT = '\u05c1' &#x05C1 in BabelMap
	$SIN_DOT = 'ׂ'; //SHIN_SMALIT = '\u05c2' &#x05C2 in BabelMap	
	$TIPEHA = '֖'; //U+0596 HEBREW ACCENT TIPEHA : tarha, me'ayla ~ mayla
	$MERKHA = '֥'; //U+05A5 HEBREW ACCENT MERKHA : yored
	$MERKHA_KEFULA = '֦'; //U+05A6 HEBREW ACCENT MERKHA KEFULA	
	$MUNAH = '֣'; //U+05A3 HEBREW ACCENT MUNAH		
	$ETNAHTA = '֑'; //U+0591 HEBREW ACCENT ETNAHTA : atnah
	$ATNAH_HAFUKH = '֢'; //U+05A2 HEBREW ACCENT ATNAH HAFUKH
	$YERAH_BEN_YOMO = '֪'; //U+05AA HEBREW ACCENT YERAH BEN YOMO : galgal
	
	// do not double letters in general
	$GEMINATE_CANDIDATES = "($ALEPH|$BET|$BHET|$GIMEL|$DALED|$VAV|$HOLAM_VAV|$ZED|$TET|$YUD|$KAF|$KHAF_SOFIT|$LAMED|$MEM|$HOLAM_MEM|$NUN|$SAMECH|$PEI|$TZADI|$KUF|$SHIN$SHIN_DOT|$SHIN$SIN_DOT|$TAV)";

	$t = preg_replace("<" . $GEMINATE_CANDIDATES . "_CHAZAK>", "\\1", $t);
	
	//Consonants
	$t = preg_replace("<".$ALEPH.">", "e", $t);
	$t = preg_replace("<".$BET.">", "b", $t);
	$t = preg_replace("<".$BHET.">", "v", $t);
	$t = preg_replace("<".$GIMEL.">", "g", $t);
	$t = preg_replace("<".$GHIMEL.">", "g", $t);
	$t = preg_replace("<".$DALED.">", "d", $t);
	$t = preg_replace("<".$DHALED.">", "d", $t);
	$t = preg_replace("<".$HEH_MAPIK.">", "h", $t);
	$t = preg_replace("<".$HEH."BOUNDARY>", "h", $t);
	$t = preg_replace("<".$HEH.">", "h", $t);
	$t = preg_replace("<".$VAV.">", "u", $t);
	$t = preg_replace("<".$HOLAM_VAV.">", "uō", $t);
	$t = preg_replace("<".$ZED.">", "z", $t);
	$t = preg_replace("<".$CHET.">", "ĥ", $t);
	$t = preg_replace("<".$TET.">", "th", $t);
	$t = preg_replace("<".$YUD_PLURAL.">", "i", $t);
	$t = preg_replace("< ".$YUD.">", " i", $t);
	$t = preg_replace("<".$YUD.">", "y", $t);
	$t = preg_replace("<".$KAF.">", "c", $t);
	$t = preg_replace("<".$KHAF_SOFIT.">", "k", $t);
	$t = preg_replace("<".$LAMED.">", "l", $t);
	$t = preg_replace("<".$MEM.">", "m", $t);
	$t = preg_replace("<".$MEM_SOFIT.">", "ɱ", $t);
	$t = preg_replace("<".$HOLAM_MEM.">", "mō", $t);
	$t = preg_replace("<".$NUN.">", "n", $t);
	$t = preg_replace("<".$NUN_SOFIT.">", "ɳ", $t);
	$t = preg_replace("<".$SAMECH.">", "s", $t);
	$t = preg_replace("<".$AYIN.">", "a", $t);
	$t = preg_replace("<".$PEI.">", "p", $t);
	$t = preg_replace("<".$PHEI_SOFIT.">", "f", $t);
	$t = preg_replace("<".$TZADI.">", "ţ", $t);
	$t = preg_replace("<".$TZADI_SOFIT.">", "ţ", $t);
	$t = preg_replace("<".$KUF.">", "q", $t);
	$t = preg_replace("<".$RESH.">", "r", $t);
	$t = preg_replace("<".$SHIN.$SHIN_DOT.">", "ş", $t);
	$t = preg_replace("<".$SHIN.$SIN_DOT.">", "s", $t);
	$t = preg_replace("<".$TAV.">", "t", $t);
	$t = preg_replace("<".$THAV.">", "t", $t);
	$t = preg_replace("<".$CHATAF_KAMETZ.">", "ā", $t);
	$t = preg_replace("<".$KAMETZ_KATAN.">", "ā", $t);
	$t = preg_replace("<".$KAMETZ.">", "ā", $t);
	$t = preg_replace("<".$CHATAF_PATACH.">", "ā", $t);
	$t = preg_replace("<".$PATACH_GANUV.">", "ē", $t);
	$t = preg_replace("<".$PATACH.">", "ē", $t);
	$t = preg_replace("<".$SHEVA_NACH.">", "â", $t);
	$t = preg_replace("<".$SHEVA.">", "î", $t);
	$t = preg_replace("<".$CHATAF_SEGOL.">", "ă", $t);
	$t = preg_replace("<".$SEGOL.">", "ę", $t);
	$t = preg_replace("<".$TZEIREI_MALEI.">", "é", $t);
	$t = preg_replace("<".$TZEIREI_CHASER.">", "é", $t);
	$t = preg_replace("<".$CHIRIK_MALEI.">", "ī", $t);
	$t = preg_replace("<".$CHIRIK_CHASER.">", "ī", $t);
	$t = preg_replace("<".$CHOLAM_MALEI.">", "ō", $t);
	$t = preg_replace("<".$CHOLAM_CHASER.">", "ō", $t);
	$t = preg_replace("<".$METEG.">", "a", $t);
	$t = preg_replace("<".$SHURUK.">", "â", $t);
	$t = preg_replace("<".$KUBUTZ.">", "ū", $t);
	$t = preg_replace("<".$TIPEHA.">", "'", $t); 
	$t = preg_replace("<".$MERKHA.">", "'", $t); 
	$t = preg_replace("<".$MERKHA_KEFULA.">", "''", $t);	
	$t = preg_replace("<".$MUNAH.">", "'", $t);		
	$t = preg_replace("<".$ETNAHTA.">", "´", $t); 
	$t = preg_replace("<".$ATNAH_HAFUKH.">", "^", $t); 
	$t = preg_replace("<".$YERAH_BEN_YOMO.">", "°", $t); 
	
	//Vowels	
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
	//	echo "len: " . $len . "--";
	for ($i = 0; $i < $len; $i++)
	{
		$letters = explode_split("SPACE", $words[$i]);
		$len2 = count($letters);
		//		echo  "lettercount: " . $len2;
		$firstTrup = null;
		for ($j = 0; $j < $len2; $j++)
		{
			//			echo " ". $letters[$j];

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

	//	echo $t;
	//	echo $s;

	// AND here is the next step: change the intermediate code into
	// transliteration
	//	echo "<p>";

	$target = $targetlang;
	if ($target=="academic")
	{
		if (!empty($_SERVER["HTTP_USER_AGENT"]))
		{
			$t1 = AcademicTransliteration($t);
			echo $t1;

		}
		else // IE
		{
			echo AcademicFontFriendlyTransliteration($t);
		}
	}
	else if ($target == "academic_u")
	{
		$t1 = AcademicTransliteration($t);
		echo $t1;
	}
	else if ($target == "academic_ff")
	{
		echo AcademicFontFriendlyTransliteration($t);
	}
	else if ($target == "ashkenazic")
	{
		$t2 = AshkenazicTransliteration($t);
		echo $t2;
		global $t_without_trup;
		$t_without_trup = explode_split("SPACE", $t2);
		generateAndPrintTrup();
	}
	else if ($target == "sefardic")
	{
		$t2 = SefardicTransliteration($t);
		echo $t2;
	}
	else if ($target == "romanian")
	{
		$t2 = RomanianTransliteration($t);
		echo $t2;
	}
	else if ($target == "mc")
	{
		$t2 = MichiganClaremontTranslit($t);
		echo $t2;
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
