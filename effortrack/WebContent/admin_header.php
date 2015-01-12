<?php 
// header for administrator home page
?>
<html>
<head>
<title>EfforTrack Administration</title>
 <link rel="stylesheet" type="text/css" href="default.css">
  <script type="text/javascript" src="jquery-1.11.1.js"></script>

</head>
<body>
<div class="effortbanner">effortrack</div>

<div class="options">
<?php 
//style the active button differently
$style = $op == "reports" ? "activebutton" : "";
echo("<div class=\"topbutton $style\"><a href=\"admin.php?view=reports\">Summary</a></div>");
$style = $op == "employees" ? "activebutton" : "";
echo("<div class=\"topbutton $style\"><a href=\"admin.php?view=employees\">Employees</a></div>");
$style = $op == "projects" ? "activebutton" : "";
echo("<div class=\"topbutton $style\"><a href=\"admin.php?view=projects\">Projects</a></div>");
$style = ($op == "password" || $op == "changepass") ? "activebutton" : "";
echo("<div class=\"topbutton $style\"><a href=\"admin.php?view=password\">Password</a></div>");

?>
<div class="logout"><a href="logout.php">Logout</a></div>

</div> 