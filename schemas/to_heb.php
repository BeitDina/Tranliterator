<?php
/**
*
* @package Transliterator
* @version $Id: heb.php,v 1.1.1 2023/10/22 21:21:14 orynider Exp $
*
*/

//Acces check
if (!defined('IN_PORTAL') && (strpos($_SERVER['PHP_SELF'], "unit_test.php") <= 0)) { die("Direct acces not allowed! This file was accesed: ".$_SERVER['PHP_SELF']."."); }

define('TO_SEARCH_PATTERN', "[)bgdhwzxTyklmns(pPcqr$&tˀḥṭˁṗṣšśאבגדהוזחטיכלמנסעפצקרששׁשׂתܐܒܓܕܗܘܙܚܛܝܟܠܡܢܣܥܦܧܨܩܪܫܬ]*");

//Definitions at https://github.com/symbl-cc/symbl-data 
//Backup at https://github.com/anio/unicode-table-data/blob/95d28cae674791b18798e5cdb846bbffde017097/loc/de/symbols/0500.txt#L200C3-L200C3
define('TO_ALEPH', 'א');
define('TO_BHET', 'ב');
define('TO_BET', 'בּ');
define('TO_GIMEL', 'גּ');
define('TO_GHIMEL', 'ג');
define('TO_DALED', 'דּ');
define('TO_DHALED', 'ד');
define('TO_HEH', 'ה');
define('TO_HEH_MAPIK', 'הִ');
define('TO_VAV', 'ו');
define('TO_ZED', 'ז');
define('TO_CHET', 'ח');
define('TO_TET', 'ט');
define('TO_YUD_PLURAL', 'י');
define('TO_YUD', 'י');
define('TO_KAF_SOFIT', 'ךּ');
define('TO_KHAF_SOFIT', 'ך');
define('TO_KAF', 'כ');
define('TO_KHAF', 'כּ');
define('TO_KHAF_KAMETZ', 'כָּ');
define('TO_LAMED', 'ל');
define('TO_MEM_SOFIT', 'ם');
define('TO_MEM', 'מ');
define('TO_NUN_SOFIT', 'ן');
define('TO_NUN', 'נ');
define('TO_SAMECH', 'ס');
define('TO_AYIN', 'ע');
define('TO_PHEI_SOFIT', 'ף');
define('TO_PEI', 'פ');
define('TO_TZADI_SOFIT', 'ץ');
define('TO_TZADI', 'צ');
define('TO_KUF', 'ק');
define('TO_RESH', 'ר');
define('TO_SHIN_NO_DOT', 'ש');	
define('TO_SHIN', 'ש'.'ׁ');
define('TO_SIN', 'ש'.'ׂ');
define('TO_SHIN_SHIN_DOT_SHEVA_NACH', 'שְּׁ');
define('TO_SHIN_SHIN_DOT_KAMETZ', 'שָּׁ');
define('TO_TAV', 'תּ');
define('TO_THAV', 'ת');
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
define('TO_SHEVA', 'ְ'); //SHVA', '\u05B0'
define('TO_SHEVA_NACH', 'ְ'); //SHVA', '\u05B0'
define('TO_SHEVA_UNKNOWN', 'ְ');
define('TO_CHATAF_SEGOL', 'ֱ'); //REDUCED_SEGOL', '\u05B1'
define('TO_CHATAF_PATACH', 'ֲ'); //REDUCED_PATAKH', '\u05B2'
define('TO_CHATAF_KAMETZ', 'ֳ'); //REDUCED_KAMATZ', '\u05B3'
define('TO_CHIRIK_MALEI', 'ִ'); //HIRIK', '\u05B4'
define('TO_CHIRIK_CHASER', 'ִ'); //HIRIK', '\u05B4'
define('TO_CHIRIK', 'ִ');
define('TO_CHIRIK_UNKNOWN', 'ֳ');
define('TO_TZEIREI', 'ֵ');
define('TO_TZEIREI_MALEI', 'ֵ'); //TZEIRE', '\u05B5'
define('TO_TZEIREI_CHASER', 'ֵ'); //TZEIRE', '\u05B5'
define('TO_TEZEIREI_CHASER', 'ֵ');
define('TO_TEZEIREI_UNKNOWN', 'ֵ');
define('TO_TZEIREI_UNKNOWN', 'ֵ');
define('TO_SEGOL', 'ֶ'); //SEGOL', '\u05B6'  
define('TO_PATACH_GANUV', '׆'); //\u05C6: Hebräisches Satzzeichen Nun Hafucha || 05C6: Hebräisches Interpunktions-Nonne Hafukha
define('TO_PATACH', 'ַ'); //PATAKH', '\u05B7'; 
define('TO_PATACH_UNKNOWN', 'ַ'); 
define('TO_KAMETZ_KATAN', 'ׇ'); //\u05C7: Hebräisches Zeichen Kametz Katan || 05C7: Hebräischer Punkt Qamats Qatan
define('TO_KAMETZ', 'ָ'); //KAMATZ', '\u05B8';
define('TO_CHOLAM', 'ֹֹ');//HOLAM', '\u05B9' 
define('TO_CHOLAM_CHASER', 'ֺ');//For Wav
define('TO_CHOLAM_UNKNOWN', 'ֺ');//For Wav
define('TO_HOLAM_HASHER', 'ֹֹ'); //HOLAM HASHER for Wav 
define('TO_HOLAM_HASHER_VAV', 'וֹ');
define('TO_HOLAM_RESH', 'רֹ');
define('TO_CHOLAM_MALEI', 'ֹֹ');//HOLAM', '\u05B9'
define('TO_HOLAM_MEM', 'מֹ'); //  מֹ מֹ
define('TO_HOLAM_VAV', 'וֺ'); //
define('TO_HOLAM_LAMED', 'לֹ');
define('TO_HOLAM_BHET', 'בֹ');
define('TO_HOLAM_TAV', 'תֹּ');
define('TO_METEG', 'ֽ'); //METEG', '\u05BD'
define('TO_MAPIQ', 'ּ'); //u05BC
define('TO_MAQAF', '־'); //u05BE
define('TO_RAFE', 'ֿ'); //u05BF
define('TO_KUBUTZ', 'ֻ'); //KUBUTZ', '\u05BB' 
define('TO_SHURUK', 'ּ'); //SHURUK', '\u05BC' //or: DAGESH_LETTER', '\u05bc'
define('TO_DAGESH', 'ּ');
define('TO_DAGESH_UNKNOWN', 'ּ');
define('TO_SHIN_UNKNOWN', 'ׁ'); 
define('TO_SHIN_DOT', 'ׁ');  //SHIN_YEMANIT', '\u05c1' &#x05C1 in BabelMap
define('TO_SIN_DOT', 'ׂ'); //SHIN_SMALIT', '\u05c2' &#x05C2 in BabelMap
define('TO_TIPEHA', '֖'); //U+0596 HEBREW ACCENT TIPEHA : tarha, me'ayla ~ mayla
define('TO_MERKHA', '֥'); //U+05A5 HEBREW ACCENT MERKHA : yored
define('TO_MERKHA_KEFULA', '֦'); //U+05A6 HEBREW ACCENT MERKHA KEFULA	
define('TO_MUNAH', '֣'); //U+05A3 HEBREW ACCENT MUNAH		
define('TO_ETNAHTA', '֑'); //U+0591 HEBREW ACCENT ETNAHTA : atnah
define('TO_ATNAH_HAFUKH', '֢'); //U+05A2 HEBREW ACCENT ATNAH HAFUKH
define('TO_YERAH_BEN_YOMO', '֪'); //U+05AA HEBREW ACCENT YERAH BEN YOMO : galgal	

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