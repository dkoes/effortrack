<?php 
require 'PasswordHash.php';
$db_host = '127.0.0.1';
$db_user = 'effortuser';
$db_name = 'effortrack';

// Base-2 logarithm of the iteration count used for password stretching
$hash_cost_log2 = 9;
// Do we require the hashes to be portable to older systems (less secure)?
$hash_portable = FALSE;

// Are we debugging this code?  If enabled, OK to leak server setup details.
$debug = TRUE;

$hasher = new PasswordHash($hash_cost_log2, $hash_portable);

//how we present dates to the user
$dateformat = 'm-d-Y';

//shared code goes here
// make sure we are an administrator
function isadmin() {
	if (!isset($_SESSION['userid']) || !isset($_SESSION['loggedIn']) || !isset($_SESSION['isadmin']) ||
			$_SESSION['loggedIn'] != true || $_SESSION['isadmin'] != true)
	{
		//completely clear session information and try again
		// Unset all of the session variables.
		session_unset();
		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
			);
		}
		
		session_destroy();
		header("Location: index.php");
		exit();
	}
}

function isuser() {

	if (!isset($_SESSION['userid']) || !isset($_SESSION['loggedIn']) || !isset($_SESSION['isadmin']) ||
			$_SESSION['loggedIn'] != true || $_SESSION['isadmin'] != false)
	{
		// Unset all of the session variables.
		session_unset();
		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
			);
		}
		
		//not logged in
		session_destroy();
		header("Location: index.php");
		exit();
	}
}


function valid_username($name) {
	return preg_match('/^[a-zA-Z0-9_]{1,60}$/', $name);
}

function get_projects($db, $startdate, $enddate) {
	//get all projects for the requested time period, while we're at it sum up the
	//effort spent on each project
	$stmt = $db->prepare('SELECT project,SUM(effort) FROM effort WHERE week >= CAST(? AS DATE) AND week <= CAST(? AS DATE) GROUP BY project ORDER BY SUM(effort) DESC ');
	$stmt->bind_param('ss',$startdate, $enddate);
	$stmt->execute();
	$stmt->bind_result($project,$effort);
	$projectdata = [];
	while($stmt->fetch()) {
		$projectdata[] = [$project,$effort];
	}
	$stmt->close();
	return $projectdata;
}

function get_centers($db, $startdate, $enddate) {
	//now get all cost centers relevant to the date range
	$stmt = $db->prepare('SELECT DISTINCT(center) FROM effort WHERE week >= CAST(? AS DATE) AND week <= CAST(? AS DATE)  ORDER BY center');
	$stmt->bind_param('ss',$startdate, $enddate);
	$stmt->execute();
	$stmt->bind_result($center);
	$centers = [];
	while($stmt->fetch()) {
		$centers[] = $center;
	}
	$stmt->close();
	
	return $centers;
}

function get_center_totals_for_project($db, $startdate, $enddate, $project) {
	//get totals for al lcost centers
	$stmt = $db->prepare('SELECT center,SUM(effort) FROM effort WHERE week >= CAST(? AS DATE) AND week <= CAST(? AS DATE) AND project = ? GROUP BY center ORDER BY center');
	$stmt->bind_param('sss',$startdate, $enddate,$project);
	$stmt->execute();
	$stmt->bind_result($center,$amount);
	$values = [];
	while($stmt->fetch()) {
		$values[$center] = $amount;
	}
	$stmt->close();
	return $values;
}
?>