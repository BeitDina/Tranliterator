<?php
/**
*
* @package Tranliterator
* @version $Id: constants.php,v 1.0 2023/10/11 10:04:08 orynider Exp $
*
*/

//Acces check
if (!defined('IN_PORTAL') ) { die("Direct acces not allowed!"); }

// Include common scripts.
date_default_timezone_set('Asia/Jerusalem'); // We have to set something or else PHP will complain.

//Definitions
define('TRANS_VERSION', "v.1.0.1"); // version...

//Definitions https://github.com/symbl-cc/symbl-data 
//Backup: https://github.com/anio/unicode-table-data/blob/95d28cae674791b18798e5cdb846bbffde017097/loc/de/symbols/0500.txt#L200C3-L200C3
define('ALEPH', 'א');
define('BHET', 'ב');
define('GHIMEL', 'ג');
define('DHALED', 'ד');
define('HEH_MAPIK', 'ה');
define('VAV', 'ו');
define('ZED', 'ז');
define('CHET', 'ח');
define('TET', 'ט');
define('YUD_PLURAL', 'י');
define('KHAF_SOFIT', 'ך');
define('KAF', 'כ');
define('LAMED', 'ל');
define('MEM_SOFIT', 'ם');
define('MEM', 'מ');
define('NUN_SOFIT', 'ן');
define('NUN', 'נ');
define('SAMECH', 'ס');
define('AYIN', 'ע');
define('PHEI_SOFIT', 'ף');
define('PEI', 'פ');
define('TZADI_SOFIT', 'ץ');
define('TZADI', 'צ');
define('KUF', 'ק');
define('RESH', 'ר');
define('SHIN', 'ש');
define('THAV', 'ת');
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
define('SHEVA_NACH', 'ְ'); //SHVA', '\u05B0'
define('CHATAF_SEGOL', 'ֱ'); //REDUCED_SEGOL', '\u05B1'
define('CHATAF_PATACH', 'ֲ'); //REDUCED_PATAKH', '\u05B2'
define('CHATAF_KAMETZ', 'ֳ'); //REDUCED_KAMATZ', '\u05B3'
define('CHIRIK_MALEI', 'ִ'); //HIRIK', '\u05B4'
define('TZEIREI_MALEI', 'ֵ'); //TZEIRE', '\u05B5'
define('SEGOL', 'ֶ'); //SEGOL', '\u05B6'  
define('PATACH_GANUV', '׆'); //\u05C6: Hebräisches Satzzeichen Nun Hafucha || 05C6: Hebräisches Interpunktions-Nonne Hafukha
define('PATACH', 'ַ'); //PATAKH', '\u05B7'; 
define('KAMETZ_KATAN', 'ׇ'); //\u05C7: Hebräisches Zeichen Kametz Katan || 05C7: Hebräischer Punkt Qamats Qatan
define('KAMETZ', 'ָ'); //KAMATZ', '\u05B8'; 
define('CHOLAM_CHASER', 'ֺ');//For Wav
define('HOLAM_HASHER', 'ֹֹ'); //HOLAM HASHER for Wav 
define('CHOLAM_MALEI', 'ֹֹ');//HOLAM', '\u05B9'
define('HOLAM_MEM', 'מֹ'); //  מֹ מֹ
define('HOLAM_VAV', 'וֺ'); //
define('METEG', 'ֽ'); //METEG', '\u05BD'
define('MAPIQ', 'ּ'); //u05BC
define('MAQAF', '־'); //u05BE
define('RAFE', 'ֿ'); //u05BF
define('KUBUTZ', 'ֻ'); //KUBUTZ', '\u05BB' 
define('SHURUK', 'ּ'); //SHURUK', '\u05BC' //or: DAGESH_LETTER', '\u05bc'
define('SHIN_DOT', 'ׁ');  //SHIN_YEMANIT', '\u05c1' &#x05C1 in BabelMap
define('SIN_DOT', 'ׂ'); //SHIN_SMALIT', '\u05c2' &#x05C2 in BabelMap	


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
