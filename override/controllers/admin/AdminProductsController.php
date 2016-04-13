<?php

require(_PS_MODULE_DIR_.'prestajine/vendor/autoload.php');

class AdminProductsController extends AdminProductsControllerCore
{
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

    public function ajaxProcessaddProductImage()
    {
        self::$currentIndex = 'index.php?tab=AdminProducts';
        $product = new Product((int)Tools::getValue('id_product'));
        $legends = Tools::getValue('legend');

        if (!is_array($legends)) {
            $legends = (array)$legends;
        }

        if (!Validate::isLoadedObject($product)) {
            $files = array();
            $files[0]['error'] = Tools::displayError('Cannot add image because product creation failed.');
        }

        $image_uploader = new HelperImageUploader('file');
        $image_uploader->setAcceptTypes(array('jpeg', 'gif', 'png', 'jpg'))->setMaxSize($this->max_image_size);
        $files = $image_uploader->process();

        foreach ($files as &$file) {
            $image = new Image();
            $image->id_product = (int)($product->id);
            $image->position = Image::getHighestPosition($product->id) + 1;

            foreach ($legends as $key => $legend) {
                if (!empty($legend)) {
                    $image->legend[(int)$key] = $legend;
                }
            }

            if (!Image::getCover($image->id_product)) {
                $image->cover = 1;
            } else {
                $image->cover = 0;
            }

            if (($validate = $image->validateFieldsLang(false, true)) !== true) {
                $file['error'] = Tools::displayError($validate);
            }

            if (isset($file['error']) && (!is_numeric($file['error']) || $file['error'] != 0)) {
                continue;
            }

            if (!$image->add()) {
                $file['error'] = Tools::displayError('Error while creating additional image');
            } else {
                if (!$new_path = $image->getPathForCreation()) {
                    $file['error'] = Tools::displayError('An error occurred during new folder creation');
                    continue;
                }

                $error = 0;

                if (!ImageManager::resize($file['save_path'], $new_path.'.'.$image->image_format, null, null, 'jpg', false, $error)) {
                    switch ($error) {
                        case ImageManager::ERROR_FILE_NOT_EXIST :
                            $file['error'] = Tools::displayError('An error occurred while copying image, the file does not exist anymore.');
                            break;

                        case ImageManager::ERROR_FILE_WIDTH :
                            $file['error'] = Tools::displayError('An error occurred while copying image, the file width is 0px.');
                            break;

                        case ImageManager::ERROR_MEMORY_LIMIT :
                            $file['error'] = Tools::displayError('An error occurred while copying image, check your memory limit.');
                            break;

                        default:
                            $file['error'] = Tools::displayError('An error occurred while copying image.');
                            break;
                    }
                    continue;
                } else {
                    $imagesTypes = ImageType::getImagesTypes('products');
                    $generate_hight_dpi_images = (bool)Configuration::get('PS_HIGHT_DPI');

                    foreach ($imagesTypes as $imageType) {
                        if (!ImageManager::resize($file['save_path'], $new_path.'-'.stripslashes($imageType['name']).'.'.$image->image_format, $imageType['width'], $imageType['height'], $image->image_format)) {
                            $file['error'] = Tools::displayError('An error occurred while copying image:').' '.stripslashes($imageType['name']);
                            continue;
                        }

                        if ($generate_hight_dpi_images) {
                            if (!ImageManager::resize($file['save_path'], $new_path.'-'.stripslashes($imageType['name']).'2x.'.$image->image_format, (int)$imageType['width']*2, (int)$imageType['height']*2, $image->image_format)) {
                                $file['error'] = Tools::displayError('An error occurred while copying image:').' '.stripslashes($imageType['name']);
                                continue;
                            }
                        }
                    }
                    // CUSTOM PRESTAJINE
                    foreach ($this->PJ_config['products'] as $PJ_type=>$PJ_settings){
                        $PJ_manager = new Intervention\Image\ImageManager();
                        $PJ_image = $PJ_manager->make($file['save_path']);
                        if (!isset($PJ_settings['width']) || !$PJ_settings['width']){
                            if ($PJ_settings['upsize']){
                                $PJ_image->heighten($PJ_settings['height']);
                            } else{
                                $PJ_image->heighten($PJ_settings['height'], function ($constraint){
                                    $constraint->upsize();
                                });
                            }
                        } elseif (!isset($PJ_settings['height']) || !$PJ_settings['height']){
                            if ($PJ_settings['upsize']){
                                $PJ_image->widen($PJ_settings['width']);
                            } else{
                                $PJ_image->widen($PJ_settings['width'], function ($constraint){
                                    $constraint->upsize();
                                });
                            }
                        } elseif ($PJ_settings['method'] == 'fit'){
                            if ($PJ_settings['upsize']){
                                $PJ_image->fit($PJ_settings['width'], $PJ_settings['height']);
                            } else{
                                $PJ_image->fit($PJ_settings['height'], $PJ_settings['height'], function ($constraint){
                                    $constraint->upsize();
                                });
                            }
                        } else{ // method undefined or 'basic'
                            if ($PJ_settings['upsize']){
                                $PJ_image->resize($PJ_settings['width'], $PJ_settings['height'], function ($constraint){
                                    $constraint->aspectRatio();
                                });
                            } else{
                                $PJ_image->resize($PJ_settings['width'], $PJ_settings['height'], function ($constraint){
                                    $constraint->aspectRatio();
                                    $constraint->upsize();
                                });
                            }
                        }
                        if (isset($PJ_settings['quality']) && $PJ_settings['quality']){
                            $PJ_image->save($this->PJ_path . '/' . $PJ_type . '/' . $image->id . '.jpg', $PJ_settings['quality']);
                        } else{
                            $PJ_image->save($this->PJ_path . '/' . $PJ_type . '/' . $image->id . '.jpg');
                        }
                    }
                    // END CUSTOM PRESTAJINE
                }

                unlink($file['save_path']);
                //Necesary to prevent hacking
                unset($file['save_path']);
                Hook::exec('actionWatermark', array('id_image' => $image->id, 'id_product' => $product->id));

                if (!$image->update()) {
                    $file['error'] = Tools::displayError('Error while updating status');
                    continue;
                }

                // Associate image to shop from context
                $shops = Shop::getContextListShopID();
                $image->associateTo($shops);
                $json_shops = array();

                foreach ($shops as $id_shop) {
                    $json_shops[$id_shop] = true;
                }

                $file['status']   = 'ok';
                $file['id']       = $image->id;
                $file['position'] = $image->position;
                $file['cover']    = $image->cover;
                $file['legend']   = $image->legend;
                $file['path']     = $image->getExistingImgPath();
                $file['shops']    = $json_shops;

                @unlink(_PS_TMP_IMG_DIR_.'product_'.(int)$product->id.'.jpg');
                @unlink(_PS_TMP_IMG_DIR_.'product_mini_'.(int)$product->id.'_'.$this->context->shop->id.'.jpg');
            }
        }

        die(Tools::jsonEncode(array($image_uploader->getName() => $files)));
    }
}
