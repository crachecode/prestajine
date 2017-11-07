Prestajine
==========

Prestajine is a thumbnail generator meant to be used with Prestashop. It is based on [Tajine](https://github.com/crachecode/tajine) which makes use of the [Intervention Image](https://github.com/Intervention/image) library.

## Features

Prestajine is an alternative to PrestaShop's original image manager.  
It allows you to create images and thumbnails at any dimensions, in a flexible way, without adding any plain background.
Desired images dimensions and parameters are to be defined directly in theme templates, while calling `<img src="...`.  
Resizing and cache are handled by Tajine.

**Currently it only works with products images.**

## Requirements

Prestajine requires PHP 5.6 or higher. It works with any version of Prestashop. Its cache functionality can make use of Apache mod_rewrite, it also allows simpler image URLs, but Apache should not be mandatory. Tajine has not been tested with any other HTTP server though.

## Installation

 1. Clone the repository using Git in Prestashop root directory :  
 `git clone https://github.com/crachecode/prestajine.git`
 
 2. Rename and enter the new directory :  
 `mv prestajine images && cd images`

 3. Install dependencies using Composer :  
 `composer update`

 4. Allow writing on the cache directory :  
 `chmod 777 public/img/cache`

## Using Prestajine

Image at any dimension can then be accessed in HTTP. Simply call `<img src="{$base_dir}images/{$image.id_image}...` from theme templates following one of these URL syntaxes :

with apache and mod_rewrite :  
`{$base_dir}images/{$image.id_image}.[width]x[height].[method].[quality].[upsize].jpg`  
e.g. :  
* `{$base_dir}images/{$image.id_image}.1280x1024.basic.90.false.jpg` (width = 1280px, height = 1024px, basic method, jpg quality 90, no upsizing)  
* `{$base_dir}images/{$image.id_image}.1280x.false.jpg` (width = 1280px, no height specified, no upsizing)  
* `{$base_dir}images/{$image.id_image}.x1024.jpg` (height = 1024px, no width specified)  

without mod_rewrite :  
`{$base_dir}images/index.php?filename={$image.id_image}.jpg&width=[width]&height=[height]&method=[method]&quality=[quality]&upsize=[upsize]`  
e.g. :  
* `{$base_dir}images/index.php?filename={$image.id_image}.jpg&width=1280&height=1024&method=basic&quality=90&upsize=false`  
* `{$base_dir}images/index.php?filename={$image.id_image}.jpg&height=1024`

### Parameters

| name            | value type                          | description                                                                   | default       |
| ---             | ---                                 | ---                                                                           | ---           |
| ```name```      | string                              | filename as accessible in ```tajine/public/img/originals``` without extension | n/a, required |
| ```extension ```| string                              | extension of filename as accessible in ```tajine/public/img/originals```      | n/a, required |
| ```width```     | integer                             | thumbnail width (in pixel)                                                    | n/a           |
| ```height```    | integer                             | thumbnail height (in pixel)                                                   | n/a           |
| ```method```    | ```basic```, ```fit``` or ```max``` | resizing behaviour, see next paragraph                                        | ```fit```     |
| ```quality```   | integer, ```0``` to ```100```       | thumbnail quality, bigger is better but files are heavier                     | ```85```      |
| ```upsize```    | boolean                             | whether or not small images should be enlarged with larger thumbnail size     | ```true```    |

**Method** can be set to :
* `basic` : image will be resized to the exact dimension, without keeping aspect ratio.
* `fit` : image will be resized to fit in specified width and / or height, keeping aspect ratio.  
If only one dimension is specified, unspecified dimension (width or height) will be adjusted depending on the other dimension.  
If both are specified, image will be cropped if necessary.
* `max` : image will be resized to fit in specified width and / or height, keeping aspect ratio, without cropping.

### Notes

Thumbnails are generated when visiting the page on which they are displayed.  
Generated thumbnails are saved as image files in ```tajine/public/img/cache``` directory.  
When using mod_rewrite these files names are the same string as the URL provided for images generation. Therefore Apache doesn't even need to process PHP to display the cached version.  
They can safely be deleted to process the generation again.