<?php session_start();

require 'config.php';

isadmin();

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="employees.csv"'); 
header('Content-Transfer-Encoding: text');
header('Connection: Keep-Alive');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

if(!isset($_POST['startdate'])) {
	echo("Starting date mysteriously missing\n");
	exit();
}
if(!isset($_POST['enddate'])) {
	echo("Ending date mysteriously missing\n");
	exit();
}	

$startdate =  $_POST['startdate'];
$enddate = $_POST['enddate'];

$db = new mysqli($db_host, $db_user, "", $db_name);

if($_POST['operation'] == "collated") {
	$projectdata = get_projects($db, $startdate, $enddate);
	$centers = get_centers($db, $startdate, $enddate);
	
	//output header
	echo("ProjectName");
	foreach($centers as $c) {
		echo(",$c");
	}
	echo(",Total\n");
	
	foreach($projectdata as $p) {
		$project = $p[0];
		$total = $p[1];
	
		echo($project);
		$values = get_center_totals_for_project($db, $startdate, $enddate, $project);

		//now iterate over centers, if not present in sql date, output zero
		foreach($centers as $c) {
			$val = 0;
			if(array_key_exists($c, $values))
				$val = $values[$c];
			echo(",$val");
		}
		//finally, total
		echo(",$total\n");
	}
}
else { //output everything
	
	echo("Week,CostCenter,ProjectName,UserID,Effort\n");
	$stmt = $db->prepare('SELECT week,userid,center,project,effort FROM effort WHERE week >= CAST(? AS DATE) AND week <= CAST(? AS DATE) ORDER BY week DESC, center ASC, project ASC,  userid ASC');
	$stmt->bind_param('ss',$startdate, $enddate);
	$stmt->execute();
	$stmt->bind_result($week,$userid,$center,$project,$amount);
	while($stmt->fetch()) {
		printf("%s,%s,%s,%s,%s\n",$week, $center, $project, $userid,$amount);
	}
	$stmt->close();
}
?>