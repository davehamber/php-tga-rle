<?php

ini_set('memory_limit', '32M');
include 'tgaheader.php';
include 'tgarle.php';

use DaveHamber\ImageFormats\TGA\TGAHeader;
use DaveHamber\ImageFormats\TGA\TGARunLengthEncoder;

main();

function main()
{
	// 32 x 32 RGB RLE encoded with alpha channel. Image is black text on white background with the word "TEST"
	$testImageData =
	"AAAKAAAAAAAAAAAAIAAgACAIn/////+f/////5//////n/////+f/////5//////n/////+f///" .
	"//5//////n/////+f/////5//////n/////+D/////wFERET/iIiI/4L/////hQAAAP8C3d3d/7" .
	"u7u/8zMzP/gQAAAP8BERER/5mZmf+C/////wG7u7v/AAAA/4T/////g/////8BRERE/4iIiP+C/" .
	"////wEAAAD/u7u7/4P/////Au7u7v8AAAD/iIiI/4H/////At3d3f8RERH/zMzM/4H/////Abu7" .
	"u/8AAAD/hP////+D/////wFERET/iIiI/4L/////AQAAAP+7u7v/if////8BERER/5mZmf+B///" .
	"//wG7u7v/AAAA/4T/////g/////8BRERE/4iIiP+C/////wEAAAD/u7u7/4b/////BMzMzP9mZm" .
	"b/MzMz/yIiIv/u7u7/gf////8Bu7u7/wAAAP+E/////4P/////AURERP+IiIj/gv////+EAAAA/" .
	"wZmZmb//////7u7u/8RERH/AAAA/2ZmZv+IiIj/g/////8Bu7u7/wAAAP+E/////4P/////AURE" .
	"RP+IiIj/gv////8BAAAA/7u7u/+E/////wEzMzP/d3d3/4b/////Abu7u/8AAAD/hP////+D///" .
	"//wFERET/iIiI/4L/////AQAAAP+7u7v/hP////8BVVVV/0RERP+B/////wKZmZn/AAAA/+7u7v" .
	"+B/////wG7u7v/AAAA/4T/////Af////+ZmZn/hQAAAP8A3d3d/4UAAAD/Av/////d3d3/MzMz/" .
	"4EAAAD/AyIiIv+ZmZn//////yIiIv+EAAAA/wBmZmb/gf////+f/////5//////n/////+f////" .
	"/5//////n/////+f/////5//////n/////+f/////5//////AAAAAAAAAABUUlVFVklTSU9OLVh" .
	"GSUxFLgA=";

	$testImageName = "test.encoded.tga";

	file_put_contents($testImageName, base64_decode($testImageData));

	$encoder = new TGARunLengthEncoder();

	//$encoder->setFileName($testImageName);
	//$encoder->decodeFile();

	$encoder->encodeFile("test.decoded.tga");
}

/*
 function main($conversionMode, $fileName)
 {

 switch ($conversionMode)
 {
 case '-e':
 $encode	= true;
 $wholeDirectory = false;
 CheckFile($fileName);

 break;
 case '-d':
 $encode = false;
 $wholeDirectory	= false;
 checkFile($fileName);
 break;

 case '-ed':
 $encode	= true;
 $wholeDirectory = true;
 break;

 case '-dd':
 $encode	= false;
 $wholeDirectory = true;
 break;

 default:
 print "usage: php tgarle.php <-e|-d|-ed|-dd> [filename]\n";
 exit(0);
 }


 }
 */
 
?>