<?php
/* Login page.  TODO - style */

?>
<html>
<head>
<title>EfforTrack Login</title>
 <link rel="stylesheet" type="text/css" href="default.css">
</head>
<body>
<form action="login.php" method="POST">
<input type="hidden" name="op" value="login">
Username:<br>
<input type="text" name="user" size="60"><br>
Password:<br>
<input type="password" name="pass" size="60"><br>
<input type="submit" value="Log in">
</form>
</body>
</html>