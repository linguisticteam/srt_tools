<?php
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<style>
body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td { 
    margin:0;
    padding:0;
}
#header {
	background-color:#222;
	color:#fff;
	font-family: Century Gothic, sans-serif;
    height: 33px;
    line-height: 33px;
}
html { height: 100%; }
body { height: 100%; background-color:#FFF;font-family: Century Gothic, sans-serif; }
#wrap { min-height: 100%;}
#main {
    overflow: auto;
    padding-bottom: 33px; /* must be same height as the footer */

}
#footer {
    position: relative;
    margin-top: -33px; /* negative value of footer height */
    height: 33px;
    line-height: 33px;
    text-align: center;
    background-color:#222;
    color:#fff;
}

#content{
	height: 100%;
	margin:0 auto;
	min-height: 100%;
	height: 100%;
	width:70%;
	padding-top:15px;
	text-align:center;
}

#footer a {
	color:inherit;
	font-size:80%;
}

#footer a:hover {
	color:#CFF;
}
#button {
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #ededed), color-stop(1, #dfdfdf) );
	background:-moz-linear-gradient( center top, #ededed 5%, #dfdfdf 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ededed', endColorstr='#dfdfdf');
	background-color:#ededed;
	-moz-border-radius:4px;
	-webkit-border-radius:4px;
	border-radius:4px;
	border:1px solid #AAA;
	display:inline-block;
	color:#222;
	font-family:arial;
	font-size:14px;
	font-weight:bold;
	padding:14px 48px;
	text-decoration:none;
}
#button:hover {
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #dfdfdf), color-stop(1, #ededed) );
	background:-moz-linear-gradient( center top, #dfdfdf 5%, #ededed 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#dfdfdf', endColorstr='#ededed');
	background-color:#dfdfdf;
	color:#000;
}


</style>
</head>
<body>
<div id="wrap">
    <div id="header">
       <p><span style="margin-left:10px;color:#FFC600;letter-spacing:0.1em">Bruno's</span><span style="color:#FFEF00;"> Playground</span>
    </div>
    <div id="main">
		<div id="content">
		<a id="button" href="analyze">Analyze SRT Files</a>
		<a id="button" href="compare">Compare SRT Files</a>
		</div>
    </div>
</div>
<div id="footer">
    <a href="http://forum.linguisticteam.org">Linguistic Team International</a>
</div>
<!-- 
<div id="strify_header">
	<div id="home_banner"><p><span style="color:#FFC600;letter-spacing:0.1em">Bruno's</span><span style="color:#FFEF00;"> Playground</span></p>
	</div>
</div>
<div id="content">
	

</div>
</body>
 -->
</html>