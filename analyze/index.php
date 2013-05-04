<?php
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<link rel="stylesheet" href="../css/main.css" />
<link rel="stylesheet" href="../css/srtify.css" />
</head>
<body>
	<div id="wrap">
<div id="header">
	<div id="srtify_banner"><p><span style="color:#EEF1AB;letter-spacing:0.2em">SRT</span><span style="color:#FFC5F4;font-size:80%;">ify</span></p>
	</div>
</div>
    <div id="main">
		<div id="content">
<?php
//If we encounter a file in the tmp session folder, we process it
if (isset($_FILES['uploadedfile']['tmp_name']) && !empty($_FILES['uploadedfile']['tmp_name'])) {
	$check_dots = (isset($_POST["chk_dots"]) && $_POST["chk_dots"] == "on") ? true : false;
	$check_gaps = (isset($_POST["chk_gaps"]) && $_POST["chk_gaps"] == "on") ? true : false;
	$export_text = (isset($_POST["export_txt"]) && $_POST["export_txt"] == "on") ? true : false;
	$export_csv = (isset($_POST["export_csv"]) && $_POST["export_csv"] == "on") ? true : false;
	if ($check_dots || $check_gaps || $export_text || $export_csv) {
		$_SESSION["FILE"] = $_FILES['uploadedfile']['tmp_name'];
		$_SESSION["FILENAME"] = substr($_FILES['uploadedfile']['name'],0,strlen($_FILES['uploadedfile']['name'])-4)."_converted.srt";
		$_SESSION["CHK_GAPS"] = $check_gaps;
		$_SESSION["CHK_DOTS"] = $check_dots;
		$_SESSION["EXPORT_TXT"] = $export_text;
		$_SESSION["EXPORT_CSV"] = $export_csv;

		if ($export_text) {
			$_SESSION["FILENAME"] = preg_replace("#(_converted\.srt)$#", ".txt", $_SESSION["FILENAME"]);
		}
		if ($export_csv) {
			$_SESSION["FILENAME"] = preg_replace("#(_converted\.srt)$#", ".csv", $_SESSION["FILENAME"]);
		}
		move_uploaded_file($_FILES['uploadedfile']['tmp_name'], "tmp");

		echo '<h5>The script was used in file manipulation mode. Click <a href=index.php>HERE</a> if you want to read the instructions again.</h5>
		<script type="text/javascript">document.location="parseFile.php";</script>';
	} else {
		require_once '../core/SRT.php';
		new SRTAnalyzer($_FILES['uploadedfile']['tmp_name'],$_FILES['uploadedfile']['name']);
	}
} else {
	?>

			<div id="srtform_error"></div>
			<div id="srtify_form">
			<form enctype="multipart/form-data" onsubmit="return validate();" action="<?php echo $_SERVER["PHP_SELF"];?>" method="post">
				<fieldset>
					<input type="hidden" name="MAX_FILE_SIZE" value="400000" />
					<div>
						<div><label>Choose a subtitle file to upload:</label><input class="inputFile" name="uploadedfile" type="file" /></div>
						<div><input id="srtify_submit" type="submit" value="ANALYZE THIS!" /></div>
					</div>
					<div><input type="checkbox" name="chk_gaps" /><label>Add/Complete gaps</label></div>
					<div><input type="checkbox" name="chk_dots" /><label>Remove strings with only periods</label></div>
					<div><input type="checkbox" name="export_txt" /><label>Export file in text format</label></div>
					<div><input type="checkbox" name="export_csv" /><label>Export file in CSV format</label></div>
				</fieldset>
			</form>
			</div>
		
<br />
Click on the "Browse" button and choose the subtitle file to analyze
from your hard drive, <br />
then click on "ANALYZE THIS !".<br />
<br />
This script <b style="color: #196">analyzes SRT subtitle files only</b>,
which can be exported from the dotsub interface. <br />

<br />
The analysis of the subtitle file will provide you with the following
information :<br />
<br />
- Average number of words per minute throughout the video, and the kind
of reading level required to read such subtitles. This should be
considered as an approximation of the amount of text that's going to be
read. The higher the value, the harder it's going to be to read the
contents.<br />
<br />
- List of strings that are displayed for shorter than 1.5 seconds<br />
<br />
- List of strings that exceed the 70 character requirement<br />
<br />
- List of timings where gaps between strings aren't set between 100 and
140ms<br />
<br />
- List of strings that exceed 35 characters, but aren't displayed for
more than 2 seconds<br />
<br />
- List of strings that are displayed for more than 6 seconds<br />
<br />

Two extra checkboxes allow the manipulation of the subtitle file :<br />
- by adding a gap of 140ms in between strings that are spaced by less
than 100ms<br />
<br />
Example :<br />
BEFORE :<br />
<p id="subtitleContent">15<br />
00:00:12,000 --> 00:00:16,500<br />
In reaction to the newest set of documents now made public<br />
<br />
16<br />
00:00:16,500 --> 00:00:18,900<br />
</p>
AFTER :<br />
<p id="subtitleContent">15<br />
00:00:12,000 --> 00:00:16,<b><span style="color: red">360</span></b><br />
In reaction to the newest set of documents now made public<br />
<br />
16<br />
00:00:16,500 --> 00:00:18,900<br />
a contentious debate rages within capitals and cafes worldwide.<br />
</p>
<br />
- by removing strings in the subtitle file that contain only periods
("."). <br />
<br />
- by removing commas at the end of strings, which is a requisite for
English transcriptions, according to our guidelines. <br />
<br />

Additionally, the script can strip timestamps from the subtitle file and present it in text format.

<?php
session_destroy();
}
?>
</div>
</div>
</div>
<div id="footer">
    <a href="http://forum.linguisticteam.org">Linguistic Team International</a>
</div>
<script type="text/javascript" src="../js/jquery-1.8.3.js"></script>
<script type="text/javascript" src="../js/jquery-ui-1.9.2.js"></script>
<script type="text/javascript" src="../js/srtify.js"></script>
</body>
</html>