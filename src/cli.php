<?php
namespace DaveHamber\ImageFormats\TGA;

ini_set('memory_limit', '32M');
require 'TGARleCli.php';
require 'TGAHeader.php';
require 'TGARunLengthEncoder.php';
require 'TGAFileRle.php';
require 'TGADataDecoder.php';
require 'TGADataEncoder.php';

use DaveHamber\ImageFormats\TGA\TGARleCli;
use DaveHamber\ImageFormats\TGA\TGAHeader;
use DaveHamber\ImageFormats\TGA\TGARunLengthEncoder;
use DaveHamber\ImageFormats\TGA\TGAFileRle;
use DaveHamber\ImageFormats\TGA\TGADataDecoder;
use DaveHamber\ImageFormats\TGA\TGADataEncoder;

TGARleCli::execute();
