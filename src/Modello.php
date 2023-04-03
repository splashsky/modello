<?php

namespace Splashsky;

class Modello
{
    private string $views = 'views/';
    private string $cache = 'cache/views/';
    private bool $cacheEnabled = true;
    private string $extension = '.mllo.php';

    // Stored blocks. Enables the @yield directive!
    private array $blocks = [];

    // The function names of the internal directive handlers.
    private array $handlers = [
        'handleIncludes',
        'handleBlocks',
        'handleBlockInline',
        'handleHasBlock',
        'handleBlockMissing',
        'handleYields',
        'handleEchoes',
        'handleEscapedEchoes',
        'handlePHP',
        'handleIf',
        'handleElse',
        'handleElseIf',
        'handleForeach',
        'handleComment'
    ];

    /**
     * Create an instance of Modello, and optionally pass a new viewPath and cachePath. Defaults to 'views/' and 'cache/views/' respectively.
     */
    public function __construct(string $viewPath = '', string $cachePath = '')
    {
        $this->views = empty($viewPath) ? 'views/' : $viewPath;
        $this->cache = empty($cachePath) ? 'cache/views/' : $cachePath;
    }

    /**
     * Compile and render the given view file! $view accepts dot notation, and looks in the $views path. Returns false on failure.
     */
    public function view(string $view, array $data = []): string|false
    {
        // If no cache directory exists, create it
        $this->makeCacheDirectory();

        // Get the path to the view template, then if we can read it compile it and render it
        $viewPath = $this->makeViewPath($view);
        if ($template = $this->read($viewPath)) {
            $compiled = $this->compile($view, $template);

            // Render the view in an output buffer sandbox and return the results.
            ob_start();
            extract($data, EXTR_SKIP);
            require $compiled;
            return ob_get_clean();
        }

        return false;
    }

    /**
     * Set the path to the views that Modello will use.
     */
    public function setViews(string $views): string
    {
        return $this->views = $views;
    }

    /**
     * Set the path for the view cache.
     */
    public function setCache(string $cache): string
    {
        return $this->cache = $cache;
    }

    /**
     * Set whether or not the caching function is enabled.
     */
    public function setCacheEnabled(bool $enabled): bool
    {
        return $this->cacheEnabled = $enabled;
    }

    /**
     * Set the extension that Modello expects when looking for view templates.
     */
    public function setExtension(string $extension): string
    {
        return $this->extension = $extension;
    }

    // Compile the given view. $view takes the path to the template file, $template takes the content.
    private function compile(string $view, string $template): string
    {
        // Get the paths to both the view template and the cached file
        $viewPath = $this->makeViewPath($view);
        $cached = $this->makeCachePath($view);

        // If there's a cached view and we don't need to recompile it, we'll just return the
        // path to the cached view
        if (!$this->viewNeedsRecompiled($viewPath, $cached)) {
            return $cached;
        }

        // Process the template content through every handler Modello has registered
        foreach ($this->handlers as $handler) {
            $template = $this->$handler($template);
        }

        // Since at this point we know we needed to (re)compile the view, we'll write the
        // compiled view to the cached view path
        $this->makeCachedView($template, $cached);

        // Return the path to the cached view
        return $cached;
    }

    // Read the given $file or throw an exception.
    private function read(string $file): string|false
    {
        if (!file_exists($file)) { return false; }
        return file_get_contents($file);
    }

    // Turn a given $view path into a real path.
    private function makeViewPath(string $view): string
    {
        // 'layouts.main' => 'views/layouts/main.mllo.php'
        return $this->views . str_replace('.', '/', $view) . $this->extension;
    }

    // Turn a given $view path into a real path for the compiled/cached view file.
    private function makeCachePath(string $view): string
    {
        // 'foo.bar' => 'cache/views/foo-bar.php'
        return $this->cache . str_replace('.', '-', $view) . '.php';
    }

    // Create the $this->cache directory with 0744 perms, if it doesn't exist.
    private function makeCacheDirectory(): void
    {
        if (!file_exists($this->cache)) {
            mkdir($this->cache, 0744);
        }
    }

    // Create the compiled/cached view file and prepend a timestamp of now.
    private function makeCachedView(string $view, string $path): void
    {
        $timestamp = '<!-- Cached on '.date('jS F Y h:i:s A').' -->' . PHP_EOL;
        file_put_contents($path, $timestamp . $view);
    }

    // Determine whether we need to recompile the given $view. Always yes if $this->cacheEnabled is false.
    private function viewNeedsRecompiled(string $view, string $cached): bool
    {
        // Any of these conditions means immediate recompile
        if (!$this->cacheEnabled || !file_exists($cached) || filemtime($cached) < filemtime($view)) {
            return true;
        }

        // Perform a check on linked includes.
        if ($this->checkIncludesChanged($view, filemtime($cached))) {
            return true;
        }

        return false;
    }

    // Used for recursively checking includes against the current cached file, to see if recompile is needed.
    private function checkIncludesChanged(string $template, int $cachedMTime)
    {
        $recompile = false;
        $content = $this->read($template);

        preg_match_all('/@(?:include|extends)\( ?\'(.*?)\' ?\)/i', $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) { return false; }

        foreach ($matches as $match) {
            $path = $this->makeViewPath($match[1]);
            if (!$this->read($path)) { continue; }
            if ($cachedMTime < filemtime($path)) { return true; }
            $recompile = $this->checkIncludesChanged($path, $cachedMTime);
        }

        return $recompile;
    }

    // Handle view includes/extension by recursively grabbing view files and parsing them in.
    private function handleIncludes(string $view): string
    {
        preg_match_all('/@(include|extends)\( ?\'(.*?)\' ?\)/i', $view, $matches, PREG_SET_ORDER);

        // Recursively process includes and extends
        foreach ($matches as $match) {
            $included = $this->read($this->makeViewPath($match[2]));
            $view = str_replace($match[0], $this->handleIncludes($included), $view);
        }

        return preg_replace('/@(include|extends)\( ?\'(.*?)\' ?\)/i', '', $view);
    }

    // Grab @block directives so we can have data to fill potenital @yield directives.
    private function handleBlocks(string $page): string
    {
        preg_match_all('/@block\( ?\'(\w*?)\' ?\)(.*?)@endblock/is', $page, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (!array_key_exists($match[1], $this->blocks)) {
                $this->blocks[$match[1]] = '';
            }

            if (strpos($match[2], '@parent') === false) {
                $this->blocks[$match[1]] = trim($match[2]);
            } else {
                $this->blocks[$match[1]] = trim(str_replace('@parent', $this->blocks[$match[1]], $match[2]));
            }

            $page = str_replace($match[0], '', $page);
        }

        return $page;
    }

    // Replace @yield directives with the @block data, if the given key exists. Blank it out otherwise.
    private function handleYields(string $page): string
    {
        foreach ($this->blocks as $key => $value) {
            $page = str_replace("@yield('$key')", $value, $page);
		}

		return preg_replace('/@yield\(\'(.*?)\'\)/i', '', $page);
    }

    // Echo!
    private function handleEchoes(string $page): string
    {
        return preg_replace('/\{{\s*(.+?)\s*\}}/is', '<?php echo $1; ?>', $page);
    }

    // THE ECHO ESCAPED!
    private function handleEscapedEchoes(string $page): string
    {
        return preg_replace('/\{{{\s*(.+?)\s*\}}}/is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\'); ?>', $page);
    }

    // Put everything in @php in <?php
    private function handlePHP(string $page): string
    {
		return preg_replace('/@php(.*?)@endphp\b/is', '<?php $1 ?>', $page);
	}

    // Handle @if with a cryptic-looking regex. The most useful control structure!
    private function handleIf(string $page): string
    {
		return preg_replace('/@if ?(\(((?:[^()]++|(?1))*)\))(.*?)@endif\b/is', '<?php if ($2) { ?>$3<?php } ?>', $page);
	}

    // Use the same cryptic regex to do @elseif
    private function handleElseIf(string $page): string
    {
		return preg_replace('/@elseif ?(\(((?:[^()]++|(?1))*)\))/is', '<?php } else if ($2) { ?>', $page);
	}

    // Else.
    private function handleElse(string $page): string
    {
		return preg_replace('/@else[^if]/i', '<?php } else { ?>', $page);
	}

    // Third time's the charm for this regex. This time, the most useful loop, @foreach!
    private function handleForeach(string $page): string
    {
		return preg_replace('/@foreach ?(\(((?:[^()]++|(?1))*)\))(.*?)@endforeach\b/is', '<?php foreach ($2) { ?>$3<?php } ?>', $page);
	}

    // Make comments disappear. Won't show up in the cached view, either.
    private function handleComment(string $page): string
    {
		return preg_replace('/{--(.*?)--}/is', '', $page);
	}

    // A directive to test whether we have a block by a given key
    function handleHasBlock(string $page): string
    {
        preg_match_all('/@hasblock\( ?\'(\w*?)\' ?\)(.*?)@endif/is', $page, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $replace = array_key_exists($match[1], $this->blocks) ? $match[2] : '';
            $page = str_replace($match[0], $replace, $page);
        }

        return $page;
    }

    // Directive that does the opposite of @hasblock
    function handleBlockMissing(string $page): string
    {
        preg_match_all('/@blockmissing\( ?\'(\w*?)\' ?\)(.*?)@endif/is', $page, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $replace = !array_key_exists($match[1], $this->blocks) ? $match[2] : '';
            $page = str_replace($match[0], $replace, $page);
        }

        return $page;
    }

    // Directive to do the same thing as @block but in one line with two strings
    function handleBlockInline(string $page): string
    {
        preg_match_all('/@block\( ?\'(\w*?)\', ?\'(\N*?)\' ?\)/is', $page, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            if (!array_key_exists($match[1], $this->blocks)) {
                $this->blocks[$match[1]] = $match[2];
            }

            $page = str_replace($match[0], '', $page);
        }

        return $page;
    }

    /**
     * Quickly parse the given $string with key => value pairs in $data. {{ foo }} + ['foo' => 'bar'] = bar
     */
    public static function parse(string $string, array $data): string
    {
        return preg_replace_callback(
            '/{{\s*([A-Za-z0-9_-]+)\s*}}/',
            function($match) use ($data) {
                return isset($data[$match[1]]) ? $data[$match[1]] : $match[0];
            },
            $string
        );
    }
}