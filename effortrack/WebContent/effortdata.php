<?php session_start();

require 'config.php';

isuser();

$user = $_SESSION['userid'];

//check that user exists and get their current cost center
$db = new mysqli($db_host, $db_user, "", $db_name);
$stmt = $db->prepare('SELECT center FROM employees WHERE userid =?');
$stmt->bind_param('s', $user);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows != 1) {
	echo("Could not identify user $user");
	exit();
}
$center = "";
$stmt->bind_result($center);
$stmt->fetch(); //$center is set after this

$dateval = $_POST['date'];

//create php date object - dateformat is from config and is how it
//is presented to the user
$date = date_create_from_format($dateformat, $dateval);

//check day of week
$day = date_format($date,'N');
if($day != 7) { //not Sunday!
	echo("Illegal date specified (not a Sunday): $dateval");
	exit();
}

//however we show it to the user, need to use sql style
$week = date_format($date,'Y-m-d');

$values = json_decode($_POST['values']);

//remove all previous data for this user and week
$stmt = $db->prepare('DELETE FROM effort WHERE userid = ? AND week = ?');
$stmt->bind_param('ss', $user, $week);
$stmt->execute();

$stmt = $db->prepare('INSERT INTO effort (week, userid, effort, center, project) VALUES (?,?,?,?,?)');

$effort = 0;
$project = "";
$stmt->bind_param('sssss', $week, $user, $effort, $center, $project);

foreach($values as $pair) {
	$project = $pair->name;
	$effort = $pair->amount;
	if(!$stmt->execute()) {
		echo("Problem inserting data.");
		exit();
	}
}

echo("SUCCESS");

?>