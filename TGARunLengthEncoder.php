<?php
namespace DaveHamber\ImageFormats\TGA;

use DaveHamber\ImageFormats\TGA\TGAFileRle;
use \Exception;

class TGARunLengthEncoder
{

    const TGA_EXTENTION_MATCH = '/(.*?)\.?(encoded|decoded|)\.tga$/';
    const LINE_END_CLI = "\n";
    const LINE_END_HTML = "<br/>\n";

    private $path = null;
    private $fileName = null;
    private $outPath = null;
    private $outFileName = null;
    
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
    
    // Sets the input file name, if a path is included then that is also set
    public function setFileName($fileName)
    {
        if (is_dir($fileName)) {
            $this->path = rtrim($fileName, "/");
        } else {
            $this->fileName = basename($fileName);
            $path = dirname($fileName);
        
            if ($this->path != "." || $this->path != null) {
                if (is_dir($path)) {
                    $this->path = rtrim($path, "/");
                }
            }
        }
    }

    public function setOutputName($fileName)
    {
        if (is_dir($fileName)) {
            $this->outPath = rtrim($fileName, "/");
        } else {
            $this->outFileName = basename($fileName);
            $path = dirname($fileName);
    
            if (is_dir($path)) {
                $this->outPath = rtrim($path, "/");
            } else {
                $this->outPath = '.';
            }
        }
    }
        
    // Encodes an individual file name. The path can be in the same string as
    // the file name or as a seperate path parameter.
    public function encodeFile($fileName, $outName = null)
    {
        $this->encodeDecodeFile(true, $fileName, $outName);
    }
    
    // Decodes an individual file name. Like above, the path can be in the same
    // string as the file name or as a seperate path parameter.
    public function decodeFile($fileName, $outName = null)
    {
        $this->encodeDecodeFile(false, $fileName, $outName);
    }
    
    private function encodeDecodeFile($encodeMode, $filePathAndName, $outPathAndName)
    {
        if ($filePathAndName == null) {
            // no file name given
        }
        
        if (is_dir($filePathAndName)) {
            // is a directory not a file
        }

        $fileName = basename($filePathAndName);
        $path = dirname($filePathAndName);
        
        if ($fileName == null) {
            $this->output("No valid file name given");
            return;
        }
        
        if ($outPathAndName == null) {
            $outName = $this->getOutputFileName($fileName, $encodeMode);
            $outPath = dirname($filePathAndName);
        } else {
            if (is_dir($outPathAndName)) {
                // make up extension
                $outName = $this->getOutputFileName($fileName, $encodeMode);
                $outPath = rtrim($outPathAndName, "/");
            } else {
                $outName = basename($outPathAndName);
                $outPath = dirname($outPathAndName);
            }
        }
        
        $tgaFileRle = new TGAFileRle(
            $path . "/" . $fileName,
            $outPath . "/" . $outName
        );
        
        if ($encodeMode) {
            try {
                $tgaFileRle->encodeFile();
            } catch (Exception $e) {
                $this->output($e->getMessage());
            }
        } else {
            try {
                $tgaFileRle->decodeFile();
            } catch (Exception $e) {
                $this->output($e->getMessage());
            }
        }
    }
    
    // Encodes all TGA files in the given input directory if not already set
    public function encodeDir($filePath = ".", $outPath = ".")
    {
        try {
            $this->encodeDecodeDirectory(true, $filePath, $outPath);
        } catch (Exception $e) {
            $this->output($e->getMessage());
        }
    }
    
    // Decodes all TGA files in the given input directory if not already set
    public function decodeDir($filePath = ".", $outPath = ".")
    {
        try {
            $this->encodeDecodeDirectory(false, $filePath, $outPath);
        } catch (Exception $e) {
            $this->output($e->getMessage());
        }
    }
    
    // Loops through a valid directory en/decoding any tga files
    // Within. Currently output goes to the same directory but
    // with renamed files.
    private function encodeDecodeDirectory($encodeMode, $filePath, $outPath)
    {
        if (! is_dir($filePath)) {
            throw new Exception("Input path provided is not valid.");
        }
        
        if (! is_dir($outPath)) {
            throw new Exception("Output path provided is not valid.");
        }
        
        if (! ($handle = opendir($filePath))) {
            throw new Exception("Could not open directory.");
        }
        
        while (($currentFile = readdir($handle)) !== false) {
            if (filetype($this->path . "/" . $currentFile) == 'file') {
                if (preg_match(self::TGA_EXTENTION_MATCH, $currentFile)) {
                    $this->encodeDecodeFile(
                        $encodeMode,
                        $this->path . "/" . $currentFile,
                        $outPath
                    );
                    
                }
            }
        }
        closedir($handle);
    }
    
    // Using the input file name, the name created tries to label output files
    // with the same name but adding either .encoded.tga or .decoded.tga
    //
    // TODO: Add explicit output naming
    private function getOutputFileName($fileName, $encoding)
    {
        $matches = null;
        preg_match(self::TGA_EXTENTION_MATCH, $fileName, $matches);
    
        if (! $matches) {
            return "temp.tga";
        }
    
        if ($encoding) {
            $extension = ".encoded.tga";
        } else {
            $extension = ".decoded.tga";
        }
    
        return $matches[1] . $extension;
    }

    private function output($outputString)
    {
        if (! $this->output) {
            return;
        }
        
        if ($this->html) {
            $outputString .= self::LINE_END_HTML;
        } else {
            $outputString .= self::LINE_END_CLI;
        }
        
        print $outputString;
    }
}
