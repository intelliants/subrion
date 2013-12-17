<?php
//##copyright##

require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'ImageWorkshop.php';

class iaPicture extends ImageWorkshop
{
	const FIT = 'fit';
	const CROP = 'crop';

	const SOURCE_PREFIX = 'source_';

	protected $_typesMap = array(
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/pjpeg' => 'jpg',
		'image/png' => 'png'
	);


	/**
	 * Return image file extension
	 *
	 * @param string $aFile file mime type
	 *
	 * @return string
	 */
	public function getImageExt($fileName)
	{
		return array_key_exists($fileName, $this->_typesMap) ? '.' . $this->_typesMap[$fileName] : '';
	}

	protected static function _createFilename($name, $ext, $thumb = false)
	{
		return $thumb ? $name . $ext : $name . '~' . $ext;
	}

	protected function _applyWaterMark($image)
	{
		$iaCore = &$this->iaCore;

		// add watermark to an image
		$watermarkFile = $iaCore->get('site_watermark') ?
				IA_UPLOADS . $iaCore->get('site_watermark') :
				IA_TEMPLATES . $iaCore->get('tmpl') . IA_DS . 'img' . IA_DS . 'watermark.png';
		if ($iaCore->get('site_watermark') && file_exists($watermarkFile) && !is_dir($watermarkFile))
		{
			$watermark_positions = array(
				'top_left' => 'TL',
				'top_center' => 'MT',
				'top_right' => 'RT',
				'middle_left' => 'LM',
				'middle_center' => 'MM',
				'middle_right' => 'RM',
				'bottom_left' => 'LB',
				'bottom_center' => 'MB',
				'bottom_right' => 'RB'
			);

			$position = $iaCore->get('site_watermark_position');
			$position = $position && array_key_exists($position, $watermark_positions) ? $watermark_positions[$position] : $watermark_positions['bottom_right'];

			$watermark = self::initFromPath($watermarkFile);
			$watermark->opacity(60);

			$image->addLayerOnTop($watermark, 10, 10, $position);
		}

		return $image;
	}

	/**
	 * Process image types here and returns filename to write
	 *
	 * @param array $aFile uploaded file information
	 * @param string $folder the file path
	 * @param string $aName the file name
	 * @param integer $imageInfo image information array: width, height, resize_mode
	 *
	 * @return string
	 */
	public function processImage($aFile, $folder, $aName, $imageInfo)
	{
		$ext = $this->getImageExt($aFile['type']);

		if (empty($ext))
		{
			$this->iaCore->setMessage(iaLanguage::get('file_type_error', array('extension' => implode(',', array_unique($this->_typesMap)))));

			return false;
		}

		$path = IA_UPLOADS . $folder;
		$image = self::initFromPath($aFile['tmp_name']);

		// save source image
		$image->save($path, self::SOURCE_PREFIX . $aName . $ext);

		// process thumbnails for files uploaded in CKEditor and other tools
		if (empty($imageInfo['image_width']) && empty($imageInfo['image_height']))
		{
			// apply watermark
			$image = self::_applyWaterMark($image);
			$image->save($path, self::_createFilename($aName, $ext));

			return true;
		}

		// check this is an animated GIF
		if ('image/gif' == $aFile['type'] && $this->iaCore->get('allow_animated_gifs'))
		{
			require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'Core' . IA_DS . 'GifFrameExtractor.php';

			$gifPath = $aFile['tmp_name'];
			if (GifFrameExtractor::isAnimatedGif($gifPath))
			{
				// Extractions of the GIF frames and their durations
				$gfe = new GifFrameExtractor();
				$frames = $gfe->extract($gifPath);

				// For each frame, we add a watermark and we resize it
				$retouchedFrames = array();
				$thumbFrames = array();
				foreach ($frames as $frame)
				{
					$frameLayer = self::initFromResourceVar($frame['image']);
					$thumbLayer = self::initFromResourceVar($frame['image']);

					$frameLayer->resizeInPixel($imageInfo['image_width'], $imageInfo['image_height'], true);
					$frameLayer = self::_applyWaterMark($frameLayer);
					$retouchedFrames[] = $frameLayer->getResult();

					$thumbLayer->resizeInPixel($imageInfo['thumb_width'], $imageInfo['thumb_height'], true);
					$thumbFrames[] = $thumbLayer->getResult();
				}

				// Then we re-generate the GIF
				require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'Core' . IA_DS . 'GifCreator.php';

				$gc = new GifCreator();
				$gc->create($retouchedFrames, $gfe->getFrameDurations(), 0);
				file_put_contents($path . self::_createFilename($aName, $ext), $gc->getGif());

				$thumbCreator = new GifCreator();
				$thumbCreator->create($thumbFrames, $gfe->getFrameDurations(), 0);
				file_put_contents($path . self::_createFilename($aName, $ext, true), $thumbCreator->getGif());

				return self::_createFilename($folder . $aName, $ext, true);
			}
		}

		// save full image
		($imageInfo['image_width'] > $imageInfo['image_height']) ? $largestSide = $imageInfo['image_width'] : $largestSide = $imageInfo['image_height'];
		$image->resizeByLargestSideInPixel($largestSide, true);
		$image = self::_applyWaterMark($image);
		$image->save($path, self::_createFilename($aName, $ext));

		// generate thumbnail
		$thumbWidth = $imageInfo['thumb_width'] ? $imageInfo['thumb_width'] : $this->iaCore->get('thumb_w');
		$thumbHeight = $imageInfo['thumb_height'] ? $imageInfo['thumb_height'] : $this->iaCore->get('thumb_h');
		if ($thumbWidth || $thumbHeight)
		{
			$thumb = self::initFromPath($aFile['tmp_name']);
			switch ($imageInfo['resize_mode'])
			{
				case self::FIT:
					$thumb->resizeInPixel($thumbWidth, $thumbHeight, true, 0, 0, 'MM');
					break;

				case self::CROP:
					$largestSide = $thumbWidth > $thumbHeight ? $thumbWidth : $thumbHeight;
					$thumb->cropMaximumInPixel(0, 0, 'MM');
					$thumb->resizeInPixel($largestSide, $largestSide);
					$thumb->cropInPixel($thumbWidth, $thumbHeight, 0, 0, 'MM');
			}

			$thumb->save($path, self::_createFilename($aName, $ext, true));
		}

		return self::_createFilename($folder . $aName, $ext, true);
	}

	// TODO: revise
	public function delete($fileName)
	{
		if (':' == $fileName[1])
		{
			$unpackedData = unserialize($fileName);
			if (is_array($unpackedData) && $unpackedData)
			{
				if (is_array($unpackedData[key($unpackedData)])) // multiupload field
				{
					foreach ($unpackedData as $entry)
					{
						if (isset($entry['path']) && $entry['path'])
						{
							$this->_deleteSingleImage($entry['path']);
						}
					}

					return true;
				}
				else // single image field
				{
					return $this->_deleteSingleImage($unpackedData['path']);
				}
			}

			return false;
		}
		else
		{
			return $this->_deleteSingleImage($fileName);
		}
	}

	protected function _deleteSingleImage($fileName)
	{
		$fileThumb = explode('.', $fileName);
		$fileThumb = end($fileThumb);
		$fileThumb = str_replace('.' . $fileThumb, '~.' . $fileThumb, $fileName);

		$fileOriginal = basename($fileName);
		$fileOriginal = str_replace($fileOriginal, self::SOURCE_PREFIX . $fileOriginal, $fileName);

		$iaUtil = $this->iaCore->factory('util');

		return (bool)(
			$iaUtil->deleteFile(IA_UPLOADS . $fileName) &&
			$iaUtil->deleteFile(IA_UPLOADS . $fileThumb) &&
			$iaUtil->deleteFile(IA_UPLOADS . $fileOriginal)
		);
	}
}