<?php 
/**
*
* @package Tranliterator
* @version $Id: sessions.php,v 0.10 2023/11/21 01:45:06 orynider Exp $
 * @license for this file @ http://www.gnu.org/licenses/gpl.html GNU General Public License (GPL)
*/

/** Level for disabled/banned accounts. */
define('BANNED', -1);

/** Level for Guest users (users who are not logged in). */
define('GUEST', 0);

/** Level for regular user accounts. */
define('USER', 1);

/** Level for moderator ("super user") accounts. */
define('MODERATOR', 2);

/** Level for Admin users. */
define('ADMIN', 3);

/** 
 * Minimum user level allowed to upload files.
 * Use the ADMIN, MODERATOR, USER, GUEST constants.
 * GUEST will allow non-logged-in users to upload.
 */
 
/**
EDIT Start Level to transliterate files.
*/
//define('LEVEL_TO_UPLOAD', ADMIN);
define('LEVEL_TO_UPLOAD', USER);

/*
EDIT Ends
* Do not edit bellow this line.
**/


/**
 * Reads information stored in files, where the key and data are separated by a tab.
 *
 * @author Borrowed from Justin Hagstrom <JustinHagstrom@yahoo.com>
 * @version 1.0.2 (January 13, 2005)
 * @package AutoIndex Bridge to Transliterate
 */
class Configuration implements Iterator
{
	/**
	 * @var array A list of all the settings
	 */
	private $configure;
	
	/**
	 * @var string The name of the file to read the settings from
	 */
	private $filename;
	
	//begin implementation of Iterator
	/**
	 * @var bool
	 */
	private $valid;
	
	/**
	 * @return string
	 */
	public function current()
	{
		return current($this->configure);
	}
	
	/**
	 * Increments the internal array pointer, and returns the new value.
	 *
	 * @return string
	 */
	public function next()
	{
		$t = next($this->configure);
		if ($t === false)
		{
			$this->valid = false;
		}
		return $t;
	}
	
	/**
	 * Sets the internal array pointer to the beginning.
	 */
	public function rewind()
	{
		reset($this->configure);
	}
	
	/**
	 * @return bool
	 */
	public function valid()
	{
		return $this->valid;
	}
	
	/**
	 * @return string
	 */
	public function key()
	{
		return key($this->configure);
	}
	//end implementation of Iterator
	
	/**
	 * @param string $line The line to test
	 * @return bool True if $line starts with characters that mean it is a comment
	 */
	public static function line_is_comment($line)
	{
		$line = trim($line);
		return (($line == '') || preg_match('@^(//|<\?|\?>|/\*|\*/|#)@', $line));
	}
	
	/**
	 * @param string $file The filename to read the data from
	 */
	public function __construct($file)
	{
		if ($file === false)
		{
			return;
		}
		
		$this->valid = true;
		$this->filename = $file;
		$contents = file($file);
		
		if ($contents === false)
		{
			die('Error reading file <em>' . self::html_output($file) . '</em>');
		}
		foreach ($contents as $i => $line)
		{
			$line = rtrim($line, "\r\n");
			if (self::line_is_comment($line))
			{
				continue;
			}
			$parts = explode("\t", $line, 2);
			if (count($parts) !== 2 || $parts[0] == '' || $parts[1] == '')
			{
				die('Incorrect format for file <em>' . self::html_output($file) . '</em> on line ' . ($i + 1) . '.<br />Format is "variable name[tab]value"');
			}
			if (isset($this->configure[$parts[0]]))
			{
				die('Error in <em>' . self::html_output($file) . '</em> on line ' . ($i + 1) . '.<br />' . self::html_output($parts[0]) . ' is already defined.');
			}
			$this->configure[$parts[0]] = $parts[1];
			$this->configure['url'] = 'index.php';
		}
	}
	
	/**
	 * $configure[$key] will be set to $info.
	 *
	* @param string $key
	 * @param string $info
	 */
	public function set($key, $info)
	{
		$this->configure[$key] = $info;
	}
		
	/**
	 * This will look for the key $item, and add one to the $info (assuming it is an integer).
	 *
	* @param string $item The key to look for
	 */
	public function add_one($item)
	{
		if ($this->is_set($item))
		{
			$h = fopen($this->filename, 'wb');
			
			if ($h === false)
			{
				die('Could not open file <em>' . self::html_output($this->filename) . '</em> for writing. Make sure PHP has write permission to this file.');
			}
			
			foreach ($this as $current_item => $count)
			{
				fwrite($h, "$current_item\t" . (($current_item == $item) ? ((int)$count + 1) : $count) . "\n");
			}
		}
		else
		{
			$h = fopen($this->filename, 'ab');			
			if ($h === false)
			{
				die('Could not open file <em>' . $this->filename . '</em> for writing.' . ' Make sure PHP has write permission to this file.');
			}
			fwrite($h, "$item\t1\n");
		}
		fclose($h);
	}
		
	/**
	* @param string $name The key to look for
	 * @return bool True if $name is set
	 */
	public function is_set($name)
	{
		return isset($this->configure[$name]);
	}
		
	/**
	 * @param string $name The key to look for
	* @return string The value $name points to
	*/
	public function __get($name)
	{
		if (isset($this->configure[$name]))
		{
			return $this->configure[$name];
		}
		trigger_error('Setting <em>' . self::html_output($name) . '</em> is missing in file <em>' . self::html_output($this->filename) . '</em>.');
	}
	
	/**
	 * @param string $path The directory name
	 * @return string If there is no slash at the end of $path, one will be added
	 */
	public function make_sure_slash($path)
	{
		$path = str_replace('\\', '/', $path);
		if (!preg_match('#/$#', $path))
		{
			$path .= '/';
		}
		return $path;
	}
	
	/**
	 * Returns the string with correct HTML entities so it can be displayed.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function html_output($str)
	{
		return htmlentities($str, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Sends the browser a header to redirect it to this URL.
	 */
	public function redirect()
	{
		$site = $this->url;
		header("Location: $site");
		trigger_error('Redirection header could not be sent.<br />' . "Continue here: <a href=\"$site\">$site</a>");
	}
}
/**
 * Stores a list of valid user accounts which are read from the user_list file.
 *
 * @author Justin Hagstrom <JustinHagstrom@yahoo.com>
 * @version 1.0.2 (July 21, 2004)
 * @package AutoIndex Bridge to Translator
 */
class Accounts implements Iterator
{
	/**
	 * @var array The list of valid accounts taken from the stored file
	 */
	private $userlist;
	
	/**
	 * @var int The size of the $userlist array
	 */
	private $list_count;
	
	//begin implementation of Iterator
	/**
	 * @var int $i is used to keep track of the current pointer inside the array when implementing Iterator
	 */
	private $i;
	
	/**
	 * @return User The current element in the array
	 */
	public function current()
	{
		if ($this->i < $this->list_count)
		{
			return $this->userlist[$this->i];
		}
		return false;
	}
	
	/**
	 * Increments the internal array pointer, then returns the user at that
	 * new position.
	 *
	 * @return User The current position of the pointer in the array
	 */
	public function next()
	{
		$this-> i++;
		return $this->current();
	}
	
	/**
	 * Sets the internal array pointer to 0.
	 */
	public function rewind()
	{
		$this->i = 0;
	}
	
	/**
	 * @return bool True if $i is a valid array index
	 */
	public function valid()
	{
		return ($this->i < $this->list_count);
	}
	
	/**
	 * @return int Returns $i, the key of the array
	 */
	public function key()
	{
		return $this->i;
	}
	//end implementation of Iterator
	
	/**
	 * Reads the user_list file, and fills the $contents array with the
	 * valid users.
	 */
	public function __construct()
	{
		global $configure;
		$filename = $configure->__get('user_list');
		$file = @file($filename);
		if ($file === false)
		{
			trigger_error('Cannot open user account file <em>' . Configuration::html_output($filename) . '</em>.');
		}
		$this->userlist = array();
		foreach ($file as $line_num => $line)
		{
			$line = rtrim($line, "\r\n");
			if (Configuration::line_is_comment($line))
			{
				continue;
			}
			$parts = explode("\t", $line);
			if (count($parts) !== 4)
			{
				die('Incorrect format for user accounts file on line ' . ($line_num + 1));
			}
			$this->userlist[] = new User($parts[0], $parts[1], $parts[2], $parts[3]);
		}
		$this->list_count = count($this->userlist);
		$this->i = 0;
	}
	
	/**
	 * @param string $name Username to find the level of
	 * @return int The level of the user
	 */
	public function get_level($name)
	{
		foreach ($this as $look)
		{
			if (strcasecmp($look->username, $name) !== 0)
			{
				continue;
			}
			$lev = (int)$look -> level;
			if ($lev < BANNED || $lev > ADMIN)
			{
				die('Invalid level for user: <em>'. Configuration::html_output($name).'</em>.');
			}
			return $lev;
		}
		die('User <em>' . Configuration::html_output($name) . '</em> does not exist.');
	}
	
	/**
	 * @param string $name Username to find the home directory for
	 * @return string The home directory of $name
	 */
	public function get_home_dir($name)
	{
		foreach ($this as $look)
		{
			if (strcasecmp($look->username, $name) === 0)
			{
				return $look->home_dir;
			}
		}
		die('User <em>'.Configuration::html_output($name).'</em> does not exist.');
	}
	
	/**
	 * Returns $name with the character case the same as it is in the accounts file.
	 *
	 * @param string $name Username to find the stored case of
	 * @return string
	 */
	public function get_stored_case($name)
	{
		foreach ($this as $look)
		{
			if (strcasecmp($look->username, $name) === 0)
			{
				return $look->username;
			}
		}
		die('User <em>'.Configuration::html_output($name) . '</em> does not exist.');
	}
	
	/**
	 * @param User $user The user to determine if it is valid or not
	 * @return bool True if the username and password are correct
	 */
	public function is_valid_user(User $user)
	{
		foreach ($this as $look)
		{
			if ($look->equals($user))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @param string $name Username to find if it exists or not
	 * @return bool True if a user exists with the username $name
	 */
	public function user_exists($name)
	{
		foreach ($this as $look)
		{
			if (strcasecmp($look->username, $name) === 0)
			{
				return true;
			}
		}
		return false;
	}
}
/**
 * Stores info about an individual user account, such as username and password.
 *
 * This class is basically just used for storing data (hence all variables are public).
*  Currently, each user has four properties: username, password, level, and home directory.
 *
 * @author Justin Hagstrom <JustinHagstrom@yahoo.com>
 * @version 1.0.1 (July 21, 2004)
 * @package AutoIndex Bridge to Transliterate
 */
class User
{
	/**
	 * @var string Username
	 */
	public $username;
	
	/**
	 * @var string The password, stored as a sha-1 hash of the actual password
	 */
	public $sha1_pass;
	
	/**
	 * @var int The user's level (use the GUEST USER ADMIN constants)
	 */
	public $level;
	
	/**
	 * @var string The user's home directory, or an empty string to use the default base_dir
	 */
	public $home_dir;
	
	/**
	 * @param User $user The user to compare to $this
	 * @return bool True if this user is equal to $user, based on username and password
	 */
	public function equals(User $user)
	{
		return ((strcasecmp($this->username, $user->username) === 0) && (strcasecmp($this->sha1_pass, $user->sha1_pass) === 0));
	}
	
	/**
	 * Since this is not an instance of UserLoggedIn, we know he is not
	 * logged in.
	 */
	public function logout()
	{
		die('You are not logged in.');
	}
	
	/**
	 * Here we display a login box rather than account options, since this is
	 * not an instance of UserLoggedIn.
	 *
	 * @return string The HTML text of the login box
	 */
	public function login_box()
	{
		global $subdir;
		return '
		<form action="' . Configuration::html_output($_SERVER['PHP_SELF']) . '?dir=' . (isset($subdir) ? rawurlencode($subdir) : '') . '" method="post">
		<div>
			<table>
			<tr class="textarea paragraph">
				<td>' . 'Username' . ':</td>
				<td><input type="text" name="username" />' . '</td>
			</tr>
			<tr class="paragraph">
				<td>' . 'Password' . ':</td>
				<td><input type="password" name="password" /></td>
			</tr>
			</table>
		</div>' . '
		<p><input class="icon pointer input" type="submit" value="' . 'Login' . '" /></p>
		</form>';
	}
	
	/**
	 * @param string $username Username
	 * @param string $sha1_pass Password as a sha-1 hash
	 * @param int $level User's level (use the GUEST, USER, MODERATOR, ADMIN constants)
	 * @param string $home_dir The home directory of the user, or blank for the default
	 */
	public function __construct($username = '', $sha1_pass = '', $level = GUEST, $home_dir = '')
	{
		$level = (int)$level;
		if (($level < BANNED || $level > ADMIN) || ($sha1_pass != '' && strlen($sha1_pass) !== 40))
		{
			print('Go to AutoIndex and Login again. Error: (for username "' . Configuration::html_output($username) . '").');
		}
		$this->sha1_pass = $sha1_pass;
		$this->username = $username;
		$this->level = $level;
		$this->home_dir = $home_dir;
	}
	
	/**
	 * @return string This string format is how it is stored in the user_list file
	 */
	public function __toString()
	{
		return $this->username . "\t" . $this->sha1_pass . "\t" . $this->level . "\t" . $this->home_dir . "\n";
	}
}

class UserLoggedIn extends User
{
	/**
	 * Since the user is already logged in, the account options will be displayed rather than a login box.
	 */
	public function login_box()
	{
		global $you, $subdir;
		return  '<p><a class="table3 translator" href="' . Configuration::html_output($_SERVER['PHP_SELF']) . '?dir=' . (isset($subdir) ?  rawurlencode($subdir) : '') . '&amp;logout=true">' . 'Logout' . ' [ ' . Configuration::html_output($this->username) . ' ]</a></p>';
	}
	
	/**
	 * Logs out the user by destroying the session data and refreshing the page.
	 */
	public function logout()
	{
		global $configure, $subdir;
		$this->level = GUEST;
		$this->sha1_pass = $this->username = '';
		session_unset();
		session_destroy();
		$home = Configuration::html_output($_SERVER['PHP_SELF']);
		$configure->redirect();
	}
	
	/**
	 * Validates username and password using the accounts stored in the user_list file.
	 *
	 * @param string $username The username to login
	 * @param string $sha1_pass The sha-1 hash of the password
	 */
	public function __construct($username, $sha1_pass)
	{
		parent::__construct($username, $sha1_pass);
		$accounts = new Accounts();
		if (!($accounts->is_valid_user($this)))
		{
			global $log;
			$log->add_entry("Invalid login (Username: $username)");
			
			session_unset();
			sleep(1);
			
			die('Invalid username or password.');
		}
		$this->level = $accounts->get_level($username);
		if ($this->level <= BANNED)
		{
			die('Your account has been disabled by the site admin.');
		}
		$this->username = $accounts->get_stored_case($username);
		$this->home_dir = $accounts->get_home_dir($username);
	}
}
/**
 * Allows information to be written to the log file.
 *
 * @author Justin Hagstrom <JustinHagstrom@yahoo.com>
 * @version 1.0.1 (July 21, 2004)
 * @package AutoIndex Bidge to Translator
 */
class Logging
{
	/**
	 * @var string Filename of the log to write to
	 */
	private $filename;
	
	/**
	 * @param string $filename The name of the log file
	 */
	public function __construct($filename)
	{
		$this->filename = $filename;
	}
	
	/**
	 * Writes data to the log file.
	 *
	 * @param string $extra Any additional data to add in the last column of the entry
	 */
	public function add_entry($extra = '')
	{
		if (LOG_FILE)
		{
			$h = @fopen($this->filename, 'ab');
			
			if ($h === false)
			{
				die('Could not open log file for writing.' . ' Make sure PHP has write permission to this file.');
			}
			global $dir, $ip, $host;
			
			$referrer = (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'N/A');
			fwrite($h, date(DATE_FORMAT) . "\t" . date('H:i:s') . "\t$ip\t$host\t$referrer\t$dir\t$extra\n");
			fclose($h);
		}
	}
	
	/**
	 * @param int $max_num_to_display
	 */
	public function display($max_num_to_display)
	{
		if (!@is_file($this->filename))
		{
			die('There are no entries in the log file.');
		}
		
		$file_array = @file($this->filename);
		
		if ($file_array === false)
		{
			die('Could not open log file for reading.');
		}
		
		$count_log = count($file_array);
		$num = (($max_num_to_display == 0) ? $count_log : min($max_num_to_display, $count_log));
		$out = "
		<p>Viewing $num (of $count_log) entries.</p>\n".'
		<table cellpadding="4">
		<tr class="autoindex_th">
			<th>#</th>
			<th>Date</th>
			<th>Time</th>
			<th>IP address</th>
			<th>Hostname</th>
			<th>Referrer</th>
			<th>Directory</th>
			<th>File downloaded or other info</th>
		</tr>';
		
		for ($i = 0; $i < $num; $i++)
		{
			$class = (($i % 2) ? 'dark_row' : 'light_row');
			$out .= '<tr><th style="border: 1px solid; border-color: #7F8FA9;" class="' . $class . '">' . ($i + 1) . '</th>';
			
			$parts = explode("\t", rtrim($file_array[$count_log-$i-1], "\r\n"), 7);
			
			if (count($parts) !== 7)
			{
				die('Incorrect format for log file on line ' . ($i + 1));
			}
			
			for ($j = 0; $j < 7; $j++)
			{
				$cell = Configuration::html_output($parts[$j]);
				if ($j === 4 && $cell != 'N/A')
				{
					$cell = "<a class=\"transliterate\" href=\"$cell\">$cell</a>";
				}
				$out .= '<td style="border: 1px solid; border-color: #7F8FA9;" class="' . $class . '">' . (($cell == '') ? '&nbsp;</td>' : "$cell</td>");
			}
			$out .= "</tr>\n";
		}
		$out .= '</table>
		<p><a class="transliterate" href="'. Configuration::html_output($_SERVER['PHP_SELF']) . '">'.'continue'.'.</a></p>';
	}
}
/*
* Session Bridge Code Starts here
* by orynider at github.com
**/

//Create a logging object:
$configure = new Configuration(CONF_SESSIONS);
$log = new Logging($configure->__get('log_file'));

//create a user object:
$log_login = false;

if (!empty($_POST['username']) && ($_POST['username'] != '') && ($_POST['password'] != ''))
{
	$you = new UserLoggedIn($_POST['username'], sha1($_POST['password']));
	$log_login = 'Successful login (Username: ' . $_POST['username'] . ')';
	define('USER_LEVEL', $you->level);
	$_SESSION['password'] = sha1($_POST['password']);
	unset($_POST['password']);
	$_SESSION['username'] = $_POST['username'];
	$log->add_entry('Successful login (Username: ' . $_POST['username'] . ')');

}
else if (!empty($_SESSION['username']))
{
	$you = new UserLoggedIn($_SESSION['username'], $_SESSION['password']);
	define('USER_LEVEL', $you->level);
	$log_login = '<p><a class="table3 translator" href="' . Configuration::html_output($_SERVER['PHP_SELF']) . '?dir=' . (isset($subdir) ?  rawurlencode($subdir) : '') . '&amp;logout=true">' . 'Logout' . ' [ ' . Configuration::html_output($_SESSION['username']) . ' ]</a></p>';

}
else
{
	$you = new User();
	define('USER_LEVEL', ANONYMOUS);
	$log_login = '<p>You must login to transliterate long files.</p>
			<div>
			<table class="table1" border="0" cellpadding="8" cellspacing="0">
				<tr class="paragraph">
					<td class="tabel2">'.$you->login_box().'</td>
				</tr>
			</table>
			</div>';
}
	
//set the logged in user's home directory:
$dir = $configure->make_sure_slash((($you->home_dir == '') ? $configure->__get('base_dir') : $you->home_dir));
$configure->set('base_dir', $dir);
$subdir = '';
	
	if (!empty($_GET['dir']))
	{
		$dir .= $configure->clean_input($_GET['dir']);
		$dir = $configure->make_sure_slash($dir);
		if (!is_dir($dir))
		{
			header('HTTP/1.0 404 Not Found');
			$_GET['dir'] = ''; //so the "continue" link will work
			die('The directory <em>' . Configuration::html_output($dir) . '</em> does not exist.');
		}
		$subdir = substr($dir, strlen($configure->__get('base_dir')));
		if (!empty($_GET['file']) && ($file = $_GET['file']))
		{
			while (preg_match('#\\\\|/$#', $file)) //remove all slashes from the end of the name
			{
				$file = substr($file, 0, -1);
			}
			$file = $configure->clean_input($file);
			if (!is_file($dir . $file))
			{
				header('HTTP/1.0 404 Not Found');
				die('The file <em>' . Configuration::html_output($file) . '</em> does not exist.');
			}
			if (!!empty($_SESSION['ref']) && (!!empty($_SERVER['HTTP_REFERER']) || stripos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) === false))
			{
				$log->add_entry('Leech Attempt');
				$self = $_SERVER['SERVER_NAME'] . Configuration::html_output($_SERVER['PHP_SELF']) . '?dir=' . Url::translate_uri($subdir);
				die('<h3>This PHP Script has an Anti-Leech feature turned on.</h3>' . ' <p>Make sure you are accessing this file directly from <a class="transliterate" href="http://' . $self . '">http://' . $self . '</a></p>');
			}
			$log->add_entry($file);
		}
	}
	
	if (!empty($_GET['logout']) && $_GET['logout'] == 'true')
	{
		$you->logout();
	}
