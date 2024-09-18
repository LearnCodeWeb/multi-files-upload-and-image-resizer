<?php

namespace anyFileUpload;

use Exception;

class FilesUploadAndImageResize
{
	protected array $allowExtension = [];
	protected string $fileDestination = '';
	protected int $filePermission = 0655;
	protected int $n = 0;
	protected int $s = 0;
	protected string $format = 'array';
	protected array $param = [];
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
	public $uploadedData = '';

	/**
	 * Must Initialize main param
	 * @param string $format (array or json)
	 * @param array $allowExtension Allowed file extensions
	 * @param string $fileDestination Path to save files
	 * @param int $filePermission File permissions (0755, 0644 etc)
	 */
	public function __construct(string $format, array $allowExtension, string $fileDestination, int $filePermission)
	{
		$this->format = strtolower($format);
		$this->allowExtension = $allowExtension;
		$this->fileDestination = $fileDestination;
		$this->filePermission = $filePermission;
	}

	/**
	 * Compress and process images
	 * @param string $sourceURL Source file path
	 * @param string $destinationURL Destination file path
	 * @param int $minImgWidth Minimum image width
	 * @param array $waterMark Watermark settings
	 * @param int $quality Image quality (1-100 for JPEG, 0-9 for PNG)
	 * @param string $newWidth New width for the resized image
	 * @return bool|string True on success, error message on failure
	 */
	public function compressImage(string $sourceURL, string $destinationURL, int $minImgWidth, array $waterMark = [], int $quality, string $newWidth): bool|string
	{
		try {
			// Check if GD extension is enabled
			if (!extension_loaded('gd')) {
				return '[Files not moved] - Check your php.ini file and enable or install GD extension';  // Extensions missing
			}

			// Watermark settings
			if (!empty($waterMark)) {
				$waterMark['font-size'] = $waterMark['font-size'] ?? 25;
				$waterMark['font-family'] = $waterMark['font-family'] ?? __DIR__ . "/fonts/Myriad-Pro-Regular.ttf";
				$waterMark['font-color'] = $waterMark['font-color'] ?? '#000000';
				$positionX = $waterMark['position-x'] ?? '';
				$positionY = $waterMark['position-y'] ?? '';
			}

			$infoImg = getimagesize($sourceURL);
			$width = $infoImg[0];
			$height = $infoImg[1];

			if ($width < $minImgWidth) {
				return '<div class="alert alert-danger">Image <strong>WIDTH</strong> is less than ' . $minImgWidth . 'px</div>';
			}

			$image = null;
			switch ($infoImg['mime']) {
				case 'image/jpeg':
				case 'image/jpg':
					$image = imagecreatefromjpeg($sourceURL);
					break;
				case 'image/png':
					$image = imagecreatefrompng($sourceURL);
					break;
				case 'image/gif':
					$image = imagecreatefromgif($sourceURL);
					break;
				default:
					return 'Unsupported image type.';
			}

			// Adding watermark
			if (!empty($waterMark)) {
				if (!empty($waterMark['value']) && is_file($waterMark['value'])) {
					$watermark = imagecreatefrompng($waterMark['value']);
					imagecopy($image, $watermark, 0, 0, 0, 0, imagesx($watermark), imagesy($watermark));
				} else {
					$positionRight = $positionX;
					$positionBottom = $positionY;
					$sx = imagesx($image);
					$sy = imagesy($image);
					$watermarktext = $waterMark['value'] ?? '';
					$font = $waterMark['font-family'] ?? '';
					$fontsize = $waterMark['font-size'] ?? '';
					list($r, $g, $b) = sscanf($waterMark['font-color'], "#%02x%02x%02x");
					$color = imagecolorallocate($image, $r, $g, $b);
					imagettftext($image, $fontsize, 0, $sx - $positionRight, $sy - $positionBottom, $color, $font, $watermarktext);
				}
			}

			// Resize image while maintaining aspect ratio
			if ($newWidth != "") {
				$diff = $width / $newWidth;
				$newHeight = $height / $diff;
			} else {
				$newWidth = $width;
				$newHeight = $height;
			}

			$imgResource = imagecreatetruecolor($newWidth, $newHeight);
			imagealphablending($imgResource, false);
			imagesavealpha($imgResource, true);
			imagecopyresampled($imgResource, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

			if ($infoImg['mime'] == 'image/png' || $infoImg['mime'] == 'image/gif') {
				// For PNG/GIF quality range is 0-9
				$newQuality = max(0, min(9, floor($quality / 10)));
				imagepng($imgResource, $destinationURL, $newQuality);
			} else {
				// For JPEG quality range is 0-100
				$quality = max(0, min(100, $quality));
				imagejpeg($imgResource, $destinationURL, $quality);
			}

			imagedestroy($image);
			imagedestroy($imgResource);
			return true;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Create directory with permissions if not exists
	 * @param string $fileDestination Folder path
	 * @param int $filePermission Directory permission (0755, 0644 etc)
	 * @return string Path of created or existing directory
	 */
	public function createDir(string $fileDestination, int $filePermission = 0655): string
	{
		try {
			if (!file_exists($fileDestination)) {
				mkdir($fileDestination, $filePermission, true);
			}
			return $fileDestination;
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Upload files and handle image processing
	 * @param string $fileParamName Input file field name
	 * @param int $minImgWidth Minimum image width
	 * @param array $waterMark Watermark settings
	 * @param string $reName Rename files with this prefix
	 * @param int $quality Image quality (1-100 for JPEG, 0-9 for PNG)
	 * @param string $newWidth New width for the resized image
	 * @param array $thumbWidth Array of thumbnail widths
	 * @return array|string Upload results
	 */
	public function uploadFiles(string $fileParamName, int $minImgWidth = 400, array $waterMark = [], string $reName = "", int $quality = 100, string $newWidth = "", array $thumbWidth = []): array|string
	{
		try {
			if (!empty($_FILES[$fileParamName])) {
				$srcPath = $this->createDir($this->fileDestination, $this->filePermission) . '/';
				if (!empty($thumbWidth)) {
					$srcThumbPath = $this->createDir($this->fileDestination . '/thumb', $this->filePermission) . '/';
				}

				foreach ($_FILES[$fileParamName]['name'] as $index => $val) {
					$this->s++;
					$fileInfo = pathinfo(basename($_FILES[$fileParamName]['name'][$index]), PATHINFO_EXTENSION);
					$fileName = 'file-' . $this->s . '-' . rand(0, 999) . time() . '.' . $fileInfo;
					if ($reName != "") {
						$fileName = $reName . $this->s . '.' . $fileInfo;
					}
					$filePath = trim($srcPath . $fileName);

					if (in_array(strtolower($fileInfo), array_map('strtolower', $this->allowExtension)) || empty($this->allowExtension)) {
						if (isset($_FILES[$fileParamName]['error'][$index]) && $_FILES[$fileParamName]['error'][$index] > 0) {
							$this->param['not_uploaded_files'][] = $fileName;
							$this->param['not_uploaded_files_error'][] = $this->phpFileUploadErrors[$_FILES[$fileParamName]['error'][$index]];
						} else {
							if (in_array(strtolower($fileInfo), ['gif', 'jpeg', 'jpg', 'png'])) {
								if ($this->compressImage($_FILES[$fileParamName]['tmp_name'][$index], $filePath, $minImgWidth, $waterMark, $quality, $newWidth)) {
									if (!empty($thumbWidth)) {
										foreach ($thumbWidth as $tw) {
											$thumbPath = trim($srcThumbPath . $tw . '-' . $fileName);
											$this->compressImage($_FILES[$fileParamName]['tmp_name'][$index], $thumbPath, $minImgWidth, $waterMark, $quality, $tw);
											$this->param['uploaded_thumb_files'][$tw][] = $tw . '-' . $fileName;
											$this->param['path_uploaded_thumb_files'][] = trim($srcThumbPath);
										}
									}
									$this->param['real_uploaded_files'][] = $val;
									$this->param['uploaded_files'][] = $fileName;
									$this->param['path_uploaded_files'][] = $srcPath;
								} else {
									$this->param['not_uploaded_files'][] = $fileName;
								}
							} else {
								if (move_uploaded_file($_FILES[$fileParamName]['tmp_name'][$index], $filePath)) {
									$this->param['real_uploaded_files'][] = $val;
									$this->param['uploaded_files'][] = $fileName;
									$this->param['path_uploaded_files'][] = $srcPath;
								} else {
									$this->param['not_uploaded_files'][] = $fileName;
								}
							}
						}
					} else {
						$this->param['bad_extension_files'][] = $fileName;
						$this->param['bad_extensions'][] = strtolower($fileInfo);
					}
				}

				return $this->uploadedData	=	$this->format === "json" ? json_encode($this->param) : $this->param;
			}
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
}
