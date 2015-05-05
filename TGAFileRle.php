<?php

namespace DaveHamber\ImageFormats\TGA;

use \Exception;

class TGAFileRle
{
    private $inputFileName = null;
    private $outputFileName = null;
    private $fileHeader = null;
    private $imageId = null;
    private $colorMap = null;
    private $fileFooter = null;
    
    private $encode = null;
    
    public function __construct($inputFileName, $outputFileName)
    {
        $this->inputFileName = $inputFileName;
        $this->outputFileName = $outputFileName;
    }
    
    public function encodeFile()
    {
        $this->encode = true;
        $this->encodeDecodeFile();
    }
    
    public function decodeFile()
    {
        $this->encode = false;
        $this->encodeDecodeFile();
    }
    
    // Checks file header is present and that the image type is valid,
    // then proceeds to either encode or decode the file
    private function encodeDecodeFile()
    {
        try {
            $this->loadData($this->inputFileName);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    
        if ($this->fileHeader == null) {
            throw new Exception("Cannot encode or decode current file, file header is missing.");
        }
    
        if ($this->fileHeader->isDecodedType()) {
            if (!$this->encode) {
                throw new Exception("$this->inputFileName is already decoded.");
            } else {
                $this->encodeTga();
            }
        } elseif ($this->fileHeader->isEncodedType()) {
            if ($this->encode) {
                throw new Exception("$this->inputFileName is already encoded.");
            } else {
                $this->decodeTga();
            }
        }
    }

    // Loads TGA header, any image descriptor, any color map table and also the
    // TGA footer.
    private function loadData()
    {
        if (! file_exists($this->inputFileName)) {
            throw new Exception("Cannot load file data, file does not exist!");
        }
    
        if (($fileSize = filesize($this->inputFileName)) < TGAHeader::TGA_HEADER_SIZE) {
            throw new Exception("File header is too small for a tga file!");
        }
    
        $handle = fopen($this->inputFileName, 'r');
        $headerData = fread($handle, TGAHeader::TGA_HEADER_SIZE);
    
        try {
            $this->fileHeader = new TGAHeader($headerData);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    
        if ($this->fileHeader->getImageIdLength() > 0) {
            $this->imageId = fread($handle, $this->fileHeader->getImageIdLength());
        }
    
        if ($this->fileHeader->getColorMapType() == 1) {
            $this->colorMap = fread($handle, $this->fileHeader->getColorMapByteSize());
        }
    
        $headerAndFooterSize = TGAHeader::TGA_HEADER_SIZE + TGAHeader::TGA_FOOTER_SIZE;
    
        if ($fileSize >= $headerAndFooterSize) {
            fseek($handle, - TGAHeader::TGA_FOOTER_SIZE, SEEK_END);
            $this->fileFooter = fread($handle, TGAHeader::TGA_FOOTER_SIZE);
    
            if (substr($this->fileFooter, 8, 18) != TGAHeader::TGA_FOOTER_SIGNATURE) {
                $this->fileFooter = null;
            }
        }
    
        fclose($handle);
    }
    
    // Reads and encodes raw image data from input file and changes the
    // image type in the header.
    private function encodeTga()
    {
        if (!$this->fileHeader->switchToEncodedType()) {
            throw new Exception("No suitable unencoded image type in header");
        }
    
        $dataLength = $this->fileHeader->getImageDataLength();
    
        $handle = fopen($this->inputFileName, 'r');
        fseek($handle, $this->fileHeader->getDataStartOffset());
        $fileData = fread($handle, $dataLength);
        fclose($handle);
    
    
        $pixelDepth = $this->fileHeader->getBytePixelDepth();
    
        $width = $this->fileHeader->getHeight();
        $height = $this->fileHeader->getWidth();
    
        $dataEncoder = new TGADataEncoder($width, $height, $pixelDepth);
    
        $this->createNewFile($dataEncoder->encodeData($fileData));
    }
    
    // Reads and decodes encoded image data from input file and changes the
    // image type in the header
    private function decodeTga()
    {
        if (!$this->fileHeader->switchToDecodedType()) {
            throw new Exception("No suitable encoded image type in header");
            return;
        }
    
        $pixelDepth = $this->fileHeader->getBytePixelDepth();
        $dataLength = $this->fileHeader->getImageDataLength();
    
        // Fetch data from offset
        $handle = fopen($this->inputFileName, 'r');
        fseek($handle, $this->fileHeader->getDataStartOffset());
        $fileData = fread($handle, $dataLength);
        fclose($handle);
    
        $dataDecoder = new TGADataDecoder($pixelDepth, $dataLength);
        $newData = $dataDecoder->decodeData($fileData);
    
        if (strlen($newData) != $dataLength) {
            throw new Exception("Decoded data is not correct length in file $this->inputFileName.");
        }
    
        $this->createNewFile($newData);
    }
    
    // Commits encoded / decoded data to file in addition to the header, any
    // image descriptor, any color table and the footer if present.
    private function createNewFile($newData)
    {
        $handle = fopen($this->outputFileName, 'w');
    
        fwrite($handle, $this->fileHeader->getHeaderData());
    
        $imageIdLen = $this->fileHeader->getImageIdLength();
    
        if ($imageIdLen > 0 && strlen($this->imageId) == $imageIdLen) {
            if (strlen($this->imageId) == $this->fileHeader->getImageIdLength()) {
                fwrite($handle, $this->imageId);
            } else {
                unlink($this->outputFileName);
                throw new Exception("Image Id does not match length of image Id data.");
            }
        }
    
        if ($this->fileHeader->getColorMapType() == 1) {
            if (strlen($this->colorMap) == $this->fileHeader->getColorMapByteSize()) {
                fwrite($handle, $this->colorMap);
            } else {
                unlink($this->outputFileName);
                throw new Exception("Color Map declared but data is not present or wrong size.");
            }
        }
    
        fwrite($handle, $newData);

        $footerLen = strlen($this->fileFooter);
    
        if ($this->fileFooter != null && $footerLen == TGAHeader::TGA_FOOTER_SIZE) {
            fwrite($handle, $this->fileFooter);
        }
    
        fclose($handle);
    }
}
