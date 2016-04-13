Prestajine
==========

Prestajine is an extra image manager for [PrestaShop](https://github.com/PrestaShop/PrestaShop). It uses the [Intervention Image](https://github.com/Intervention/image) library.

## Features

Prestajine is not bypassing PrestaShop's original image manager, it adds an extra management.  
It allows you to create images and thumbnails at any dimensions, in a flexible way,
always keeping aspect ratio (no stretching) without adding any plain background.

**Currently it only works with products images, and only with jpg.**

## Requirements

Prestajine has been developed and tested on PrestaShop 1.6.1.1

## Installation

 1. Clone the repository using Git in the modules directory :  
 `git clone https://github.com/crachecode/prestajine.git`

 2. Install dependencies using Composer :  
 `composer update`

 3. Copy the `config.example.php` file to `config.php` and modify as necessary.

 4. Install the module from PrestaShop back-office.

## Configuration

Copy `config.example.php` to `config.php` and edit it.

Image types are defined as such :
```
'products' => [
	'big' => [
		'width' => 1280,
		'height' => 1024,
		'method' => 'basic',
		'quality' => 90,
		'upsize' => false
	],
	'thumbnail' => [
		'width' => 120,
		'height' => 100,
		'method' => 'fit',
		'quality' => 95,
		'upsize' => true,
		'regen' => true
	]
]
```
Images always keep their aspect ratios.  
`big` and `thumbnail` are image types (name for size format). Do not use any special character, these names will be used to create directories.

If you don't specify **width** *or* **height**, images will be set to the dimension you specify,
and the other dimension will be adjusted keeping original aspect ratio.  
Method doesn't have to be specified in that case.

**Methods** can be set to `basic` (default, images will be resized not to exceed specified dimensions),
or `fit` (images will be resized to the exact dimension you specify, cropping some parts if necessary).

**Upsize** defines if you want to resize images when originals are smaller than destinations.

**Regen** specify if every images have to be regenerated (e.g. put it on if you changed anything in the setup). Otherwise image files are generated only if they don't exist yet.

## Using Prestajine

Prestajine creates an img/prestajine directory, in which each image type has its own subdirectory.  
Images are saved in these directories as `[image id].jpg` when you add new images for a product,
or when you regenerate thumbnails from the back-office (Preferences > Images > Regenerate Thumbnails).

You can display them in your template files using this code :
```
{$img_ps_dir}prestajine/[image type]/{$image.id_image}.jpg
```
You have to replace `[image type]` depending on which one you want to display.