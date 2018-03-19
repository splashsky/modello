<?php
/*
// Modello
// A super-simple and lightweight templater! :D
//
// @author: Skylear Johnson
//
// @TODO
// - Update code comments
// - Improve file handling
// - Improve errors and exception handling
// - Improve usability
*/

class Modello
{
    // @string Default path for templates
    protected $tmpPath;

    // @string File path for if we're only on one template
    protected $file;

    // @array Holds a batch of template files!
    protected $batch = [];

    // @array Holds the values for either a batch or single template!
    protected $values = [];

    // @bool Just a toggle for htmlentities. Set with a setter.
    protected $safe = false;

    /*
    // Static method to allow method chains!
    */
    public static function new($tmpPath = '')
    {
        return new Modello($tmpPath);
    }

    /*
    // Magic method! Sets tmpPath and returns the instance
    */
    public function __construct($tmpPath = '')
    {
        $this->tmpPath = $tmpPath;

        return $this;
    }

    public function setSafe($safe)
    {
        $this->safe = $safe;
        return $this;
    }

    public function getSafe()
    {
        return $this->safe;
    }

    public function setFile(string $file)
    {
        if (is_readable($this->getExtension($file))) {
            $this->file = $this->getExtension($file);
        } else {
            throw new Exception('Invalid path/file passed to setFile in Modello!');
        }

        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setBatch(array $files)
    {
        if (count($files) > 1) {
            foreach ($files as $key => $file) {
                if (is_readable($this->getExtension($file))) {
                    $this->batch[$key] = $this->getExtension($file);
                }
            }
        } else {
            throw new Exception('Invalid argument passed to setBatch in Modello.');
        }

        return $this;
    }

    public function getBatch()
    {
        return $this->batch;
    }

    public function setValues($values = [], $fileKey = '')
    {
        if ($this->isBatch() && array_key_exists($fileKey, $this->batch)) {
            foreach ($values as $key => $value) {
                $value = $this->safe ? htmlspecialchars($value) : $value;
                $this->values[$fileKey][$key] = $value;
            }
        } else {
            foreach ($values as $key => $value) {
                $value = $this->safe ? htmlspecialchars($value) : $value;
                $this->values[$key] = $value;
            }
        }

        return $this;
    }

    public function getValues()
    {
        return $this->values;
    }

    protected function getExtension(string $URI)
    {
        if (preg_match('/(.tmp)/', $URI)) return $this->tmpPath.$URI;
        return $this->tmpPath.$URI.'.tmp';
    }

    protected function isBatch()
    {
        return (count($this->batch) > 1) ? true : false;
    }

    public function output($fileKey = '')
    {
        if ($this->isBatch() && array_key_exists($fileKey, $this->batch)) {
            $output = file_get_contents($this->batch[$fileKey]);

            if (!array_key_exists($fileKey, $this->values)) {
                $values = [];
            } else {
                $values = $this->values[$fileKey];
            }
        } else {
            $output = file_get_contents($this->file);
            $values = $this->values;
        }

        // Remove all PHP-style comments.
        $cmmt_pattern = array('#/\*.*?\*/#s', '#(?<!:)//.*#');
        $output = preg_replace($cmmt_pattern, null, $output);

        // Locate and void out any undefined tags
        $tags = array();
        preg_match_all('/\[@([A-Za-z1-9]*)\]/', $output, $tags);
        foreach ($tags[1] as $tag => $key) {
            if (!isset($values[$key])) {
                $values[$key] = '';
            }
        }

        $toReplace = array_keys($values);
        foreach ($toReplace as $i => $val) {
            $toReplace[$i] = '[@'.$val.']';
        }
        $values = array_values($values);

        return str_replace($toReplace, $values, $output);
    }
}
