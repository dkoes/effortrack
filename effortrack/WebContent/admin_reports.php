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
<div class="admincontentblock">
<fieldset class='collatedresults'>
  <legend>Summary:</legend>
<form action="admin.php" method="POST">
<input type="hidden" name="view" value="reports"> 
<label>Start Date:</label>
<select name="startdate">
<?php 
week_dropdown_options($allweeks, $startdate);
?>
</select>
&nbsp;<label>End Date:</label>
<select name="enddate">
<?php 
week_dropdown_options($allweeks, $enddate);
?>
</select>
<input type="submit" value="Recalculate">
</form>

<table class="collatedtable">
<thead>
<?php 
//make a header key
echo('<tr><th class="projecthead"></th>');
$numcenters = count($centers);
echo("<th colspan=\"$numcenters\">Cost Centers (Percent Effort Units)</th><th></th></tr>");		

//make a table with a row for each project and a column for each cost center
//first print out cost centers
echo('<tr><th class="projecthead">Project</th>');
foreach($centers as $c) {
	echo("<th>$c</th>\n");
}
?>
<th class="totalhead">Total</th></tr></thead>
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

<div class="pieChart">

<canvas id="pieChart" width="250" height="250"></canvas>
<div id="pieLegend"></div>
</div>
</fieldset>
</div>

<div class="admincontentblock">
<fieldset class='downloadcollated'>
  <legend>Download Collated:</legend>
<form action="admin_download.php" method="POST">
<input type="hidden" name="operation" value="collated"> 
<label>Start Date:</label><select name="startdate">
<?php 
week_dropdown_options($allweeks, $startdate);
?>
</select>
&nbsp;<label>End Date:</label><select name="enddate">
<?php 
week_dropdown_options($allweeks, $enddate);
?>
</select>
<input type="submit" value="Download by Cost Center...">
</form>
</fieldset>
</div>

<div class="admincontentblock">
<fieldset class='downloadall'>
  <legend>Full Download:</legend>
<form action="admin_download.php" method="POST">
<input type="hidden" name="operation" value="all"> 
<label>Start Date:</label><select name="startdate">
<?php 
week_dropdown_options($allweeks, $startdate);
?>
</select>
&nbsp;<label>End Date:</label><select name="enddate">
<?php 
week_dropdown_options($allweeks, $enddate);
?>
</select>
<input type="submit" value="Download All Data...">
</form>
</fieldset>
</div>

<script src="Chart.js/Chart.min.js"></script>
<script>


$( document ).ready(function() {

	//convert a number to a two digit hexadecimal string
	//it is amazing that javascript doesn't have native support for this..
	var tohexString = function(val) {
		return ("00"+Math.floor(val).toString(16)).substr(-2);
	}
	//return a color for val which in the range [0,max)
	var colorFromNth = function(val, max) {
		var lo = 0.0, hi = max;
		if(typeof(val) == "undefined")
			return "#ffffff";
		if(val < lo) val = lo;
		if(val > hi) val = hi;
		var scale = (val-lo)/(hi-lo);
		  
		var h = -(scale+0.5);
	
		console.log("h "+h);
		var r = Math.sin(Math.PI*h);
		r = r*r*255;
		var g = Math.sin(Math.PI*(h+1/3.0));
		g = g*g*255;
		var b = Math.sin(Math.PI*(h+2/3.0));
		b = b*b*255;

		var hex = "#";
		hex += tohexString(r);
		hex += tohexString(g);
		hex += tohexString(b);
		
		console.log("h "+h+" r "+r+" g "+g+" b "+b+" hex "+hex);
		return hex;
	};
	
<?php 
printf("var projecttotals = %s;\n",json_encode($projectdata));
?>	
	var ctx = $("#pieChart").get(0).getContext("2d");
	var piedata = [];
	for(var i = 0, n = projecttotals.length; i < n; i++)
	{
		var name = projecttotals[i][0];
		var val = parseInt(projecttotals[i][1]);
		piedata.push({value: val, label: name, color: colorFromNth(i,n) });
	}
	var pie = new Chart(ctx).Pie(piedata,{
		legendTemplate: "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li style=\"color:<%=segments[i].fillColor%>\"><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>"
		});
	$('#pieLegend').html(pie.generateLegend());
});
</script>
