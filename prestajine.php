<?php

if (!defined('_PS_VERSION_'))
{
	exit;
}

class Prestajine extends Module
{

	public function __construct()
	{
		$this->name = 'prestajine';
		$this->tab = 'front_office_features';
		$this->version = '2.0.0';
		$this->author = 'crachecode';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		parent::__construct();
		$this->displayName = $this->l('Prestajine');
		$this->description = $this->l('Improved image management using Intervention');
	}

	public function install()
	{
		$rewrite_rule = "<IfModule mod_rewrite.c>\n RewriteEngine On\n RewriteCond %{REQUEST_URI} images/\n RewriteRule images/(.*)$ modules/prestajine/$1 [L]\n</IfModule>";

		$prestashop_header = '# ~~start~~ Do not remove this comment';
		$prestajine_header = '# ~~prestajine start~~';
		$prestajine_footer = '# ~~prestajine end~~';

		$prestajine_content = $prestajine_header."\n".$rewrite_rule."\n".$prestajine_footer."\n";

		if(!$htaccess = file_get_contents(_PS_ROOT_DIR_.'/.htaccess')){
			Tools::generateHtaccess();
			$htaccess = file_get_contents(_PS_ROOT_DIR_.'/.htaccess');
		}

		if (preg_match('/\# ~~prestajine start~~(.*?)\# ~~prestajine end~~/s', $htaccess, $m)){
			$content_to_remove = $m[0];
			$htaccess = str_replace($content_to_remove, $prestajine_content, $htaccess);
		}
		else {
			$htaccess = str_replace($prestashop_header, $prestajine_content.$prestashop_header, $htaccess);
		}
		file_put_contents(_PS_ROOT_DIR_.'/.htaccess', $htaccess);


		if (!parent::install()){
			return false;
		}
		return true;
	}

	public function uninstall()
	{
		if ($htaccess = file_get_contents(_PS_ROOT_DIR_.'/.htaccess')){
			if (preg_match('/\# ~~prestajine start~~(.*?)\# ~~prestajine end~~/s', $htaccess, $m)){
				$content_to_remove = $m[0];
				$htaccess = str_replace($content_to_remove, '', $htaccess);
				file_put_contents(_PS_ROOT_DIR_.'/.htaccess', $htaccess);
			}
		}
		$path = _PS_ROOT_DIR_.'/img/prestajine';
		if (is_dir($path)){
			$files = glob($path . '/*');
			foreach ($files as $file) {
				unlink($file);
			}
			rmdir($path);
		}
		if (!parent::uninstall())
			return false;
		return true;
	}

}
