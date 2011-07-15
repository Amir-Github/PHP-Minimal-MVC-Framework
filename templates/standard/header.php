<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<?php

  $cssFiles = $this->header_options["css"];
  $jsFiles  = $this->header_options["js"];
  $metaTags = $this->header_options["meta"];
  $title    = $this->header_options["title"]; 
 
  foreach($metaTags as $tag)
   echo '<meta name="'.$tag['name'].'" content="'.$tag['content'].'" />';
  foreach($cssFiles as $stylesheet)
    echo '<link href="/one-donation/css/'.$stylesheet.'" rel="stylesheet" type="text/css" />';
  foreach($jsFiles as $jsFile)
    echo '<script language="javascript" type="text/javascript" src="/one-donation/javascript/'.$jsFile.'"></script>';    
?>

<title><?= $title ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body>
<div id="page">

<div id="header"> 
   HEADER    
</div>

