<?php
/**
*
* @package Transliterator
* @version $Id: arc.php,v 1.0.0 2023/10/22 21:21:14 orynider Exp $
*
*/

//Acces check ܟ݁ܬ݂ܵܒ݂ܵܐ ܕ݁ܝܠܼܝܕ݂ܘܿܬ݂ܹܗ ܕ݂݁ܝܹܫܘܿܥ ܡܫܼܝܚܵܐ ܒ݂ܸ݁ܪܹܗ ܕ݂݁ܕ݂ܸܘܼܝܕ݂ ܒ݂ܸ݁ܪܹܗ ܕ݁ܲܐܒ݂ܪܵܗܵܡ ܀ 
if (!defined('IN_PORTAL') && (strpos($_SERVER['PHP_SELF'], "unit_test.php") <= 0)) { die("Direct acces not allowed! This file was accesed: ".$_SERVER['PHP_SELF']."."); }

define('SEARCH_PATTERN', "[)bgdhwzxTyklmns(pPcqr$&tˀḥṭˁṗṣšśאבגדהוזחטיכלמנסעפצקרששׁשׂתܐܒܓܕܗܘܙܚܛܝܟܠܡܢܣܥܦܧܨܩܪܫܬ]*");

//Definitions for Aramaic  
define('ALEPH', 'ܐ'); //U+0710 SYRIAC LETTER ALAPH
define('ALEPH_SUP', 'ܑ');
define('BHET_PARSI', 'ܭ');
define('BHET', 'ܒ');
define('BET', 'ܒ');
define('GIMEL', 'ܔ');
define('GHIMEL', 'ܓ');
define('GHIMEL_PARSI', 'ܮ');
define('DALED', 'ܕ݂݁');
define('DHALED', 'ܕ');
define('DHALED_PARSI', 'ܯ');
define('HEH', 'ܗ');
define('YUD_HEH', 'ܞ');
define('HEH_MAPIK', 'ܗ');
define('VAV', 'ܘ');
define('ZED', 'ܙ');
define('ZED_SOGDIAN', 'ݍ');
define('CHET', 'ܚ');
define('TET', 'ܛ');
define('TET_GARSHUNI', 'ܜ'); //arc
define('YUD_PLURAL', 'ܝ');
define('YUD', 'ܝ');
define('KAF_SOFIT', 'ܟ');
define('KHAF_SOFIT', 'ܟ');
define('KAF', 'ܟ');
define('KHAF', 'ܟ');
define('KHAF_SOGDIAN', 'ݎ');
define('LAMED', 'ܠ');
define('MEM_SOFIT', 'ܡ');
define('MEM', 'ܡ');
define('NUN_SOFIT', 'ܢ');
define('NUN', 'ܢ');
define('SAMECH', 'ܣ');
define('SAMECH_SOFIT', 'ܤ'); //arc
define('AYIN', 'ܥ');
define('PHEI_SOFIT', 'ܦ');
define('PEI', 'ܦ');
define('PEH', 'ܧ');
define('FE', 'ݏ'); //SOGDIAN
define('TZADI_SOFIT', 'ܨ');
define('TZADI', 'ܨ');
define('KUF', 'ܩ');
define('RESH', 'ܪ');
define('SHIN_NO_DOT', 'ܫ');	
define('SHIN', 'ܫ'.'݀' );
define('SIN', 'ܫ'.'݁');
define('SHIN_SHIN_DOT_SHEVA_NACH', 'ܫܿ');
define('SHIN_SHIN_DOT_KAMETZ', 'ܫܿ');
define('TAV', 'ܬ');
define('THAV', 'ܬ'); //U+074F

define('RUKKAKHA_UP_ZLAMA_ANGULAR', '݂ܹ');
define('PTHAHA_UP', 'ܰ'); //| U+0730 | Syriac Pthaha Above |
define('PTHAHA_DOWN', 'ܱ'); //| U+0731 |Syriac Pthaha Below |
define('PTHAHA_DOTTED', 'ܲ'); //| U+0732 | Syriac Pthaha Dotted |
define('ZQAPHA_UP', 'ܳ'); //| U+0733 | Syriac Zqapha Above |
define('ZQAPHA_DOWN', 'ܴ'); //| U+0734 | Syriac Zqapha Below |
define('ZQAPHA_DOTTED', 'ܵ'); //| U+0735  | Syriac Zqapha Dotted |
define('RBASA_UP', 'ܶ'); //| U+0736 | Syriac Rbasa Above |
define('RBASA_DOWN', 'ܷ'); //| U+0737 | Syriac Rbasa Below |
define('RBASA_DOTTED', 'ܸ'); //| U+0738  | Syriac Dotted Zlama Horizontal |
define('ZLAMA_ANGULAR', 'ܹ'); //| U+0739  | Syriac Dotted Zlama Angular |
define('ZLAMA_UP', 'ܺ'); //| U+073A | Syriac Hbasa Above |
define('ZLAMA_DOWN', 'ܻ'); //| U+073B | Syriac Hbasa Below |
define('ZLAMA_DOTTED', 'ܼ'); //| U+073C | Syriac Hbasa-Esata Dotted |
define('ESASA_UP', 'ܽ'); //| U+073D | Syriac Esasa Above |
define('ESASA_DOWN', 'ܾ'); //| U+073E | Syriac Esasa Below |
define('RWAHA', 'ܿ'); //| U+073F | Syriac Rwaha |
define('FEMININE_DOT', '݀'); //| U+0740 | Syriac Feminine Dot |
define('QUSHSHAYA', '݁'); //| U+0741 | Syriac Qushshaya |
define('RUKKAKHA', '݂'); //| U+0742 | Syriac Rukkakha |
define('VERTICAL_DOTS_UP', '݃'); //| U+0743 | Syriac Two Vertical Dots Above |
define('VERTICAL_DOTS_DOWN', '݄'); //| U+0744 | Syriac Two Vertical Dots Below |
define('THREE_DOTS_UP', '݅'); //| U+0745 | Syriac Three Dots Above |
define('THREE_DOTS_DOWN', '݆'); //| U+0746 | Syriac Three Dots Below |
define('OBLIQUE_LINE_UP', '݇'); //| U+0747  | Syriac Oblique Line Above |
define('OBLIQUE_LINE_DOWN', '݈'); //| U+0748 | Syriac Oblique Line Below |
define('MUSIC', '݉'); //| U+0749 | Syriac Music |
define('BARREKH', '݊'); //| U+074A | Syriac Barrekh |
define('MAQAF', '־'); //u05BE Hebrew UP LINE

//Definitions for Imperial Aramaic


//Redefinitions from Aramaic to Hebrew
define('HOLAM_HASHER', '݀'); //HOLAM HASHER for Wav 
define('CHOLAM_MALEI', '݀');//HOLAM', '\u05B9'
define('VAV_LEFT_DOT', 'ܘ݀'); //וֺ
define('HOLAM_VAV', 'ܘܿ');
define('HOLAM_MEM', 'ܡ݀'); //מֹ
define('HOLAM_LAMED', 'ܠ݀'); //לֹ
define('HOLAM_BHET', 'ܒ݀'); //בֹ
define('HOLAM_TAV', 'ܛ݀'); //תֹּ
define('HOLAM_RESH', 'ܪ݀'); //רֹ
define('HOLAM_HASHER_VAV', 'ܘܿ'); //וֹ
define('SHEVA', '݄'); //SHVA', '\u05B0'
define('SHEVA_NACH', '݄'); //SHVA', '\u05B0'
define('SHEVA_UNKNOWN', '݄');


?>