<?php

namespace DaveHamber\ImageFormats\TGA;

class TGADataDecoder
{
    const ENCODED_BIT = 128;
    private $pixelDepth = 0;
    private $dataLength = 0;
    
    public function __construct($pixelDepth, $dataLength)
    {
        $this->pixelDepth = $pixelDepth;
        $this->dataLength = $dataLength;
    }
    
    public function decodeData($imageData)
    {
        $pointer = 0;
        $outputData = "";
        
        while (strlen($outputData) != $this->dataLength || $pointer >= strlen($imageData)) {
            $packetHeader = ord($imageData[$pointer]);
            $packetCounter = $packetHeader % self::ENCODED_BIT + 1;
            
            // The last bit of the packet header byte (128) indicates that the
            // packet is encoded so if the unsigned value of the byte is greater.
            // than 127, it is encoded.
            if ($packetHeader >= self::ENCODED_BIT) {
                // Add the run of the same pixal to the data string.
                $packetData = substr($imageData, $pointer + 1, $this->pixelDepth);
                for ($i = 0; $i < $packetCounter; $i ++) {
                    $outputData .= $packetData;
                }
        
                // Packet header + pixel field.
                $pointer += 1 + $this->pixelDepth;
            } else {
                // The encode bit is not set so we add just the next pixel field.
                $outputData .= substr($imageData, $pointer + 1, $packetCounter * $this->pixelDepth);
        
                // Advance the pointer by header + pixel field length.
                $pointer += 1 + ($packetCounter * $this->pixelDepth);
            }
        }
        
        return $outputData;
    }
}
