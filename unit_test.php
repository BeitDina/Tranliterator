<?php
	include 'trans.php';
	//$sourcetext = "מוֹל סוּף";
	$sourcetext = "&#1502;&#1493;&#1465;&#1500; &#1505;&#1493;&#1468;&#1507;"; // mol suf	
	$targetlang = "ashkenazic";
	echo generateTransliteration($sourcetext, $targetlang, FALSE, TRUE);
	
?>