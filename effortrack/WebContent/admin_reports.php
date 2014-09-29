<?php 
// this is imported by admin.php, but check to make sure we admins to prevent
//accessing this page directly
isadmin();

//get all the available dates
$db = new mysqli($db_host, $db_user, "", $db_name);
$stmt = $db->prepare('SELECT DISTINCT(week) FROM effort ORDER BY week DESC');
$stmt->execute();
$allweeks = [];
$week = "";
$stmt->bind_result($week);
while ($stmt->fetch()) {
	$allweeks[] = $week;
}
$stmt->close();
if(count($allweeks) == 0) {
	echo("<h2>No data yet entered</h2>");
	exit();
}
$enddate = $allweeks[0]; 
$startdate = $allweeks[ min(count($allweeks)-1, 3)]; //default to a range of four weeks

if(isset($_POST['startdate']))
	$startdate =  $_POST['startdate'];
if(isset($_POST['enddate']))
	$enddate = $_POST['enddate'];


$projectdata = get_projects($db, $startdate, $enddate);
$centers = get_centers($db, $startdate, $enddate);


function week_dropdown_options($weeks, $selected) {
	//output option statments for a select consisting of all available weeks
	foreach($weeks as $w) {
		if($w == $selected)
			printf ("<option value=\"%s\" selected>%s</option>", $w, $w);
		else
			printf ("<option value=\"%s\">%s</option>", $w, $w);		
	}
}
?>
<div class='collatedresults'>
<form action="admin.php" method="POST">
<input type="hidden" name="view" value="reports"> 
<select name="startdate">
<?php 
week_dropdown_options($allweeks, $startdate);
?>
</select>
<select name="enddate">
<?php 
week_dropdown_options($allweeks, $enddate);
?>
</select>
<input type="submit" value="Recalculate">
</form>

<table class="collatedtable">
<thead>
<th class="projecthead"></th>
<?php 
//make a table with a row for each project and a column for each cost center
//first print out cost centers
foreach($centers as $c) {
	echo("<th>$c</th>\n");
}
?>
<th class="totalhead">Total</th></thead>
<?php 
foreach($projectdata as $p) {
	$project = $p[0];
	$total = $p[1];
	
	echo("<tr>");
	echo("<td class='projectname'>$project</td>");
	$values = get_center_totals_for_project($db, $startdate, $enddate, $project);
	
	//now iterate over centers, if not present in sql date, output zero
	foreach($centers as $c) {
		$val = 0;
		if(array_key_exists($c, $values))
			$val = $values[$c];
		echo("<td>$val</td>");
	}
	//finally, total
	echo("<td class='totalvalue'>$total</td>");
	echo("</tr>\n");
}
?>
</table>
</div>

<div class='downloadcollated'>
<form action="admin_download.php" method="POST">
<input type="hidden" name="operation" value="collated"> 
<select name="startdate">
<?php 
week_dropdown_options($allweeks, $startdate);
?>
</select>
<select name="enddate">
<?php 
week_dropdown_options($allweeks, $enddate);
?>
</select>
<input type="submit" value="Download Results by Cost Center...">
</form>
</div>

<div class='downloadall'>
<form action="admin_download.php" method="POST">
<input type="hidden" name="operation" value="all"> 
<select name="startdate">
<?php 
week_dropdown_options($allweeks, $startdate);
?>
</select>
<select name="enddate">
<?php 
week_dropdown_options($allweeks, $enddate);
?>
</select>
<input type="submit" value="Download All Results...">
</form>
</div>
