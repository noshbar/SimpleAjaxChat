<?
session_start();

//if we're scheduled to logout, do it
if(isset($_GET['logout']))
{	
	session_destroy();
	header("Location: index.php");
}

//handle a login form post, check against allowed names and store encryption key.
//this is just a sample login process, you'd be better off doing something intelligent here.
if (isset($_POST['login']))
{
	if($_POST['name'] != "" && $_POST['key'] != "")
	{
		$tempName         = stripslashes(htmlspecialchars($_POST['name']));
		//maybe check $tempName against allowed names here...
		$key              = $_POST['key'];
		$_SESSION['name'] = $tempName;
		$_SESSION['key']  = $key;
	}
	if ($_SESSION['name'] == '')
	{
		die();
	}	
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Chat</title>
<link type="text/css" rel="stylesheet" href="style.css" />
</head>

<?php
if(!isset($_SESSION['name']))
{
?>
<div>
<form action="index.php" method="post" id="loginform">
	<input type="text" name="name" id="name" />
	<input type="text" name="key" id="key" />
	<input type="submit" name="login" id="login" value="Login" />
</form>
</div>
<?php
}
else
{
?>
<div id="wrapper">
	<div id="menu">
		<p class="welcome">Welcome, <b><?php echo $_SESSION['name']; ?></b></p>
		<p class="logout"><a id="exit" href="#">Exit Chat</a></p>
		<div style="clear:both"></div>
	</div>	
	<div id="chatbox"></div>
	
	<form name="message" action="">
		<input name="userMessage" type="text" id="userMessage" size="63" />
		<input name="submitMessage" type="submit"  id="submitMessage" value="Send" />
		<input name="enableAlert" type="button" id="enableAlert" value="Enable Desktop Alerts" />
	</form>
</div>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js"></script>
<script type="text/javascript" src="http://crypto-js.googlecode.com/svn/tags/3.0.2/build/rollups/aes.js"></script>
<script type="text/javascript">

var popup;
var currentLineCount      = 0;      //the amount of lines of dialog we know about (initially 0 to get all)
var currentFileTime       = 0;      //the last modified time of the file we know about (intially 0 to trigger a file read)
var justAlert             = true;   //if true we don't try any fancy Chrome notification, we simply call "alert"
var changed               = false;  //if we've detected new lines in the idle loop
var alerted               = false;  //if we've alerted the user since detecting the new lines in idle
var titleToggle           = true;   //used to toggle the title in idle if new messages have been detected
var idleCounter           = 0;      //how many times we've been through the idle branch of the code (equal to seconds spent in idle)
var idleCounterCheckPoint = 10;     //check for new messages every time idleCounter >= idleCounterCheckPoint
var checkCount            = 0;      //count how many times we've checked for new messages in idle, if it's "high", then alter idleCounterCheckPoint
                                    //to some other value to slow the polling down.

$(document).ready(function()
{
	//detect urls in a string and turn then into anchors
	function urlify(contents)
	{
	    var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
	    return contents.replace(exp,"<a target='_blank' href='$1'>$1</a>"); 
	}

	//send a new message to the server
	function sendMessage(newMessage)
	{
		//seeing as we're sending a message, start checking for a reply quicker again
		idleCounter           = 0;
		idleCounterCheckPoint = 10;
		checkCount            = 0;

		var now = new Date();
		var dateStr = "(" + now.toLocaleTimeString() + ") ";

		newMessage = urlify(newMessage);
		newMessage = "<font <?php if ($_SESSION['name']=='Four'){ echo 'color=\"blue\"'; } else { echo 'color=\"red\"'; }; ?>>" + dateStr + "<b><?php echo $_SESSION['name']; ?>:&nbsp;</b></font>" + newMessage;
		newMessage = CryptoJS.AES.encrypt(newMessage, "<?php echo $_SESSION['key']; ?>");
		var mess = encodeURIComponent(newMessage);
		$.post("file.php", {text: mess});				
		$("#userMessage").attr("value", ""); //clear the entry box.
		return false;
	}

	//create an ajax request to get the log
	function loadLog()
	{
		$.ajax(
		{
			type     : 'POST',
			url      : "file.php",
			cache    : false,
			dataType : "json",
			data     :
			{
				lineCount : currentLineCount,
				fileTime  : currentFileTime,
			},
			success : function(response)
			{
				var lineCount    = parseInt(response['lineCount']);
				var html         = response['text'];
				currentFileTime  = parseInt(response['fileTime']);

				if (lineCount == 0) //no new lines, so simply return
				{
					return;
				}

				var lines = html.split("\n");
				var output = $("#chatbox").html();

				for(i = 0; i < lines.length-1; i++)
				{
					var line = decodeURIComponent(lines[i]);
					var decrypted = CryptoJS.AES.decrypt(line, "<?php echo $_SESSION['key']; ?>");
					decrypted = CryptoJS.enc.Utf8.stringify(decrypted);
				        output = output + "<div class='messageLine'>" + decrypted + "</div>";
				}
				var oldscrollHeight = $("#chatbox").attr("scrollHeight") - 20;
				$("#chatbox").html(output);
				var newscrollHeight = $("#chatbox").attr("scrollHeight") - 20;
				if(newscrollHeight > oldscrollHeight)
				{
					$("#chatbox").animate({ scrollTop: newscrollHeight }, 'normal'); //autoscroll to bottom of div
				}

				if (lineCount != currentLineCount)
				{
					changed = true;
				}
				currentLineCount = lineCount;
		  	},
		});
	}

	function alertMe()
	{
		if (alerted)
		{
			return;
		}
		alerted = true;
		if (justAlert)
		{
			alert('New message');
		}
		else
		{
			popup = window.webkitNotifications.createNotification('alert.png', 'Alert', 'New message');
			popup.show();
		}
	}

	function delertMe()
	{
		if (!alerted)
		{
			return;
		}
		alerted = false;
		if (popup)
		{
			popup.cancel();
			popup = undefined;
		}
	}

	function refresh()
	{
		if (document.hasFocus())
		{
			delertMe();
			changed               = false;
			idleCounter           = 0;
			idleCounterCheckPoint = 10;
			checkCount            = 0;
			document.title        = 'Chat';
			loadLog();
		}
		else
		{
			if (alerted) //we know there's a new message, we've told the user, so no need to poll until they check again
			{
				document.title = titleToggle ? 'Chat' : 'New Message';			
				titleToggle = !titleToggle;
				return;
			}

			if (changed) //if we've detected a change, raise an alert and return
			{
				alertMe();
				return;
			}

			idleCounter++;
			if (idleCounter >= idleCounterCheckPoint)
			{
				loadLog();
				idleCounter = 0;
				//if we've waited more than 5 minutes (10 seconds * 6 * 5), change the update to once a minute
				// 1 second timer * idleCounterCheckPoint * 6 * 5
				checkCount++;
				if (checkCount >= 6 * 5)
				{
					idleCounterCheckPoint = 60;
				}
			}
		}
	}

	function permissionCallback()
	{
		if (window.webkitNotifications.checkPermission() == 0)
		{
			justAlert = false;
			document.getElementById("enableAlert").style.visibility = 'hidden';
		}
	}

	if (window.webkitNotifications)
	{
		document.querySelector('#enableAlert').addEventListener('click', function() 
		{
			if (window.webkitNotifications.checkPermission() != 0)
			{
				window.webkitNotifications.requestPermission(permissionCallback);
			}
		}, false);

		if (window.webkitNotifications.checkPermission() == 0)
		{
			justAlert = false;
			document.getElementById("enableAlert").style.visibility = 'hidden';
		}
	}
	else
	{
		document.getElementById("enableAlert").style.visibility='hidden';
	}

	//hit enter or click the button to send the current text
	$("#submitMessage").click(function()
	{
		sendMessage($("#userMessage").val());
		return false;
	});

	//if they clicked the exit link, confirm then optionally exit
	$("#exit").click(function()
	{
		var exit = confirm("Are you sure you want to end the session?");
		if (exit==true)
		{
			sendMessage("<? echo $_SESSION['name'] ?> has left the chat session.");
			window.location = 'index.php?logout=true';
		}
	});

	sendMessage("(joined chat)");
	setInterval(refresh, 1000);
	loadLog();
});
</script>
<?php
}
?>
</body>
</html>
