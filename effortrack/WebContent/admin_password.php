<?php 
// this is imported by admin.php, but check to make sure we admins to prevent
//accessing this page directly
isadmin();

?>
<form action="admin.php" method="POST">
<input type="hidden" name="view" value="changepass">
Old Password:<br>
<input type="password" name="oldpass" size="60"><br>
New Password:<br>
<input type="password" name="pass" size="60"><br>
Retype New Password:<br>
<input type="password" name="pass2" size="60"><br>
<input type="submit" value="Change Password">
</form>