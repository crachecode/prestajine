<?php

require(_PS_MODULE_DIR_.'prestajine/vendor/autoload.php');

class AdminImagesController extends AdminImagesControllerCore
{
    protected $PJ_config;
    protected $PJ_path;

    public function __construct()
    {
        parent::__construct();
        $this->PJ_config = require(_PS_MODULE_DIR_.'prestajine/config.php');
        $this->PJ_path = _PS_IMG_DIR_.'prestajine';
        if (!file_exists($this->PJ_path)) {
            mkdir($this->PJ_path, 0777);
        }
        foreach ($this->PJ_config as $PJ_type=>$PJ_settings){
            if (!file_exists($this->PJ_path.'/'.$PJ_type)) {
                mkdir($this->PJ_path.'/'.$PJ_type, 0777);
            }
        }
    }

    /**
     * Regenerate images
     *
     * @param $dir
     * @param $type
     * @param bool $productsImages
     * @return bool|string
     */
    protected function _regenerateNewImages($dir, $type, $productsImages = false)
    {
        // CUSTOM PRESTAJINE
        if (!$this->max_execution_time) {
            $this->max_execution_time = 100000;
        }
        // END CUSTOM PRESTAJINE

        if (!is_dir($dir)) {
            return false;
        }

        $generate_hight_dpi_images = (bool)Configuration::get('PS_HIGHT_DPI');

        if (!$productsImages) {
            foreach (scandir($dir) as $image) {
                if (preg_match('/^[0-9]*\.jpg$/', $image)) {
                    foreach ($type as $k => $imageType) {
                        // Customizable writing dir
                        $newDir = $dir;
                        if ($imageType['name'] == 'thumb_scene') {
                            $newDir .= 'thumbs/';
                        }
                        if (!file_exists($newDir)) {
                            continue;
                        }
                        if (!file_exists($newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.jpg')) {
                            if (!file_exists($dir.$image) || !filesize($dir.$image)) {
                                $this->errors[] = sprintf(Tools::displayError('Source file does not exist or is empty (%s)'), $dir.$image);
                            } else {
                                if (!ImageManager::resize($dir.$image, $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.jpg', (int)$imageType['width'], (int)$imageType['height'])) {
                                    $this->errors[] = sprintf(Tools::displayError('Failed to resize image file (%s)'), $dir.$image);
                                }

                                if ($generate_hight_dpi_images) {
                                    if (!ImageManager::resize($dir.$image, $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'2x.jpg', (int)$imageType['width']*2, (int)$imageType['height']*2)) {
                                        $this->errors[] = sprintf(Tools::displayError('Failed to resize image file to high resolution (%s)'), $dir.$image);
                                    }
                                }
                            }
                        }
                        if (time() - $this->start_time > $this->max_execution_time - 4) { // stop 4 seconds before the timeout, just enough time to process the end of the page on a slow server
                            return 'timeout';
                        }
                    }
                }
            }
        } else {
            foreach (Image::getAllImages() as $image) {
                $imageObj = new Image($image['id_image']);
                $existing_img = $dir.$imageObj->getExistingImgPath().'.jpg';
                if (file_exists($existing_img) && filesize($existing_img)) {
                    foreach ($type as $imageType) {
                        if (!file_exists($dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.jpg')) {
                            if (!ImageManager::resize($existing_img, $dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.jpg', (int)$imageType['width'], (int)$imageType['height'])) {
                                $this->errors[] = sprintf(Tools::displayError('Original image is corrupt (%s) for product ID %2$d or bad permission on folder'), $existing_img, (int)$imageObj->id_product);
                            }

                            if ($generate_hight_dpi_images) {
                                if (!ImageManager::resize($existing_img, $dir.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'2x.jpg', (int)$imageType['width']*2, (int)$imageType['height']*2)) {
                                    $this->errors[] = sprintf(Tools::displayError('Original image is corrupt (%s) for product ID %2$d or bad permission on folder'), $existing_img, (int)$imageObj->id_product);
                                }
                            }
                        }
                    }
                    // CUSTOM PRESTAJINE
                    foreach ($this->PJ_config['products'] as $PJ_type=>$PJ_settings){
                        if (!file_exists($this->PJ_path.'/'.$PJ_type.'/'.$image['id_image'].'.jpg' || (isset($PJ_settings['regen']) && $PJ_settings['regen']))){
                            $PJ_manager = new Intervention\Image\ImageManager();
                            $PJ_image = $PJ_manager->make($existing_img);
                            if (!isset($PJ_settings['width']) || !$PJ_settings['width']){
                                if ($PJ_settings['upsize']){
                                    $PJ_image->heighten($PJ_settings['height']);
                                }
                                else {
                                    $PJ_image->heighten($PJ_settings['height'], function($constraint){
                                        $constraint->upsize();
                                    });
                                }
                            }
                            elseif (!isset($PJ_settings['height']) || !$PJ_settings['height']){
                                if ($PJ_settings['upsize']){
                                    $PJ_image->widen($PJ_settings['width']);
                                }
                                else {
                                    $PJ_image->widen($PJ_settings['width'], function($constraint){
                                        $constraint->upsize();
                                    });
                                }
                            }
                            elseif ($PJ_settings['method']=='fit'){
                                if ($PJ_settings['upsize']){
                                    $PJ_image->fit($PJ_settings['width'], $PJ_settings['height']);
                                }
                                else {
                                    $PJ_image->fit($PJ_settings['height'], $PJ_settings['height'], function($constraint){
                                        $constraint->upsize();
                                    });
                                }
                            }
                            else { // method undefined or 'basic'
                                if ($PJ_settings['upsize']){
                                    $PJ_image->resize($PJ_settings['width'], $PJ_settings['height'], function($constraint){
                                        $constraint->aspectRatio();
                                    });
                                }
                                else {
                                    $PJ_image->resize($PJ_settings['width'], $PJ_settings['height'], function($constraint){
                                        $constraint->aspectRatio();
                                        $constraint->upsize();
                                    });
                                }
                            }
                            if (isset($PJ_settings['quality']) && $PJ_settings['quality']){
                                $PJ_image->save($this->PJ_path.'/'.$PJ_type.'/'.$image['id_image'].'.jpg', $PJ_settings['quality']);
                            }
                            else {
                                $PJ_image->save($this->PJ_path.'/'.$PJ_type.'/'.$image['id_image'].'.jpg');
                            }
                        }
                    }
                    // END CUSTOM PRESTAJINE
                } else {
                    $this->errors[] = sprintf(Tools::displayError('Original image is missing or empty (%1$s) for product ID %2$d'), $existing_img, (int)$imageObj->id_product);
                }
                if (time() - $this->start_time > $this->max_execution_time - 4) { // stop 4 seconds before the tiemout, just enough time to process the end of the page on a slow server
                    return 'timeout';
                }
            }
        }

        return (bool)count($this->errors);
    }
}
