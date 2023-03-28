<p align="center"><img src="https://i.imgur.com/xZKsXPi.jpg" width="600"></p>
<p align="center">
    <img src="https://img.shields.io/github/v/release/splashsky/modello?style=for-the-badge">
    <img src="https://img.shields.io/badge/made%20with-php-blueviolet?style=for-the-badge">
</p>

# Modello - simple, lightweight template compiler

**Modello** is a super-simple, super-lightweight templating engine written in PHP. It's purpose is to be a standalone class that can be included in any project and used to quickly and efficiently parse/compile a template.

## Contributing
While I don't intend for this to become a replacement for other templating solutions, I felt it would be fun to make a simple little compiler for small projects. The goal is ease of use and compactness, and hopefully my work reflects that.

If you have ideas, or want to contribute some code, feel more than welcome to open an Issue or a Pull Request. Thanks for dropping by!

## Getting Started
The easiest way of using Modello in your project is Composer.
```shell
composer require splashsky/modello
```

Otherwise, you can download the `.zip` or clone the project using Git. After that, move the `Modello.php` class file to wherever you want!

## How do I start up the compiler?
Modello is easy to get started with.

```php
// require() and/or 'use' Modello, depending on your environment
$modello = new Modello('path/to/views', 'path/to/cache');
```

The first argument for the constructor tells Modello where your template directory is, and this will be the root from which Modello looks for template files. The second argument tells Modello where to create the compiled views.

`$modello->setViews(string $viewPath)` and `$modello->setCache(string $cachePath)` are available to change the views and cache directories at runtime.
Using `$modello->setExtension(string $extension)` allows you to change the extension Modello looks for on template files - by default this is `.mllo.php`.
You can use `$modello->setCacheEnabled(bool $enabled)` to enable or disable caching - disabled means that Modello will re-compile the view on every render.

## How do I compile a template?
Simply call `$modello->view()`!

```php
/**
 * File Directory
 * | cache/views/
 *   example.php (will be created at render)
 * | views/
 *   example.mllo.php
*/

echo $modello->view('example', ['foo' => 'bar']);
```

When telling Modello what template to render, ensure you're not adding the extension - only use the name of the template. Modello will use whatever extension it has set when it looks for your file. You can use dot notation as well, so 'foo.bar' is as valid as 'foo/bar'.

The second argument in the `view()` function is your data array - these `key => value` pairs will be extracted into the resulting template.

When a template is compiled for the first time - or if Modello detects the original template file has changed - it will generate a newly compiled version of the template and store it in the cached directory for faster subsequent renders.

## What syntax can I use in the template?
Modello uses template syntax very similar (basically identical) to Laravel Blade. Here's the directives it currently supports:

```
// This is the echo syntax
{{ $foo }}
{{ bar() }}

// This is the if-else syntax
@if(condition)
    // ...
@elseif(condition)
    // ...
@else
    // ...
@endif

// This is the foreach syntax
@foreach($array as $key => $value)
    {{ $key }} equals {{ $value }}
@endforeach

// This is a comment
{{-- I won't show up in the HTML or in the compiled template file! --}}

// This is the almighty include directive!
@include('path.to.template')

// We also support blocks and yielding
@block('foo')
    something here
@endblock

@yield('foo') // Outputs: something here

// Add block conditionals
@hasblock('foo')
if block foo exists, this will be output!
@endif

@blockmissing('bar')
if the bar block doesn't exist, this will be put out!
@endif
```

As long as the data you provide the directives is valid PHP, it will work!

For the include directive, it will look for the template you specify relative to the template directory you gave to Modello. If it cannot find the template you specify, it will return "path/to/template.php could not be found", which will show up in your HTML.

## What if I just need to parse?
Modello still has the classic parsing functionality from before - in the static method `parse()`.

```php
echo Modello::parse('Hello, {{ name }}!', ['name' => 'Jerry']);
```

Of course, you won't have access to any of the directives or other features of a compiled template.

## License
Modello is completely free, open source software. It's covered under the MIT license, and you can read the details in the [license.md](license.md).