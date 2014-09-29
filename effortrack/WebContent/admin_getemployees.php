<?php session_start();

require 'config.php';
/* The administrators portal.  Has sections for viewing/downloading reports,
 * adding/editing users, adding/removing projects, and setting password
 */



isadmin();

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="employees.csv"'); 
header('Content-Transfer-Encoding: text');
header('Connection: Keep-Alive');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

$db = new mysqli($db_host, $db_user, "", $db_name);
$stmt = $db->prepare('SELECT userid,First,Last,center FROM employees');
$stmt->execute();
$stmt->store_result();
$userid = ""; $first = ""; $last = ""; $center = "";
$stmt->bind_result($userid, $first, $last, $center);
while ($stmt->fetch()) {
	printf ("%s,%s,%s,%s\n", $userid, $first, $last, $center);
}
$stmt->close();

exit();
?>