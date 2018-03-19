<?php

/*
// Modello.php
// @author: Skylear "Splashsky" Johnson
//
// ...
*/

class Modello {
    
    protected $directory;
    protected $file;
    protected $safe = true;
    protected $batch = [];
    protected $variables = [];
    protected $extensions = ['tmp', 'modello'];

    protected $rawTags     = ['{!!', '!!}'];
    protected $contentTags = ['{{', '}}'];
    protected $escapedTags = ['{{{', '}}}'];

    public function __construct(string $directory = '')
    {
        if (is_dir($directory)) {
            $this->directory = $directory;
        }

        throw new InvalidArgumentException('Directory given to Modello wasn\'t a directory!');
    }

    public function setFile(string $file)
    {
        if (is_file($file)) {
            $this->file = $file;
        }

        throw new InvalidArgumentException('Attempted to set file for Modello that wasn\'t a file!');
    }

    public function getFile()
    {
        return $this->file;
    }

    public function addExtension(string ...$extensions)
    {
        foreach ($extensions as $ext) {
            $this->extensions[] = $ext;
        }
    }
}