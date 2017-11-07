<?php

// include composer autoload
require '../vendor/autoload.php';

/*
'width' => 1280
'height' => 1024
'method' => 'basic'
'quality' => 90
'upsize' => false
*/
// poney.300x400.basic.90.false.png
// machin.php?filename=$1.$7width=$2&height=$3&method=$4&quality=$5&upsize=$6
// index.php?filename=poney.png&width=300&height=400&method=basic&quality=90&upsize=false
// http://localhost/img/name/1280x1024.basic.90.false.jpg

$originals_path = dirname(__FILE__).'/../../img/p';
$cache_path = dirname(__FILE__).'/img/cache';

$image = new Crachecode\Prestajine\Image();

$image->manual = array();
$params = array('filename','width','height','method','quality','upsize');
foreach ($params as $param) {
	if (isset($_GET[$param]) && !empty($_GET[$param])){
		$image->$param = $_GET[$param];
		$image->manual[$param] = true;
	}
}
$image->originals_path = $originals_path;
$image->cache_path = $cache_path;
$image->basename = pathinfo($image->filename,PATHINFO_FILENAME);
$image->extension = pathinfo($image->filename,PATHINFO_EXTENSION);

$image->getPrestashopOrginalPath();
$image->show();
