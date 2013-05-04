<?php
session_start();
$original = (isset($_FILES['uploadedfile']['tmp_name'][0]) && !empty($_FILES['uploadedfile']['tmp_name'][0])) ? $_FILES['uploadedfile']['tmp_name'][0] : null;
$modified = (isset($_FILES['uploadedfile']['tmp_name'][1]) && !empty($_FILES['uploadedfile']['tmp_name'][1])) ? $_FILES['uploadedfile']['tmp_name'][1] : null;
$base = (isset($_FILES['uploadedfile']['tmp_name'][2]) && !empty($_FILES['uploadedfile']['tmp_name'][2])) ? $_FILES['uploadedfile']['tmp_name'][2] : null;
$config=new stdClass();
$config->simpleCheck = (isset($_POST["chk_simple"]) && $_POST["chk_simple"] == "on") ? true : false;
require_once '../core/meters.php';
require_once '../core/srtdiff.php';
//new ResultViewer("srt/NRt.srt", "srt/Rt.srt", "srt/bf.srt",false,true);
new ResultViewer($original,$modified,$base,$config);
session_destroy();