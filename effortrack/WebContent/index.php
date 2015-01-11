<?php
/* Login page.  TODO - style */

?>
<html>
<head>
<title>EfforTrack Login</title>
 <link rel="stylesheet" type="text/css" href="default.css">
</head>
<body>
<div class="loginpage">
<div class="effortbanner">
    effortrack
</div>

<div class="loginbox">
<form action="login.php" method="POST">
<input type="hidden" name="op" value="login">
Username:<br>
<input type="text" autofocus="autofocus" name="user" size="60"><br>
Password:<br>
<input type="password" name="pass" size="60"><br>
<input type="submit" value="Log in">
</form>
</div>
</div>
</body>
</html>