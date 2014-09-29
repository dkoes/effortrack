<?php session_start();

require 'config.php';

require 'effort_header.php';

$N = 4; //number of weeks worth of effort to fetch

isuser();
$user = $_SESSION['userid'];

echo("<div id=\"effortcontainer\"></div>"); //this will be populated by javascript
//get all possible projects
$projects = array();
$db = new mysqli($db_host, $db_user, "", $db_name);
$stmt = $db->prepare('SELECT name FROM projects');
$stmt->execute();
$stmt->store_result();
$name = "";
$stmt->bind_result($name);
while ($stmt->fetch()) {
	$projects[] = $name; //pushing onto array
}
$stmt->close();

//export data as json javascript objects
echo("<script>");
echo("var projects = ".json_encode($projects).";\n");

//first figure out the current week (ie, sunday's date)
$timestamp = time();
$day = date("N",$timestamp);

$date = date_create(date("Y-m-d",$timestamp)); //Ymd is php standard, this basically rounds down to day
$sunday = $date;
if($day < 7) { //today isn't sunday
	$sunday = date_sub($date, date_interval_create_from_date_string($day.' days'));
}

//make weeks array of all the dates we're going to look for
//dateformat is defined in config.ph since effortdata.php uses it
$weekdates = array($sunday);
for($i = 1; $i < $N; $i++) {
	$weekdates[$i] = clone $weekdates[$i-1];
	//date_sub modifies, so set to previous week
	date_sub($weekdates[$i], date_interval_create_from_date_string('7 days'));
}

//for each of the N weeks, get all the effort reported by this user
//we will put this all into a javascript array and then input validation
//will be done client side in javascript
$data = [];
$stmt = $db->prepare('SELECT project,effort FROM effort WHERE userid = ? AND week = ?');
$sqlweek = "";
$stmt->bind_param('ss',$user,$sqlweek);

foreach($weekdates as $weekdate) {
	$userweek = date_format($weekdate,$dateformat); //for showing to user
	$sqlweek =  date_format($weekdate,'Y-m-d'); //for querying sql
	$stmt->execute();
	$stmt->store_result();
	$project = ""; $effort = 0;
	$stmt->bind_result($project, $effort);
	$effortlist = [];
	while($stmt->fetch()) {
		$effortlist[] = [$project,$effort];//order matters so use nested arrays instead of dict
	}
	$data[] = [$userweek, $effortlist];
}
echo("var effortdata = ".json_encode($data).";\n");
echo("</script>\n")

?>

<script>

$( document ).ready(function() {

	var addWeek = function(date, effort, preveffort) {
		//the function does all the work, it creates a div for a week's worth
		//of effort that has the ability to add/remove projects, import data
		//from previous week, and submit changes
		//has event handlers to do all data consistency checks on the client
		
		var inputbox = null; //where all the action happens
		var submitbutton = null; //send to server
		var message = null; //communicate errors
		var cnt = 0; //for uniquely label
		
		//event handlers
		
		var extractData = function() {
			//for each singleeffort line, extract the project name and effort amount 
			//and return in a list of objects
			var combined = {}; //merge same projects
			$(inputbox).children('.singleeffort').each(function() {
				//this is a single effort div
				var projname = $(this).children('.effortchooser').first().val();
				if(projname == null) projname = ""; //nothing selected, don't convert to "null" since a sick and twisted individual could make that a project name
				var amount = parseInt($(this).children('.effortamount').first().val());
				if(typeof(combined[projname]) == "undefined") {
					combined[projname] = amount;
				}
				else {
					combined[projname] += amount;
				}
			});

			//convert to array of pairs
			var ret = [];			
			for (var name in combined) {
			    if (combined.hasOwnProperty(name)) {
			      ret.push({name: name, amount: combined[name]});  
			    }
			}
			return ret;
		};
		
		//checks input values, sets message and enablement of submit appropriately
		var updateStatus = function () {
			var data = extractData();
			var total = 0;
			var okay = 1;
			for(var i = 0; i < data.length; i++)
			{
				var d = data[i];
				total += d.amount;
				if(d.name == "" || d.name == null) {
					okay = 0;
				}
			}

			//set message
			$(message).empty();
			if(total != 100) { //doesn't add to 100%
				$(submitbutton).prop("disabled",true);
				
				if(total == 0 && preveffort != null && preveffort.length > 0) {
					//nothing here, but something pervious, offer to copy
					var copy = $('<input type="button" value="Copy From Previous Week" />');
					copy.appendTo(message).click(function() {
						//click handler for copying preveffort
						for(var i = 0; i < preveffort.length; i++) {
							addProjectChooser(preveffort[i][0],preveffort[i][1],true);
						}
						updateStatus();
					});
				}
				else {
					$(message).text("Total effort ("+total+") does not equal 100.");
				}
			}
			else if(!okay) { //empty project name
				$(submitbutton).prop("disabled",true);
				$(message).text("Missing project name.");
			} else {
				$(submitbutton).prop("disabled",false);				
			}
		};
		
		var addProjectChooser  = function(projectname, effortamount, setchanged) { 
			//add a remove button, project dropdown and a text entry box
			var singleproj = document.createElement('div');
			$(singleproj).addClass('singleeffort').appendTo(inputbox);

			var remove = $("<input type = 'button' value = 'Remove'>");
			remove.click(function() { //remove singleproj
				$(singleproj).remove();
				updateStatus();
			}
			);
			remove.addClass('effortremove').appendTo(singleproj);

			//now dropdown
			var s = $("<select name=\"projectname\" />");
			for(var i = 0; i < projects.length; i++) {
				var n = projects[i];
    			$("<option />", {value: n, text: n}).appendTo(s);
			}
			s.addClass('effortchooser').appendTo(singleproj);

			//if project name as provided, set
			if(typeof(projectname) != "undefined" && projectname != null) {
				s.val(projectname);
			}
			s.change(updateStatus);
			
			//text entry, let's try some shiny HTML5 
			var text = $("<input name='effortamount' type='number' min='0' max='100' defaultvalue='0' />");
			text.val(0);
			text.addClass('effortamount').appendTo(singleproj);

			//if amount was passed, set
			if(typeof(effortamount) != "undefined" && effortamount != null) {
				text.val(effortamount);
				if(setchanged) //for when copying, isn't what's on the server
					text.addClass("changedvalue"); 
			}
			else {
				text.addClass("changedvalue"); //it is different from the server
			}
			
			text.on('input',function() { 
				//anything that gets changed needs to be styledas such
				text.addClass("changedvalue");
				updateStatus(); 
			});

			updateStatus();
			
		};


		//main code

		var bigcontainer = $('#effortcontainer');
		var container = document.createElement('div');
		$(container).addClass('effortbox').appendTo(bigcontainer);

        var title = document.createElement('div');
        $(title).addClass('efforttitle').appendTo(container);

        var titlestr = "Effort for week starting " + date;        
        $(title).text(titlestr);
        
		var message = document.createElement('div'); //for displaying informative message
		$(message).addClass('effortmsg').appendTo(container);

		//div that contains project pulldowns and effort entry
		var inputbox = document.createElement('div');
		$(inputbox).addClass('inputbox').appendTo(container);

		var addprojbutton = $("<input type = 'button' value='Add Project' />");
		addprojbutton.addClass('addprojbutton').appendTo(container);
		$(addprojbutton).click(addProjectChooser);

		var submitbutton = $("<input type='button' value='Submit'>");
		submitbutton.addClass('effortsubmit').appendTo(container);
		submitbutton.click(function() {

			$( document ).ajaxError(function( event, jqxhr, settings, thrownError ) {
				//report an error
				alert("There was an issue submitting your data.  Please tell someone.");
			});

			var data = extractData();
			$.post( "effortdata.php", { 'values': JSON.stringify(data), 'date': date } , 
					function(result) { //check the return value
					if(result != "SUCCESS") {
						alert("Server returned error: "+result);
					}
					else {
						$(container).find('.changedvalue').removeClass('changedvalue');
					}
			});
		});
		//populate with existing values

		for(var i = 0; i < effort.length; i++) {
			addProjectChooser(effort[i][0],effort[i][1]);
		}
		
		updateStatus();
	}

	console.log(JSON.stringify(effortdata));
	
	//when dom is ready, add server data
	//most recent data is first in array
	for (var i = 0; i < effortdata.length; i++) {
    	var next = null;
    	if(i+1 < effortdata.length)
        	next = effortdata[i+1][1];
    	addWeek(effortdata[i][0], effortdata[i][1], next);		
	}
});

</script>
</body></html>