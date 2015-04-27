<?php

namespace DaveHamber\ImageFormats\TGA;
use \Exception;

class TGARunLengthEncoder
{
	const TGA_EXTENTION_MATCH = '/(.*?)\.?(encoded|decoded|)\.tga$/';
	const LINE_END_CLI = "\n";
	const LINE_END_HTML = "<br/>\n";
	
	private $fileHeader = null;
	private $imageId = null;
	private $colorMap = null;
	private $fileFooter = null;
	private $encodeFile = true;
	private $path = ".";
	private $fileName = "";
	
	private $output = true;
	private $html = false;
	
	// Adds html <br/> tags to end of output lines
	public function htmlBreaks()
	{
	    $this->html = true;
	}
	
	// Turns off all output and error reporting
	public function outputOff()
	{
	    $this->output = false;
	}
	
	// Sets the input directory file path
	public function setPath($path)
	{
		if (is_dir($path))
		{
			$this->path = rtrim($path, "/");
			return true;
		}
		
		return false;
	}
	
	// Sets the input file name, if a path is included then that is also set
	public function setFileName($fileName)
	{
		$this->fileName = basename($fileName);
		
		$path = dirname($fileName);
		
		if ($path != '.')
		{
			$this->path = $path;
		}
	}
	
	// Encodes all TGA files in the given input directory if not already set
	public function encodeDir($filePath = ".")
	{
	    if ($filePath == null)
	    {
	        $filePath = ".";
	    }
	    
	    if (!$this->setPath($filePath))
	    {
	        $this->output(
                "Cannot encode directory, the given directory is not valid.");
	        return;
	    }
	    
	    try {
            $this->encodeDecodeDirectory(true);
	    }
	    catch (Exception $e)
	    {
	        $this->output($e->getMessage());
	    }
	}

	// Decodes all TGA files in the given input directory if not already set
	public function decodeDir($filePath = ".")
	{
	    if ($filePath == null)
	    {
	        $filePath = ".";
	    }
	     
	    if (!$this->setPath($filePath))
	    {
	        $this->output(
                "Cannot decode directory, the given directory is not valid.");
	        return;
	    }
	     
        try {
            $this->encodeDecodeDirectory(true);
	    }
	    catch (Exception $e)
	    {
	        $this->output($e->getMessage());
	    }
	}
	
	// Encodes an individual file name. The path can be in the same string as
	// the file name or as a seperate path parameter.
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
		
		try {
            $this->encodeDecodeFile(true);
		}
		catch (Exception $e)
		{
		    $this->output($e->getMessage());
		}
	}

	// Decodes an individual file name. Like above, the path can be in the same
	// string as the file name or as a seperate path parameter.
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
		
		try {
            $this->encodeDecodeFile(false);
		}
		catch (Exception $e)
		{
		    $this->output($e->getMessage());
		}
	}
	
	// Using the input file name, the name created tries to label output files
	// with the same name but adding either .encoded.tga or .decoded.tga
	//
	//TODO: Add explicit output naming
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
	
	// Loops through a valid directory en/decoding any tga files
	// Within. Currently output goes to the same directory but
	// with renamed files.
	private function encodeDecodeDirectory($encodeMode)
	{
	    if (!is_dir($this->path))
	    {
	        throw new Exception("Path provided is not valid.");
	    }
	    
		if (!($handle = opendir($this->path)))
		{
			throw new Exception("Could not open directory.");
		}
		
		while (($currentFile = readdir($handle)) !== false)
		{
			if (filetype($this->path . "/" . $currentFile) == 'file')
			{
				if (preg_match(self::TGA_EXTENTION_MATCH, $currentFile))
				{
					try {
					    $this->fileName = $currentFile;
						$this->encodeDecodeFile($encodeMode);
					}
					catch (Exception $e)
					{
					    if ($encodeMode)
					    {
					        $this->output("Could not encode $currentFile: " .
                                $e->getMessage());
					    }
					    else
					    {
                            $this->output("Could not decode $currentFile: " .
                                $e->getMessage());
					    }
					}
				}
			}
		}
		closedir($handle);
	}
	
	// Loads TGA header, any image descriptor, any color map table and also the
	// TGA footer.
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
		
		try
		{
		  $this->fileHeader = new TGAHeader($headerData);
		}
		catch (Exception $e)
		{
		    throw new Exception($e->getMessage());
		}
		
		if ($this->fileHeader->getImageIdLength() > 0)
		{
		    $this->imageId = fread($h, $this->fileHeader->getImageIdLength());
		}
		
		if ($this->fileHeader->getColorMapType() == 1)
		{
            $this->colorMap = fread($h,
                $this->fileHeader->getColorMapByteSize());
		}
		
		$headerAndFooterSize =
            TGAHeader::TGA_HEADER_SIZE +
            TGAHeader::TGA_FOOTER_SIZE;
		
		if ($fileSize >= $headerAndFooterSize)
		{
		    fseek($h, -TGAHeader::TGA_FOOTER_SIZE, SEEK_END);
		    $this->fileFooter = fread($h, TGAHeader::TGA_FOOTER_SIZE);

		    if (substr($this->fileFooter, 8, 18) != TGAHeader::TGA_FOOTER_SIGNATURE)
		    {
		        $this->fileFooter = null;
		    }
		}
		
		fclose($h);
	}

    // Checks file header is present and that the image type is valid,
    // then proceeds to either encode or decode the file
	private function encodeDecodeFile($encode)
	{
		$this->output("Attempting to load data for " .
            $this->path . "/" . $this->fileName);
		
		try {
		    $this->loadData($this->path . "/" . $this->fileName, $encode);
		}
		catch (Exception $e)
		{
		    throw new Exception($e->getMessage());
		}
		
		
		if ($this->fileHeader == null)
		{
			throw new Exception("Cannot encode or decode current file, " .
                "file header is missing.");
		}

		switch ($this->fileHeader->getImageTypeCode())
		{
			case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_RGB:
			case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_BLACK_AND_WHITE:
			case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_COLOR_MAPPED:
				if (!$encode)
				{
					throw new Exception("$this->fileName is already decoded.");
				}
				else
				{
					$this->encodeTga();
				}
				break;
		
			case TGAHeader::IMAGE_TYPE_RLE_RGB:
			case TGAHeader::IMAGE_TYPE_RLE_BLACK_AND_WHITE:
			case TGAHeader::IMAGE_TYPE_RLE_COLOR_MAPPED:			
				if ($encode)
				{
					throw new Exception("$this->fileName is already encoded.");
				}
				else
				{
					$this->decodeTga();
				}
				break;
		}			
	}

    // Reads and encodes raw image data from input file and changes the
    // image type in the header.
	private function encodeTga()
	{
		
		$this->output("Encoding: $this->fileName");
				
		switch ($this->fileHeader->getImageTypeCode())
		{
		    case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_RGB:
		        // Set the image type to compressed RGB.
		        $this->fileHeader->setImageTypeCode(
                    TGAHeader::IMAGE_TYPE_RLE_RGB);
		        break;
		    case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_BLACK_AND_WHITE:
		        // Set the image type to compressed black and white.
		        $this->fileHeader->setImageTypeCode(
                    TGAHeader::IMAGE_TYPE_RLE_BLACK_AND_WHITE);
		        break;
		    case TGAHeader::IMAGE_TYPE_UNCOMPRESSED_COLOR_MAPPED:
		        // Set the image type to compressed color mapped.
		        $this->fileHeader->setImageTypeCode(
                    TGAHeader::IMAGE_TYPE_RLE_COLOR_MAPPED);
		        break;
		    default:
		        throw new Exception(
                    "No suitable unencoded image type in header");
		}
		
		$pixelDepth = $this->fileHeader->getBytePixelDepth();
		
		$width = $this->fileHeader->getHeight();
		$height = $this->fileHeader->getWidth();
		
		$dataLength = $this->fileHeader->getImageDataLength();
			
		$h = fopen($this->path . "/" . $this->fileName, 'r');
		fseek($h, $this->fileHeader->getDataStartOffset());
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
				
				// set the next pixel to null if we are at the
				// end of the scan line
				if ($x == $width - 1)
				{
					$nextPixel = null;
				}
				else
				{
					$nextPixel =
                        substr($fileData, $pointer + $pixelDepth, $pixelDepth);
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
		
		$this->createNewFile(true, $newData);
	}
	
	// Reads and decodes encoded image data from input file and changes the
	// image type in the header
	private function decodeTga()
	{
		$this->output("Decoding: $this->fileName");
		
		switch ($this->fileHeader->getImageTypeCode())
		{
		    case TGAHeader::IMAGE_TYPE_RLE_RGB:
		        // Set the image type to uncompressed RGB.
		        $this->fileHeader->setImageTypeCode(
                    TGAHeader::IMAGE_TYPE_UNCOMPRESSED_RGB);
		        break;
		    case TGAHeader::IMAGE_TYPE_RLE_BLACK_AND_WHITE:
		        // Set the image type to uncompressed black and white.
		        $this->fileHeader->setImageTypeCode(
                    TGAHeader::IMAGE_TYPE_UNCOMPRESSED_BLACK_AND_WHITE);
		        break;
		    case TGAHeader::IMAGE_TYPE_RLE_COLOR_MAPPED:
		        $this->fileHeader->setImageTypeCode(
                    TGAHeader::IMAGE_TYPE_UNCOMPRESSED_COLOR_MAPPED);
		        break;
		    default:
		        throw new Exception("No suitable encoded image type in header");
		        return;
		}
		
		$pixelDepth = $this->fileHeader->getBytePixelDepth();
		
		$width = $this->fileHeader->getHeight();
		$height = $this->fileHeader->getWidth();
		
		$dataLength = $this->fileHeader->getImageDataLength();

		$h = fopen($this->path . "/" . $this->fileName, 'r');
		fseek($h, $this->fileHeader->getDataStartOffset());
		$data = fread($h, $dataLength);
		fclose($h);
	
		$newData = '';
		$pointer = 0;
	
		while (strlen($newData) != $dataLength || $pointer > strlen($data))
		{	
			$count = 0;

			$packetHeader = ord($data[$pointer]);
			
			// the last bit of the packet header byte (128) indicates that the
			// packet is encoded so if the unsigned value of the byte is greater
			// than 127, it is encoded, we then get the number of the run of
			// pixels by deducting 128 (the encode status bit).
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
		
		if (strlen($newData) != $dataLength)
		{
			throw new Exception(
                "Decoded data is not correct length in file $this->fileName.");
		}
		
		$this->createNewFile(false, $newData);
	}
	
	// Commits encoded / decoded data to file in addition to the header, any
	// image descriptor, any color table and the footer if present.
	private function createNewFile($encoding, $newData)
	{
	    $newFileName = $this->getOutputFileName($encoding);
	    $h = fopen($this->path . "/" . $newFileName, 'w');
	    
	    fwrite($h, $this->fileHeader->getHeaderData());
	    
	    $imageIdLen = $this->fileHeader->getImageIdLength();
	    
	    if ($imageIdLen > 0 && strlen($this->imageId) == $imageIdLen)
	    {
	        if (strlen($this->imageId) == $this->fileHeader->getImageIdLength())
	        {
	            fwrite($h, $this->imageId);
	        }
	        else
	        {
	            unlink($newFileName);
	            throw new Exception(
                    "Image Id does not match length of image Id data.");
	        }
	    }
	    
	    if ($this->fileHeader->getColorMapType() == 1)
	    {
	        if (strlen($this->colorMap) == $this->fileHeader->getColorMapByteSize())
	        {
	            fwrite($h, $this->colorMap);
	        }
	        else
	        {
	            unlink($newFileName);
	            throw new Exception(
                    "Color Map declared but data is ".
                    "not present or wrong size.");
	        }
	    }
	    
	    fwrite($h, $newData);
	    
	    $footerNotNull = ($this->fileFooter != null);
	    $footerLen = strlen($this->fileFooter);
	    
	    if ($footerNotNull && $footerLen == TGAHeader::TGA_FOOTER_SIZE)
	    {
	        fwrite($h, $this->fileFooter);
	    }

	    fclose($h);
	}
	
	private function output($outputString)
	{
        if (!$this->output)
        {
            return;
        }
    
        if ($this->html)
        {
            $outputString .= self::LINE_END_HTML;
        }
        else
        {
            $outputString .= self::LINE_END_CLI;
        }
            
        print $outputString;
	}	
}

?>