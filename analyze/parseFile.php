<?php
session_start();
header("Content-Type: application/force download");
header("Content-Transfer-Encoding: binary");
header("Pragma: no-cache");
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Expires: 0");
header("Content-Disposition: attachment; filename=" . $_SESSION["FILENAME"]);

require_once ('../core/SRT.php');
new SRTEditor("tmp", $_SESSION["CHK_GAPS"], $_SESSION["CHK_DOTS"],$_SESSION["EXPORT_TXT"],$_SESSION["EXPORT_CSV"]);

@unlink("tmp");
session_destroy();
?>