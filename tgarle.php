<?php

namespace DaveHamber\ImageFormats\TGA;
use \Exception;

class TGARunLengthEncoder
{
	const TGA_EXTENTION_MATCH = '/(.*?)\.?(encoded|decoded|)\.tga$/';
	
	private $fileHeader = null;
	private $encodeFile = true;
	private $path = ".";
	private $fileName = "";
	
	public function setPath($path)
	{
		if (is_dir($path))
		{
			$this->path = $path;
			return true;
		}
		
		return false;
	}
	
	public function setFileName($fileName)
	{
		$this->fileName = basename($fileName);
		
		$path = dirname($fileName);
		
		if ($path != '.')
		{
			$this->path = dirname($fileName);
		}
	}
	
	public function encodeFile($fileName = null, $filePath = null)
	{
		if ($filePath != null)
		{
			$this->setPath($filePath);
		}
		
		if ($fileName != null)
		{
			$this->setFileName($fileName);
		}
		
		$this->encodeDecodeFile(true);
	}
	
	public function decodeFile($fileName = null, $filePath = null)
	{
		if ($filePath != null)
		{
			$this->setPath($filePath);
		}
		
		if ($fileName != null)
		{
			$this->setFileName($fileName);
		}
		
		$this->encodeDecodeFile(false);
	}
	
	private function getOutputFileName($encoding)
	{
		$matches = null;
		preg_match(self::TGA_EXTENTION_MATCH, $this->fileName, $matches);
		
		if (!$matches)
		{
			return "temp.tga";
		}
		
		if ($encoding)
		{
			$extension = ".encoded.tga";
		}
		else
		{
			$extension = ".decoded.tga";
		}
		
		return $matches[1] . $extension;
	}
	
	private function encodeDecodeDirectory($encodeMode, $directory = './')
	{			
		if (!($handle = opendir($directory)))
		{
			return;
		}
		
		while (($currentFile = readdir($handle)) !== false)
		{
			if (filetype($directory . $currentfile) == 'file')
			{
				if (preg_match(self::TGA_EXTENTION_MATCH, $currentFile))
				{
					try {
						encodeDecodeFile($currentFile, $encodeMode);
					} catch (Exception $e) {
						echo "Could not decode $currentFile";
					}
					
				}
			}
		}
		closedir($handle);
	}
	
	private function loadData($fileName, $encode)
	{
		if (!file_exists($fileName))
		{
			throw new Exception("Cannot load file data, file does not exist!");
		}
		
		if (($fileSize = filesize($fileName)) < TGAHeader::TGA_HEADER_SIZE)
		{
			throw new Exception("File header is too small for a tga file!");
		}
		
		$h = fopen($fileName, 'r');
		$headerData = fread($h, TGAHeader::TGA_HEADER_SIZE);
		fclose($h);
		
		$this->fileHeader = new TGAHeader($headerData);
		
		if ($encode && $fileSize < $this->fileHeader->getMinPossibleFileSize())
		{
			$this->fileHeader = null;
			throw new Exception("File size is too small for date specified in header! File size is:$fileSize and min pos is " . $this->fileHeader->getMinPossibleFileSize());
		}
		
		
		/*
		fseek($h, $this->fileHeader->getDataStartOffset());
		
		$this->fileData = fread($h, $this->fileHeader->getImageDataLength()); */
	}

	private function encodeDecodeFile($encode)
	{
		
		$this->loadData($this->path . "/" . $this->fileName, $encode);
		
		if ($this->fileHeader == null)
		{
			throw new Exception("Cannot encode or decode current file, file header is missing.");
		}

		switch ($this->fileHeader->getImageTypeCode())
		{
			case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_RGB:
			case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_BLACK_AND_WHITE:
				if (!$encode)
				{
					throw new Exception("$fileName is already decoded\n");
				}
				else
				{
					$this->encodeTga();
				}
				break;
		
			case TGAHeader::IMAGE_TYPE_RLE_RGB:
			case TGAHeader::IMAGE_TYPE_RLE_BLACK_AND_WHITE:
			
				if ($encode)
				{
					throw new Exception("$fileName is already encoded\n");
				}
				else
				{
					$this->decodeTga();
				}
				break;
		}			
	}

	private function encodeTga()
	{
		
		print "Encoding: $this->fileName\n";
		
		$newHeader = new TGAHeader($this->fileHeader->getHeaderData());
		
		switch ($this->fileHeader->getImageTypeCode())
		{
		    case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_RGB:
		        // Set the image type to compressed RGB.
		        $newHeader->setImageTypeCode(TGAHeader::IMAGE_TYPE_RLE_RGB);
		        break;
		        
		    case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_BLACK_AND_WHITE:
		        // Set the image type to compressed black and white.
		        $newHeader->setImageTypeCode(TGAHeader::IMAGE_TYPE_RLE_BLACK_AND_WHITE);
		        break;
		    default:
		        throw new Exception("No suitable unencoded image type in header");
		}
		
		$pixelDepth = $this->fileHeader->getBytePixelDepth();
		
		$width = $this->fileHeader->getHeight();
		$height = $this->fileHeader->getWidth();
		
		$dataLength = $this->fileHeader->getImageDataLength();
			
		$h = fopen($this->path . "/" . $this->fileName, 'r');
		fseek($h, TGAHeader::TGA_HEADER_SIZE);
		$fileData = fread($h, $dataLength);
	
		$newData		= '';
		$rawstring		= '';
		$encodePacket	= false;
		$counter		= 0;
		$pointer		= 0;
	
		for ($y = 0; $y < $height; $y++)
		{
			for ($x = 0; $x < $width; $x++)
			{
				
				$pointer = ($y * $width * $pixelDepth) + ($x * $pixelDepth);
				$currentPixel = substr($fileData, $pointer, $pixelDepth);
				
				// set the next pixel to null if we are at the end of the scan line
				if ($x == $width - 1)
				{
					$nextPixel = null;
				}
				else
				{
					$nextPixel = substr($fileData, $pointer + $pixelDepth, $pixelDepth);
				}
				
				// our pixel is the same as the next pixel
				if ($currentPixel === $nextPixel)
				{		
					if (($x == 0) || ($counter == 0))
					{
						$encodePacket = true;
					}
					
					if ($encodePacket)
					{
						$counter++;
						
						if ($counter == 128)
						{
							$newData .= chr(128 + $counter - 1) . $currentPixel;
							$counter = 0;
						}			
					}
					else
					{
						if ($counter > 0)
						{
							$newData .= chr($counter - 1) . $rawstring;
						}
						
						$rawstring = '';
						$encodePacket = true;
						$counter = 1;
					}
				}
				// our pixel is the last in the scanline
				elseif ($nextPixel === null)
				{	
					if ($encodePacket)
					{
						$counter++;
						$newData .= chr(128 + $counter - 1) . $currentPixel;
						$rawstring = '';
						$counter = 0;
					}
					else {
						$counter++;
						$rawstring .= $currentPixel;
						$newData .= chr($counter - 1) . $rawstring;
						$rawstring = '';
						$encodePacket = true;
						$counter = 0;			
					}
				}
				// our pixel is not the same as the next pixel
				else
				{					
					if ($x == 0 || $counter == 0)
					{
						$encodePacket = false;
					}
					
					if ($encodePacket)
					{
						$counter++;
						$newData .= chr(128 + $counter - 1) . $currentPixel;
						//our pixel counts as one for the rawpacket mode
						$counter = 0;
						$encodePacket = false;
					}
					else
					{
						$counter++;
						$rawstring .= $currentPixel;
						if ($counter == 128)
						{
							$newData .= chr($counter - 1) . $rawstring;
							$rawstring = '';
							$counter = 0;
						}
					}
				}	
			}
		}
		
		$h = fopen($this->getOutputFileName(true), 'w');
		fwrite($h, $newHeader->getHeaderData() . $newData);
		fclose($h);
	}
	
	private function decodeTga()
	{
		print "Decoding: $this->fileName\n";
		
		$newHeader = new TGAHeader($this->fileHeader->getHeaderData());
		
		switch ($this->fileHeader->getImageTypeCode())
		{
		    case TGAHeader::IMAGE_TYPE_RLE_RGB:
		        // Set the image type to uncompressed RGB.
		        $newHeader->setImageTypeCode(TGAHeader::IMAGE_TYPE_UNCOMPRESSED_RGB);
		        break;
		    case TGAHeader::IMAGE_TYPE_RLE_BLACK_AND_WHITE:
		        // Set the image type to uncompressed black and white.
		        $newHeader->setImageTypeCode(TGAHeader::IMAGE_TYPE_UNCOMPRESSED_BLACK_AND_WHITE);
		        break;
		    default:
		        throw new Exception("No suitable encoded image type in header");
		        return;
		}
		
		$pixelDepth = $this->fileHeader->getBytePixelDepth();
		
		$width = $this->fileHeader->getHeight();
		$height = $this->fileHeader->getWidth();
		
		$dataLength = $this->fileHeader->getImageDataLength();
				
		print "pixel depth:$pixelDepth, width:$width, height:$height, datalength:$dataLength\n";

		$h = fopen($this->fileName, 'r');
		fseek($h, TGAHeader::TGA_HEADER_SIZE);
		$data = fread($h, $dataLength);
		fclose($h);
	
		$newData = '';
		$pointer = 0;
	
		while (strlen($newData) != $dataLength || $pointer > strlen($data))
		{	
			$count = 0;

			$packetHeader = ord($data[$pointer]);
			
			// the last bit of the packet header byte (128) indicates that the packet is encoded
			// so if the unsigned value of the byte is greater than 127, it is encoded, we
			// then get the number of the run of pixels by deducting 128 (the encode status bit).
			if ($packetHeader > 127)
			{
				// add the run of the same pixal to the data string
				
				$count = $packetHeader - 128 + 1;
				for ($i = 0;$i < $count; $i++)
				{
					$newData .= substr($data, $pointer + 1, $pixelDepth);
				}
				
				// packet header + pixel field
				$pointer += 1 + $pixelDepth; 
			}
			else
			{
				// the encode bit is not set so we add just the next pixel field
				$count = $packetHeader + 1;
				$newData .= substr($data, $pointer + 1, $count * $pixelDepth);

				// header + pixel field
				$pointer += 1 + ($count * $pixelDepth); 
			}
		}
		
		if (strlen($newData) < $dataLength)
		{
			throw new Exception("Not enough data could be decoded from file $this->fileName to make a full image.");
		}
		
		$h = fopen($this->getOutputFileName(false), 'w');
		fwrite($h, $newHeader->getHeaderData() . substr($newData, 0, $dataLength));
		fclose($h);
	}
}

?>