<?php

namespace DaveHamber\ImageFormats\TGA;
use \Exception;

class TGAHeader
{
	const TGA_HEADER_SIZE = 18;
	const BYTE = 8;

	const ID_LENGTH = 0;		/* 00h Byte - Size of Image ID field */
	const COLOR_MAP_TYPE = 1;	/* 01h Byte - Color map type */
	const IMAGE_TYPE_CODE = 2;	/* 02h Byte - Image type code */
	const C_MAP_START = 3;      /* 03h Word - Color map origin */
	const C_MAP_LENGTH = 5;		/* 05h Word - Color map length */
	const C_MAP_DEPTH = 7;		/* 07h Byte Depth of color map entries */
	const X_OFFSET	= 8;        /* 08h Word - X origin of image */
	const Y_OFFSET = 10;        /* 0Ah Word - Y origin of image */
	const WIDTH = 12;           /* 0Ch Word - Width of image */
	const HEIGHT = 14;          /* 0Eh Word - Height of image */
	const PIXEL_DEPTH = 16;     /* 10h Byte - Image pixel size */
	const IMAGE_DESCRIPTOR = 17;/* 11h Byte - Image descriptor byte */

	// 0 - No image data included.
	const IMAGE_TYPE_NO_IMAGE_DATA = 0;

	// 1 - Uncompressed, color-mapped images.
	const IMAGE_TYPE_UNCOMPRESSED_COLOR_MAPPED = 1;

	// 2 - Uncompressed, RGB images.
	const IMAGE_TYPE_UNCOMPRESSED_RGB = 2;

	// 3 - Uncompressed, black and white images.
	const IMAGE_TYPE_UNCOMPRESSED_BLACK_AND_WHITE = 3;

	// 9 - Runlength encoded color-mapped images.
	const IMAGE_TYPE_RLE_COLOR_MAPPED = 9;

	// 10 - Runlength encoded RGB images.
	const IMAGE_TYPE_RLE_RGB = 10;
	
	// 11 - Runlength encoded, black and white images.
	const IMAGE_TYPE_RLE_BLACK_AND_WHITE = 11;

	// 32 - Compressed color-mapped data, using Huffman, Delta, and runlength encoding.
	const IMAGE_TYPE_HD_RLE_COLOR_MAPPED = 32;

	// 33 - Compressed color-mapped data, using Huffman, Delta, and runlength encoding.  4-pass quadtree-type process.
	const IMAGE_TYPE_HD_RLE_4PASS_COLOR_MAPPED = 33;

	private $tgaHeader = "";

	function __construct($tgaHeader)
	{
		if (!is_string($tgaHeader))
		{
			throw new Exception("TGA Header constructor requires given data type to be a string.");
		}

		if (strlen($tgaHeader) != self::TGA_HEADER_SIZE)
		{
			throw new Exception("TGA Header constructor given data is the wrong size.");
		}

		$this->tgaHeader = $tgaHeader;
	}

	// methods to get all of the header basic information
	public function getImageIdLength()
	{
		return ord($this->tgaHeader[self::ID_LENGTH]);
	}

	public function getColorMapType()
	{
		return ord($this->tgaHeader[self::COLOR_MAP_TYPE]);
	}

	public function getImageTypeCode()
	{
		return ord($this->tgaHeader[self::IMAGE_TYPE_CODE]);
	}
	
	public function setImageTypeCode($imageType)
	{
		if (!is_numeric($imageType))
		{
			return;
		}
		
		$imageType = (int)$imageType;
		
		if ($imageType > 255)
		{
			return;
		}
		
		$this->tgaHeader[self::IMAGE_TYPE_CODE] = chr($imageType);
	}

	public function getColorMapStart()
	{
		return $this->getWord(self::C_MAP_LENGTH);
	}

	public function getColorMapLength()
	{
		return $this->getWord(self::C_MAP_LENGTH);
	}

	public function getColorMapDepth()
	{
		return ord($this->tgaHeader[self::C_MAP_DEPTH]);
	}

	public function getXOffset()
	{
		return $this->getWord(self::X_OFFSET);
	}

	public function getYOffset()
	{
		return $this->getWord(self::Y_OFFSET);
	}

	public function getWidth()
	{
		return $this->getWord(self::WIDTH);
	}

	public function getHeight()
	{
		return $this->getWord(self::HEIGHT);
	}

	public function getPixelDepth()
	{
		return ord($this->tgaHeader[self::PIXEL_DEPTH]);
	}

	public function getImageDescriptor()
	{
		return ord($this->tgaHeader[self::IMAGE_DESCRIPTOR]);
	}

	// methods that get information based on the basic data
	
	public function getHeaderData()
	{
		return $this->tgaHeader;
	}
	
	public function getBytePixelDepth()
	{
		return (int)ceil(ord($this->tgaHeader[self::PIXEL_DEPTH]) / self::BYTE);
	}

	public function getImageDataLength()
	{
		return $this->getWidth() * $this->getHeight() * $this->getBytePixelDepth();
	}

	public function getColorMapByteDepth()
	{
		return ceil($this->getColorMapDepth() / self::BYTE);
	}

	public function getColorMapByteSize()
	{
		return $this->getColorMapLength() * $this->getColorMapByteDepth();
	}

	public function getDataStartOffset()
	{
		$offset = self::TGA_HEADER_SIZE;

		$offset += $this->getImageIdLength();

		if ($this->getColorMapType() == 1)
		{
			$offset += $this->getColorMapByteSize();
		}
	}

	// The minimum filesize based on the TGA 1 standard
	// TGA2 has a 26 byte footer
	public function getMinPossibleFileSize()
	{
		return $this->getDataStartOffset() + $this->getImageDataLength() + self::TGA_HEADER_SIZE;
	}
	
	private function getWord($offset)
	{
		return ord($this->tgaHeader[$offset]) + (ord($this->tgaHeader[$offset + 1]) << self::BYTE);
	}
}

?>