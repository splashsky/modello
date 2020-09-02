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
    private array $values;
    private string $workingFile;

    /**
     * Create the instance of Modello
     * 
     * @param string $directory
     * @param string $ext
     * @return void
     */
    public function __construct(string $directory = '', string $ext = '.php')
    {
        $this->directory = $directory;
        $this->extension = $ext;

        $this->createCache();
    }

    /**
     * Find a given template using the currently stored directory
     * 
     * @param string $template
     * @return string
     */
    private function find(string $template)
    {
        $path = str_replace('.', '/', $template);
        $path = $this->directory . $path . $this->extension;
        $this->workingFile = $path;

        return $this->read($path);
    }

    /**
     * See if the file we're looking for is readable, if not then
     * we'll return an error string to ensure the user knows.
     * 
     * @param string $path
     * @return string
     */
    private function read(string $path)
    {
        if (! is_readable($path)) { return $path . ' not found'; }
        return file_get_contents($path);
    }

    /**
     * Takes the path of the template and the values from bake()
     * and parses the template, caches a compiled version and returns
     * it.
     * 
     * @param string $template
     * @param array $values
     * @return string
     */
    private function parse(string $template, array $values = [])
    {
        $this->values = $values;

        /**
         * Echo tag (e.g. {{ $var }})
         */
        $template = preg_replace_callback('/{{\s*(\$([A-Za-z0-9_]+))\s*}}/', [$this, 'parseEchoTag'], $template);

        /**
         * If statement tags (e.g. @if(condition) ... @endif)
         */
        $template = preg_replace_callback('/@if\(\s*(.+)\s*\)/', [$this, 'parseIfTag'], $template);
        $template = preg_replace_callback('/@else/', [$this, 'parseElseTag'], $template);
        $template = preg_replace_callback('/@endif/', [$this, 'parseEndIfTag'], $template);

        $cache = $this->directory . '/cached';
        $cached = md5($this->workingFile).'.php';
        $file = $cache.'/'.$cached;

        /**
         * If the cached file doesn't exist, or has changed since it's last
         * compile, we'll write into the file with the new template
         */
        if (! is_readable($file) || md5($template) != md5_file($file)) {
            file_put_contents($file, $template);
        }
        
        /**
         * We'll extract our values array into the global scope and then
         * return the compiled template through a require statement so
         * it will execute
         */
        extract($values);
        return require $file;
    }

    /**
     * Parse the echo tags in the template (e.g. {{ $foo }} becomes <?php echo($foo); ?>)
     * 
     * @param array $match
     * @return string
     */
    private function parseEchoTag(array $match) : string
    {
        if (isset($this->values[$match[2]])) { return '<?php echo('.$match[1].'); ?>'; }
        
        return $match[0];
    }

    /**
     * Parse the if tags in the template (e.g. @if(condition) becomes <?php if($condition) { ?>)
     * 
     * @param array $match
     * @return string
     */
    private function parseIfTag(array $match) : string
    {
        return "<?php if (".$match[1].") { ?>";
    }

    /**
     * Parse the else tag for if statements! (e.g. @else becomes <?php } else { ?>)
     * 
     * @param array $match
     * @return string
     */
    private function parseElseTag(array $match) : string
    {
        return "<?php } else { ?>";
    }

    /**
     * Parse the tags that end if statements (e.g. @endif becomes <?php } ?>)
     * 
     * @param array $match
     * @return string
     */
    private function parseEndIfTag(array $match) : string
    {
        return "<?php } ?>";
    }

    /**
     * Create the "cached" folder in the template directory if it doesn't exist
     * 
     * @return void
     */
    private function createCache()
    {
        $dir = $this->directory;

        if (! file_exists($dir.'/cached')) {
            mkdir($dir.'/cached');
        }
    }

    /**
     * This is the primary run function - it's given a path to the template
     * relative to the class' $directory, and an optional array of values
     * to pass to the template
     * 
     * @param string $template
     * @param array $values
     * @return string
     */
    public function bake(string $template, array $values = [])
    {
        $template = $this->find($template);
        return $this->parse($template, $values);
    }

    /**
     * A quick and easy static function for when you want to parse a string
     * without all the fancy rules and stuff. Parses tags like {{ foo }}
     * with values provided, e.g. ['foo' => 'bar']
     * 
     * @param string $template
     * @param array $values
     * @return $string
     */
    public static function simple(string $template, array $values = [])
    {
        if (is_readable($template)) { $template = file_get_contents($template); }

        return preg_replace_callback(
            '/{{\s*([A-Za-z0-9_-]+)\s*}}/',
            function($match) use ($values) {
                return isset($values[$match[1]]) ? $values[$match[1]] : $match[0];
            },
            $template
        );
    }
}