<?php

ini_set('memory_limit', '32M');
include 'tgaheader.php';
include 'tgarle.php';

use DaveHamber\ImageFormats\TGA\TGAHeader;
use DaveHamber\ImageFormats\TGA\TGARunLengthEncoder;
use DaveHamber\ImageFormats\TGA\DaveHamber\ImageFormats\TGA;

main();

function main()
{
    global $argv;
    
    switch (count($argv))
    {
        case 1:
        case 2:
            print commandHelp();
            var_dump($argv);
            exit(0);    
    }
    
    $option = $argv[1];
    $file = $argv[2];
    
    $encoder = new TGARunLengthEncoder();
    
    switch ($option)
    {
        case '-e':
            $encoder->encodeFile($file);

            break;
        case '-d':
            $encoder->decodeFile($file);
            break;

        case '-E':
//             $encoder->deco
            break;

        case '-D':
            $encode	= false;
            $wholeDirectory = true;
            break;

        default:
            print commandHelp();
                        
                
            exit(0);
    }
}

function commandHelp()
{
    return
        formatUsage("php tgarle.phar [OPTION] [FILE]") .
        formatDescription("Encodes or decodes TGA 1 and TGA 2 runlength encoded images which are either RBG, Grayscale or Color Mapped.") .
        formatOption("Encode a single TGA file from its uncompressed form.", "e", "encode") .
        formatOption("Decode a single TGA file from its compressed form.", "d", "decode") .
        formatOption("Encode TGA files in the given directory.", "E", "encodedir") .
        formatOption("Decode TGA files in the given directory.", "D", "decodedir") . "\n";
}

function formatUsage($usage)
{
    return wordwrap("Usage: " . $usage, 80) . "\n";
}

function formatDescription($description)
{
    return wordwrap($description, 80) . "\n\n";
}

function formatOption($description, $shortOption, $longOption = "")
{
    $descriptionLines = explode("\n", wordwrap($description, 51));
    
    if ($longOption == "")
    {
        $option = sprintf("  -%1.1s,%-24s%-51s\n", $shortOption, "", $descriptionLines[0]);
    }
    else
    {
        if (strlen($longOption) > 19)
        {
            $option = sprintf("  -%1.1s, --%-72s\n%-29s%-51s\n", $shortOption, $longOption, "", $descriptionLines[0]);
        }
        else
        {
            $option = sprintf("  -%1.1s, --%-21s%-51s\n", $shortOption, $longOption, $descriptionLines[0]);
        }
    }

    if (count($descriptionLines) > 1)
    {
        for ($i = 1; $i < count($descriptionLines); $i++)
        {
            $option .= sprintf("%-29s%-51s\n", "", $descriptionLines[$i]);
        }
    }
    
    return $option;
}


function test()
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

// 	$testImageName = "test.encoded.tga";
// 	$testImageName = "test-grayscale.encoded.tga";

// 	file_put_contents($testImageName, base64_decode($testImageData));

// 	$encoder = new TGARunLengthEncoder();

// 	$encoder->setFileName($testImageName);
// 	$encoder->decodeFile();

// 	$encoder->encodeFile("test-grayscale.decoded.tga");

	$h = fopen("test.index.decoded.tga", "r");
	$header = new TGAHeader(fread($h, 18));
	fclose($h);
	
	print $header->__toString();
	
	
}

?>