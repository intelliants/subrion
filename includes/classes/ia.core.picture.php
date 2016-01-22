<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'ImageWorkshop.php';
use phpimageworkshop\ImageWorkshop as ImageWorkshop;

class iaPicture extends abstractCore
{
	const FIT = 'fit';
	const CROP = 'crop';
	const ALLOW_ANIMATED_GIFS = false;

	const SOURCE_PREFIX = 'source_';

	protected static $_typesMap = array(
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/pjpeg' => 'jpg',
		'image/png' => 'png'
	);


	/**
	 * Return image file extension
	 *
	 * @param string $fileName file mime type
	 *
	 * @return string
	 */
	protected static function _getImageExt($fileName)
	{
		return array_key_exists($fileName, self::$_typesMap) ? '.' . self::$_typesMap[$fileName] : '';
	}

	/**
	 * Generate filename for an uploaded image
	 *
	 * @param string $name name of the uploaded file
	 * @param string $ext extension of the uploaded file
	 * @param bool $thumb true to generate thumbnail filename
	 *
	 * @return string
	 */
	protected static function _createFilename($name, $ext, $thumb = false)
	{
		return $thumb ? $name . $ext : $name . '~' . $ext;
	}

	protected function _applyWaterMark($image)
	{
		$iaCore = &$this->iaCore;

		if (!$iaCore->get('watermark'))
		{
			return $image;
		}

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

		$position = $iaCore->get('watermark_position');
		$position = $position && array_key_exists($position, $watermark_positions) ? $watermark_positions[$position] : $watermark_positions['bottom_right'];

		if ('text' == $iaCore->get('watermark_type'))
		{
			$watermark = ImageWorkshop::initTextLayer(
				$iaCore->get('watermark_text', 'Subrion CMS'),
				IA_INCLUDES . 'phpimageworkshop/Fonts/arial.ttf',
				$iaCore->get('watermark_text_size', 11),
				$iaCore->get('watermark_text_color', '#FFFFFF')
			);

			$image->addLayerOnTop($watermark, 10, 10, $position);
		}
		else
		{
			$watermarkFile = $iaCore->get('watermark_image') ?
				IA_UPLOADS . $iaCore->get('watermark_image') :
				IA_TEMPLATES . $iaCore->get('tmpl') . IA_DS . 'img' . IA_DS . 'watermark.png';
			if (file_exists($watermarkFile) && !is_dir($watermarkFile))
			{
				$watermark = ImageWorkshop::initFromPath($watermarkFile);
				$watermark->opacity(70);

				$image->addLayerOnTop($watermark, 10, 10, $position);
			}
		}

		return $image;
	}

	/**
	 * Process image types here and returns filename to write
	 *
	 * @param array $aFile uploaded file information
	 * @param string $folder the file path
	 * @param string $aName the file name
	 * @param array $imageInfo image information array: width, height, resize_mode
	 *
	 * @return bool|string
	 * @throws ImageWorkshopException
	 * @throws Exception
	 */
	public function processImage($aFile, $folder, $aName, $imageInfo)
	{
		$ext = self::_getImageExt($aFile['type']);

		if (empty($ext))
		{
			$this->setMessage(iaLanguage::getf('file_type_error', array('extension' => implode(', ', array_unique(self::$_typesMap)))));

			return false;
		}

		try {
			$path = IA_UPLOADS . $folder;
			$image = ImageWorkshop::initFromPath($aFile['tmp_name']);

			// save source image
			$image->save($path, self::SOURCE_PREFIX . $aName . $ext);

			// process thumbnails for files uploaded in CKEditor and other tools
			if (empty($imageInfo))
			{
				// apply watermark
				$image = self::_applyWaterMark($image);
				$image->save($path, self::_createFilename($aName, $ext));

				return true;
			}

			// check this is an animated GIF
			if (self::ALLOW_ANIMATED_GIFS && 'image/gif' == $aFile['type'])
			{
				require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'Core' . IA_DS . 'GifFrameExtractor.php';

				$gifPath = $aFile['tmp_name'];
				if (GifFrameExtractor\GifFrameExtractor::isAnimatedGif($gifPath))
				{
					// Extractions of the GIF frames and their durations
					$gfe = new GifFrameExtractor\GifFrameExtractor();
					$frames = $gfe->extract($gifPath);

					// For each frame, we add a watermark and we resize it
					$retouchedFrames = array();
					$thumbFrames = array();
					foreach ($frames as $frame)
					{
						$frameLayer = ImageWorkshop::initFromResourceVar($frame['image']);
						$thumbLayer = ImageWorkshop::initFromResourceVar($frame['image']);

						$frameLayer->resizeInPixel($imageInfo['image_width'], $imageInfo['image_height'], true);
						$frameLayer = self::_applyWaterMark($frameLayer);
						$retouchedFrames[] = $frameLayer->getResult();

						$thumbLayer->resizeInPixel($imageInfo['thumb_width'], $imageInfo['thumb_height'], true);
						$thumbFrames[] = $thumbLayer->getResult();
					}

					// Then we re-generate the GIF
					require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'Core' . IA_DS . 'GifCreator.php';

					$gc = new GifCreator\GifCreator();
					$gc->create($retouchedFrames, $gfe->getFrameDurations(), 0);
					file_put_contents($path . self::_createFilename($aName, $ext), $gc->getGif());

					$thumbCreator = new GifCreator\GifCreator();
					$thumbCreator->create($thumbFrames, $gfe->getFrameDurations(), 0);
					file_put_contents($path . self::_createFilename($aName, $ext, true), $thumbCreator->getGif());

					return self::_createFilename($folder . $aName, $ext, true);
				}
			}

			// save full image
			$largestSide = ($imageInfo['image_width'] > $imageInfo['image_height']) ? $imageInfo['image_width'] : $imageInfo['image_height'];

			if ($largestSide)
			{
				$image->resizeByLargestSideInPixel($largestSide, true);
			}

			$image = self::_applyWaterMark($image);
			$image->save($path, self::_createFilename($aName, $ext));

			// generate thumbnail
			$thumbWidth = $imageInfo['thumb_width'] ? $imageInfo['thumb_width'] : $this->iaCore->get('thumb_w');
			$thumbHeight = $imageInfo['thumb_height'] ? $imageInfo['thumb_height'] : $this->iaCore->get('thumb_h');

			if ($thumbWidth || $thumbHeight)
			{
				$thumb = ImageWorkshop::initFromPath($aFile['tmp_name']);
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
		}
		catch (Exception $e)
		{
			$this->iaView->setMessages(iaLanguage::get('invalid_image_file'));
			return false;
		}

		return self::_createFilename($folder . $aName, $ext, true);
	}

	/**
	 * Delete picture field value
	 *
	 * @param string $fileName filename to be deleted
	 *
	 * @return bool
	 */
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

	/**
	 * Delete image & derivatives (original, thumbnail) from the filesystem
	 *
	 * @param string $fileName filename to be deleted
	 *
	 * @return bool
	 */
	protected function _deleteSingleImage($fileName)
	{
		$fileThumb = explode('.', $fileName);
		$fileThumb = end($fileThumb);
		$fileThumb = str_replace('.' . $fileThumb, '~.' . $fileThumb, $fileName);

		$fileOriginal = basename($fileName);
		$fileOriginal = str_replace($fileOriginal, self::SOURCE_PREFIX . $fileOriginal, $fileName);

		$this->iaCore->factory('util')->deleteFile(array(
			IA_UPLOADS . $fileName,
			IA_UPLOADS . $fileThumb,
			IA_UPLOADS . $fileOriginal
		));

		return (
			!file_exists(IA_UPLOADS . $fileName) &&
			!file_exists(IA_UPLOADS . $fileThumb) &&
			!file_exists(IA_UPLOADS . $fileOriginal)
		);
	}
}