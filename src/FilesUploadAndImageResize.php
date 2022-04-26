<?php

namespace	anyFileUpload;

use Exception;

class FilesUploadAndImageResize
{
	protected array $allowExtension	=	[];
	protected string $fileDestination	=	'';
	protected int $filePermission	=	0655;
	protected int $n				=	0;
	protected int $s				=	0;
	protected string $format		=	'array';
	protected array $param			=	[];
	protected array $phpFileUploadErrors = [
		0 => 'There is no error, the file uploaded with success',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing a temporary folder',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.',
	];
	public $uploadedData			=	'';

	/**
	 * Must Initialize main param
	 * @param 
	 * 1) Format set the return result set array or json
	 * 2) Pass file extentions in array
	 * 3) Dir path where you want to upload the files *thumb folder will be craeted inside
	 * 
	 */

	public function __construct(string $format, array $allowExtension, string $fileDestination, int $filePermission)
	{
		$this->format			=	strtolower($format);
		$this->allowExtension	=	$allowExtension;
		$this->fileDestination	=	$fileDestination;
		$this->filePermission	=	$filePermission;
	}

	/**
	 * Image compress and processing
	 * Main function that we used to compress images
	 * Consider only jpeg,jpg,png,gif images [If you want to use other extensions then use Imagick]
	 * 
	 */

	public function compressImage(string $sourceURL, string $destinationURL, int $minImgWidth, array $waterMark = [], int $quality, string $newWidth): bool|string
	{
		try {
			if (!empty($waterMark)) {
				$waterMark['font-size']		=	(empty($waterMark['font-size'])) ? 25 : $waterMark['font-size'];
				$waterMark['font-family']	=	(empty($waterMark['font-family'])) ? __DIR__ . "/fonts/Myriad-Pro-Regular.ttf" : $waterMark['font-family'];
				$waterMark['font-color']	=	(empty($waterMark['font-color'])) ? '#000000' : $waterMark['font-color'];
				$positionX = $waterMark['position-x'] ?? '';
				$positionY = $waterMark['position-y'] ?? '';
			}

			$infoImg 	= 	getimagesize($sourceURL);
			$width		=	$infoImg[0];
			$height		=	$infoImg[1];
			if ($width < $minImgWidth) {
				echo '<div class="alert alert-danger">Image <strong>WIDTH</strong> is less then ' . $minImgWidth . 'px</div>';
				exit;
			}

			$image		=	'';
			if ($infoImg['mime'] == 'image/jpeg') {
				$image 	= 	imagecreatefromjpeg($sourceURL);
			} elseif ($infoImg['mime'] == 'image/jpg') {
				$image 	= 	imagecreatefromjpeg($sourceURL);
			} elseif ($infoImg['mime'] == 'image/png') {
				$image 	= 	imagecreatefrompng($sourceURL);
			} elseif ($infoImg['mime'] == 'image/gif') {
				$image 	= 	imagecreatefromgif($sourceURL);
			}

			//Adding watermark
			if (!empty($waterMark)) {
				if (!empty($waterMark['value']) && is_file($waterMark['value'])) {
					$watermark 		= 	imagecreatefrompng($waterMark['value']);
					imagecopy($image, $watermark, 0, 0, 0, 0, imagesx($watermark), imagesy($watermark));
				} else {
					$positionRight 	= 	$positionX;
					$positionBottom = 	$positionY;
					$sx 	= 	imagesx($image);
					$sy 	= 	imagesy($image);
					$watermarktext	=	($waterMark['value'] != "") ? $waterMark['value'] : '';
					$font			=	($waterMark['font-family'] != "") ? $waterMark['font-family'] : '';
					$fontsize		=	($waterMark['font-size'] != "") ? $waterMark['font-size'] : '';
					list($r, $g, $b) = sscanf($waterMark['font-color'], "#%02x%02x%02x");
					$color			=	imagecolorallocate($image, $r, $g, $b);
					imagettftext($image, $fontsize, 0, $sx - $positionRight, $sy - $positionBottom, $color, $font, $watermarktext);
				}
			}

			// Creating new width and height with aspect ratio
			if ($newWidth != "") {
				$diff 		= 	$width / $newWidth;
				$newHeight 	= 	$height / $diff;
			} else {
				$newWidth 	= 	$width;
				$newHeight 	= 	$height;
			}

			$imgResource 	= 	imagecreatetruecolor($newWidth, $newHeight);

			imagealphablending($imgResource, false);
			imagesavealpha($imgResource, true);

			imagecopyresampled($imgResource, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
			if ($infoImg['mime'] == 'image/png' || $infoImg['mime'] == 'image/gif') {
				$newQuality	=	($quality / 10) - 1;
				imagealphablending($imgResource, false);
				imagesavealpha($imgResource, true);
				$responseImage	=	imagepng($imgResource, $destinationURL, $newQuality); //For png quality range is 0-9
			} else {
				$responseImage	=	imagejpeg($imgResource, $destinationURL, $quality);
			}

			imagedestroy($image);
			return $responseImage;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Create folder with permission
	 * If exist no need to create
	 * @param folder path and permission [default = 0655]
	 * 
	 */

	public function createDir(string $fileDestination, int $filePermission): string
	{
		try {
			if (!file_exists($fileDestination)) {
				mkdir($fileDestination, $filePermission, true);
				$fName	=	$fileDestination;
			} else {
				$fName	=	$fileDestination;
			}
			return $fName;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Main function to upload files
	 * This function return Array with status & names
	 * Array index tells the status of files
	 * ['bad-extension-files'] return all files with bad extension which is set by user
	 * ['bad-extensions'] return only bad extensions which is set by user
	 * ['uploaded-files'] return all uploaded files
	 * ['real-uploaded-files'] return all uploaded real files name.
	 * ['not-uploaded-files'] return all not move files into the destination folder [ Note: Folder (Dir) Permission issue ]
	 * 
	 */

	public function uploadFiles(string $fileParamName, int $minImgWidth = 400, array $waterMark, string $reName = "", int $quality = 100, string $newWidth = "", array $thumbWidth = []): array|string
	{
		try {
			if (!empty($_FILES[$fileParamName])) {

				$srcPath	=	$this->createDir($this->fileDestination, $this->filePermission) . '/';
				if (isset($thumbWidth) && !empty($thumbWidth)) {
					$srcThumbPath	=	$this->createDir($this->fileDestination . '/thumb', $this->filePermission) . '/';
				}
				foreach ($_FILES[$fileParamName]['name'] as $val) {
					$this->s++;

					$fileInfo		=	pathinfo(basename($_FILES[$fileParamName]['name'][$this->n]), PATHINFO_EXTENSION);
					$fileName		=	'file-' . $this->s . '-' . rand(0, 999) . time() . '.' . $fileInfo;
					if ($reName != "") {
						$fileName	=	$reName . $this->s . '.' . $fileInfo;
					}
					$filePath			=	trim($srcPath . $fileName);
					if (in_array(strtolower($fileInfo), array_map('strtolower', $this->allowExtension)) || empty($this->allowExtension)) {
						// Upload and compress only images
						if (strtolower($fileInfo) == 'gif' || strtolower($fileInfo) == 'jpeg' || strtolower($fileInfo) == 'jpg' || strtolower($fileInfo) == 'png') {
							if (isset($_FILES[$fileParamName]['error'][$this->n]) && $_FILES[$fileParamName]['error'][$this->n] > 0) {
								$this->param['not_uploaded_files'][]	=	$fileName; //All not move files name into the destination folder [ Note: Check Folder Permission ]
								$this->param['not_uploaded_files_error'][]	=	$this->phpFileUploadErrors[$_FILES[$fileParamName]['error']]; //All not move files name into the destination folder with error [ Note: Check Folder Permission ]
							} else {
								if ($this->compressImage($_FILES[$fileParamName]['tmp_name'][$this->n], $filePath, $minImgWidth, $waterMark, $quality, $newWidth)) {
									if (isset($thumbWidth) && !empty($thumbWidth)) {
										foreach ($thumbWidth as $tw) {
											$thumbPath		=	trim($srcThumbPath . $tw . '-' . $fileName);
											$this->compressImage($_FILES[$fileParamName]['tmp_name'][$this->n], $thumbPath, $minImgWidth, $waterMark, $quality, $tw);
											$this->param['uploaded_thumb_files'][$tw][]	=	$tw . '-' . $fileName; //All uploaded thumbnail files name are move in this array
											$this->param['path_uploaded_thumb_files'][]	=	trim($srcThumbPath); //All uploaded thumbnail files with complete path
										}
									}

									$this->param['real_uploaded_files'][] = $val; //All uploaded files with real name
									$this->param['uploaded_files'][]	=	$fileName; //All uploaded files name are move in this array
									$this->param['path_uploaded_files'][]	=	$srcPath; //All uploaded files name are move in this array
								} else {
									$this->param['not_uploaded_files'][]	=	$fileName; //All not move files name into the destination folder [ Note: Check Folder Permission ]
								}
							}
						} else {
							// Upload all other files
							if (move_uploaded_file($_FILES[$fileParamName]['tmp_name'][$this->n], $filePath)) {
								$this->param['real_uploaded_files'][] = $val; //All uploaded files with real name
								$this->param['uploaded_files'][]	=	$fileName; //All uploaded files name are move in this array
								$this->param['path_uploaded_files'][]	=	$srcPath; //All uploaded files name are move in this array
							} else {
								$this->param['not_uploaded_files'][]	=	$fileName; //All not move files name into the destination folder [ Note: Check Folder Permission ]
							}
						}
					} else {
						$this->param['bad_extension_files'][]	=	$fileName; //Bad extension files name are move in this array
						$this->param['bad_extensions'][]		=	strtolower($fileInfo);  //Bad extensions move in this array
					}

					$this->n++;
				}
				if ($this->format == "array") {
					$this->uploadedData	=	$this->param;
				} else if ($this->format == "json") {
					$this->uploadedData	=	json_encode($this->param);
				}
				return $this->uploadedData;
			}
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
}
