<?php session_start();
require 'config.php';


if (!isset($_SESSION['userid']) || !isset($_SESSION['loggedIn']) || !isset($_SESSION['isadmin']) ||
		$_SESSION['loggedIn'] != true || $_SESSION['isadmin'] != false)
{
	//not logged in
	session_destroy();
	header("Location: index.php");
	exit();
}
$user = $_SESSION['userid'];

require 'effort_header.php';

if(isset($_POST['oldpass']) && isset($_POST['pass']) && isset($_POST['pass2'])) {
	//change the password
	$oldpass = $_POST['oldpass'];
	$newpass = $_POST['pass'];
	$newpass2 = $_POST['pass2'];
	
	if($newpass != $newpass2) {
		echo("<h2>Passwords aren't the same.  Try again</h2>");
	}
	else { //check old password
		$db = new mysqli($db_host, $db_user, "", $db_name);
			
		$hasher = new PasswordHash($hash_cost_log2, $hash_portable);
		$newhash = $hasher->HashPassword($newpass);
	
		$stmt = $db->prepare('SELECT password FROM employees WHERE userid =?');
		$stmt->bind_param('s', $user);
		$stmt->execute();
		$stmt->store_result();
		$hash = "";
		$stmt->bind_result($hash);
		$stmt->fetch();
			
		if(strlen($newhash) < 20) {
			echo("<h2>Error with new password</h2>");
		}
		else if ($hasher->CheckPassword($oldpass, $hash)) {
			//passwords are correct, do the change
	
			($stmt = $db->prepare('UPDATE employees set password=? where userid=?'));
			$stmt->bind_param('ss', $newhash, $user);
			if(!$stmt->execute()) {
				echo("<h2>Error changing password.  This is probably not your fault.</h2>");
			}
			else {
				echo("<h3>Password changed successfully. <a href='effort.php'>Continue</a></h3>");
			}
		}
		else { //old password wrong
			echo("<h2>Old password incorrect. Try again<h2>");
		}
	}	
	
}
?>

<form action="changepass.php" method="POST">
Old Password:<br>
<input type="password" name="oldpass" size="60"><br>
New Password:<br>
<input type="password" name="pass" size="60"><br>
Retype New Password:<br>
<input type="password" name="pass2" size="60"><br>
<input type="submit" value="Change Password">
</form>