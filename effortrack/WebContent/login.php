<?php  session_start();
/* Process login.  Heavily influenced by phpass article
 *  */

require 'config.php';

function fail($pub, $pvt = '')
{
	global $debug;
	$msg = $pub;
	if ($debug && $pvt !== '')
		$msg .= ": $pvt";
	/* The $pvt debugging messages may contain characters that would need to be
	 * quoted if we were producing HTML output, like we would be in a real app,
	 * but we're using text/plain here.  Also, $debug is meant to be disabled on
	 * a "production install" to avoid leaking server setup details. */
	//dkoes - try to only fail before generating html header..
	header('Content-Type: text/plain');
	
	exit("An error occurred ($msg).\n");
}

//produce html with error message
function failhtml($msg)
{
	echo("<html><title>Error</title><body>");
	printf("<h1>%s<h1>", $msg);
	echo("</body></html>");
	exit();
}


$user = $_POST['user'];
/* Sanity-check the username, don't rely on our use of prepared statements
 * alone to prevent attacks on the SQL server via malicious usernames. */
if (!valid_username($user))
	fail('Invalid username');

$pass = $_POST['pass'];
/* Don't let them spend more of our CPU time than we were willing to.
 * Besides, bcrypt happens to use the first 72 characters only anyway. */
if (strlen($pass) > 72)
	fail('The supplied password is too long');

$db = new mysqli($db_host, $db_user, "", $db_name);
if (mysqli_connect_errno())
	fail('MySQL connect', mysqli_connect_error());

/* BEGIN silly admin setup
 * first check to see if admin table is empty, if it is, create an admin
 * user with password 'changeme'
 * Yeah, this is iffy, but I think it is the easiest way to set things up.
 * Feel free to remove it after the admin account is setup.
 */
 
($admcnt = $db->prepare('select count(*) from admins'))
	|| fail('MySQL prepare', $db->error);
if (!$admcnt->execute()) {
	fail('Could not count admins');	
}
$cnt = 0;
$admcnt->bind_result($cnt)
|| fail('MySQL bind_result', $db->error);
if (!$admcnt->fetch() && $db->errno)
	fail('MySQL fetch', $db->error);
$admcnt->close();

if($cnt == 0) {
	$hash = $hasher->HashPassword("changeme");
	$db->query("INSERT INTO admins (userid, password) VALUES ('admin','$hash')") || 
		fail('Could not create first admin',$db->error);
}

/* END silly admin setup */

$user = $_POST['user'];
/* Sanity-check the username, don't rely on our use of prepared statements
 * alone to prevent attacks on the SQL server via malicious usernames. */
if (!preg_match('/^[a-zA-Z0-9_]{1,60}$/', $user))
	fail('Invalid username. Letters/numbers only please.');

$pass = $_POST['pass'];
/* Don't let them spend more of our CPU time than we were willing to.
 * Besides, bcrypt happens to use the first 72 characters only anyway. */
if (strlen($pass) > 72)
	fail('The supplied password is too long.  I\'m going to ignore characters after 72 so save yourself some typing');

//check for ADMIN user

($checkadm = $db->prepare('SELECT password FROM admins WHERE userid =?')) ||
	fail('MySQL prepare', $db->error);

$checkadm->bind_param('s', $user) || fail('Bind', $db->error);
$checkadm->execute();
$checkadm->store_result();
if($checkadm->num_rows > 0) { //have admin username
	$hash = "";
	$checkadm->bind_result($hash) || fail('Bind result', $db->error);
	if (!$checkadm->fetch() && $db->errno)
		fail('MySQL fetch', $db->error);
	
	if ($hasher->CheckPassword($pass, $hash)) {
		session_regenerate_id();
		$_SESSION['userid']  = $user;
		$_SESSION['loggedIn'] = true;
		$_SESSION['isadmin'] = true;
		session_write_close();
		header("location:admin.php");
		exit();
		
	} else {
		failhtml("Invalid password for $user");
	}

} else { // employee user
	$checkadm->close();
	
	($stmt = $db->prepare('SELECT password FROM employees WHERE userid=?')) ||
		fail('Prepare employees', $db->error);
	$stmt->bind_param('s', $user) || fail('Bind employee', $db->error);
	$stmt->execute();
	$stmt->store_result();
	if($stmt->num_rows > 0) { //have valid username
		$hash = "";
		$stmt->bind_result($hash) || fail('Bind emp', $db->error);
		if(!$stmt->fetch() && $db->errno)
			fail('Fetch emp', $db->error);
		
		if ($hasher->CheckPassword($pass, $hash)) {
			session_regenerate_id();
			$_SESSION['userid']  = $user;
			$_SESSION['loggedIn'] = true;
			$_SESSION['isadmin'] = false;
			session_write_close();
			header("location:effort.php");
			exit();
		
		} else {
			failhtml("Invalid password for $user");
		}
		
	} else {
		failhtml("Invalid user");
	}
	
}
?>
