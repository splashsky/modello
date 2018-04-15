<?php

/*
// Modello
// A super-simple, super lightweight templating engine!
//
// @author: Skylear "Splashsky" Johnson
*/

class Modello {

    protected $directory;
    protected $file;
    protected $batch = [];
    protected $variables = [];
    protected $extensions = ['tmp', 'modello'];
    protected $tags = ['{{', '}}'];

    // A static constructor for method chaining!
    // For example, echo Modello::new('example')->output();
    public static function new(string $directory = '')
    {
        return new Modello($directory);
    }

    // Classic constructor; sets the root directory for template files
    public function __construct(string $directory = '')
    {
        $this->setDir($directory);
    }

    // A setter if you want to switch directories mid-way
    public function setDir(string $directory = '')
    {
        if (is_dir($directory)) {
            $this->directory = $directory;
        }

        return $this;
    }

    // Sets the individual file, but has nothing to do with batch
    public function setFile(string $file)
    {
        if (is_file($this->directory.$file) && $this->verifyExt($file)) {
            $this->file = $file;
        }

        return $this;
    }

    // Sets the batch array to as many files attached to keys as you'd like
    public function setBatch(array $batch)
    {
        foreach ($batch as $key => $file) {
            if (is_file($this->directory.$file) && $this->verifyExt($file)) {
                $this->batch[$key] = $file;
            }
        }

        return $this;
    }

    // Sets template variables either for the individual file or a key in the
    // batch array
    public function setVars(array $vars, string $key = '')
    {
        if ($key == '') {
            $this->variables = $vars;
        } else {
            $this->variables[$key] = $vars;
        }

        return $this;
    }

    // Pass as many strings to this function as you want to add more acceptable
    // extensions programmatically
    public function addExt(string ...$extensions)
    {
        foreach ($extensions as $ext) {
            $this->extensions[] = $ext;
        }
    }

    // Tells us whether or not the provided file name has an approved extension
    public function verifyExt(string $filename)
    {
        $exts = implode('|', $this->extensions);
        return preg_match('/([^\s]+(\.'.$exts.')$)/', $filename);
    }

    // Works out the display/output logic
    public function output($key = '')
    {
        // Determine if we're working on the individual file or a file in the
        // current batch
        if ($key == '') {
            $output = file_get_contents($this->directory.$this->file);
            $vars   = $this->variables;
        } else {
            if (! empty($this->batch[$key])) {
                $output = file_get_contents($this->directory.$this->batch[$key]);

                if (empty($this->variables[$key])) {
                    $vars = [];
                } else {
                    $vars = $this->variables[$key];
                }
            }
        }

        // Get all matching tags in the template
        $matches = [];
        $tags = '/'.$this->tags[0].'([A-Za-z1-9]*)'.$this->tags[1].'/';
        preg_match_all($tags, $output, $matches);

        // If the variable hasn't been defined, make it disappear!
        foreach ($matches[1] as $match => $var) {
            if (empty($vars[$var])) {
                $vars[$var] = '';
            }
        }

        // Next, find all those tags
        $replace = array_keys($vars);
        $values  = array_values($vars);
        foreach ($replace as $k => $tag) {
            $replace[$k] = $this->tags[0].$tag.$this->tags[1];
        }

        // In one swoop we'll replace all the tags with their respective
        // variables! Cuts down on loop overhead
        return str_replace($replace, $values, $output);
    }
}
