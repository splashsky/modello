<?php

namespace Splashsky;

class Modello
{
    /**
     * The path where Modello looks for view templates
     */
    private static string $views = 'views/';

    /**
     * The path where Modello caches compiled views
     */
    private static string $cache = 'views/cache/';

    /**
     * Whether or not Modello should serve cached views or recompile every view() call
     */
    private static bool $cacheEnabled = false;

    /**
     * The extension that Modello expects on view template files
     */
    private static string $extension = '.mllo.php';

    /**
     * An array of stored blocks, to insert data through extended/included views
     */
    private static array $blocks = [];

    /**
     * An array of all parsers and handlers in Modello
     */
    private static array $handlers = [
        'handleIncludes',
        'handleBlocks',
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
     * Compile and render the given view file! $view accepts dot notation, and looks in the $views path.
     */
    public static function view(string $view, array $data = []): void
    {
        // If no cache directory exists, create it
        self::makeCacheDirectory();

        // Get the path to the view template, then if we can read it compile it and render it
        $viewPath = self::makeViewPath($view);
        if ($template = self::read($viewPath)) {
            $compiled = self::compile($view, $template);
            extract($data, EXTR_SKIP);
            require $compiled;
        }
    }

    /**
     * Set the path to the views that Modellp will use.
     */
    public static function setViews(string $views): string
    {
        return self::$views = $views;
    }

    /**
     * Set the path for the view cache.
     */
    public static function setCache(string $cache): string
    {
        return self::$cache = $cache;
    }

    /**
     * Set whether or not the caching function is enabled.
     */
    public static function setCacheEnabled(bool $enabled): bool
    {
        return self::$cacheEnabled = $enabled;
    }

    /**
     * Set the extension that Modello expects when looking for view templates.
     */
    public static function setExtension(string $extension): string
    {
        return self::$extension = $extension;
    }

    /**
     * Compile the given view; $view takes the name of the view file for file naming purposes, and
     * $template takes all the content of a view template file
     */
    private static function compile(string $view, string $template): string
    {
        // Get the paths to both the view template and the cached file
        $viewPath = self::makeCachePath($view);
        $cached = self::makeCachePath($view);

        // If there's a cached view and we don't need to recompile it, we'll just return the
        // path to the cached view
        if (!self::viewNeedsRecompiled($viewPath, $cached)) {
            return $cached;
        }

        // Process the template content through every handler Modello has registered
        foreach (self::$handlers as $handler) {
            $template = self::$handler($template);
        }

        // Since at this point we know we needed to (re)compile the view, we'll write the
        // compiled view to the cached view path
        self::makeCachedView($template, $cached);

        // Return the path to the cached view
        return $cached;
    }

    /**
     * Attempt to read the given file. If it doesn't exist, throw an Exception.
     */
    private static function read(string $file)
    {
        if (!file_exists($file)) {
            throw new \Exception("$file doesn't exist.");
        }

        return file_get_contents($file);
    }

    /**
     * Turn the given view name into the path to the view file.
     */
    private static function makeViewPath(string $view): string
    {
        // 'layouts.main' => 'views/layouts/main.mllo.php'
        return self::$views . str_replace('.', '/', $view) . self::$extension;
    }

    /**
     * Turn the given view name into the path to the cached view file.
     */
    private static function makeCachePath(string $view): string
    {
        // 'foo.bar' => 'views/cache/foo-bar.php'
        return self::$cache . str_replace('.', '-', $view) . '.php';
    }

    /**
     * If the cache directory Modello has ($cache) doesn't exist, create it with
     * 0744 permissions.
     */
    private static function makeCacheDirectory(): void
    {
        if (!file_exists(self::$cache)) {
            mkdir(self::$cache, 0744);
        }
    }

    /**
     * Generate the cached view file by putting the compiled view content into a file, prepended
     * with an HTML comment containing the date and time the view was cached.
     */
    private static function makeCachedView(string $view, string $path): void
    {
        $timestamp = '<!-- Cached on '.date('jS F Y h:i:s A').' -->' . PHP_EOL;
        file_put_contents($path, $timestamp . $view);
    }

    /**
     * Determine whether or not the given view at the path $view needs to be recompiled.
     */
    private static function viewNeedsRecompiled(string $view, string $cached): bool
    {
        if (!self::$cacheEnabled || !file_exists($cached) || filemtime($cached) < filemtime($view)) {
            return true;
        }

        return false;
    }

    /**
     * Handle the almighty @include and @extend directive by recursively pulling in other view templates.
     */
    private static function handleIncludes(string $view): string
    {
        preg_match_all('/@(include|extends)\( ?\'(.*?)\' ?\)/i', $view, $matches, PREG_SET_ORDER);

        // Recursively process includes and extends
        foreach ($matches as $match) {
            $included = self::read(self::makeViewPath($match[2]));
            $view = str_replace($match[0], self::handleIncludes($included), $view);
        }

        return preg_replace('/@(include|extends)\( ?\'(.*?)\' ?\)/i', '', $view);
    }

    /**
     * Handle "blocks" of content for our yield() directive.
     */
    private static function handleBlocks(string $page): string
    {
        preg_match_all('/@block ?\( ?\'(\w*?)\' ?\)(.*?)@endblock/is', $page, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (!array_key_exists($match[1], self::$blocks)) {
                self::$blocks[$match[1]] = '';
            }

            if (strpos($match[2], '@parent') === false) {
                self::$blocks[$match[1]] = trim($match[2]);
            } else {
                self::$blocks[$match[1]] = trim(str_replace('@parent', self::$blocks[$match[1]], $match[2]));
            }

            $page = str_replace($match[0], '', $page);
        }

        return $page;
    }

    /**
     * Process yield() directives with data stored in Modello::$blocks by our blocks handler.
     */
    private static function handleYields(string $page): string
    {
        foreach (self::$blocks as $key => $value) {
            $page = preg_replace("/@yield ?\( ?'$key' ?\)/", $value, $page);
		}

		return preg_replace('/@yield ?\( ?\'(.*?)\' ?\)/i', '', $page);
    }

    /**
     * Parse generic echo statements.
     */
    private static function handleEchoes(string $page): string
    {
        return preg_replace('/\{{\s*(.+?)\s*\}}/is', '<?php echo $1; ?>', $page);
    }

    /**
     * Parse escaped echo statements.
     */
    private static function handleEscapedEchoes(string $page): string
    {
        return preg_replace('/\{{{\s*(.+?)\s*\}}}/is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\'); ?>', $page);
    }

    /**
     * Parse a PHP block by simply throwing everything between them into PHP tags.
     */
    private static function handlePHP(string $page): string
    {
		return preg_replace('/@php(.*?)@endphp/is', '<?php $1 ?>', $page);
	}

    /**
     * Parse the @if directive with basic replacement strategy
     */
    private static function handleIf(string $page): string
    {
		return preg_replace('/@if ?\( ?(.*?) ?\)(.*?)@endif/is', '<?php if ($1) { ?> $2 <?php } ?>', $page);
	}

    /**
     * Parse the @elseif directive with basic replacement strategy
     */
    private static function handleElseIf(string $page): string
    {
		return preg_replace('/@elseif ?\( ?(.*?) ?\)/is', '<?php } else if ($1) { ?>', $page);
	}

    /**
     * Parse the @else directive with basic replacement strategy
     */
    private static function handleElse(string $page): string
    {
		return preg_replace('/@else[^if]/i', '<?php } else { ?>', $page);
	}

    /**
     * Parse the @foreach directive with basic replacement strategy
     */
    private static function handleForeach(string $page): string
    {
		return preg_replace('/@foreach ?\( ?(.*?) ?\)(.*?)@endforeach/is', '<?php foreach ($1) { ?> $2 <?php } ?>', $page);
	}

    /**
     * Get rid of all template comments via simple replace
     */
    private static function handleComment(string $page): string
    {
		return preg_replace('/{--(.*?)--}/is', '', $page);
	}
}