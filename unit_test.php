<?php
	$phpEx = 'php'; $root_path = "./";
	include $root_path.'trans.'.$phpEx;
	$sourcetext = 'מוֹל סוּף'; //$sourcetext = "&#1502;&#1493;&#1465;&#1500; &#1505;&#1493;&#1468;&#1507;"; // mol suf	
	$targetlang = "ashkenazic";
	print '<br><br> i.e. Source text: ' . $sourcetext . '.
	<br>See result above: <br>'. generateTransliteration($sourcetext, $targetlang, FALSE, TRUE);
?>
