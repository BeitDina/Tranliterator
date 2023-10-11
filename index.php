<?php 
/**
*
* @package Tranliterator
* @version $Id: index.php,v 1.5 2023/10/11 10:04:08 orynider Exp $
*
*/
define('IN_PORTAL', 1);
$phpEx = substr(strrchr(__FILE__, '.'), true);
$root_path = "./";

include($root_path . 'trans.' . $phpEx);

?>
<html>
<head>
<title>Transliterate</title>
<style>
<!--
body{margin-right:1em;margin-left:1em;}
body,td,div,.p,a{font-family:arial,sans-serif}
 // -->
</style>
</head>
<body bgcolor=#ffffff text=#000000 link=#0000cc vlink=#551a8b alink=#ff0000>
<table cellspacing=2 cellpadding=0 border=0 width=99%>
<tr>
<td width=100% align=right>
<table cellspacing=0 cellpadding=0 border=0 width=100%>
<tr>
<td bgcolor=#3366cc colspan=2><img width=1 height=1 alt="" />
</td>
</tr>
<tr bgcolor=#E5ECF9>
<td><b>&nbsp;Transliterate</b></td>
<td align=right><font size=-1>&nbsp;|&nbsp;<a href="about.html">All About Hebrew Transliteration</a></font>
</td>
</tr>
</table>
</td>
</tr>
</table>


<table cellspacing=0 cellpadding=2 border=0 width=99%>
<tr bgcolor=#E6ECF9>
<td>
<table width=100% border=0 cellspacing=0 cellpadding=1>
<tr>
<td>
<form action="http://www.google.com/search">
<font size=-1>&nbsp;This text has been automatically transliterated from Hebrew:</font><br>&nbsp;&nbsp;
<textarea name=q rows=5 cols=45 wrap=PHYSICAL>
<?php generateTransliteration($_POST['sourcetext'], $_POST['targetlang'], strpos($_SERVER["HTTP_USER_AGENT"], "Firefox"), strpos($_SERVER["HTTP_USER_AGENT"], "Opera")); ?>
</textarea>&nbsp;&nbsp;
<input type=hidden name=hl value="en" />
<input type=hidden name=ie value="UTF8" />
<input type=hidden name=oe value="UTF8" />
<input type=submit value="Google Search" />
</form>
</td>
</tr>

<tr>
<td>
<table width=100% cellpadding=3 cellspacing=0 border=0>
<tr bgcolor=#ffffff>
<td>
<form action=index.php method=post>
<font size=-1>&nbsp;&nbsp;Transliterate text</font>
<br>&nbsp;&nbsp;
<textarea name=sourcetext rows=5 cols=45 wrap=PHYSICAL>
<?php	echo $origHebrew; ?>
</textarea>
<br>&nbsp;&nbsp;
<font size=-1>from Hebrew to</font>
<select name=targetlang selected="$_POST['targetlang']">
<option value="academic" <?php if($_POST['targetlang'] == 'academic') {echo("selected"); } ?> >Academic</option>
<option value="academic_u" <?php if($_POST['targetlang'] == 'academic_u'){ echo("selected"); } ?> >Academic Unicode</option>
<option value="academic_ff" <?php if($_POST['targetlang'] == 'academic_ff'){ echo("selected"); } ?> >Academic Font Friendly</option>
<option value="ashkenazic" <?php if($_POST['targetlang'] == 'ashkenazic'){ echo("selected"); } ?> >Ashkenazic</option>
<option value="sefardic" <?php if($_POST['targetlang'] == 'sefardic'){ echo("selected"); } ?> >Sefardic</option>
<option value="mc" <?php if($_POST['targetlang'] == 'mc'){ echo("selected");} ?> >Michigan - Claremont</option>
</select>
<input type=hidden name=hl value="en" />
<input type=hidden name=ie value="UTF8" />
<input type=submit value="Transliterate" />
</form>
</td>
</tr>

</table>
</td>
</tr>
</table>
</td>
</tr>
</table>

<p>Here is the first verse of Deuteronomy2, available <a href="http://mechon-mamre.org/p/pt/pt0201.htm">here at mechon-mamre</a>, 
which is in general a good place to get you Hebrew text for the purpose of 
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
<center>
<br>
<font size=-1>&copy;2006 Joshua Waxman & &copy;2023 Florin C. Bodin</font>
<p>
<!-- 
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
-->
</center>
</body>
</html>
