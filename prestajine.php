<?php

if (!defined('_PS_VERSION_'))
	exit;

class Prestajine extends Module {
	public function __construct(){
		$this->name = 'prestajine';
		$this->tab = 'front_office_features';
		$this->version = 1.0;
		$this->author = 'crachecode';
		$this->need_instance = 0;
		parent::__construct();
		$this->displayName = $this->l('Prestajine');
		$this->description = $this->l('Improved image management using Intervention');
	}

	public function install(){
		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);
		if (!parent::install()){
			return false;
		}
		return true;
	}

}
