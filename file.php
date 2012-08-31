<?

//probably useless and overly paranoid file open routine.
function openfile($filename, $filemode)
{
	$counter = 0;
	$handle = fopen($filename, $filemode);
	while (!$handle)
	{
		sleep(1);
		$handle = fopen($filename, $filemode);
		$counter++;
		if ($counter > 5)
		{
			return FALSE;
		}
	}
	return $handle;
}

//append the message to the file, erasing the log if it's older than today
function writeMessage($message)
{
	$filename = 'log.txt';
	$filemode = 'a';

	if (file_exists($filename))
	{
		$modded = date("dmY", filemtime($filename));
		$today  = date("dmY");
		if ($today != $modded)
		{
			$filemode = 'w';
		}
	}
	$fp = openfile($filename, $filemode);
	fwrite($fp, $message);
	fwrite($fp, "\n");
	fclose($fp);
}

//first compare the currentTime against the modified time, and only if it's different then continue processing.
//if there are more lines than the specified current line count, then return only the new lines.
function getMessage($currentTime, $currentLineCount)
{
	$filename  = 'log.txt';
	$fileTime  = filemtime($filename);
	$counter   = 0;
	$contents  = '';
	$lineCount = 0;
	$text      = '';

	if ($fileTime != $currentTime)
	{
		$contents = file_get_contents($filename);
	}

	if ($contents != '')
	{ 
		$lines     = explode("\n", trim($contents));
		$lineCount = count($lines); 

		for ($index = $currentLineCount; $index < $lineCount; $index++)
		{
			$text = $text . $lines[$index] . "\n";
		}
	}

	$response = array();
	$response['text']      = $text;
	$response['fileTime']  = $fileTime;
	$response['lineCount'] = $lineCount;
	echo json_encode($response);
	flush();
}

session_start();
$name      = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$message   = isset($_POST['text']) ? $_POST['text'] : '';
$fileTime  = isset($_POST['fileTime']) ? $_POST['fileTime'] : '';
$lineCount = isset($_POST['lineCount']) ? $_POST['lineCount'] : '';

//this is some server-side debug stuff.
if ($_SERVER['argc'] > 1) 
{ 
	$name = 'noshbar';
	for ($commandLineIndex=1; $commandLineIndex < $_SERVER['argc']; $commandLineIndex++) 
	{ 
	        $commandLineArgument      = explode('=', $_SERVER['argv'][$commandLineIndex]); 
	        $commandLineArgumentKey   = array_shift($commandLineArgument); 
	        $commandLineArgumentValue = implode('=', $commandLineArgument); 

		switch($commandLineArgumentKey) 
		{ 
			case '-text' :
				$message = $commandLineArgumentValue; 
                    		break; 
			case '-fileTime' :
				$fileTime = $commandLineArgumentValue; 
				break; 
			case '-lineCount' :
				$lineCount = $commandLineArgumentValue; 
				break; 
		} 
	} 
}  

//if we don't have a valid session, don't do anything.
if ($name == '')
{
	die();
}

if ($message != '')
{
	writeMessage($message);
	die();
}

if ($fileTime != '' && $lineCount != '')
{
	getMessage(intval($fileTime), intval($lineCount));
}

?>
