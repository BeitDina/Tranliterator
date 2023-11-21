<?php 
/**
*
* @package Tranliterator
* @version $Id: index.php,v 1.75 2023/11/21 04:07:52 orynider Exp $
*
*/

/**
EDIT Start of OPTIONAL SETTINGS
*/
define('CACHE_STORAGE_DIR', 'files/');
/**
 * Format to display dates in.
 * @see function date()
 */
define('DATE_FORMAT', 'Y-M-d');
/**
 * Sets debug mode: true or false
 */
define('DEBUG', true);

@session_cache_expire('1440');
@set_time_limit('1500');

/*
EDIT Ends of OPTIONAL SETTINGS 
**/

define('IN_PORTAL', true);
define('ANONYMOUS', 0);
$root_path = "./";
$phpExt = substr(strrchr(__FILE__, '.'), true);

define('CONF_SESSIONS', $root_path . 'tranliterate.conf.'.$phpExt);
//find and store the user's IP address and hostname: 
$ip = (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
//localhost.localdomain
if (!empty($_SESSION['host']))
{
	$host = (function_exists('php_uname')) ? php_uname('n') : $_SESSION['host'];
}
else
{
	$_SESSION['host'] = $host = (function_exists('php_uname')) ? php_uname('n') : gethostbyaddr($ip);
}
if (empty($_SERVER['SERVER_NAME']))
{
	$_SERVER['SERVER_NAME'] = (function_exists('php_uname')) ? php_uname('n') : gethostbyaddr($ip);
}

//Now we need the sessions class
if (is_file(CONF_SESSIONS)) 
{
	@session_name('AutoIndex2');
	session_start();
	include($root_path . 'sessions.' . $phpExt);
	if (!is_readable(CONF_SESSIONS))
	{
		die('Make sure we have permissions to read the file from the server: <em>' . Configuration::html_output(CONF_SESSIONS) . '</em>');
	}
	@session_name('Tranliterator');
	session_start();
}
else
{
	define('LEVEL_TO_UPLOAD', ANONYMOUS);
	define('USER_LEVEL', ANONYMOUS);
	$log_login = false;
}

include($root_path . 'trans.' . $phpExt);

print '
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html" charset="UTF-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	
	<meta name="title"       content="Transliterator Index" />
	<meta name="author"      content="Beit Dina Bible Arheology and Translation Institute @ beitdina.net" />
	<meta name="copyright"   content="default template © Beit Dina 2019 based on subSilver style © 2005 phpBB Group." />
	<meta name="keywords"    content="Beit, Dina, Bible, Arheology" />
	<meta name="description" lang="en" content="Directory Index. This is the description search engines show when listing your site." />
	<meta name="category"    content="general" />
	<meta name="robots"      content="index,follow" />
	<meta name="revisit-after" content="7 days" >
	
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="blue" />
 	
	<title>'.$l_about_title.'</title>
	<!-- Then load addon template *.css definition located in the folder -->
	<link rel="stylesheet" href="'.$root_path.'index.css" type="text/css" />
</head>
<body bgcolor=#ffffff text=#000000 link=#0000cc vlink=#551a8b alink=#ff0000>
<table cellspacing=2 cellpadding=0 border=0 width=99%>
<tr>
<td width=100% align=right>
<table cellspacing=0 cellpadding=0 border=0 width=100%>
<tr>
<td bgcolor=#3366cc colspan=2>
<img width=1 height=1 alt="" />
</td>
</tr>
<tr bgcolor=#E5ECF9>
<td><b>&nbsp;Transliterate</b></td>
<td align=right>
<font size=-1>&nbsp;|&nbsp;<a href="index.php?copy=about">All About Hebrew Transliteration</a></font>
</td>
</tr>
</table>
</td>
</tr>
</table>
<table cellspacing=0 cellpadding=2 border=0 width=99%>
<tr bgcolor=#E6ECF9>
<td>';
$origHebrew = '&nbsp;&nbsp;';

if (!empty($_FILES['fileToUpload']) && (USER_LEVEL > ANONYMOUS))
{
	$origfile = array();
	foreach ($_FILES['fileToUpload'] as $var => $value)
	{
		//vars: name, type, tmp_name, error, size 
		$origfile[$var] = $value;
	}
	
	$sourcetext = isset($_POST['sourcetext']) ? $_POST['sourcetext'] : print_r(file($origfile['tmp_name']), true);

}
else
{
	$sourcetext = isset($_POST['sourcetext']) ? $_POST['sourcetext'] : 'בְּרֵאשִׁ֖ית בָּרָ֣א אֱלֹהִ֑ים אֵ֥ת הַשָּׁמַ֖יִם וְאֵ֥ת הָאָֽרֶץ׃';
}

$targetlang = isset($_POST['targetlang']) ? $_POST['targetlang'] : 'romanian';
$sourcelang = isset($_POST['sourcelang']) ? $_POST['sourcelang'] : 'hebrew';
print '
<table width="100%" border="0" cellspacing="0" cellpadding="1">
<tr>
<td>';
print '
<form action="http://www.google.com/search">
<span class="nav"><font size=-1>&nbsp;This text has been automatically transliterated from '. ucfirst($sourcelang) .':</font></span>
<br />&nbsp;&nbsp;
<textarea id="inputbox" name="q" rows="5" cols="55" wrap="PHYSICAL">
';
generateTransliteration($sourcetext, $targetlang, false, false); 
print '
</textarea>&nbsp;&nbsp;
<input type="hidden" name="hl" value="en" />
<input type="hidden" name="ie" value="UTF8" />
<input type="hidden" name="oe" value="UTF8" />
<input type="hidden" name="ro" value="UTF8" />
<input type="submit" class="icon pointer input" value="Google Search" />
</form>
</td>
</tr>
<tr>
<td>';
print '
<table width="100%" cellpadding="3" cellspacing="0" border="0">
<tr bgcolor="#ffffff">
<td>';
$mainform = '
<form name="text" action="index.php" enctype="multipart/form-data" method="post">
<span class="nav"><font size="-1">&nbsp;&nbsp;Transliterate text</font></span>
<br />&nbsp;&nbsp; 
<textarea id="drop-area" name="sourcetext" rows="5" cols="55" wrap="PHYSICAL">';
$mainform .= $origHebrew;
$mainform .= '</textarea><br />&nbsp;&nbsp;';
$mainform .= '<span class="nav"><font size="-1">from</font></span>';
$mainform .= '<select id="dropdown" name="sourcelang" selected="'.$sourcelang.'">';
$mainform .= '	<option type="select" value="aramaic"'; if($sourcelang == 'aramaic') { $mainform .= ' selected'; } $mainform .= '>Aramaic</option>';
$mainform .= '	<option type="select" value="romanian"'; if($sourcelang == 'romanian'){ $mainform .= ' selected'; } $mainform .= '>Romanian</option>';
$mainform .= '	<option type="select" value="hebrew"'; if($sourcelang == 'hebrew'){ $mainform .= ' selected';} $mainform .= '>Original - Hebrew</option>';
$mainform .= '</select>';
$mainform .= '<span class="nav"><font size="-1">to</font></span>';
$mainform .= '<select id="dropdown" name="targetlang" selected="'.$targetlang.'">';
$mainform .= '	<option value="academic"'; if($targetlang == 'academic') { $mainform .= ' selected'; } $mainform .= '>Academic</option>';
$mainform .= '	<option value="academic_u"'; if($targetlang == 'academic_u'){ $mainform .= ' selected'; } $mainform .= '>Academic Unicode</option>';
$mainform .= '	<option value="academic_ff"'; if($targetlang == 'academic_ff'){ $mainform .= ' selected'; } $mainform .= '>Academic Font Friendly</option>';
$mainform .= '	<option value="academic_s"'; if($targetlang == 'academic_s'){ $mainform .= ' selected'; } $mainform .= '>Academic Spirantization</option>';
$mainform .= '	<option value="ashkenazic"'; if($targetlang == 'ashkenazic'){ $mainform .= ' selected'; } $mainform .= '>Ashkenazic</option>';
$mainform .= '	<option value="sefardic"'; if($targetlang == 'sefardic'){ $mainform .= ' selected'; } $mainform .= '>Sefardic</option>';
$mainform .= '	<option value="romaniote"'; if($targetlang == 'romaniote'){ $mainform .= ' selected'; } $mainform .= '>Romaniote</option>';
$mainform .= '	<option value="romanian"'; if($targetlang == 'romanian'){ $mainform .= ' selected'; } $mainform .= '>Romanian</option>';
$mainform .= '	<option value="ukrainian"'; if($targetlang == 'ukrainian'){ $mainform .= ' selected'; } $mainform .= '>Ukrainian</option>';
$mainform .= '	<option value="mc"'; if($targetlang == 'mc'){ $mainform .= ' selected';} $mainform .= '>Michigan - Claremont</option>';
$mainform .= '</select>';
$mainform .= '<input type="hidden" name="hl" value="en" />';
$mainform .= '<input type="hidden" name="ie" value="UTF8" />';
$mainform .= '<input type="submit" name="transliterate" class="icon pointer input liteoption" value="Transliterate" id="btnlite" /><br />';
$mainform .= '	</form>';
print $mainform;
if (USER_LEVEL > ANONYMOUS)
{
print '
	<form name="upload" action="index.php" enctype="multipart/form-data" method="post">
	<span id="addbutton" class="gen">
	<input type="file" id="fileElem" aria-label="Choose your file" multiple accept="text/*" data-csrf="true" onchange="handleFiles(this.files)" />
	<div id="file_browse" style="position:relative;"></div>
	<input type="file" class="button" for="fileElem" name="fileToUpload" id="fileToUpload" />
	</span>
	<input class="icon pointer input mainoption" type="submit" name="submit" value="submit" for="fileElem" />
	</form>';
}
print '
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>';
print '<font size=-1>
		&nbsp;
		This mechanism is offered as-is to support customers for the purpose of transliterating Hebrew Alphabet. 
		Here is the first verse of Deuteronomy chapt. 2, available <a href="http://mechon-mamre.org/p/pt/pt0201.htm">here at mechon-mamre</a>:
		</font>&nbsp;<br />	
&#1488;&#1461;&#1500;&#1468;&#1462;&#1492; 
&#1492;&#1463;&#1491;&#1468;&#1456;&#1489;&#1464;&#1512;&#1460;&#1497;&#1501;, 
&#1488;&#1458;&#1513;&#1473;&#1462;&#1512; 
&#1491;&#1468;&#1460;&#1489;&#1468;&#1462;&#1512; 
&#1502;&#1465;&#1513;&#1473;&#1462;&#1492;,
&#1488;&#1462;&#1500;-&#1499;&#1468;&#1464;&#1500;-&#1497;&#1460;&#1513;&#1474;&#1456;&#1512;&#1464;&#1488;&#1461;&#1500;, 
&#1489;&#1468;&#1456;&#1506;&#1461;&#1489;&#1462;&#1512;
&#1492;&#1463;&#1497;&#1468;&#1463;&#1512;&#1456;&#1491;&#1468;&#1461;&#1503;,
&#1489;&#1468;&#1463;&#1502;&#1468;&#1460;&#1491;&#1456;&#1489;&#1468;&#1464;&#1512;, 
&#1489;&#1468;&#1464;&#1506;&#1458;&#1512;&#1464;&#1489;&#1464;&#1492; 
&#1502;&#1493;&#1465;&#1500; &#1505;&#1493;&#1468;&#1507;, 
&#1489;&#1468;&#1461;&#1497;&#1503;-&#1508;&#1468;&#1464;&#1488;&#1512;&#1464;&#1503;, 
&#1493;&#1468;&#1489;&#1461;&#1497;&#1503;-&#1514;&#1468;&#1465;&#1508;&#1462;&#1500;, 
&#1493;&#1456;&#1500;&#1464;&#1489;&#1464;&#1503;, 
&#1493;&#1463;&#1495;&#1458;&#1510;&#1461;&#1512;&#1465;&#1514;--&#1493;&#1456;&#1491;&#1460;&#1497; 
&#1494;&#1464;&#1492;&#1464;&#1489;
:<br />';
if (LEVEL_TO_UPLOAD == ANONYMOUS)
{
	print '<div id="textarea">
			<table class="table3" border="0" cellpadding="8" cellspacing="0">
				<tr class="paragraph">
					<td class="table2"><span class="gen">You must login to transliterate long files.</span></td>
				</tr>
				<tr class="paragraph">
					<td class="table1"><span class="gen">To allow login bridge with AutoIndex using .htpasswd file.</span></td>
				</tr>
			</table>
			</div>';
}
else
{
	print $log_login;
}
print '<center>
<font size=-1>&copy;2006 Joshua Waxman & &copy;2023 Florin C. Bodin</font>
<!-- 
&#1488; &#1493;&#1456;&#1488;&#1461;&#1431;&#1500;&#1468;&#1462;&#1492; &#1513;&#1473;&#1456;&#1502;&#1493;&#1465;&#1514;&#1433; &#1489;&#1468;&#1456;&#1504;&#1461;&#1443;&#1497; &#1497;&#1460;&#1513;&#1474;&#1456;&#1512;&#1464;&#1488;&#1461;&#1428;&#1500; &#1492;&#1463;&#1489;&#1468;&#1464;&#1488;&#1460;&#1430;&#1497;&#1501; &#1502;&#1460;&#1510;&#1456;&#1512;&#1464;&#1425;&#1497;&#1456;&#1502;&#1464;&#1492; &#1488;&#1461;&#1443;&#1514; &#1497;&#1463;&#1469;&#1506;&#1458;&#1511;&#1465;&#1428;&#1489; &#1488;&#1460;&#1445;&#1497;&#1513;&#1473; &#1493;&#1468;&#1489;&#1461;&#1497;&#1514;&#1430;&#1493;&#1465; &#1489;&#1468;&#1464;&#1469;&#1488;&#1493;&#1468;&#1475; &#1489; &#1512;&#1456;&#1488;&#1493;&#1468;&#1489;&#1461;&#1443;&#1503; &#1513;&#1473;&#1460;&#1502;&#1456;&#1506;&#1428;&#1493;&#1465;&#1503; &#1500;&#1461;&#1493;&#1460;&#1430;&#1497; &#1493;&#1460;&#1469;&#1497;&#1492;&#1493;&#1468;&#1491;&#1464;&#1469;&#1492;&#1475; &#1490; &#1497;&#1460;&#1513;&#1468;&#1474;&#1464;&#1513;&#1499;&#1464;&#1445;&#1512; &#1494;&#1456;&#1489;&#1493;&#1468;&#1500;&#1467;&#1430;&#1503; &#1493;&#1468;&#1489;&#1460;&#1504;&#1456;&#1497;&#1464;&#1502;&#1460;&#1469;&#1503;&#1475; &#1491; &#1491;&#1468;&#1464;&#1445;&#1503; &#1493;&#1456;&#1504;&#1463;&#1508;&#1456;&#1514;&#1468;&#1464;&#1500;&#1460;&#1430;&#1497; &#1490;&#1468;&#1464;&#1445;&#1491; &#1493;&#1456;&#1488;&#1464;&#1513;&#1473;&#1461;&#1469;&#1512;&#1475; &#1492; &#1493;&#1463;&#1469;&#1497;&#1456;&#1492;&#1460;&#1431;&#1497; &#1499;&#1468;&#1464;&#1500;&#1470;&#1504;&#1462;&#1435;&#1508;&#1462;&#1513;&#1473; &#1497;&#1465;&#1469;&#1510;&#1456;&#1488;&#1461;&#1445;&#1497; &#1497;&#1462;&#1469;&#1512;&#1462;&#1498;&#1456;&#1470;&#1497;&#1463;&#1506;&#1458;&#1511;&#1465;&#1430;&#1489; &#1513;&#1473;&#1460;&#1489;&#1456;&#1506;&#1460;&#1443;&#1497;&#1501; &#1504;&#1464;&#1425;&#1508;&#1462;&#1513;&#1473; &#1493;&#1456;&#1497;&#1493;&#1465;&#1505;&#1461;&#1430;&#1507; &#1492;&#1464;&#1497;&#1464;&#1445;&#1492; &#1489;&#1456;&#1502;&#1460;&#1510;&#1456;&#1512;&#1464;&#1469;&#1497;&#1460;&#1501;&#1475; &#1493; &#1493;&#1463;&#1497;&#1468;&#1464;&#1444;&#1502;&#1464;&#1514; &#1497;&#1493;&#1465;&#1505;&#1461;&#1507;&#1433; &#1493;&#1456;&#1499;&#1464;&#1500;&#1470;&#1488;&#1462;&#1495;&#1464;&#1428;&#1497;&#1493; &#1493;&#1456;&#1499;&#1465;&#1430;&#1500; &#1492;&#1463;&#1491;&#1468;&#1445;&#1493;&#1465;&#1512; &#1492;&#1463;&#1492;&#1469;&#1493;&#1468;&#1488;&#1475; &#1494; &#1493;&#1468;&#1489;&#1456;&#1504;&#1461;&#1443;&#1497; &#1497;&#1460;&#1513;&#1474;&#1456;&#1512;&#1464;&#1488;&#1461;&#1431;&#1500; &#1508;&#1468;&#1464;&#1512;&#1447;&#1493;&#1468; &#1493;&#1463;&#1469;&#1497;&#1468;&#1460;&#1513;&#1473;&#1456;&#1512;&#1456;&#1510;&#1435;&#1493;&#1468; &#1493;&#1463;&#1497;&#1468;&#1460;&#1512;&#1456;&#1489;&#1468;&#1445;&#1493;&#1468; &#1493;&#1463;&#1497;&#1468;&#1463;&#1469;&#1506;&#1463;&#1510;&#1456;&#1502;&#1430;&#1493;&#1468; &#1489;&#1468;&#1460;&#1502;&#1456;&#1488;&#1465;&#1443;&#1491; &#1502;&#1456;&#1488;&#1465;&#1425;&#1491; &#1493;&#1463;&#1514;&#1468;&#1460;&#1502;&#1468;&#1464;&#1500;&#1461;&#1445;&#1488; &#1492;&#1464;&#1488;&#1464;&#1430;&#1512;&#1462;&#1509; &#1488;&#1465;&#1514;&#1464;&#1469;&#1501;&#1475;
<p>
&#1431; = revii
<br />
&#1444; = mahpach / yetiv - yetiv if after the first consonant
<br />
&#1433; = pashta / kadma. pashta repeats if stressed early. otherwise pashta on last letter
<br />
&#1443; = munach
<br />
&#1428; = zakef katon
<br />
&#1429; = zakef gadol
<br />
&#1445; = mercha
<br />
&#1430; = tipcha
<br />
&#1425; = etnachta
<br />
&#1469; = silluq / early stress - can disambiguate via position in sentence
<br />
&#1475; = sof pasuk
<br />
&#1435; = tevir
<br />
&#1447; = darga	
<br />
&#1436; = geresh
<br />
&#1438; = gershayim
<br />
&#1454; = zarka
<br />
&#1426; = segolta
<br />
&#1440; = telisha ketana
<br />
&#1449; = telisha gedola
-->
</center>';
print "
<script>
    const dropArea = document.getElementById('drop-area');
    
	['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false)
    });
	
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
	
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, function(e) {
            //dropArea.style.backgroundColor = 'yellow';
            dropArea.classList.add('highlight');
        }, false)
    });
	
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, function(e) {
            //dropArea.style.backgroundColor = 'lightgreen';
            dropArea.classList.remove('highlight')
        }, false);
    });
	
    function drop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        window.files = files;
        // console.log(dt);
        // console.log(files);
		
        ([...files]).forEach(uploadFile);
		
        alert('DROPPED!');
    }
	
    function uploadFile(file) {
        console.log(file);
		
        let httpRequest = new XMLHttpRequest();
        httpRequest.open('POST', '/paste', true);
        httpRequest.send(file);
        httpRequest.onload = (event) => 
		{
			console.log('upload: onload event', httpRequest.responseText);
		// console.log('textarea', this.textarea);
        };
    }
    dropArea.addEventListener('drop', drop, false);
</script>";
print "
</body>
</html>";
?>
