# Multi files upload & image resizer

A PHP base class that you can use to upload any file into the server. It is a very lightweight class and highly customizable. You can use this class with any framework or with core PHP.

----

Composer installation:

```composer
composer require learncodeweb/upload-files-with-image-thumb-and-resizer
```

----

A PHP base class that you can use to upload any file into the server. It is a very lightweight class and heighly customizable. You can use this class with any framewrok or with core PHP.

1) Upload Single Or Multiple Files.
2) Upload Any Type Of Files (Not Only Images).
3) Image file can Resize.
4) Create Image Thumbnail (With Keep The Image Aspect Ratio).
5) You can add watermark (Text, Image).
6) Easy Integration With Forms.
7) Create Any Number Of Thumbnails Under One Upload.
8) Customizable Paths To Thumbnails Folders.
9) Customizable Thumbnails Sizes And Dimensions.
10) Files Extension Filters.
11) File Size Limit for Uploading.

----

How to use after installation:

```php
require('../multi-files-upload-and-image-resizer.php');
$rename    =    rand(1000, 5000) . time(); // left empty if you want the real file name
$upload    =    new anyFileUpload\ImageUploadAndResize('array', ['jpeg', 'jpg', 'png'], '../uploads', 0655);
$upload->uploadFiles('files', 250, '', $rename, 100, '850', ['350','450']);
```

>For watermark you will use array and able to add image as a watermark or text.

**With text below will be the parameters:**
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

**With image below will be the parameters:**
```php
[
    'value' => "your-image-complete-path",
    'position-x' => 400,
    'position-y' => 100
];
```

**And the response will be get like below:**

In the below response you will get the uploaded/not uploaded/bad extensions and success/error flags array or json data.

```php
print "<pre>";
print_r($upload->uploadedData);
print "</pre>";
```
