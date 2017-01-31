<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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
 * @link https://subrion.org/
 *
 ******************************************************************************/

require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'ImageWorkshop.php';
use phpimageworkshop\ImageWorkshop as ImageWorkshop;

class iaPicture extends abstractCore
{
	const FIT = 'fit';
	const CROP = 'crop';

	protected $_typesMap = array(
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'image/pjpeg' => 'jpg',
		'image/png' => 'png'
	);

	protected $_allowAnimatedGifs = false;


	public function getSupportedImageTypes()
	{
		return $this->_typesMap;
	}

	protected function _applyWaterMark(&$image)
	{
		$iaCore = &$this->iaCore;

		if (!$iaCore->get('watermark'))
		{
			return;
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

		$opacity = $iaCore->get('watermark_opacity', 75);
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
			$watermark->opacity($opacity);

			$image->addLayerOnTop($watermark, 10, 10, $position);

			// delete layer otherwise it's applied several times
			$watermark->delete();
		}
		else
		{
			$watermarkFile = $iaCore->get('watermark_image') ?
				IA_UPLOADS . $iaCore->get('watermark_image') :
				IA_TEMPLATES . $iaCore->get('tmpl') . IA_DS . 'img' . IA_DS . 'watermark.png';

			if (is_file($watermarkFile) && !is_dir($watermarkFile))
			{
				$watermark = ImageWorkshop::initFromPath($watermarkFile);
				$watermark->opacity($opacity);

				$image->addLayerOnTop($watermark, 10, 10, $position);

				// delete layer otherwise it's applied several times
				$watermark->delete();
			}
		}
	}

	public function process($sourceFile, $destinationFile, $mimeType, $width, $height, $resizeMode, $applyWatermark = false)
	{
		$result = false;

		try {
			// check this is an animated GIF
			if ('image/gif' == $mimeType)
			{
				return $this->_processAnimatedGif($sourceFile, $destinationFile, $width, $height, $applyWatermark);
			}

			$image = ImageWorkshop::initFromPath($sourceFile);

			// resize image
			switch ($resizeMode)
			{
				case self::FIT:
					$image->resizeInPixel($width, $height, true, 0, 0, 'MM');
					break;
				case self::CROP:
					$image->cropToAspectRatioInPixel($width, $height, 0, 0, 'MM');
					$image->resizeInPixel($width, $height);
			}

			// apply watermark
			$applyWatermark && self::_applyWaterMark($image);

			$path = pathinfo($destinationFile, PATHINFO_DIRNAME);
			$file = pathinfo($destinationFile, PATHINFO_BASENAME);

			$image->save($path, $file);

			$result = true;
		}
		catch (Exception $e)
		{
			$this->setMessage(iaLanguage::get('invalid_image_file'));
		}

		return $result;
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

	protected function _processAnimatedGif($sourceFile, $destinationFile, $width, $height, $applyWatermark = false)
	{
		require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'Core' . IA_DS . 'GifFrameExtractor.php';

		if (GifFrameExtractor\GifFrameExtractor::isAnimatedGif($sourceFile) && $this->_allowAnimatedGifs)
		{
			// Extractions of the GIF frames and their durations
			$gfe = new GifFrameExtractor\GifFrameExtractor();

			// For each frame, we add a watermark and we resize it
			$frames = array();
			foreach ($gfe->extract($sourceFile) as $frame)
			{
				$frameLayer = ImageWorkshop::initFromResourceVar($frame['image']);

				$frameLayer->resizeInPixel($width, $height, true);
				$applyWatermark && self::_applyWaterMark($frameLayer);
				$frames[] = $frameLayer->getResult();
			}

			// Then we re-generate the GIF
			require_once IA_INCLUDES . 'phpimageworkshop' . IA_DS . 'Core' . IA_DS . 'GifCreator.php';

			$gc = new GifCreator\GifCreator();
			$gc->create($frames, $gfe->getFrameDurations(), 0);

			return file_put_contents($destinationFile, $gc->getGif());
		}

		return false;
	}
}