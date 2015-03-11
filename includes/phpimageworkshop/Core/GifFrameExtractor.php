<?php

namespace GifFrameExtractor;

/**
 * Extract the frames (and their duration) of a GIF
 * 
 * @version 1.5
 * @link https://github.com/Sybio/GifFrameExtractor
 * @author Sybio (Clément Guillemain  / @Sybio01)
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright Clément Guillemain
 */
class GifFrameExtractor
{
    // Properties
    // ===================================================================================
    
    /**
     * @var resource
     */
    private $gif;
    
    /**
     * @var array
     */
    private $frames;
    
    /**
     * @var array
     */
    private $frameDurations;
    
    /**
     * @var array
     */
    private $frameImages;
    
    /**
     * @var array
     */
    private $framePositions;
    
    /**
     * @var array
     */
    private $frameDimensions;
    
    /**
     * @var integer 
     * 
     * (old: $this->index)
     */
    private $frameNumber;
    
    /**
     * @var array 
     * 
     * (old: $this->imagedata)
     */
    private $frameSources;
    
    /**
     * @var array 
     * 
     * (old: $this->fileHeader)
     */
    private $fileHeader;
    
    /**
     * @var integer The reader pointer in the file source 
     * 
     * (old: $this->pointer)
     */
    private $pointer;
    
    /**
     * @var integer
     */
    private $gifMaxWidth;
    
    /**
     * @var integer
     */
    private $gifMaxHeight;
    
    /**
     * @var integer
     */
    private $totalDuration;
    
    /**
     * @var integer
     */
    private $handle;
    
    /**
     * @var array 
     * 
     * (old: globaldata)
     */
    private $globaldata;
    
    /**
     * @var array 
     * 
     * (old: orgvars)
     */
    private $orgvars;
    
    // Methods
    // ===================================================================================
    
    /**
     * Extract frames of a GIF
     * 
     * @param string $filename GIF filename path
     * @param boolean $originalFrames Get original frames (with transparent background)
     * 
     * @return array
     */
    public function extract($filename, $originalFrames = false)
    {
        if (!self::isAnimatedGif($filename)) {
            throw new \Exception('The GIF image you are trying to explode is not animated !');
        }
        
        $this->reset();
        $this->parseFramesInfo($filename);
        $prevImg = null;
        
        for ($i = 0; $i < count($this->frameSources); $i++) {
            
            $this->frames[$i] = array();
            $this->frameDurations[$i] = $this->frames[$i]['duration'] = $this->frameSources[$i]['delay_time'];
            
            $img = imagecreatefromstring($this->fileHeader["gifheader"].$this->frameSources[$i]["graphicsextension"].$this->frameSources[$i]["imagedata"].chr(0x3b));
            
            if (!$originalFrames) {
                
                if ($i > 0) {
                    
                    $prevImg = $this->frames[$i - 1]['image'];
                    
                } else {
                    
                    $prevImg = $img;
                }
                
                $sprite = imagecreate($this->gifMaxWidth, $this->gifMaxHeight);
                imagesavealpha($sprite, true);
                
                $transparent = imagecolortransparent($prevImg);
                
                if ($transparent > -1 && imagecolorstotal($prevImg) > $transparent) {
                    
                    $actualTrans = imagecolorsforindex($prevImg, $transparent);
                    imagecolortransparent($sprite, imagecolorallocate($sprite, $actualTrans['red'], $actualTrans['green'], $actualTrans['blue']));
                }
                
                if ((int) $this->frameSources[$i]['disposal_method'] == 1 && $i > 0) {
                    
                    imagecopy($sprite, $prevImg, 0, 0, 0, 0, $this->gifMaxWidth, $this->gifMaxHeight);
                }
                
                imagecopyresampled($sprite, $img, $this->frameSources[$i]["offset_left"], $this->frameSources[$i]["offset_top"], 0, 0, $this->gifMaxWidth, $this->gifMaxHeight, $this->gifMaxWidth, $this->gifMaxHeight);
                $img = $sprite;
            }
            
            $this->frameImages[$i] = $this->frames[$i]['image'] = $img;
        }
        
        return $this->frames;
    }
    
    /**
     * Check if a GIF file at a path is animated or not
     * 
     * @param string $filename GIF path
     */
    public static function isAnimatedGif($filename)
    {
        if (!($fh = @fopen($filename, 'rb'))) {
            return false;
        }
        
        $count = 0;

        while (!feof($fh) && $count < 2) {
            
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
        }
        
        fclose($fh);
        return $count > 1;
    }
    
    // Internals
    // ===================================================================================
    
    /**
     * Parse the frame informations contained in the GIF file
     * 
     * @param string $filename GIF filename path
     */
    private function parseFramesInfo($filename)
    {
        $this->openFile($filename);
        $this->parseGifHeader();
        $this->parseGraphicsExtension(0);
        $this->getApplicationData();
        $this->getApplicationData();
        $this->getFrameString(0);
        $this->parseGraphicsExtension(1);
        $this->getCommentData();
        $this->getApplicationData();
        $this->getFrameString(1);
        
        while (!$this->checkByte(0x3b) && !$this->checkEOF()) {
            
            $this->getCommentData(1);
            $this->parseGraphicsExtension(2);
            $this->getFrameString(2);
            $this->getApplicationData();
        }
    }
    
    /** 
     * Parse the gif header (old: get_gif_header)
     */
    private function parseGifHeader()
    {
        $this->pointerForward(10);
        
        if ($this->readBits(($mybyte = $this->readByteInt()), 0, 1) == 1) {
            
            $this->pointerForward(2);
            $this->pointerForward(pow(2, $this->readBits($mybyte, 5, 3) + 1) * 3);
            
        } else {
            
            $this->pointerForward(2);
        }

        $this->fileHeader["gifheader"] = $this->dataPart(0, $this->pointer);
        
        // Decoding
        $this->orgvars["gifheader"] = $this->fileHeader["gifheader"];
        $this->orgvars["background_color"] = $this->orgvars["gifheader"][11];
    }
    
    /**
     * Parse the application data of the frames (old: get_application_data)
     */
    private function getApplicationData()
    {
        $startdata = $this->readByte(2);
        
        if ($startdata == chr(0x21).chr(0xff)) {
            
            $start = $this->pointer - 2;
            $this->pointerForward($this->readByteInt());
            $this->readDataStream($this->readByteInt());
            $this->fileHeader["applicationdata"] = $this->dataPart($start, $this->pointer - $start);
            
        } else {
            
            $this->pointerRewind(2);
        }
    }
    
    /**
     * Parse the comment data of the frames (old: get_comment_data)
     */
    private function getCommentData()
    {
        $startdata = $this->readByte(2);
        
        if ($startdata == chr(0x21).chr(0xfe)) {
            
            $start = $this->pointer - 2;
            $this->readDataStream($this->readByteInt());
            $this->fileHeader["commentdata"] = $this->dataPart($start, $this->pointer - $start);
            
        } else {
            
            $this->pointerRewind(2);
        }
    }
    
    /**
     * Parse the graphic extension of the frames (old: get_graphics_extension)
     * 
     * @param integer $type
     */
    private function parseGraphicsExtension($type)
    {
        $startdata = $this->readByte(2);
        
        if ($startdata == chr(0x21).chr(0xf9)) {
            
            $start = $this->pointer - 2;
            $this->pointerForward($this->readByteInt());
            $this->pointerForward(1);
            
            if ($type == 2) {
                
                $this->frameSources[$this->frameNumber]["graphicsextension"] = $this->dataPart($start, $this->pointer - $start);
                
            } elseif ($type == 1) {
                
                $this->orgvars["hasgx_type_1"] = 1;
                $this->globaldata["graphicsextension"] = $this->dataPart($start, $this->pointer - $start);
                
            } elseif ($type == 0) {
                
                $this->orgvars["hasgx_type_0"] = 1;
                $this->globaldata["graphicsextension_0"] = $this->dataPart($start, $this->pointer - $start);
            }
            
        } else {
            
            $this->pointerRewind(2);
        }
    }
    
    /**
     * Get the full frame string block (old: get_image_block)
     * 
     * @param integer $type
     */
    private function getFrameString($type)
    {
        if ($this->checkByte(0x2c)) {
            
            $start = $this->pointer;
            $this->pointerForward(9);
            
            if ($this->readBits(($mybyte = $this->readByteInt()), 0, 1) == 1) {
                
                $this->pointerForward(pow(2, $this->readBits($mybyte, 5, 3) + 1) * 3);
            }
            
            $this->pointerForward(1);
            $this->readDataStream($this->readByteInt());
            $this->frameSources[$this->frameNumber]["imagedata"] = $this->dataPart($start, $this->pointer - $start);

            if ($type == 0) {
                
                $this->orgvars["hasgx_type_0"] = 0;
                
                if (isset($this->globaldata["graphicsextension_0"])) {
                    
                    $this->frameSources[$this->frameNumber]["graphicsextension"] = $this->globaldata["graphicsextension_0"];
                    
                } else {
                    
                    $this->frameSources[$this->frameNumber]["graphicsextension"] = null;
                }
                    
                unset($this->globaldata["graphicsextension_0"]);
                
            } elseif ($type == 1) {
                
                if (isset($this->orgvars["hasgx_type_1"]) && $this->orgvars["hasgx_type_1"] == 1) {
                    
                    $this->orgvars["hasgx_type_1"] = 0;
                    $this->frameSources[$this->frameNumber]["graphicsextension"] = $this->globaldata["graphicsextension"];
                    unset($this->globaldata["graphicsextension"]);
                    
                } else {
                    
                    $this->orgvars["hasgx_type_0"] = 0;
                    $this->frameSources[$this->frameNumber]["graphicsextension"] = $this->globaldata["graphicsextension_0"];
                    unset($this->globaldata["graphicsextension_0"]);
                }
            }

            $this->parseFrameData();
            $this->frameNumber++;
        }
    }
    
    /**
     * Parse frame data string into an array (old: parse_image_data)
     */
    private function parseFrameData()
    {
        $this->frameSources[$this->frameNumber]["disposal_method"] = $this->getImageDataBit("ext", 3, 3, 3);
        $this->frameSources[$this->frameNumber]["user_input_flag"] = $this->getImageDataBit("ext", 3, 6, 1);
        $this->frameSources[$this->frameNumber]["transparent_color_flag"] = $this->getImageDataBit("ext", 3, 7, 1);
        $this->frameSources[$this->frameNumber]["delay_time"] = $this->dualByteVal($this->getImageDataByte("ext", 4, 2));
        $this->totalDuration += (int) $this->frameSources[$this->frameNumber]["delay_time"];
        $this->frameSources[$this->frameNumber]["transparent_color_index"] = ord($this->getImageDataByte("ext", 6, 1));
        $this->frameSources[$this->frameNumber]["offset_left"] = $this->dualByteVal($this->getImageDataByte("dat", 1, 2));
        $this->frameSources[$this->frameNumber]["offset_top"] = $this->dualByteVal($this->getImageDataByte("dat", 3, 2));
        $this->frameSources[$this->frameNumber]["width"] = $this->dualByteVal($this->getImageDataByte("dat", 5, 2));
        $this->frameSources[$this->frameNumber]["height"] = $this->dualByteVal($this->getImageDataByte("dat", 7, 2));
        $this->frameSources[$this->frameNumber]["local_color_table_flag"] = $this->getImageDataBit("dat", 9, 0, 1);
        $this->frameSources[$this->frameNumber]["interlace_flag"] = $this->getImageDataBit("dat", 9, 1, 1);
        $this->frameSources[$this->frameNumber]["sort_flag"] = $this->getImageDataBit("dat", 9, 2, 1);
        $this->frameSources[$this->frameNumber]["color_table_size"] = pow(2, $this->getImageDataBit("dat", 9, 5, 3) + 1) * 3;
        $this->frameSources[$this->frameNumber]["color_table"] = substr($this->frameSources[$this->frameNumber]["imagedata"], 10, $this->frameSources[$this->frameNumber]["color_table_size"]);
        $this->frameSources[$this->frameNumber]["lzw_code_size"] = ord($this->getImageDataByte("dat", 10, 1));
        
        $this->framePositions[$this->frameNumber] = array(
            'x' => $this->frameSources[$this->frameNumber]["offset_left"],
            'y' => $this->frameSources[$this->frameNumber]["offset_top"],
        );
        
        $this->frameDimensions[$this->frameNumber] = array(
            'width' => $this->frameSources[$this->frameNumber]["width"],
            'height' => $this->frameSources[$this->frameNumber]["height"],
        );
                
        // Decoding
        $this->orgvars[$this->frameNumber]["transparent_color_flag"] = $this->frameSources[$this->frameNumber]["transparent_color_flag"];
        $this->orgvars[$this->frameNumber]["transparent_color_index"] = $this->frameSources[$this->frameNumber]["transparent_color_index"];
        $this->orgvars[$this->frameNumber]["delay_time"] = $this->frameSources[$this->frameNumber]["delay_time"];
        $this->orgvars[$this->frameNumber]["disposal_method"] = $this->frameSources[$this->frameNumber]["disposal_method"];
        $this->orgvars[$this->frameNumber]["offset_left"] = $this->frameSources[$this->frameNumber]["offset_left"];
        $this->orgvars[$this->frameNumber]["offset_top"] = $this->frameSources[$this->frameNumber]["offset_top"];
        
        // Updating the max width
        if ($this->gifMaxWidth < $this->frameSources[$this->frameNumber]["width"]) {
            
            $this->gifMaxWidth = $this->frameSources[$this->frameNumber]["width"];
        }
        
        // Updating the max height
        if ($this->gifMaxHeight < $this->frameSources[$this->frameNumber]["height"]) {
            
            $this->gifMaxHeight = $this->frameSources[$this->frameNumber]["height"];
        }
    }
    
    /**
     * Get the image data byte (old: get_imagedata_byte)
     * 
     * @param string $type
     * @param integer $start
     * @param integer $length
     * 
     * @return string
     */
    private function getImageDataByte($type, $start, $length)
    {
        if ($type == "ext") {
            
            return substr($this->frameSources[$this->frameNumber]["graphicsextension"], $start, $length);
        }
        
        // "dat"
        return substr($this->frameSources[$this->frameNumber]["imagedata"], $start, $length);
    }
    
    /**
     * Get the image data bit (old: get_imagedata_bit)
     * 
     * @param string $type
     * @param integer $byteIndex
     * @param integer $bitStart
     * @param integer $bitLength
     * 
     * @return number
     */
    private function getImageDataBit($type, $byteIndex, $bitStart, $bitLength)
    {
        if ($type == "ext") {
            
            return $this->readBits(ord(substr($this->frameSources[$this->frameNumber]["graphicsextension"], $byteIndex, 1)), $bitStart, $bitLength);
        } 
        
        // "dat"
        return $this->readBits(ord(substr($this->frameSources[$this->frameNumber]["imagedata"], $byteIndex, 1)), $bitStart, $bitLength);
    }
    
    /**
     * Return the value of 2 ASCII chars (old: dualbyteval)
     * 
     * @param string $s
     * 
     * @return integer
     */
    private function dualByteVal($s)
    {
        $i = ord($s[1]) * 256 + ord($s[0]);
        
        return $i;
    }
    
    /**
     * Read the data stream (old: read_data_stream)
     * 
     * @param integer $firstLength
     */
    private function readDataStream($firstLength)
    {
        $this->pointerForward($firstLength);
        $length = $this->readByteInt();
        
        if ($length != 0) {
            
            while ($length != 0) {
                
                $this->pointerForward($length);
                $length = $this->readByteInt();
            }
        }
    }
    
    /**
     * Open the gif file (old: loadfile)
     * 
     * @param string $filename
     */
    private function openFile($filename)
    {
        $this->handle = fopen($filename, "rb");
        $this->pointer = 0;
        
        $imageSize = getimagesize($filename);
        $this->gifWidth = $imageSize[0];
        $this->gifHeight = $imageSize[1];
    }
    
    /**
     * Close the read gif file (old: closefile)
     */
    private function closeFile()
    {
        fclose($this->handle);
        $this->handle = 0;
    }
    
    /**
     * Read the file from the beginning to $byteCount in binary (old: readbyte)
     * 
     * @param integer $byteCount
     * 
     * @return string
     */
    private function readByte($byteCount)
    {
        $data = fread($this->handle, $byteCount);
        $this->pointer += $byteCount;
        
        return $data;
    }
    
    /**
     * Read a byte and return ASCII value (old: readbyte_int)
     * 
     * @return integer
     */
    private function readByteInt()
    {
        $data = fread($this->handle, 1);
        $this->pointer++;
        
        return ord($data);
    }
    
    /**
     * Convert a $byte to decimal (old: readbits)
     * 
     * @param string $byte
     * @param integer $start
     * @param integer $length
     * 
     * @return number
     */
    private function readBits($byte, $start, $length)
    {
        $bin = str_pad(decbin($byte), 8, "0", STR_PAD_LEFT);
        $data = substr($bin, $start, $length);
        
        return bindec($data);
    }
    
    /**
     * Rewind the file pointer reader (old: p_rewind)
     * 
     * @param integer $length
     */
    private function pointerRewind($length)
    {
        $this->pointer -= $length;
        fseek($this->handle, $this->pointer);
    }
    
    /**
     * Forward the file pointer reader (old: p_forward)
     * 
     * @param integer $length
     */
    private function pointerForward($length)
    {
        $this->pointer += $length;
        fseek($this->handle, $this->pointer);
    }
    
    /**
     * Get a section of the data from $start to $start + $length (old: datapart)
     * 
     * @param integer $start
     * @param integer $length
     * 
     * @return string
     */
    private function dataPart($start, $length)
    {
        fseek($this->handle, $start);
        $data = fread($this->handle, $length);
        fseek($this->handle, $this->pointer);
        
        return $data;
    }
    
    /**
     * Check if a character if a byte (old: checkbyte)
     * 
     * @param integer $byte
     * 
     * @return boolean
     */
    private function checkByte($byte)
    {
        if (fgetc($this->handle) == chr($byte)) {
            
            fseek($this->handle, $this->pointer);
            return true;
        }
        
        fseek($this->handle, $this->pointer);

        return false;
    }
    
    /**
     * Check the end of the file (old: checkEOF)
     * 
     * @return boolean
     */
    private function checkEOF()
    {
        if (fgetc($this->handle) === false) {
            
            return true;
        }
            
        fseek($this->handle, $this->pointer);
        
        return false;
    }
    
    /**
     * Reset and clear this current object
     */
    private function reset()
    {
        $this->gif = null;
        $this->totalDuration = $this->gifMaxHeight = $this->gifMaxWidth = $this->handle = $this->pointer = $this->frameNumber = 0;
        $this->frameDimensions = $this->framePositions = $this->frameImages = $this->frameDurations = $this->globaldata = $this->orgvars = $this->frames = $this->fileHeader = $this->frameSources = array();
    }
    
    // Getter / Setter
    // ===================================================================================
    
    /**
     * Get the total of all added frame duration
     * 
     * @return integer
     */
    public function getTotalDuration()
    {
        return $this->totalDuration;
    }
    
    /**
     * Get the number of extracted frames
     * 
     * @return integer
     */
    public function getFrameNumber()
    {
        return $this->frameNumber;
    }
    
    /**
     * Get the extracted frames (images and durations)
     * 
     * @return array
     */
    public function getFrames()
    {
        return $this->frames;
    }
    
    /**
     * Get the extracted frame positions
     * 
     * @return array
     */
    public function getFramePositions()
    {
        return $this->framePositions;
    }
    
    /**
     * Get the extracted frame dimensions
     * 
     * @return array
     */
    public function getFrameDimensions()
    {
        return $this->frameDimensions;
    }
    
    /**
     * Get the extracted frame images
     * 
     * @return array
     */
    public function getFrameImages()
    {
        return $this->frameImages;
    }
    
    /**
     * Get the extracted frame durations
     * 
     * @return array
     */
    public function getFrameDurations()
    {
        return $this->frameDurations;
    }
}