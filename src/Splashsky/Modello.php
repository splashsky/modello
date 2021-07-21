<?php

namespace Splashsky;

class Modello
{
    /**
     * Defaults to 'views/'. The directory in which we look for view templates, and where we make the cache.
     */
    private static string $views = 'views/';

    /**
     * Defaults to 'views/cache/'. The directory we create to store cached files.
     */
    private static string $cache = 'views/cache/';

    /**
     * Defaults to '.html'. The file extension we expect when reading files.
     */
    private static string $extension = '.html';

    /**
     * Defaults to false. Whether or not we'll serve cached files or recompile them every request.
     */
    private static bool $cacheEnabled = false;

    /**
     * Stores the blocks we're working with in the current file.
     */
    private static array $blocks = [];

    /**
     * Set the directory that views will be looked for in.
     */
    public static function setViews(string $views): string
    {
        return self::$views = $views;
    }

    /**
     * Set where the compiled views will be cached.
     */
    public static function setCache(string $cache): string
    {
        return self::$cache = $cache;
    }

    /**
     * Set whether or not the cache is enabled; whether all views should be compiled every run.
     */
    public static function setCacheEnabled(bool $enabled): bool
    {
        return self::$cacheEnabled = $enabled;
    }

    /**
     * Set the extension of the view files.
     */
    public static function setExtension(string $extension): string
    {
        return self::$extension = $extension;
    }

    /**
     * Takes a given file path and an array of data, and processes the given file (if it exists)
     * according to the rules of the engine. Returns nothing, but uses require to "call" the compiled
     * script.
     *
     * @param string $file The path to a file to read. Prepended with Modello::$views
     * @param array $data Defaults to an empty array. An optional array of data to pass to the compiler.
     */
    public static function view(string $file, array $data = []): void
    {
        $cached = self::cache($file);
        extract($data, EXTR_SKIP);
        require $cached;
    }

    /**
     * Determines whether the given file needs to be cached, or if it needs to be remade. If the file
     * needs recompiled (or compiled for the first time) that process kicks off here. Returns the path
     * to the compiled/cached file.
     */
    private static function cache(string $file): string
    {
        self::makeCacheDirectory();

        $cached = self::$cache . str_replace(['/', self::$extension], ['-', ''], "$file.php");
        $filePath = self::$views . $file . self::$extension;

        // If the cache is disabled, or if the file isn't cached, or if it's been recently modified,
        // (re)compile the file and throw it in the cache
        if (!self::$cacheEnabled || !file_exists($cached) || filemtime($cached) < filemtime($filePath)) {
            $page = self::handleIncludes($file);
            $page = self::compile($page);

            $timestamp = '<!-- Cached on '.date('jS F Y h:i:s A').' -->' . PHP_EOL;
            file_put_contents($cached, $timestamp . $page);
        }

        return $cached;
    }

    /**
     * If the cache directory (Modello::$cache) doesn't exist, create it.
     */
    private static function makeCacheDirectory(): void
    {
        if (!file_exists(self::$cache)) {
            mkdir(self::$cache, 0744);
        }
    }

    /**
     * Recursively handle includes and extends directives, bringing each requested file's contents
     * into the current working file.
     */
    private static function handleIncludes(string $file): string
    {
        $file = self::$views . $file . self::$extension;
        $page = file_get_contents($file);

        preg_match_all('/@(include|extends)\( ?\'(\w+)\' ?\)/i', $page, $matches, PREG_SET_ORDER);

        // Recursively process includes and extends
        foreach ($matches as $match) {
            $page = str_replace($match[0], self::handleIncludes($match[2]), $page);
        }

        return preg_replace('/@(include|extends)\( ?\'(\w+)\' ?\)/i', '', $page);
    }

    /**
     * Send the given page content through all our handler methods, to process directives.
     */
    private static function compile(string $page): string
    {
        $page = self::handleBlocks($page);
        $page = self::handleYields($page);
        $page = self::handleEchoes($page);
        $page = self::handleEscapedEchoes($page);
        $page = self::handlePHP($page);
        $page = self::handleIf($page);
        $page = self::handleElse($page);
        $page = self::handleForeach($page);

        return $page;
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
     * Parse the @else directive with basic replacement strategy
     */
    private static function handleElse(string $page): string
    {
		return preg_replace('/@else/i', '<?php } else { ?>', $page);
	}

    /**
     * Parse the @foreach directive with basic replacement strategy
     */
    private static function handleForeach(string $page): string
    {
		return preg_replace('/@foreach ?\( ?(.*?) ?\)(.*?)@endforeach/is', '<?php foreach ($1) { ?> $2 <?php } ?>', $page);
	}
}