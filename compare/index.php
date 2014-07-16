<?php
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<link rel="stylesheet" href="../css/main.css" />
<link rel="stylesheet" href="../css/srtdiff.css" />
<title>LTI Subtitle Comparison Tool</title>
</head>
<body>
	<div id="wrap">

<?php
if (!empty($_FILES)&&!(isset($_POST["chk_simple"]) && $_POST["chk_simple"] == "on")) {
//if(true){
	$original = (isset($_FILES['uploadedfile']['tmp_name'][0]) && !empty($_FILES['uploadedfile']['tmp_name'][0])) ? $_FILES['uploadedfile']['tmp_name'][0] : null;
	$modified = (isset($_FILES['uploadedfile']['tmp_name'][1]) && !empty($_FILES['uploadedfile']['tmp_name'][1])) ? $_FILES['uploadedfile']['tmp_name'][1] : null;
	$base = (isset($_FILES['uploadedfile']['tmp_name'][2]) && !empty($_FILES['uploadedfile']['tmp_name'][2])) ? $_FILES['uploadedfile']['tmp_name'][2] : null;
	$config=new stdClass();
	$config->seeAll = (isset($_POST["chk_all"]) && $_POST["chk_all"] == "on") ? true : false;
	require_once '../core/meters.php';
	require_once '../core/srtdiff.php';
	//$config->hasTimings = (isset($_POST["chk_no_timings"]) && $_POST["chk_no_timings"] == "on") ? true : false;
	?>
	<div id="header">
		<div id="srtdiff_banner"><span id="banner_title"><span style="color:#EEF1AB;letter-spacing:0.2em">S<span style="font-size:50%;letter-spacing:0.2em">o</span>RT</span><span style="font-size:80%;letter-spacing:0.5em">a</span><span style="color:#FFC5F4;">LIKE</span></span>
		<span id="srtdiff_index"><a href="index.php">Compare other subtitles</a></span>
		</div>
				
		<div id="srtdiff_controls">
			<div><input type="checkbox" id="showNoChange"/><label>Non-modified strings</label></div>
			<div><input type="checkbox" id="showChanged" checked="checked"/><label>Modified strings (text and/or timestamp)</label></div>
			<div><input type="checkbox" id="showAdded" checked="checked"/><label>Added strings</label></div>
			<div><input type="checkbox" id="showDeleted" checked="checked"/><label>Deleted strings</label></div>
		</div>
	</div>
		    <div id="main">
	<div id="content">
		<div id="srtdiff_error" class="noDisplay"></div>
		<?php
		execTime::start();
		memUsage::start();
		new ResultViewer($original,$modified,$base,$config);
		//new ResultViewer("../../SRT/nr_dm.srt","../../SRT/r_dm.srt",null,null);
		?>
		<div id="srtdiff_execTime"><?php 
		echo "Executed in ".execTime::out()." (".memUsage::out()." memory used).";
		?>
		</div>
	</div>
<?php
} else {
?>
		<div id="header">
			<div id="srtdiff_banner"><span id="banner_title"><a href="index.php" style="text-decoration: none"><span style="color:#EEF1AB;letter-spacing:0.2em">S<span style="font-size:50%;letter-spacing:0.2em">o</span>RT</span><span style="font-size:80%;letter-spacing:0.5em">a</span><span style="color:#FFC5F4;">LIKE</span></a></span>
				</div>
		</div>
		    <div id="main">
				<div id="content">
<?php 
	if(!empty($_POST)){
		$original = (isset($_FILES['uploadedfile']['tmp_name'][0]) && !empty($_FILES['uploadedfile']['tmp_name'][0])) ? $_FILES['uploadedfile']['tmp_name'][0] : null;
		$modified = (isset($_FILES['uploadedfile']['tmp_name'][1]) && !empty($_FILES['uploadedfile']['tmp_name'][1])) ? $_FILES['uploadedfile']['tmp_name'][1] : null;
		$config=new stdClass();
		$config->simpleCheck = true;
		require_once '../core/meters.php';
		require_once '../core/srtdiff.php';
		$s = new ResultViewer($original,$modified,null,$config);
		$result = $s->getSimpleCheck();
	}else{
		$result="";
	}
?>
	<div id="srtform_error"></div>
	<div id="srtdiff_form">
		<form enctype="multipart/form-data" onsubmit="return validate();" action="<?php echo $_SERVER["PHP_SELF"];?>" method="post">
			<fieldset>
				<input type="hidden" name="MAX_FILE_SIZE" value="400000"/>
				<div><a class="tooltip"><label>Original file :<span class="classic">The original file (i.e the translated subtitle file)</span></label></a><input id="srtOriginal" class="inputFile" name="uploadedfile[]" type="file"/></div>
				<div><a class="tooltip"><label>Modified file :<span class="classic">The modified file (i.e the proofread subtitle file)</span></label></a><input id="srtModified" class="inputFile" name="uploadedfile[]" type="file"/></div>
				<div><input id="srtdiff_submit" type="submit" value="Compare"/></div>
				<div id="srtdiff_formOptions">
				<label>Options:</label>
				<div><a class="tooltip"><label>Base file :</label><input class="inputFile" name="uploadedfile[]" type="file"/><span class="classic">The file in the original language, if one wants to check the accuracy of a translation/proofread file. (Its timestamps have to be identical to the 'original file' file uploaded above)</span></a></div>
				<div><input type="checkbox" id="chk_simple" name="chk_simple"/><a class="tooltip"><label>Just check whether files are identical<span class="classic">Shows a message indicating whether the files are identical and if not, the number of strings that changed.</span></label></a></div>
				<div><input type="checkbox" name="chk_all"/><a class="tooltip"><label>Have the choice of displaying strings that haven't been modified<span class="classic">In case you'd like to see the context around the strings that have been modified. This essentially displays all strings.</span></label></a></div>
				<!--<div><input type="checkbox" name="chk_has_timings"/><a class="tooltip"><label>Just check the contents (no timestamp check)<span class="classic">In case you'd like to see the context around the strings that have been modified. This essentially displays all strings.</span></label></a></div> -->
				</div>
			</fieldset>
		</form>
	</div>
	<div id="srtdiff_dialog"></div>
	<div id="srtdiff_content">
		<p id="srtdiff_manual">
		Click on the "Browse" button and choose the subtitle files to compare
		from your hard drive, then click on "Compare".<br /> <br />
		Optionally, a third subtitle "base" file containing the original
		transcription can be uploaded as well, in case you want to
		simultaneously compare the differences between files and see how the
		translation fares as compared to the original. <br />
		Should you decide to upload the original transcription, be aware of the fact that the original transcription
		must have the same timestamps as the original file to compare.
		</p>
	</div>
<?php 
}
?>
</div>
</div>
</div>
<div id="footer">
    <a href="http://forum.linguisticteam.org">Linguistic Team International</a>
</div>
<script type="text/javascript" src="../js/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="../js/jquery-ui-1.11.0.min.js"></script>
<script type="text/javascript" src="../js/srtdiff.js"></script>	
<script type="text/javascript">$("#srtform_error").html("<span><?php echo $result;?></span>");$('#srtform_error').addClass('errorShown');</script>
</body>
</html>