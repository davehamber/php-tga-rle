<?php
namespace DaveHamber\ImageFormats\TGA;

class TGADataEncoder
{
    const ENCODED_BIT = 128;
    
    private $width = 0;
    private $height = 0;
    private $pixelDepth = 0;
    private $packetCount = 0;
    private $packetData = "";
    private $outputData = "";
    private $encoded = false;
    private $currentPixel = null;
    private $nextPixel = null;
    
    public function __construct($width, $height, $pixelDepth)
    {
        $this->width = $width;
        $this->height = $height;
        $this->pixelDepth = $pixelDepth;
    }
    
    public function encodeData($imageData)
    {
        if ($this->width == 0 || $this->height == 0 || $this->pixelDepth == 0) {
            return null;
        }
    
        $pointer = 0;
    
        for ($y = 0; $y < $this->height; $y ++) {
            for ($x = 0; $x < $this->width; $x ++) {
                $pointer = ($y * $this->width * $this->pixelDepth) + ($x * $this->pixelDepth);
                $this->currentPixel = substr($imageData, $pointer, $this->pixelDepth);
    
                // Set the next pixel to null if we
                // are at the end of the scan line.
                if ($x == $this->width - 1) {
                    $this->nextPixel = null;
                } else {
                    $this->nextPixel =
                        substr(
                            $imageData,
                            $pointer + $this->pixelDepth,
                            $this->pixelDepth
                        );
                }
    
                // our pixel is the same as the next pixel.
                if ($this->currentPixel === $this->nextPixel) {
                    $this->samePixel();
                // our pixel is the last in the scanline.
                } elseif ($this->nextPixel === null) {
                    $this->lastPixel();
                // Our pixel is different to the next pixel.
                } else {
                    $this->differentPixel();
                }
            }
        }
    
        return $this->outputData;
    }
    
    // Operations performed when the next pixel is the same as the current
    // pixel.
    private function samePixel()
    {
        // The packet is at its start, the next pixel is the same, set packet
        // mode to encode.
        if ($this->packetCount == 0) {
            $this->encoded = true;
        }
        
        // The packet is currently set to encode the same pixels, the next
        // pixel is the same.
        if ($this->encoded) {
            // Increment packet pixel count.
            $this->packetCount ++;
        
            // The packet is at the maximum size, add to output data and
            // reset packet counter.
            if ($this->packetCount == self::ENCODED_BIT) {
                $this->outputData .= chr(self::ENCODED_BIT + $this->packetCount - 1) . $this->currentPixel;
                $this->packetCount = 0;
            }
        } else {
            // The packet is currently raw and the next pixel is the same.
            // This will cause the current raw packet to end.
            $this->outputData .= chr($this->packetCount - 1) . $this->packetData;
            $this->packetData = '';
            $this->encoded = true;
            $this->packetCount = 1;
        }
    }

    // Operations performed when the next pixel is the last pixel of the
    // scan line.
    private function lastPixel()
    {
        // Increment packet count.
        $this->packetCount++;
        
        // End encoded or raw packet and reset counter.
        if ($this->encoded) {
            $this->outputData .= chr(self::ENCODED_BIT + $this->packetCount - 1) . $this->currentPixel;
        } else {
            $this->packetData .= $this->currentPixel;
            $this->outputData .= chr($this->packetCount - 1) . $this->packetData;
            $this->packetData = '';
        }
        
        $this->packetCount = 0;
    }

    // Operations performed when the next pixel is different to the current
    // pixel.
    private function differentPixel()
    {
        // The packet is at its start, the next pixel is different, set packet
        // mode to raw.
        if ($this->packetCount == 0) {
            $this->encoded = false;
        }
        
        // Increment packet pixel count.
        $this->packetCount++;
        
        if ($this->encoded) {
            // Our packet is encoded and the next pixel is different.
            // End the current encoded packet and add packet to output data.
            $this->outputData .= chr(self::ENCODED_BIT + $this->packetCount - 1) . $this->currentPixel;
            $this->packetCount = 0;
            $this->encoded = false;
        } else {
            // Our packet is raw and the next pixel is also different.
            // Add current pixel to packet data.
            $this->packetData .= $this->currentPixel;
            
            // Our raw packet is now at its maximum length. End the packet
            // and reset the packet count.
            if ($this->packetCount == self::ENCODED_BIT) {
                $this->outputData .= chr($this->packetCount - 1) . $this->packetData;
                $this->packetData = '';
                $this->packetCount = 0;
            }
        }
    }
}
