<?php 
// this is imported by admin.php, but check to make sure we admins to prevent
//accessing this page directly
isadmin();


//can add an employee, add a list of employees, or remove an employee

$op = isset($_POST['operation']) ? $_POST['operation'] : '';

function adduser($db, $first, $last, $center, $userid, $password) { //check fields and add if valid
	global $hasher;
	
	if(strlen($first) == 0) {
		echo("<h3>First name missing.  Employee $first $last ($userid) not added.</h3>");
		return;
	}
	if(strlen($last) == 0) {
		echo("<h3>Last name missing.  Employee $first $last ($userid) not added.</h3>");
		return;
	}
	if(strlen($center) == 0) {
		echo("<h3>Cost center missing.  Employee $first $last ($userid) not added.</h3>");
		return;
	}
	
	if(strlen($userid) == 0 || !valid_username($userid)) {
		echo("<h3>UserID $userid invalid.  Employee $first $last ($userid) not added.</h3>");
		return;
	}
	
	$hash = $hasher->HashPassword($password);
	if (strlen($hash) < 20) {
		echo("<h3>Error with password for user $userid</h3>");
		return;
	}
	//adding will not modify			
	$stmt = $db->prepare('INSERT INTO employees (First, Last, center, userid, password) VALUES (?,?,?,?,?)');
	$stmt->bind_param('sssss', $first,$last,$center,$userid,$hash);
	if (!$stmt->execute()) {
		if ($db->errno === 1062 /* ER_DUP_ENTRY */)
			echo("<h2>User $userid already exists.  Cannot add.</h2>");
		else
			echo('<h2>Unspecified error adding employee</h2>');
	}
	else {
		echo("<h3>Successfully added $first $last ($userid)</h3>");
	}
	$stmt->close();
}

switch($op) {
	case "add": 
	case "modify":
		
		$first = htmlspecialchars(trim($_POST['firstname']));
		$last = htmlspecialchars(trim($_POST['lastname']));
		$center = htmlspecialchars(trim($_POST['center']));
		$userid = $_POST['user'];
		$password = $_POST['password'];
		
		if($op == "add") {
			$db = new mysqli($db_host, $db_user, "", $db_name);
			adduser($db, $first, $last, $center, $userid, $password);		
		}
		else if(strlen($userid) == 0) { //absent user with modify
			echo("<h3>Must provide userid to modify.</h3>");
		}
		else { //modify
		
			//does user exist?
			$db = new mysqli($db_host, $db_user, "", $db_name);
			$stmt = $db->prepare('SELECT * FROM employees WHERE userid = ?');
			$stmt->bind_param('s', $userid);
			$stmt->execute();
			$stmt->store_result();
			
			if($stmt->num_rows > 0) {
				$stmt->close();
				//can modify, see what is different
				if(strlen($first) > 0) { //change first name
					$stmt = $db->prepare('UPDATE employees SET First=? WHERE userid = ?');
					$stmt->bind_param('ss',$first,$userid);
					$stmt->execute();
					$stmt->close();
				}
				if(strlen($last) > 0) { //change last name
					$stmt = $db->prepare('UPDATE employees SET Last=? WHERE userid = ?');
					$stmt->bind_param('ss',$last,$userid);
					$stmt->execute();
					$stmt->close();
				}
				if(strlen($center) > 0) { //change center
					$stmt = $db->prepare('UPDATE employees SET center=? WHERE userid = ?');
					$stmt->bind_param('ss',$center,$userid);
					$stmt->execute();
					$stmt->close();
				}
				if(strlen($password) > 0) { //change password
					$hash = $hasher->HashPassword($password);
					if (strlen($hash) < 20) {
						echo("<h3>Error with password for user $userid</h3>");
						return;
					}
					$stmt = $db->prepare('UPDATE employees SET password=? WHERE userid = ?');
					$stmt->bind_param('ss',$hash,$userid);
					$stmt->execute();
					$stmt->close();
				}
				
			} else { //user doesn't exist
				$stmt->close();
				echo("<h3>User $userid does not exist.  Cannot modify (add first).</h3>");	
			}
		}
		break;
	case "remove":
		$userid = $_POST['useridremove'];
		$db = new mysqli($db_host, $db_user, "", $db_name);
		$stmt = $db->prepare('DELETE FROM employees WHERE userid = ?');
		$stmt->bind_param('s', $userid);
		$stmt->execute();
		if (!$stmt->execute()) {
			echo("<h2>Failed to remove $userid</h2>");
		}
		else {
			echo("<h3>Successfully removed $userid</h3>");
		}
		$stmt->close();
		break;
		
	case "fileadd":		
		if ($_FILES['userfile']['error'] == UPLOAD_ERR_OK               //checks for errors
				&& is_uploaded_file($_FILES['userfile']['tmp_name'])) { //checks that file is uploaded
			$db = new mysqli($db_host, $db_user, "", $db_name);						
			$file = fopen($_FILES['userfile']['tmp_name'], "r");
			while(!feof($file)){
				$line = trim(fgets($file));
				if(strlen($line) == 0) { //ignore blanks
					continue;
				}
				$vals = split(',',$line);
				if(sizeof($vals) != 5) {
					echo("<h2>Cannot parse line: $line</h2>");
					continue;
				}
				else {
					list($first, $last, $center, $userid, $pass) = $vals;
					$first = htmlspecialchars(trim($first));
					$last = htmlspecialchars(trim($last));
					$center = htmlspecialchars(trim($center));
					$userid = trim($userid);
					$pass = trim($pass); //no spaces in pass
					adduser($db, $first, $last, $center, $userid, $pass);
				}
			}
							
			fclose($file);	
		}
		else {
			echo("<h2>Failed to upload file</h2>");
			echo("Error: ".$_FILES['userfile']['error']);
			echo("Name: ".$_FILES['userfile']['tmp_name']);
		}
		
		break;
}


?>

<form action="admin.php" method="POST">
<input type="hidden" name="view" value="employees"> 
<fieldset>
  <legend>Add/Change Employee:</legend>
  
 <select name="operation">
 <option value="add">Add - Create new user with all fields</option>
 <option value="modify">Modify - Update existing user with only non-blank fields</option>
 </select><br>
  First Name: <input type="text" name="firstname"><br>
  Last Name: <input type="text" name="lastname"><br>
  Cost Center: <input type="text" name="center"><br>  
  UserID: <input type="text" name="user"><br>  
  Password: <input type="password" name="password"><br>
  <input type="submit" value="Add/Modify">
  <br>
  </fieldset>
</form>

<form action="admin.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="view" value="employees"> 
<input type="hidden" name="operation" value="fileadd"> 
<fieldset>
  <legend>Add From File:</legend>
<input type="file" name="userfile"><br>
File must be a text file with a single employee record on each line.
Fields must be comma separated and in the order:<br>
First Name, Last Name, Cost Center, UserID, Password
<br>
<input type="submit" value="Add">
 </fieldset>
</form>

<form action="admin.php" method="POST" >
<input type="hidden" name="view" value="employees"> 
<input type="hidden" name="operation" value="remove"> 
<fieldset>
  <legend>Remove Employee:</legend>
  <select name="useridremove">
  <?php 
	$db = new mysqli($db_host, $db_user, "", $db_name);
	$stmt = $db->prepare('SELECT userid,First,Last FROM employees');
	$stmt->execute();
	$stmt->store_result();
	$userid = ""; $first = ""; $last = "";
	$stmt->bind_result($userid, $first, $last);
	while ($stmt->fetch()) {
        printf ("<option value=\"%s\">%s - %s %s</option>", $userid, $userid, $first, $last);
    }
	$stmt->close();
  ?>
  </select>
<input type="submit" value="Remove">
 </fieldset>
</form>

<form action="admin_getemployees.php" method="POST" >
<fieldset>
  <legend>Download:</legend>
<input type="submit" value="Download CSV...">
 </fieldset>
</form>

