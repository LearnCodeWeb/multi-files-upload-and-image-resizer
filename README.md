# Multi files uploader & image resizer

A PHP base class that you can use to upload any file into the server. It is a very lightweight class and highly customizable. You can use this class with any framework or with core PHP.

## Dependencies:

This class is based on the GD library for image processing. If you don’t have this library install it or enable it in php.ini. To install the GD library use the below command.

```
sudo apt-get install php-gd
```

After installation makes sure you have a gd.ini file then open the php.ini file and find it below.

```
;extension=gd
```

Remove the comment [ **;** ] and change it to below.

```
extension=gd
```

----

## Composer installation:

```
composer require learncodeweb/filesupload
```

After installation recreates the autoload file with the help of the below command.

```
composer dump-autoload
```

----

## How to import into the project:
In laravel 8/9 (Tested)
```php
use anyFileUpload\FilesUploadAndImageResize as anyFilesUpload;

$files = new anyFilesUpload('array', ['jpg', 'jpeg', 'png'], public_path('uploads'), 0777);
$files->uploadFiles('files', 250, '', '', 100, '850', ['350']);

dd($files->uploadedData);
```

In core PHP just add the autoload.php file to your project like below.

```php
required('.../vendor/autoload.php');
$upload    =    new anyFileUpload\ImageUploadAndResize('array', ['jpeg', 'jpg', 'png'], '../uploads', 0655);
$upload->uploadFiles('files', 250, '', $rename, 100, '850', ['350','450']);
```

## Class option & features:

A PHP base class that you can use to upload any file into the server. It is a very lightweight class and highly customizable. You can use this class with any framework or with core PHP.

1) Upload Single Or Multiple Files.
2) Upload Any Type Of Files (Not Only Images).
3) The image file can Resize.
4) Create Image Thumbnails (With Keep The Image Aspect Ratio).
5) You can add a watermark (Text, Image).
6) Easy Integration With Forms.
7) Create Any Number Of Thumbnails Under One Upload.
8) Customizable Paths To Thumbnails Folders.
9) Customizable Thumbnails Sizes And Dimensions.
10) Files Extension Filters.
11) File Size Limit for Uploading.


## All parameters that you need to set in the constructor

|   Parameters         |   Default Value   |   Description |
|----------------------|-------------------|---------------|
|   Response format    |   array           |   You can set it to JSON or array.    |
|   Allow extensions   |   Not set         |   You can set the file extensions in the array like ['jpg','PNG'].    |
|   Dir path           |   false           |   Folder name where you need to save images [‘../Upload/’]. If you set the thumbs size array, the thumb folder will be created and thumb files move there. |
|   Dir permission     |   0655            |   You can set the permission of the newly created Dir.   |

## All parameters that you need to set in the method

|   Parameters          |   Default Value   |   Description    |
|-----------------------|-------------------|------------------|
|   Input index name    |   User set        |   You can set your input="file" name index.   |
|   Check minimum with  |   400             |   Default min with is 400, you can change with any number.    |
|   Watermark           |   empty           |   You can set the watermark array to see the below details.  |
|   Re-name             |   empty           |   Rename the uploaded file if you need it. Left empty get system created default name.    |
|   Image Quality       |   100             |   Image quality in percent 1-100. Apply only for images (jpg,jpeg,png,gif).    |
|   New Width           |   empty           |   If you want to resize the image then pass int value else upload without resizing the image will be saved.   |
|   Thumb Widths        |   empty           |   If you want to create multiple thumbs then pass int value with array [350,450].  |


----

## How to use with direct access:

```php
require('../FilesUploadAndImageResize.php'); // File direct access
$rename    =    rand(1000, 5000) . time(); // left empty if you want the real file name
$upload    =    new anyFileUpload\ImageUploadAndResize('array', ['jpeg', 'jpg', 'png'], '../uploads', 0655);
$upload->uploadFiles('files', 250, '', $rename, 100, '850', ['350','450']);
```

## For watermark you will use an array and be able to add the image as a watermark or text.

>With text below will be the parameters:
```php
[
    'value' => "HI I AM ZAID",
    'font-size' => 50,
    'font-family' => "../fonts/Myriad-Pro-Regular.ttf",
    'font-color' => '#0a103e',
    'position-x' => 400,
    'position-y' => 100
];
```

>With the image below will be the parameters:
```php
[
    'value' => "your-image-complete-path",
    'position-x' => 400,
    'position-y' => 100
];
```

## The response will get like the below:

In the below response, you will get the uploaded/not uploaded/bad extensions and success/error flags array or JSON data.

```php
print "<pre>";
print_r($upload->uploadedData);
print "</pre>";
```

## Upload file size change on the server

There is a possibility the upload file size is not set on a server, the default 2MB value is set on a server. If you face this type of issue just find the right path of your php.ini file and change the bleow two parameters.

```
upload_max_filesize = 2M
post_max_size = 8M
```

Change to below.

```
upload_max_filesize = 100M
post_max_size = 150M
```

### Remember:
post max size should be greater than upload max filesize.
