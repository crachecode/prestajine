<?php

namespace Crachecode\Prestajine;

class Image extends \Crachecode\Tajine\Image {

	public function getPrestashopOrginalPath()
	{
		$img_path = implode('/',str_split($this->basename));
		$this->originals_path = $this->originals_path.'/'.$img_path;
	}

}