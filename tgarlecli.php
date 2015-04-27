<?php

ini_set('memory_limit', '32M');
include 'tgaheader.php';
include 'tgarle.php';

use DaveHamber\ImageFormats\TGA\TGAHeader;
use DaveHamber\ImageFormats\TGA\TGARunLengthEncoder;
use DaveHamber\ImageFormats\TGA\DaveHamber\ImageFormats\TGA;

TgaRleCli::execute();

class TgaRleCli
{
    public static function execute()
    {
        global $argv;
    
        switch (count($argv))
        {
            case 1:
            case 2:
                print commandHelp();
                exit(0);
        }
    
        $option = $argv[1];
        $file = $argv[2];
    
        $encoder = new TGARunLengthEncoder();
        $encoder->htmlBreaks();
        $encoder->outputOff();
       
    
        switch ($option)
        {
            case '-e':
                $encoder->encodeFile($file);
                break;
            case '-d':
                $encoder->decodeFile($file);
                break;
            case '-E':
                $encoder->encodeDir($file);
                break;
            case '-D':
                $encoder->decodeDir($file);
                break;
            default:
                print commandHelp();
                exit(0);
        }
    }
    
    private static function commandHelp()
    {
        return
        $this->formatUsage("php tgarle.phar [OPTION] [FILE]") .
        $this->formatDescription("Encodes or decodes TGA 1 and TGA 2 runlength encoded images which are either RBG, Grayscale or Color Mapped.") .
        $this->formatOption("Encode a single TGA file from its uncompressed form.", "e", "encode") .
        $this->formatOption("Decode a single TGA file from its compressed form.", "d", "decode") .
        $this->formatOption("Encode TGA files in the given directory.", "E", "encodedir") .
        $this->formatOption("Decode TGA files in the given directory.", "D", "decodedir") . "\n";
    }
    
    private static function formatUsage($usage)
    {
        return wordwrap("Usage: " . $usage, 80) . "\n";
    }
    
    private static function formatDescription($description)
    {
        return wordwrap($description, 80) . "\n\n";
    }
    
    private static function formatOption($description, $shortOption, $longOption = "")
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
}





?>