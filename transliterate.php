<?php

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
			echo "hello";
	}

	function print_trup_tree()
	{
		global $t_without_trup;

		for ($i = $this->begin_offset; $i <= $this->end_offset; $i++)
		{
			if ($i != $this->begin_offset) // skip the first time
				echo '.';
			echo $t_without_trup[$i];
		}


		if (! is_null($this->left) )
		{
			echo "[";
			$this->left->print_trup_tree();
			echo "]";
		}
		if (! is_null($this->right) )
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


// end new trup code



$isOpera = 0;
$isFirefox = 0;
$origHebrew ="";
function PostHebrewExtendedASCIIToIntermediate($t)
{
	$t = ereg_replace(" ", "BOUNDARY SPACE BOUNDARY ", $t);
	$t = ereg_replace(",", "BOUNDARY COMMA BOUNDARY ", $t);
	$t = ereg_replace("-", "BOUNDARY DASH BOUNDARY ", $t);
	$t = ereg_replace("\.", "BOUNDARY PERIOD BOUNDARY ", $t);

	$ALEPH = chr(224);
	$BET_U = chr(225);
	$GIMEL_U = chr(226);
	$DALED_U = chr(227);
	$HEH_U = chr(228);
	$VAV_U = chr(229);
	$ZED = chr(230);
	$CHET = chr(231);
	$TET = chr(232);
	$YUD_U = chr(233);
	$KAF_S_U = chr(234);
	$KAF_U = chr(235);
	$LAMED = chr(236);
	$MEM_S = chr(237);
	$MEM = chr(238);
	$NUN_S = chr(239);
	$NUN = chr(240);
	$SAMECH = chr(241);
	$AYIN = chr(242);
	$PEI_S_U = chr(243);
	$PEI_U = chr(244);
	$TZADI_S = chr(245);
	$TZADI = chr(246);
	$KUF = chr(247);
	$RESH = chr(248);
	$SHIN_U = chr(249);
	$TAV_U = chr(250);

	$SHEVA_U = chr(192);
	$CHATAF_SEGOL = chr(193);
	$CHATAF_PATACH = chr(194);
	$CHATAF_KAMETZ = chr(195);
	$CHIRIK_U = chr(196);
	$TZEIREI_U = chr(197);
	$SEGOL    = chr(198);
	$PATACH_U = chr(199);
	$KAMETZ   = chr(200);
	$CHOLAM_U = chr(201);
	$KUBUTZ = chr(203);
	$DAGESH_U = chr(204);
	$SHIN_DOT = chr(209);
	$SIN_DOT = chr(210);


	$t = ereg_replace("$ALEPH", "ALEPH ", $t);
	$t = ereg_replace("$BET_U", "BET_UNKNOWN ", $t);
	$t = ereg_replace("$GIMEL_U", "GIMEL_UNKNOWN ", $t);
	$t = ereg_replace("$DALED_U", "DALED_UNKNOWN ", $t);
	$t = ereg_replace("$HEH_U", "HEH_UNKNOWN ", $t);
	$t = ereg_replace("$VAV_U", "VAV_UNKNOWN ", $t);
	$t = ereg_replace("$ZED", "ZED ", $t);
	$t = ereg_replace("$CHET", "CHET ", $t);
	$t = ereg_replace("$TET", "TET ", $t);
	$t = ereg_replace("$YUD_U", "YUD_UNKNOWN ", $t);
	$t = ereg_replace("$KAF_U", "KAF_UNKNOWN ", $t);
	$t = ereg_replace("$KAF_S_U", "KAF_SOFIT_UNKNOWN ", $t);
	$t = ereg_replace("$LAMED", "LAMED ", $t);
	$t = ereg_replace("$MEM", "MEM ", $t);
	$t = ereg_replace("$MEM_S", "MEM_SOFIT ", $t);
	$t = ereg_replace("$NUN", "NUN ", $t);
	$t = ereg_replace("$NUN_S", "NUN_SOFIT ", $t);
	$t = ereg_replace("$SAMECH", "SAMECH ", $t);
	$t = ereg_replace("$AYIN", "AYIN ", $t);
	$t = ereg_replace("$PEI_U", "PEI_UNKNOWN ", $t);
	$t = ereg_replace("$PEI_S_U", "PEI_SOFIT_UNKNOWN ", $t);
	$t = ereg_replace("$TZADI", "TZADI ", $t);
	$t = ereg_replace("$TZADI_S", "TZADI_SOFIT ", $t);
	$t = ereg_replace("$KUF", "KUF ", $t);
	$t = ereg_replace("$RESH", "RESH ", $t);
	$t = ereg_replace("$SHIN_U", "SHIN_UNKNOWN ", $t);
	$t = ereg_replace("$TAV_U", "TAV_UNKNOWN ", $t);


	$t = ereg_replace("$SHEVA_U", "SHEVA_UNKNOWN ", $t);
	$t = ereg_replace("$CHATAF_SEGOL", "CHATAF_SEGOL ", $t);
	$t = ereg_replace("$CHATAF_PATACH", "CHATAF_PATACH ", $t);
	$t = ereg_replace("$CHATAF_KAMETZ", "CHATAF_KAMETZ ", $t);
	$t = ereg_replace("$CHIRIK_U", "CHIRIK_UNKNOWN ", $t);
	$t = ereg_replace("$TZEIREI_U", "TZEIREI_UNKNOWN ", $t);
	$t = ereg_replace("$SEGOL", "SEGOL ", $t);
	$t = ereg_replace("$PATACH_U", "PATACH_UNKNOWN ", $t);
	$t = ereg_replace("$KAMETZ", "KAMETZ ", $t);
	$t = ereg_replace("$CHOLAM_U", "CHOLAM_UNKNOWN ", $t);
	$t = ereg_replace("$KUBUTZ", "KUBUTZ ", $t);
	$t = ereg_replace("$DAGESH_U", "DAGESH_UNKNOWN ", $t);
	$t = ereg_replace("$SHIN_DOT", "SHIN_DOT ", $t);
	$t = ereg_replace("$SIN_DOT", "SIN_DOT ", $t);

	return $t;
}

function PostHebrewExtendedASCIIToEncodedUnicode($t)
{
	$ALEPH = chr(224);
	$BET_U = chr(225);
	$GIMEL_U = chr(226);
	$DALED_U = chr(227);
	$HEH_U = chr(228);
	$VAV_U = chr(229);
	$ZED = chr(230);
	$CHET = chr(231);
	$TET = chr(232);
	$YUD_U = chr(233);
	$KAF_S_U = chr(234);
	$KAF_U = chr(235);
	$LAMED = chr(236);
	$MEM_S = chr(237);
	$MEM = chr(238);
	$NUN_S = chr(239);
	$NUN = chr(240);
	$SAMECH = chr(241);
	$AYIN = chr(242);
	$PEI_S_U = chr(243);
	$PEI_U = chr(244);
	$TZADI_S = chr(245);
	$TZADI = chr(246);
	$KUF = chr(247);
	$RESH = chr(248);
	$SHIN_U = chr(249);
	$TAV_U = chr(250);

	$SHEVA_U = chr(192);
	$CHATAF_SEGOL = chr(193);
	$CHATAF_PATACH = chr(194);
	$CHATAF_KAMETZ = chr(195);
	$CHIRIK_U = chr(196);
	$TZEIREI_U = chr(197);
	$SEGOL    = chr(198);
	$PATACH_U = chr(199);
	$KAMETZ   = chr(200);
	$CHOLAM_U = chr(201);
	$KUBUTZ = chr(203);
	$DAGESH_U = chr(204);
	$SHIN_DOT = chr(209);
	$SIN_DOT = chr(210);

	$t = ereg_replace("$ALEPH", "&#1488;", $t);
	$t = ereg_replace("$BET_U", "&#1489;", $t);
	$t = ereg_replace("$GIMEL_U", "&#1490;", $t);
	$t = ereg_replace("$DALED_U", "&#1491;", $t);
	$t = ereg_replace("$HEH_U", "&#1492;", $t);
	$t = ereg_replace("$VAV_U", "&#1493;", $t);
	$t = ereg_replace("$ZED", "&#1494;", $t);
	$t = ereg_replace("$CHET", "&#1495;", $t);
	$t = ereg_replace("$TET", "&#1496;", $t);
	$t = ereg_replace("$YUD_U", "&#1497;", $t);
	$t = ereg_replace("$KAF_U", "&#1499;", $t);
	$t = ereg_replace("$KAF_S_U", "&#1498;", $t);
	$t = ereg_replace("$LAMED", "&#1500;", $t);
	$t = ereg_replace("$MEM", "&#1502;", $t);
	$t = ereg_replace("$MEM_S", "&#1501;", $t);
	$t = ereg_replace("$NUN", "&#1504;", $t);
	$t = ereg_replace("$NUN_S", "&#1503;", $t);
	$t = ereg_replace("$SAMECH", "&#1505;", $t);
	$t = ereg_replace("$AYIN", "&#1506;", $t);
	$t = ereg_replace("$PEI_U", "&#1508;", $t);
	$t = ereg_replace("$PEI_S_U", "&#1507;", $t);
	$t = ereg_replace("$TZADI", "&#1510;", $t);
	$t = ereg_replace("$TZADI_S", "&#1509;", $t);
	$t = ereg_replace("$KUF", "&#1511;", $t);
	$t = ereg_replace("$RESH", "&#1512;", $t);
	$t = ereg_replace("$SHIN_U", "&#1513;", $t);
	$t = ereg_replace("$TAV_U", "&#1514;", $t);

	$t = ereg_replace("$SHEVA_U", "&#1456;", $t);
	$t = ereg_replace("$CHATAF_SEGOL", "&#1457;", $t);
	$t = ereg_replace("$CHATAF_PATACH", "&#1458;", $t);
	$t = ereg_replace("$CHATAF_KAMETZ", "&#1459;", $t);
	$t = ereg_replace("$CHIRIK_U", "&#1460;", $t);
	$t = ereg_replace("$TZEIREI_U", "&#1461;", $t);
	$t = ereg_replace("$SEGOL", "&#1462;", $t);
	$t = ereg_replace("$PATACH_U", "&#1463;", $t);
	$t = ereg_replace("$KAMETZ", "&#1464;", $t);
	$t = ereg_replace("$CHOLAM_U", "&#1465;", $t);
	$t = ereg_replace("$KUBUTZ", "&#1467;", $t);
	$t = ereg_replace("$DAGESH_U", "&#1468;", $t);
	$t = ereg_replace("$SHIN_DOT", "&#1473;", $t);
	$t = ereg_replace("$SIN_DOT", "&#1474;", $t);

	return $t;
}


function PostHebrewToIntermediate($t)
{
	$t = ereg_replace(" ", "BOUNDARY SPACE BOUNDARY ", $t);
	$t = ereg_replace(",", "BOUNDARY COMMA BOUNDARY ", $t);
	$t = ereg_replace("-", "BOUNDARY DASH BOUNDARY ", $t);
	$t = ereg_replace("\.", "BOUNDARY PERIOD BOUNDARY ", $t);

	$t = ereg_replace("&#1488;", "ALEPH ", $t);
	$t = ereg_replace("&#1489;", "BET_UNKNOWN ", $t);
	$t = ereg_replace("&#1490;", "GIMEL_UNKNOWN ", $t);
	$t = ereg_replace("&#1491;", "DALED_UNKNOWN ", $t);
	$t = ereg_replace("&#1492;", "HEH_UNKNOWN ", $t);
	$t = ereg_replace("&#1493;", "VAV_UNKNOWN ", $t);
	$t = ereg_replace("&#1494;", "ZED ", $t);
	$t = ereg_replace("&#1495;", "CHET ", $t);
	$t = ereg_replace("&#1496;", "TET ", $t);
	$t = ereg_replace("&#1497;", "YUD_UNKNOWN ", $t);
	$t = ereg_replace("&#1498;", "KAF_SOFIT_UNKNOWN ", $t);
	$t = ereg_replace("&#1499;", "KAF_UNKNOWN ", $t);
	$t = ereg_replace("&#1500;", "LAMED ", $t);
	$t = ereg_replace("&#1501;", "MEM_SOFIT ", $t);
	$t = ereg_replace("&#1502;", "MEM ", $t);
	$t = ereg_replace("&#1503;", "NUN_SOFIT ", $t);
	$t = ereg_replace("&#1504;", "NUN ", $t);
	$t = ereg_replace("&#1505;", "SAMECH ", $t);
	$t = ereg_replace("&#1506;", "AYIN ", $t);
	$t = ereg_replace("&#1507;", "PHEI_SOFIT ", $t);
	$t = ereg_replace("&#1508;", "PEI_UNKNOWN ", $t);
	$t = ereg_replace("&#1509;", "TZADI_SOFIT ", $t);
	$t = ereg_replace("&#1510;", "TZADI ", $t);
	$t = ereg_replace("&#1511;", "KUF ", $t);
	$t = ereg_replace("&#1512;", "RESH ", $t);
	$t = ereg_replace("&#1513;", "SHIN_UNKNOWN ", $t);
	$t = ereg_replace("&#1514;", "TAV_UNKNOWN ", $t);

// now for the nikud
	$t = ereg_replace("&#1456;", "SHEVA_UNKNOWN ", $t);
	$t = ereg_replace("&#1457;", "CHATAF_SEGOL ", $t);
	$t = ereg_replace("&#1458;", "CHATAF_PATACH ", $t);
	$t = ereg_replace("&#1459;", "CHATAF_KAMETZ ", $t);
	$t = ereg_replace("&#1460;", "CHIRIK_UNKNOWN ", $t);
	$t = ereg_replace("&#1461;", "TZEIREI_UNKNOWN ", $t);
	$t = ereg_replace("&#1462;", "SEGOL ", $t);
	$t = ereg_replace("&#1464;", "KAMETZ ", $t);
	$t = ereg_replace("&#1463;", "PATACH_UNKNOWN ", $t);
	$t = ereg_replace("&#1465;", "CHOLAM_UNKNOWN ", $t);
	$t = ereg_replace("&#1467;", "KUBUTZ ", $t);

	$t = ereg_replace("&#1473;", "SHIN_DOT ", $t);
	$t = ereg_replace("&#1474;", "SIN_DOT ", $t);

	$t = ereg_replace("&#1468;", "DAGESH_UNKNOWN ", $t);

// trup code now
	$t = ereg_replace("&#1431;", "REVII ", $t);
	$t = ereg_replace("&#1444;", "MAHPACH ", $t);
	$t = ereg_replace("&#1433;", "KADMA ", $t);
	$t = ereg_replace("&#1443;", "MUNACH ", $t);
	$t = ereg_replace("&#1428;", "ZAKEF_KATON ", $t);
	$t = ereg_replace("&#1429;", "ZAKEF_GADOL ", $t);
	$t = ereg_replace("&#1445;", "MERCHA ", $t);
	$t = ereg_replace("&#1430;", "TIPCHA ", $t);
	$t = ereg_replace("&#1425;", "ETNACHTA ", $t);
	$t = ereg_replace("&#1469;", "METEG ", $t);
	$t = ereg_replace("&#1475;", "SOF_PASUK ", $t);
	$t = ereg_replace("&#1435;", "TEVIR ", $t);
	$t = ereg_replace("&#1447;", "DARGA ", $t);
	$t = ereg_replace("&#1436;", "GERESH ", $t);
	$t = ereg_replace("&#1438;", "GERSHAYIM ", $t);
	$t = ereg_replace("&#1454;", "ZARKA ", $t);
	$t = ereg_replace("&#1426;", "SEGOLTA ", $t);
	$t = ereg_replace("&#1440;", "TELISHA_KETANA ", $t);
	$t = ereg_replace("&#1449;", "TELISHA_GEDOLA ", $t);

// since until now expressions were escaped as in &#number; we only handle now
	$t = ereg_replace(";", "BOUNDARY SEMICOLON BOUNDARY ", $t);

	return $t;
}

function handleSpecials($t)
{
	// certain penultimately stressed words s/t mess up the 
	// transliteration, which assumes ultimate stress. detecting
	// stress is a non-trivial matter, and so we handle this here
	// by listing the common words and fixing the mapping a bit
	// 1. mitzrAyma	
	$t = ereg_replace("(MEM CHIRIK_MALEI TZADI SHEVA_NACH RESH KAMETZ YUD) (SHEVA_NA) (MEM KAMETZ HEH)",
			"\\1 SHEVA_NACH \\3", $t);

}

function ApplyRulesToIntermediateForm($t)
{
// now that we have it in intermediate form
// we want to perform some transformations

// first, arrive at correct shin/sin
// and alas! dagesh can intervene between shin and shin dot, and same for sin
	$t = ereg_replace("DAGESH_UNKNOWN (SHIN_DOT|SIN_DOT)", "\\1 DAGESH_UNKNOWN", $t);

	$t = ereg_replace("SHIN_UNKNOWN SHIN_DOT ", "SHIN ", $t);
	$t = ereg_replace("SHIN_UNKNOWN SIN_DOT ", "SIN ", $t);

// then, handle heh/mapik heh
	$t = ereg_replace("HEH_UNKNOWN DAGESH_UNKNOWN", "HEH_MAPIK", $t);
	$t = ereg_replace("HEH_UNKNOWN", "HEH", $t);

// vav cholam = cholam malei, every other cholam = chaser
	$t = ereg_replace("VAV_UNKNOWN CHOLAM_UNKNOWN", "CHOLAM_MALEI", $t);
	$t = ereg_replace("CHOLAM_UNKNOWN", "CHOLAM_CHASER", $t);

// handle examples like tetzavveh: 
// vav_unknown dagesh_unknown vowel = vav_chazak vowel
 	$NIKUD = "(PATACH|PATACH_GANUV|CHATAF_PATACH|KAMETZ|CHATAF_KAMETZ|SHEVA_NA|SHEVA_NACH|SHEVA_UNKNOWN|SEGOL|CHATAF_SEGOL|TZEIREI_UNKNOWN|TZEIREI_MALEI|TEZEIREI_CHASER|CHIRIK_MALEI|CHIRIK_CHASER|CHOLAM_CHASER|CHOLAM_MALEI)";
	$t = ereg_replace("VAV_UNKNOWN DAGESH_UNKNOWN $NIKUD", "VAV_CHAZAK \\1", $t);

// else - vav_unknown dagesh_unknown = SHURUK
	$t = ereg_replace("VAV_UNKNOWN DAGESH_UNKNOWN", "SHURUK", $t);

// remaining vav will be actual vav
	$t = ereg_replace("VAV_UNKNOWN", "VAV", $t);

// shva at the end of a word will always be shva nach
	$t = ereg_replace("SHEVA_UNKNOWN BOUNDARY", 
				"SHEVA_NACH BOUNDARY", $t);

// BEGEDKEFET
// then, handle begedkefet at the beginning of a word = plosive
	$t = ereg_replace("BOUNDARY ((BET|GIMEL|DALED|KAF|PEI|TAV)_UNKNOWN) DAGESH_UNKNOWN", "BOUNDARY \\2", $t);

// begedkefet followed by anything but dagesh is the fricative
	$BGDKFT_UNKNOWN = "(BET_UNKNOWN|GIMEL_UNKNOWN|DALED_UNKNOWN|KAF_UNKNOWN|PEI_UNKNOWN|TAV_UNKNOWN)";

	$t = ereg_replace("BET_UNKNOWN BOUNDARY", "BHET BOUNDARY", $t);
	$t = ereg_replace("BET_UNKNOWN " . $NIKUD, "BHET \\1", $t);

	$t = ereg_replace("GIMEL_UNKNOWN BOUNDARY", "GIMEL_UNKNOWN BOUNDARY", $t);
	$t = ereg_replace("GIMEL_UNKNOWN " . $NIKUD, "GHIMEL \\1", $t);

	$t = ereg_replace("DALED_UNKNOWN BOUNDARY", "DHALED BOUNDARY", $t);
	$t = ereg_replace("DALED_UNKNOWN " . $NIKUD, "DHALED \\1", $t);

	$t = ereg_replace("KAF_SOFIT_UNKNOWN SHEVA_NACH BOUNDARY", "KHAF_SOFIT SHEVA_NACH BOUNDARY", $t);
	$t = ereg_replace("KAF_UNKNOWN " . $NIKUD, "KHAF \\1", $t);

	$t = ereg_replace("PEI_UNKNOWN BOUNDARY", "PHEI BOUNDARY", $t);
	$t = ereg_replace("PEI_UNKNOWN " . $NIKUD, "PHEI \\1", $t);

	$t = ereg_replace("TAV_UNKNOWN BOUNDARY", "THAV BOUNDARY", $t);
	$t = ereg_replace("TAV_UNKNOWN " . $NIKUD, "THAV \\1", $t);

// then, handle patach ganuv vs. regular patach

	$t = ereg_replace("(AYIN|CHET|HEH_MAPIK) PATACH_UNKNOWN BOUNDARY", 
				"PATACH_GANUV \\1 BOUNDARY", $t);
	$t = ereg_replace("PATACH_UNKNOWN", "PATACH", $t);


// SHEVA:
// shva after a gutteral will always be shva nach
	$t = ereg_replace("(ALEPH|HEH|CHET|AYIN) SHEVA_UNKNOWN", 
				"\\1 SHEVA_NACH", $t);


// shva at beginning of word should be shva na
	// some of these, such as PHEI_UNKNOWN, are not possible, but it 
	// is simpler to write
	$NON_GUTTERALS = "(B(H?)ET|G(H?)IMEL|D(H?)ALED|VAV(_UNKNOWN)?|ZED|TET|YUD(_UNKNOWN)?|K(H?)AF(_UNKNOWN)?|LAMED|MEM|NUN|SAMECH|P(H?)EI(_UNKNOWN)?|TZADI|KUF|RESH|S(H?)IN(_UNKNOWN)?|T(H?)AV(_UNKNOWN)?)";
	$t = ereg_replace("BOUNDARY " . $NON_GUTTERALS . " SHEVA_UNKNOWN", "BOUNDARY \\1 SHEVA_NA", $t);


// for geminates, we should first have satisfied begedkefet rules

	$GEMINATE_CANDIDATES = "(BET|GIMEL|DALED|VAV|ZED|TET|YUD|KAF|KAF_SOFIT|LAMED|MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = ereg_replace($GEMINATE_CANDIDATES . " DAGESH_UNKNOWN", "\\1_CHAZAK", $t);


// TZEIREI MALEI/CHASER
	$t = ereg_replace("TZEIREI_UNKNOWN YUD_UNKNOWN", "TZEIREI_MALEI", $t);
	$t = ereg_replace("TZEIREI_UNKNOWN", "TZEIREI_CHASER", $t);

// CHIRIK_MALEI/CHASER
	$t = ereg_replace("CHIRIK_UNKNOWN YUD_UNKNOWN", "CHIRIK_MALEI", $t);
	$t = ereg_replace("CHIRIK_UNKNOWN", "CHIRIK_CHASER", $t);

// yud followed by nikud, except for patach ganuv, is a full yud
// we must handle this rule AFTER patach ganuv to handle cases like mashiach
 	$NIKUD_WO_GANUV = "(PATACH|CHATAF_PATACH|KAMETZ|CHATAF_KAMETZ|SHEVA_NA|SHEVA_NACH|SHEVA_UNKNOWN|SEGOL|CHATAF_SEGOL|TZEIREI_MALEI|TEZEIREI_CHASER|CHIRIK_MALEI|CHIRIK_CHASER|CHOLAM_CHASER|CHOLAM_MALEI)";
	$t = ereg_replace("YUD_UNKNOWN " . $NIKUD_WO_GANUV, "YUD \\1", $t);

// the same is true for yud followed by dagesh
	$t = ereg_replace("YUD_UNKNOWN DAGESH_UNKNOWN", "YUD_CHAZAK", $t);


// more shva_na/nach
// *controversial: Short Vowel + non-plosive non geminate + shva should
// be nach. problem is that some hold by shva merachef and 
// especially in the instance in which the dagesh disappears in yud and
// mem. however, we will assume that they are simple nachs.
	$NON_FINAL_NON_PLOSIVES = "(ALEPH|BHET|GHIMEL|DHALED|HEH|VAV|ZED|CHET|TET|YUD|KHAF|LAMED|MEM|NUN|SAMECH|AYIN|PHEI|TZADI|KUF|RESH|SHIN|SIN|THAV)";
	$SHORT_VOWELS = "(PATACH|SEGOL|CHIRIK_CHASER|KUBUTZ)";
	$t = ereg_replace("$SHORT_VOWELS $NON_FINAL_NON_PLOSIVES SHEVA_UNKNOWN", "\\1 \\2 SHEVA_NACH", $t);


// before apply shva na/nach for long vowels, handle shva nach in 
// consonant clusters
// sheva_? + letter + chataf = nach
	$LETTER_AFTER_NACH = "(ALEPH|BET|HEH|VAV|ZED|CHET|TET|YUD|LAMED|MEM|NUN|SAMECH|AYIN|TZADI|KUF|RESH|SHIN|SIN)";
	$BEGEDKEFET_UNKNOWN = "((BET|GIMEL|DALED|KAF|PEI|TAV)_UNKNOWN)";
	$NAS = "(SHEVA_NA|CHATAF)";
	$VOWELS = "(PATACH|SEGOL|CHIRIK|KUBUTZ|CHOLAM|KAMETZ|TZEIREI)";

	$t = ereg_replace("SHEVA_UNKNOWN $LETTER_AFTER_NACH $NAS", "SHEVA_NACH \\1 \\2", $t);		
	$t = ereg_replace("SHEVA_UNKNOWN $BEGEDKEFET_UNKNOWN DAGESH_UNKNOWN $NAS", "SHEVA_NACH \\2 \\3", $t);		
	$t = ereg_replace("SHEVA_UNKNOWN $BEGEDKEFET_UNKNOWN DAGESH_UNKNOWN $VOWELS", "SHEVA_NACH \\2 \\3", $t);		



// similarly, Long vowel + non-plosive non geminate + shva
// should be na
	$LONG_VOWELS = "(KAMETZ|TZEIREI_MALEI|TZEIREI_CHASER|CHIRIK_MALEI|CHOLAM_CHASER|CHOLAM_MALEI|SHURUK)";
// with an exception of e.g. ubhnei rather than ubhenei, not maintaining 
// sheva merachef
	$t = ereg_replace("BOUNDARY SHURUK $NON_FINAL_NON_PLOSIVES SHEVA_UNKNOWN", "BOUNDARY SHURUK \\1 SHEVA_NACH", $t);
	$t = ereg_replace($LONG_VOWELS . " " . $NON_FINAL_NON_PLOSIVES . " SHEVA_UNKNOWN", "\\1 \\2 SHEVA_NA", $t);

// back to begedkefet: handle shva nach begedkefet dagesh as non-geminate
	$t = ereg_replace("SHEVA_NACH ((BET|GIMEL|DALED|KAF|PEI|TAV)_UNKNOWN) DAGESH_UNKNOWN", "SHEVA_NACH \\2", $t);

// and then handle short vowel + begedkefet + dagesh --> geminate begedkefet
	$t = ereg_replace("$SHORT_VOWELS $BEGEDKEFET_UNKNOWN DAGESH_UNKNOWN", "\\1 \\3_CHAZAK", $t);

// some begedkefets, such as those followed by what were unknown 
// vowels/matres lectiones, have not yet been handled. handle them now

	$t = ereg_replace("BET_UNKNOWN", "BHET", $t);
	$t = ereg_replace("GIMEL_UNKNOWN", "GHIMEL", $t);
	$t = ereg_replace("DALED_UNKNOWN", "DHALED", $t);
	$t = ereg_replace("KAF_UNKNOWN", "KHAF", $t);
	$t = ereg_replace("PEI_UNKNOWN", "PHEI", $t);
	$t = ereg_replace("TAV_UNKNOWN", "THAV", $t);


// chazak followed by sheva_unknown should make the shva into a na
	$t = ereg_replace("_CHAZAK SHEVA_UNKNOWN", "_CHAZAK SHEVA_NA", $t);



// handle certain yud_unknowns
// yud at the end of a word, unhandled before, is a mere yud
	$t = ereg_replace("YUD_UNKNOWN BOUNDARY", "YUD", $t);
// yud after a segol is unpronounced and is there to show plurality
	$t = ereg_replace("SEGOL YUD_UNKNOWN", "SEGOL YUD_PLURAL", $t);
// finally, otherwise unmarked yud_unknowns should be made known
	$t = ereg_replace("YUD_UNKNOWN", "YUD", $t);



// handle certain Divine names which are written differently than they
// are pronounced
	$t = ereg_replace("YUD SHEVA_NA HEH VAV KAMETZ HEH", 
		"ALEPH CHATAF_PATACH DHALED CHOLAM_CHASER NUN KAMETZ YUD", $t);

	$t = ereg_replace("YUD HEH VAV KAMETZ HEH",
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
	$t = ereg_replace("$NIKUD $EM_KERIYA $EM_KERIYA", "\\1 (\\2) \\3", $t);

	$NON_EM_KERIYA = "";
/*
	$t = ereg_replace("$NIKUD $EM_KERIYA $EM_KERIYA", "\\1 (\\2) \\3", $t);
*/	


// some work on kametz katon
// 1) a stop-word - kol, bakol, lakol, lekhol, etc. that is, consider
// 	morphology

	$t = ereg_replace("(BET|BHET|LAMED|KAF|KHAF) (SHEVA_NA KHAF) (KAMETZ) (LAMED BOUNDARY)", "\\1 \\2 \\3_KATAN \\4", $t);
	$t = ereg_replace("(BET|BHET|LAMED|KAF|KHAF) (PATACH KAF_CHAZAK) (KAMETZ) (LAMED BOUNDARY)", "\\1 \\2 \\3_KATAN \\4", $t);
	$t = ereg_replace("(MEM CHIRIK_CHASER KAF_CHAZAK) (KAMETZ) (LAMED BOUNDARY)", "\\1 \\2_KATAN \\3", $t);
	$t = ereg_replace("(KAF) (KAMETZ) (LAMED BOUNDARY)", "\\1 \\2_KATAN \\3", $t);

// 1.5) catches the above rule much better
//    kametz + consonant + boundary + dash --> kametz katan
	$LETTER_AFTER_KATAN = "(BHET|GHIMEL|DHALED|VAV|ZED|TET|KHAF_SOFIT|LAMED|MEM_SOFIT|NUN_SOFIT|SAMECH|PHEI_SOFIT|TZADI_SOFIT|KUF|RESH|SHIN|SIN|THAV)";

//	echo $t;
	$t = ereg_replace("(KAMETZ) $LETTER_AFTER_KATAN (BOUNDARY DASH)", "\\1_KATAN \\2 \\3", $t);


// 2) Another common word - chochma and related forms
// we are modifying from the incorectly computed transliteration

	$t = ereg_replace("(CHET) (KAMETZ) (KHAF) SHEVA_NA (MEM) (KAMETZ|PATACH)", "\\1 \\2_KATAN \\3 SHEVA_NACH \\4 \\5", $t);


// 3) kametz + cons + chataf_kametz, that first kametz was katon
	$t = ereg_replace("(KAMETZ) $NON_FINAL_NON_PLOSIVES (CHATAF_KAMETZ)", 
				"\\1_KATAN \\2 \\3", $t);


// 4) kametz katan generally results from reduction from cholam.
//	various forms betray this reduction happened
// 		one common form is kametz + cons + shva_nach + bgdkft_plosive
//		because kametz is a long vowel and so in unstressed
//		syllables it should be open.
//		the problem is where it occurs in stressed syllables
//		s.t. we will need to undo the damage we are about to cause

	$t = ereg_replace("(KAMETZ) $NON_FINAL_NON_PLOSIVES (SHEVA_NACH) (BET|GIMEL|DALED|KAF|PEI|TAV)", 
				"\\1_KATAN \\2 \\3 \\4", $t);


	return $t;	
}

function CleanUpPunctuation($t)
{
	$t = ereg_replace("BOUNDARY", "", $t);
	$t = ereg_replace("COMMA ", ",", $t);
	$t = ereg_replace("DASH ", "-", $t);
	$t = ereg_replace("SEMICOLON ", ";", $t);
	$t = ereg_replace("PERIOD ", ".", $t);
	$t = ereg_replace(" ", "", $t);
	$t = ereg_replace("SPACE", " ", $t);
	return $t;
}

function MichiganClaremontTransliteration($t)
{
	$t = ereg_replace("ALEPH", ")", $t);
	$t = ereg_replace("BET", "b.", $t);
	$t = ereg_replace("BHET", "b", $t);
	$t = ereg_replace("GIMEL", "g.", $t);
	$t = ereg_replace("GHIMEL", "g", $t);
	$t = ereg_replace("DALED", "d.", $t);
	$t = ereg_replace("DHALED", "d", $t);
	$t = ereg_replace("HEH_MAPIK", "h", $t);
	$t = ereg_replace("HEH", "h", $t);
	$t = ereg_replace("VAV", "w", $t);
	$t = ereg_replace("ZED", "z", $t);
	$t = ereg_replace("CHET", "x", $t);
	$t = ereg_replace("TET", "+", $t);
	$t = ereg_replace("YUD_PLURAL", "Y", $t);
	$t = ereg_replace("YUD", "Y", $t);
	$t = ereg_replace("KAF", "K.", $t);
	$t = ereg_replace("KHAF(_SOFIT)?", "K", $t);
	$t = ereg_replace("LAMED", "L", $t);
	$t = ereg_replace("MEM(_SOFIT)?", "M", $t);
	$t = ereg_replace("NUN(_SOFIT)?", "N", $t);
	$t = ereg_replace("SAMECH", "S", $t);
	$t = ereg_replace("AYIN", "(", $t);
	$t = ereg_replace("PEI", "P.", $t);
	$t = ereg_replace("PHEI(_SOFIT)?", "P", $t);
	$t = ereg_replace("TZADI(_SOFIT)?", "C", $t);
	$t = ereg_replace("KUF", "Q", $t);
	$t = ereg_replace("RESH", "R", $t);
	$t = ereg_replace("SHIN", "$", $t);
	$t = ereg_replace("SIN", "&amp;", $t);
	$t = ereg_replace("TAV", "T.", $t);
	$t = ereg_replace("THAV", "T.", $t);
	$t = ereg_replace("CHATAF_KAMETZ", ":F", $t);
	$t = ereg_replace("KAMETZ_KATAN", "F", $t);
	$t = ereg_replace("KAMETZ", "F", $t);
	$t = ereg_replace("CHATAF_PATACH", ":A", $t);
	$t = ereg_replace("PATACH_GANUV", "A", $t);
	$t = ereg_replace("PATACH", "A", $t);
	$t = ereg_replace("SHEVA_NACH", ":", $t);
	$t = ereg_replace("SHEVA_NA", ":", $t);
	$t = ereg_replace("CHATAF_SEGOL", ":E", $t);
	$t = ereg_replace("SEGOL", "E", $t);
	$t = ereg_replace("TZEIREI_MALEI", "\"", $t);
	$t = ereg_replace("TZEIREI_CHASER", "\"", $t);
	$t = ereg_replace("CHIRIK_MALEI", "I", $t);
	$t = ereg_replace("CHIRIK_CHASER", "I", $t);
	$t = ereg_replace("CHOLAM_MALEI", "O", $t);
	$t = ereg_replace("CHOLAM_CHASER", "O", $t);
	$t = ereg_replace("SHURUK", "W.", $t);
	$t = ereg_replace("KUBUTZ", "U", $t);

	// chazak becomes dagesh, unless already have this for bgdkft
	$t = ereg_replace("\._CHAZAK", ".", $t);
	$t = ereg_replace("_CHAZAK", ".", $t);

	$t = CleanUpPunctuation($t);

	return $t;
}


function AcademicTransliteration($t)
{
	$GEMINATE_CANDIDATES = "(BET|GIMEL|DALED|VAV|ZED|TET|YUD|KAF|KAF_SOFIT|LAMED|MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = ereg_replace($GEMINATE_CANDIDATES . "_CHAZAK", "\\1 \\1", $t);

	$t = ereg_replace("ALEPH", "&#702;", $t);
	$t = ereg_replace("BET", "b", $t);
	$t = ereg_replace("BHET", "&#7687;", $t);
	$t = ereg_replace("GIMEL", "g", $t);
	$t = ereg_replace("GHIMEL", "&#7713;", $t);
	$t = ereg_replace("DALED", "d", $t);
	$t = ereg_replace("DHALED", "&#7695;", $t);
	$t = ereg_replace("HEH_MAPIK", "h", $t);
	$t = ereg_replace("HEH", "h", $t);
	$t = ereg_replace("VAV", "w", $t);
	$t = ereg_replace("ZED", "z", $t);
	$t = ereg_replace("CHET", "&#7717;", $t);
	$t = ereg_replace("TET", "&#7789;", $t);
	$t = ereg_replace("YUD_PLURAL", "(y)", $t);
	$t = ereg_replace("YUD", "y", $t);
	$t = ereg_replace("KAF", "k", $t);
	$t = ereg_replace("KHAF(_SOFIT)?", "&#7733;", $t);
	$t = ereg_replace("LAMED", "l", $t);
	$t = ereg_replace("MEM(_SOFIT)?", "m", $t);
	$t = ereg_replace("NUN(_SOFIT)?", "n", $t);
	$t = ereg_replace("SAMECH", "s", $t);
	$t = ereg_replace("AYIN", "&#703;", $t);
	$t = ereg_replace("PEI", "p", $t);
	$t = ereg_replace("PHEI(_SOFIT)?", "p&#772;", $t);
	$t = ereg_replace("TZADI(_SOFIT)?", "&#7779;", $t);
	$t = ereg_replace("KUF", "q", $t);
	$t = ereg_replace("RESH", "r", $t);
	$t = ereg_replace("SHIN", "&#353;", $t);
	$t = ereg_replace("SIN", "&#347;", $t);
	$t = ereg_replace("TAV", "t", $t);
	$t = ereg_replace("THAV", "&#7791;", $t);
	$t = ereg_replace("CHATAF_KAMETZ", "&#335;", $t);
	$t = ereg_replace("KAMETZ_KATAN", "o", $t);
	$t = ereg_replace("KAMETZ", "&#257;", $t);
	$t = ereg_replace("CHATAF_PATACH", "&#259;", $t);
	$t = ereg_replace("PATACH_GANUV", "<sup>a</sup>", $t);
	$t = ereg_replace("PATACH", "a", $t);
	$t = ereg_replace("SHEVA_NACH", "", $t);
	$t = ereg_replace("SHEVA_NA", "&#601;", $t);
	$t = ereg_replace("CHATAF_SEGOL", "&#277;", $t);
	$t = ereg_replace("SEGOL", "e", $t);
	$t = ereg_replace("TZEIREI_MALEI", "&#234;", $t);
	$t = ereg_replace("TZEIREI_CHASER", "&#275;", $t);
	$t = ereg_replace("CHIRIK_MALEI", "&#238;", $t);
	$t = ereg_replace("CHIRIK_CHASER", "i", $t);
	$t = ereg_replace("CHOLAM_MALEI", "&#244;", $t);
	$t = ereg_replace("CHOLAM_CHASER", "&#333;", $t);
	$t = ereg_replace("SHURUK", "&#251;", $t);
	$t = ereg_replace("KUBUTZ", "u", $t);

	$t = CleanUpPunctuation($t);

	return $t;
}


function AcademicFontFriendlyTransliteration($t)
{
	$GEMINATE_CANDIDATES = "(BET|GIMEL|DALED|VAV|ZED|TET|YUD|KAF|KAF_SOFIT|LAMED|MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = ereg_replace($GEMINATE_CANDIDATES . "_CHAZAK", "\\1 \\1", $t);

	$t = ereg_replace("ALEPH", "`", $t);
	$t = ereg_replace("BET", "b", $t);
	$t = ereg_replace("BHET", "bh", $t);
	$t = ereg_replace("GIMEL", "g", $t);
	$t = ereg_replace("GHIMEL", "gh", $t);
	$t = ereg_replace("DALED", "d", $t);
	$t = ereg_replace("DHALED", "dh", $t);
	$t = ereg_replace("HEH_MAPIK", "h", $t);
	$t = ereg_replace("HEH", "h", $t);
	$t = ereg_replace("VAV", "w", $t);
	$t = ereg_replace("ZED", "z", $t);
	$t = ereg_replace("CHET", "&#295;", $t);
	$t = ereg_replace("TET", "&#335;", $t);
	$t = ereg_replace("YUD_PLURAL", "(y)", $t);
	$t = ereg_replace("YUD", "y", $t);
	$t = ereg_replace("KAF", "k", $t);
	$t = ereg_replace("KHAF(_SOFIT)?", "kh", $t);
	$t = ereg_replace("LAMED", "l", $t);
	$t = ereg_replace("MEM(_SOFIT)?", "m", $t);
	$t = ereg_replace("NUN(_SOFIT)?", "n", $t);
	$t = ereg_replace("SAMECH", "s", $t);
	$t = ereg_replace("AYIN", "'", $t);
	$t = ereg_replace("PEI", "p", $t);
	$t = ereg_replace("PHEI(_SOFIT)?", "ph", $t);
	$t = ereg_replace("TZADI(_SOFIT)?", "&#351;", $t);
	$t = ereg_replace("KUF", "q", $t);
	$t = ereg_replace("RESH", "r", $t);
	$t = ereg_replace("SHIN", "&#353;", $t);
	$t = ereg_replace("SIN", "&#347;", $t);
	$t = ereg_replace("TAV", "t", $t);
	$t = ereg_replace("THAV", "th", $t);
	$t = ereg_replace("CHATAF_KAMETZ", "&#335;", $t);
	$t = ereg_replace("KAMETZ_KATAN", "o", $t);
	$t = ereg_replace("KAMETZ", "&#257;", $t);
	$t = ereg_replace("CHATAF_PATACH", "&#259;", $t);
	$t = ereg_replace("PATACH_GANUV", "<sup>a</sup>", $t);
	$t = ereg_replace("PATACH", "a", $t);
	$t = ereg_replace("SHEVA_NACH", "", $t);
	$t = ereg_replace("SHEVA_NA", "&#601;", $t);
	$t = ereg_replace("CHATAF_SEGOL", "&#277;", $t);
	$t = ereg_replace("SEGOL", "e", $t);
	$t = ereg_replace("TZEIREI_MALEI", "&#234;", $t);
	$t = ereg_replace("TZEIREI_CHASER", "&#275;", $t);
	$t = ereg_replace("CHIRIK_MALEI", "&#238;", $t);
	$t = ereg_replace("CHIRIK_CHASER", "i", $t);
	$t = ereg_replace("CHOLAM_MALEI", "&#244;", $t);
	$t = ereg_replace("CHOLAM_CHASER", "&#333;", $t);
	$t = ereg_replace("SHURUK", "&#251;", $t);
	$t = ereg_replace("KUBUTZ", "u", $t);

/*	$t = ereg_replace("BOUNDARY COMMA BOUNDARY", ",", $t);
	$t = ereg_replace("COMMA", ",", $t);
	$t = ereg_replace("BOUNDARY DASH BOUNDARY", "-", $t);
	$t = ereg_replace("BOUNDARY SEMICOLON BOUNDARY", ";", $t);
	$t = ereg_replace("SEMICOLON", ";", $t);
	$t = ereg_replace(" ", "", $t);
	$t = ereg_replace("BOUNDARY", " ", $t);
	$t = ereg_replace("PERIOD", ".", $t);
*/
	$t = CleanUpPunctuation($t);
	return $t;
}


function AshkenazicTransliteration($t)
{
// do not double letters in general
	$GEMINATE_CANDIDATES = "(BET|GIMEL|DALED|VAV|ZED|TET|YUD|KAF|KAF_SOFIT|LAMED|MEM|NUN|SAMECH|PEI|TZADI|KUF|SHIN|SIN|TAV)";
	$t = ereg_replace($GEMINATE_CANDIDATES . "_CHAZAK", "\\1", $t);

	$t = ereg_replace("ALEPH", "", $t);
	$t = ereg_replace("BET", "b", $t);
	$t = ereg_replace("BHET", "v", $t);
	$t = ereg_replace("GIMEL", "g", $t);
	$t = ereg_replace("GHIMEL", "g", $t);
	$t = ereg_replace("DALED", "d", $t);
	$t = ereg_replace("DHALED", "d", $t);
	$t = ereg_replace("HEH_MAPIK", "h", $t);
	$t = ereg_replace("HEH BOUNDARY", "", $t);
	$t = ereg_replace("HEH", "h", $t);
	$t = ereg_replace("VAV", "v", $t);
	$t = ereg_replace("ZED", "z", $t);
	$t = ereg_replace("CHET", "ch", $t);
	$t = ereg_replace("TET", "t", $t);
	$t = ereg_replace("YUD_PLURAL", "", $t);
	$t = ereg_replace("YUD", "y", $t);
	$t = ereg_replace("KAF", "k", $t);
	$t = ereg_replace("KHAF(_SOFIT)?", "ch", $t);
	$t = ereg_replace("LAMED", "l", $t);
	$t = ereg_replace("MEM(_SOFIT)?", "m", $t);
	$t = ereg_replace("NUN(_SOFIT)?", "n", $t);
	$t = ereg_replace("SAMECH", "s", $t);
	$t = ereg_replace("AYIN", "", $t);
	$t = ereg_replace("PEI", "p", $t);
	$t = ereg_replace("PHEI(_SOFIT)?", "f", $t);
	$t = ereg_replace("TZADI(_SOFIT)?", "tz", $t);
	$t = ereg_replace("KUF", "k", $t);
	$t = ereg_replace("RESH", "r", $t);
	$t = ereg_replace("SHIN", "sh", $t);
	$t = ereg_replace("SIN", "s", $t);
	$t = ereg_replace("TAV", "t", $t);
	$t = ereg_replace("THAV", "s", $t);
	$t = ereg_replace("CHATAF_KAMETZ", "a", $t);
	$t = ereg_replace("KAMETZ_KATAN", "o", $t);
	$t = ereg_replace("KAMETZ", "a", $t);
	$t = ereg_replace("CHATAF_PATACH", "a", $t);
	$t = ereg_replace("PATACH_GANUV", "a", $t);
	$t = ereg_replace("PATACH", "a", $t);
	$t = ereg_replace("SHEVA_NACH", "", $t);
	$t = ereg_replace("SHEVA_NA", "'", $t);
	$t = ereg_replace("CHATAF_SEGOL", "e", $t);
	$t = ereg_replace("SEGOL", "e", $t);
	$t = ereg_replace("TZEIREI_MALEI", "ei", $t);
	$t = ereg_replace("TZEIREI_CHASER", "ei", $t);
	$t = ereg_replace("CHIRIK_MALEI", "i", $t);
	$t = ereg_replace("CHIRIK_CHASER", "i", $t);
	$t = ereg_replace("CHOLAM_MALEI", "o", $t);
	$t = ereg_replace("CHOLAM_CHASER", "o", $t);
	$t = ereg_replace("SHURUK", "u", $t);
	$t = ereg_replace("KUBUTZ", "u", $t);

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
	$words = explode("SPACE ", $t);
	$len = count($words);
//	echo "len: " . $len . "--";
	for ($i = 0; $i < $len; $i++)
	{
		$letters = explode(" ", $words[$i]);
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
	$t = ereg_replace("REVII ", "", $t);
	$t = ereg_replace("MAHPACH ", "", $t);
	$t = ereg_replace("KADMA ", "", $t);
	$t = ereg_replace("MUNACH ", "", $t);
	$t = ereg_replace("ZAKEF_KATON ", "", $t);
	$t = ereg_replace("ZAKEF_GADOL ", "", $t);
	$t = ereg_replace("MERCHA ", "", $t);
	$t = ereg_replace("TIPCHA ", "", $t);
	$t = ereg_replace("ETNACHTA ", "", $t);
	$t = ereg_replace("METEG ", "", $t);
	$t = ereg_replace("SOF_PASUK ", "", $t);
	$t = ereg_replace("TEVIR ", "", $t);
	$t = ereg_replace("DARGA ", "", $t);
	$t = ereg_replace("GERESH ", "", $t);
	$t = ereg_replace("GERSHAYIM ", "", $t);
	$t = ereg_replace("ZARKA ", "", $t);
	$t = ereg_replace("SEGOLTA ", "", $t);
	$t = ereg_replace("TELISHA_KETANA ", "", $t);
	$t = ereg_replace("TELISHA_GEDOLA ", "", $t);

	return $t;
}


function chunker($t)
{
// this function separated words into syllables
// is operated grammatically, such that geminate consonants
// close the previous syllable
// END_SYL with be the marker for the end of a syllable

	$NON_FINAL_NON_PLOSIVES = "(ALEPH|BHET|GHIMEL|DHALED|HEH|VAV|ZED|CHET|TET|YUD|KHAF|LAMED|MEM|NUN|SAMECH|AYIN|PHEI|TZADI|KUF|RESH|SHIN|SIN|THAV)";
	
	$t = ereg_replace("NON_FINAL_NON_PLOSIVES (SHEVA_NACH)", "\\1 \\2 END_SYL", $t);
	$t = ereg_replace("NON_FINAL_NON_PLOSIVES (SHEVA_NACH)", "\\1 \\2 END_SYL", $t);

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

function generateTransliteration()
{
	$t = $_POST['sourcetext'];

	global $isOpera, $isFirefox, $origHebrew;
	$isFirefox = strpos($_SERVER["HTTP_USER_AGENT"], "Firefox") ;
	$isOpera = strpos($_SERVER["HTTP_USER_AGENT"], "Opera") ;

	if ($isFirefox || $isOpera)
	{
		$origHebrew = $t; 
		$t = "BOUNDARY " . PostHebrewToIntermediate($_POST['sourcetext']) . "BOUNDARY";
	}
	else 
	{
		$origHebrew = PostHebrewExtendedASCIIToEncodedUnicode($t);
		$t = "BOUNDARY " . PostHebrewExtendedASCIIToIntermediate($_POST['sourcetext']) . "BOUNDARY";
	}
	$s = $_POST['sourcetext'];

	global $t_with_trup;
	$t_with_trup = $t;
	$t = RemoveTrup($t);
	$t = ApplyRulesToIntermediateForm($t);

//	echo $t;
//	echo $s;

// AND here is the next step: change the intermediate code into 
// transliteration
//	echo "<p>";	

	$target = $_POST['targetlang'];
	if ($target=="academic")
	{
		if($isOpera || $isFirefox)
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
		$t_without_trup = explode(" ", $t2);
		generateAndPrintTrup();
	}
	else if ($target == "mc")
	{  
		$t2 = MichiganClaremontTransliteration($t);
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

<html><head><title>Transliterate</title><style><!--
body{margin-right:1em;margin-left:1em;}
body,td,div,.p,a{font-family:arial,sans-serif}
 // -->
</style></head><body bgcolor=#ffffff text=#000000 link=#0000cc 
vlink=#551a8b alink=#ff0000><table cellspacing=2 cellpadding=0 border=0 
width=99%><tr><td width=100% align=right><table 
cellspacing=0 cellpadding=0 border=0 width=100%><tr><td bgcolor=#3366cc 
colspan=2><img width=1 height=1 alt=""></td></tr><tr 
bgcolor=#E5ECF9><td><b>&nbsp;Transliterate</b></td><td 
align=right><font 
size=-1>
&nbsp;|&nbsp;<a href="about.html">All About Hebrew Transliteration</a></font></td></tr></table></td></tr></table>


<table 
cellspacing=0 cellpadding=2 border=0 width=99%><tr 
bgcolor=#E6ECF9><td><table width=100% border=0 cellspacing=0 
cellpadding=1>
<tr><td>
<form action="http://www.google.com/search"><font size=-1>&nbsp;This 
text has been automatically transliterated from 
Hebrew:</font><br>&nbsp;&nbsp;
<textarea name=q 
rows=5 cols=45 wrap=PHYSICAL><?php generateTransliteration();?>
</textarea>&nbsp;&nbsp;<input type=hidden name=hl 
value="en"><input type=hidden name=ie value="UTF8"><input type=hidden 
name=oe value="UTF8"><input type=submit value="Google 
Search"></form></td></tr>

<tr><td><table width=100% cellpadding=3 cellspacing=0 
border=0><tr bgcolor=#ffffff><td><form action=action.php 
method=post><font size=-1>&nbsp;&nbsp;Transliterate 
text</font>
<br>&nbsp;&nbsp;<textarea name=sourcetext rows=5 cols=45 
wrap=PHYSICAL><?php
	echo $origHebrew;
?>
</textarea>

<br>&nbsp;&nbsp;<font size=-1>from Hebrew to</font>
<select name=targetlang>
<option value="academic">Academic</option>
<option value="academic_u">Academic Unicode</option>
<option value="academic_ff">Academic Font Friendly</option>
<option value="ashkenazic">Ashkenazic</option>
<option value="mc">Michigan - Claremont</option>
</select>
<input type=hidden name=hl value="en"><input 
type=hidden name=ie value="UTF8"><input type=submit 
value="Transliterate"></form></td></tr>

</table></td></tr></table></td></tr></table>

<p>
Here is the first verse of Deuteronomy2, available <a 
href="http://mechon-mamre.org/p/pt/pt0201.htm">here at mechon-mamre</a>, 
which 
is in general a good place to get you Hebrew text for the purpose of 
transliterating: <br>
&#1488;&#1461;&#1500;&#1468;&#1462;&#1492; 
&#1492;&#1463;&#1491;&#1468;&#1456;&#1489;&#1464;&#1512;&#1460;&#1497;&#1501;, 
&#1488;&#1458;&#1513;&#1473;&#1462;&#1512; 
&#1491;&#1468;&#1460;&#1489;&#1468;&#1462;&#1512; 
&#1502;&#1465;&#1513;&#1473;&#1462;&#1492; 
&#1488;&#1462;&#1500;-&#1499;&#1468;&#1464;&#1500;-&#1497;&#1460;&#1513;&#1474;&#1456;&#1512;&#1464;&#1488;&#1461;&#1500;, 
&#1489;&#1468;&#1456;&#1506;&#1461;&#1489;&#1462;&#1512;, 
&#1492;&#1463;&#1497;&#1468;&#1463;&#1512;&#1456;&#1491;&#1468;&#1461;&#1503;:  
&#1489;&#1468;&#1463;&#1502;&#1468;&#1460;&#1491;&#1456;&#1489;&#1468;&#1464;&#1512; 
&#1489;&#1468;&#1464;&#1506;&#1458;&#1512;&#1464;&#1489;&#1464;&#1492; 
&#1502;&#1493;&#1465;&#1500; &#1505;&#1493;&#1468;&#1507; 
&#1489;&#1468;&#1461;&#1497;&#1503;-&#1508;&#1468;&#1464;&#1488;&#1512;&#1464;&#1503; 
&#1493;&#1468;&#1489;&#1461;&#1497;&#1503;-&#1514;&#1468;&#1465;&#1508;&#1462;&#1500;, 
&#1493;&#1456;&#1500;&#1464;&#1489;&#1464;&#1503; 
&#1493;&#1463;&#1495;&#1458;&#1510;&#1461;&#1512;&#1465;&#1514;--&#1493;&#1456;&#1491;&#1460;&#1497; 
&#1494;&#1464;&#1492;&#1464;&#1489;
<p>
<center><br><font 
size=-1>&copy;2006 Joshua Waxman</font>
<p>
<!--WEBBOT bot="HTMLMarkup" startspan ALT="Site Meter" -->
<a href="http://s15.sitemeter.com/stats.asp?site=s15jewsforjohn" 
target="_top">
<img src="http://s15.sitemeter.com/meter.asp?site=s15jewsforjohn" 
alt="Site Meter" border=0></a>

<p>
&#1488; &#1493;&#1456;&#1488;&#1461;&#1431;&#1500;&#1468;&#1462;&#1492; &#1513;&#1473;&#1456;&#1502;&#1493;&#1465;&#1514;&#1433; &#1489;&#1468;&#1456;&#1504;&#1461;&#1443;&#1497; &#1497;&#1460;&#1513;&#1474;&#1456;&#1512;&#1464;&#1488;&#1461;&#1428;&#1500; &#1492;&#1463;&#1489;&#1468;&#1464;&#1488;&#1460;&#1430;&#1497;&#1501; &#1502;&#1460;&#1510;&#1456;&#1512;&#1464;&#1425;&#1497;&#1456;&#1502;&#1464;&#1492; &#1488;&#1461;&#1443;&#1514; &#1497;&#1463;&#1469;&#1506;&#1458;&#1511;&#1465;&#1428;&#1489; &#1488;&#1460;&#1445;&#1497;&#1513;&#1473; &#1493;&#1468;&#1489;&#1461;&#1497;&#1514;&#1430;&#1493;&#1465; &#1489;&#1468;&#1464;&#1469;&#1488;&#1493;&#1468;&#1475; &#1489; &#1512;&#1456;&#1488;&#1493;&#1468;&#1489;&#1461;&#1443;&#1503; &#1513;&#1473;&#1460;&#1502;&#1456;&#1506;&#1428;&#1493;&#1465;&#1503; &#1500;&#1461;&#1493;&#1460;&#1430;&#1497; &#1493;&#1460;&#1469;&#1497;&#1492;&#1493;&#1468;&#1491;&#1464;&#1469;&#1492;&#1475; &#1490; &#1497;&#1460;&#1513;&#1468;&#1474;&#1464;&#1513;&#1499;&#1464;&#1445;&#1512; &#1494;&#1456;&#1489;&#1493;&#1468;&#1500;&#1467;&#1430;&#1503; &#1493;&#1468;&#1489;&#1460;&#1504;&#1456;&#1497;&#1464;&#1502;&#1460;&#1469;&#1503;&#1475; &#1491; &#1491;&#1468;&#1464;&#1445;&#1503; &#1493;&#1456;&#1504;&#1463;&#1508;&#1456;&#1514;&#1468;&#1464;&#1500;&#1460;&#1430;&#1497; &#1490;&#1468;&#1464;&#1445;&#1491; &#1493;&#1456;&#1488;&#1464;&#1513;&#1473;&#1461;&#1469;&#1512;&#1475; &#1492; &#1493;&#1463;&#1469;&#1497;&#1456;&#1492;&#1460;&#1431;&#1497; &#1499;&#1468;&#1464;&#1500;&#1470;&#1504;&#1462;&#1435;&#1508;&#1462;&#1513;&#1473; &#1497;&#1465;&#1469;&#1510;&#1456;&#1488;&#1461;&#1445;&#1497; &#1497;&#1462;&#1469;&#1512;&#1462;&#1498;&#1456;&#1470;&#1497;&#1463;&#1506;&#1458;&#1511;&#1465;&#1430;&#1489; &#1513;&#1473;&#1460;&#1489;&#1456;&#1506;&#1460;&#1443;&#1497;&#1501; &#1504;&#1464;&#1425;&#1508;&#1462;&#1513;&#1473; &#1493;&#1456;&#1497;&#1493;&#1465;&#1505;&#1461;&#1430;&#1507; &#1492;&#1464;&#1497;&#1464;&#1445;&#1492; &#1489;&#1456;&#1502;&#1460;&#1510;&#1456;&#1512;&#1464;&#1469;&#1497;&#1460;&#1501;&#1475; &#1493; &#1493;&#1463;&#1497;&#1468;&#1464;&#1444;&#1502;&#1464;&#1514; &#1497;&#1493;&#1465;&#1505;&#1461;&#1507;&#1433; &#1493;&#1456;&#1499;&#1464;&#1500;&#1470;&#1488;&#1462;&#1495;&#1464;&#1428;&#1497;&#1493; &#1493;&#1456;&#1499;&#1465;&#1430;&#1500; &#1492;&#1463;&#1491;&#1468;&#1445;&#1493;&#1465;&#1512; &#1492;&#1463;&#1492;&#1469;&#1493;&#1468;&#1488;&#1475; &#1494; &#1493;&#1468;&#1489;&#1456;&#1504;&#1461;&#1443;&#1497; &#1497;&#1460;&#1513;&#1474;&#1456;&#1512;&#1464;&#1488;&#1461;&#1431;&#1500; &#1508;&#1468;&#1464;&#1512;&#1447;&#1493;&#1468; &#1493;&#1463;&#1469;&#1497;&#1468;&#1460;&#1513;&#1473;&#1456;&#1512;&#1456;&#1510;&#1435;&#1493;&#1468; &#1493;&#1463;&#1497;&#1468;&#1460;&#1512;&#1456;&#1489;&#1468;&#1445;&#1493;&#1468; &#1493;&#1463;&#1497;&#1468;&#1463;&#1469;&#1506;&#1463;&#1510;&#1456;&#1502;&#1430;&#1493;&#1468; &#1489;&#1468;&#1460;&#1502;&#1456;&#1488;&#1465;&#1443;&#1491; &#1502;&#1456;&#1488;&#1465;&#1425;&#1491; &#1493;&#1463;&#1514;&#1468;&#1460;&#1502;&#1468;&#1464;&#1500;&#1461;&#1445;&#1488; &#1492;&#1464;&#1488;&#1464;&#1430;&#1512;&#1462;&#1509; &#1488;&#1465;&#1514;&#1464;&#1469;&#1501;&#1475;
<p>
&#1431; = revii
<br>
&#1444; = mahpach / yetiv - yetiv if after the first consonant
<br>
&#1433; = pashta / kadma. pashta repeats if stressed early. otherwise pashta on last letter
<br>
&#1443; = munach
<br>
&#1428; = zakef katon
<br>
&#1429; = zakef gadol
<br>
&#1445; = mercha
<br>
&#1430; = tipcha
<br>
&#1425; = etnachta
<br>
&#1469; = silluq / early stress - can disambiguate via position in sentence
<br>
&#1475; = sof pasuk
<br>
&#1435; = tevir
<br>
&#1447; = darga	
<br>
&#1436; = geresh
<br>
&#1438; = gershayim
<br>
&#1454; = zarka
<br>
&#1426; = segolta
<br>
&#1440; = telisha ketana
<br>
&#1449; = telisha gedola
</center></body></html>
