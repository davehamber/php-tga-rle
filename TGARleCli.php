<?php
namespace DaveHamber\ImageFormats\TGA;

use DaveHamber\ImageFormats\TGA\TGAHeader;
use DaveHamber\ImageFormats\TGA\TGARunLengthEncoder;

class TGARleCli
{
    // main commandline execution for encoding / decoding individual files
    // or directories.
    public static function execute()
    {
        switch (count($GLOBALS['argv']))
        {
            case 1:
            case 2:
                print self::commandHelp();
                exit(0);
        }

        $option = $GLOBALS['argv'][1];
        $file   = $GLOBALS['argv'][2];

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

    // Command line help. Given when incorrect parameters are used.
    private function commandHelp()
    {
        return
        TGARleCli::formatUsage("php tgarle.phar [OPTION] [FILE]") .
        TGARleCli::formatDescription(
            "Encodes or decodes TGA 1 and TGA 2 runlength encoded images " .
            "which are either RBG, Grayscale or Color Mapped."
        ) .
        TGARleCli::formatOption(
            "Encode a single TGA file from its uncompressed form.",
            "e",
            "encode"
        ) .
        TGARleCli::formatOption(
            "Decode a single TGA file from its compressed form.",
            "d",
            "decode"
        ) .
        TGARleCli::formatOption(
            "Encode TGA files in the given directory.",
            "E",
            "encodedir"
        ) .
        TGARleCli::formatOption(
            "Decode TGA files in the given directory.",
            "D",
            "decodedir"
        ) . "\n";
    }

    // Formatting functions.
    private function formatUsage($usage)
    {
        return wordwrap("Usage: " . $usage, 80) . "\n";
    }

    private function formatDescription($description)
    {
        return wordwrap($description, 80) . "\n\n";
    }

    private function formatOption($description, $shortOption, $longOption = "")
    {
        $descriptionLines = explode("\n", wordwrap($description, 51));

        if ($longOption == "") {
            $option = sprintf(
                "  -%1.1s,%-24s%-51s\n",
                $shortOption,
                "",
                $descriptionLines[0]
            );
        } else {
            if (strlen($longOption) > 19) {
                $option = sprintf(
                    "  -%1.1s, --%-72s\n%-29s%-51s\n",
                    $shortOption,
                    $longOption,
                    "",
                    $descriptionLines[0]
                );
            } else {
                $option = sprintf(
                    "  -%1.1s, --%-21s%-51s\n",
                    $shortOption,
                    $longOption,
                    $descriptionLines[0]
                );
            }
        }

        if (count($descriptionLines) > 1) {
            for ($i = 1; $i < count($descriptionLines); $i++) {
                $option .= sprintf("%-29s%-51s\n", "", $descriptionLines[$i]);
            }
        }

        return $option;
    }
}
