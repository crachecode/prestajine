<?php

// include composer autoload
require '../vendor/autoload.php';

$originals_path = dirname(__FILE__).'/../../img/p';
$cache_path = dirname(__FILE__).'/../../img/prestajine';

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
