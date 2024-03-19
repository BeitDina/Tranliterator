# transliterate

This is a Hebrew transliteration program, written in PHP, by Josh Waxman.

Transliterator is a mechanism offered as-is to support users for the purpose of transliterating from Hebrew Alphabet into other alphabets.

After transliteration anyone might be able to read and pronounce the Hebrew words in his own alphabet.

Demo: http://beitdina.net/heb

Version Change:
* Added the .htpasswd.autoindex file in November 19, 2023 as a copy from AutoIndex with users and passwords.
This version is the default having user "admin" and password "admin", and user "user" and password "user".
You can copy or upload your own file from AutoIndex.
* Added CODE_OF_CONDUCT.md file in November 6, 2023
* Updated this README.md file in November 5, 2023 with Demo Link.
* Added SECURITY.md file in November 6, 2023
* Added VERSION file in November 6, 2023
* Added contants.php file in November 19, 2023
* Added index.css put as set appart file in November 19, 2023
* Update index.php to look for sessions.php in November 19, 2023
* Added optional files to bridge with AutoIndex in November 19, 2023
* Added sessions.php that is required after we upload tranliterate.conf.php and ignored if this file is deleted.
* Added trans.cmd in October 13, 2023
* Updated trans.php for PHP 7.4 and added new functions.
* Updated trans.php for PHP 8.2.7
