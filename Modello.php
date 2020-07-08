<?php

/**
 * Modello
 * 
 * A simple, ultra-lightweight template engine in PHP, for
 * small projects
 * 
 * @author Skylear "Splashsky" Johnson
 */

class Modello
{
    private string $directory;
    private string $extension;

    public static function new(string $directory = '', string $ext = '.html')
    {
        return new Modello($directory, $ext);
    }

    public function __construct(string $directory = '', string $ext = '.html')
    {
        $this->directory = $directory;
        $this->extension = $ext;

        return $this;
    }

    private function find(string $template, string $directory = null)
    {
        $path = str_replace('.', '/', $template);
        $dir = !is_null($directory) ? $directory : $this->directory;
        $path = $dir . $path . $this->extension;

        return $this->read($path);
    }

    private function read(string $path)
    {
        if (!is_readable($path)) {
            throw new Exception('Unable to read() given path.');
        }

        return file_get_contents($path);
    }

    private function parse(string $template, array $values = [])
    {
        return preg_replace_callback(
            '/{{\s*([A-Za-z0-9_-]+)\s*}}/',
            function($match) use ($values) {
                return isset($values[$match[1]]) ? $values[$match[1]] : $match[0];
            },
            $template
        );
    }

    public static function staticParse(string $template, array $values = [])
    {
        return (new self)->parse($template, $values);
    }

    public function bake(string $template, array $values = [])
    {
        $template = $this->find($template);
        return $this->parse($template, $values);
    }

    public static function quick(string $template, array $values = [], string $ext = '.html')
    {
        $path = str_replace('.', '/', $template);
        $path = $path . $ext;

        if (!is_readable($path)) {
            throw new Exception('Unable to quick() template with given path.');
        }

        $template = file_get_contents($path);

        return self::staticParse($template, $values);
    }
}

?>