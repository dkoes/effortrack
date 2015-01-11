<?php session_start();

require 'config.php';
/* The administrators portal.  Has sections for viewing/downloading reports,
 * adding/editing users, adding/removing projects, and setting password
 */



isadmin();
$admin = $_SESSION['userid'];
// determine what we should be doing
$op = "reports"; //default

if(isset($_REQUEST["view"])) {
	$op = $_REQUEST["view"];
}

require 'admin_header.php';

echo("<div class='admincontent'>");
//after header, custom content for each operation
switch ($op) {
	case "reports":		
		require 'admin_reports.php';
		break;
	
	case "employees":
		
		require 'admin_employees.php';
		break;
		
	case "projects":
		require 'admin_projects.php';
		
		break;
		
	case "password":
		require 'admin_password.php';
		break;
		
	case "changepass":
		//validate password submission
		//first, are the two passwords the same?
		$oldpass = $_POST['oldpass'];
		$newpass = $_POST['pass'];
		$newpass2 = $_POST['pass2'];
		
		if($newpass != $newpass2) {
			echo("<h2>Passwords aren't the same.  Try again</h2>");
			require 'admin_password.php';
		}
		else { //check old password
			$db = new mysqli($db_host, $db_user, "", $db_name);
			if (mysqli_connect_errno())
				fail('MySQL connect', mysqli_connect_error());
			
			$hasher = new PasswordHash($hash_cost_log2, $hash_portable);
			$newhash = $hasher->HashPassword($newpass);
				
			$checkadm = $db->prepare('SELECT password FROM admins WHERE userid =?');
			$checkadm->bind_param('s', $admin);
			$checkadm->execute();
			$checkadm->store_result();
			$hash = "";
			$checkadm->bind_result($hash);
			$checkadm->fetch();
			
			if(strlen($newhash) < 20) {
				echo("<h2>Error with new password</h2>");
				require 'admin_password.php';
			}
			else if ($hasher->CheckPassword($oldpass, $hash)) {
				//passwords are correct, do the change
				
				($stmt = $db->prepare('UPDATE admins set password=? where userid=?'));
				$stmt->bind_param('ss', $newhash, $admin);
				if(!$stmt->execute()) {
					echo("<h2>Error changing password.  This is probably not your fault.</h2>");
				}
				else {
					echo("<h3>Password changed successfully.</h3>");
				}
			}
			else { //old password wrong
				echo("<h2>Old password incorrect. Try again<h2>");
				require 'admin_password.php';
			}
		}
		
		break;
}

?>
</div>
</body></html>