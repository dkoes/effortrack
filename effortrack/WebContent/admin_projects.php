<?php 
// this is imported by admin.php, but check to make sure we admins to prevent
//accessing this page directly
isadmin();

//can add a project, remove a project, or import a whole list of projects
//this is also responsible for processign requests

$op = isset($_POST['operation']) ? $_POST['operation'] : '';

function addproj($db, $name) { //add project name to database db	
	$stmt = $db->prepare('INSERT INTO projects (name) VALUES (?)');
	$stmt->bind_param('s', $name);
	if (!$stmt->execute()) {
		if ($db->errno === 1062 /* ER_DUP_ENTRY */)
			echo("<h2>Project name $name already exists</h2>");
		else
			echo('<h2>Unspecified error adding project</h2>');
	}
	else {
		echo("<h3>Successfully added project $name</h3>");
	}
	$stmt->close();
}

//echo out options for a select dropdown of all available project names
function project_dropdown_options() {
	global $db_host, $db_user, $db_name;
	$db = new mysqli($db_host, $db_user, "", $db_name);
	$stmt = $db->prepare('SELECT name FROM projects');
	$stmt->execute();
	$stmt->store_result();
	$name = "";
	$stmt->bind_result($name);
	while ($stmt->fetch()) {
		printf ("<option value=\"%s\">%s</option>", $name, $name);
	}
	$stmt->close();
}

switch($op) {
	case "add":
		$name = htmlspecialchars($_POST['projectname']);
		$db = new mysqli($db_host, $db_user, "", $db_name);
		addproj($db, $name);
		break;
		
	case "remove":
		$name = htmlspecialchars($_POST['projectname']);
		$db = new mysqli($db_host, $db_user, "", $db_name);
		$stmt = $db->prepare('DELETE FROM projects WHERE name = ?');
		$stmt->bind_param('s', $name);
		$stmt->execute();
		if (!$stmt->execute()) {
			echo("<h2>Failed to remove $name</h2>");
		}
		else {
			echo("<h3>Successfully removed $name</h3>");
		}
		
		break;
		
	case "fileadd":
		
		if ($_FILES['projectfile']['error'] == UPLOAD_ERR_OK               //checks for errors
				&& is_uploaded_file($_FILES['projectfile']['tmp_name'])) { //checks that file is uploaded
			$db = new mysqli($db_host, $db_user, "", $db_name);						
			$file = fopen($_FILES['projectfile']['tmp_name'], "r");
			while(!feof($file)){
				$name = htmlspecialchars(trim(fgets($file))); //sanitize
				if(strlen($name) == 0) 
					continue; //skip empty strings			
				$db = new mysqli($db_host, $db_user, "", $db_name);
				addproj($db, $name);
			}
							
			fclose($file);	
		}
		else {
			echo("<h2>Failed to upload file</h2>");
			echo("Error: ".$_FILES['projectfile']['error']);
			echo("Name: ".$_FILES['projectfile']['tmp_name']);
		}
		
		break;
}

//having prcessed any previous requests, build list of existing projects

?>

<form action="admin.php" method="POST">
<input type="hidden" name="view" value="projects"> 
<input type="hidden" name="operation" value="add"> 
<fieldset>
  <legend>Add Project:</legend>
  Name: <input type="text" name="projectname"><br>
<input type="submit" value="Add">
 </fieldset>
</form>

<form action="admin.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="view" value="projects"> 
<input type="hidden" name="operation" value="fileadd"> 
<fieldset>
  <legend>Add Projects From File:</legend>
<input type="file" name="projectfile"><br>
File should be a text file with a single project name on each line.<br>
<input type="submit" value="Add">
 </fieldset>
</form>

<form action="admin.php" method="POST" >
<input type="hidden" name="view" value="projects"> 
<input type="hidden" name="operation" value="remove"> 
<fieldset>
  <legend>Remove Project:</legend>
  <select name="projectname">
  <?php 
  project_dropdown_options()
  ?>
  </select>
<input type="submit" value="Remove">
 </fieldset>
</form>

