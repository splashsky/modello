<p align="center"><img src="https://i.imgur.com/xZKsXPi.jpg" width="600"></p>
<p align="center">
    <img src="https://img.shields.io/github/v/release/splashsky/modello?style=for-the-badge">
    <img src="https://img.shields.io/badge/made%20with-php-blueviolet?style=for-the-badge">
</p>

# Modello - simple, lightweight template compiler

**Modello** is a super-simple, super-lightweight templating engine written in PHP. It's purpose is to be a standalone class that can be included in any project and used to quickly and efficiently parse/compile a template.

## Contributing
While I don't intend for this to become a replacement for other templating solutions or anything to really depend on in a project, I felt it would be fun to make a simple little compiler for small projects. The goal is ease of use and compactness, and hopefully my work reflects that.

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
// require() or 'use' Modello, depending on your environment

$modello = new Modello('path/to/template/directory');
```

The first argument for the constructor tells Modello where your template directory is, and this will be the root from which Modello looks for template files. This is also where Modello will created a "cached" directory, where compiled templates are cached.

The second argument allows you to change the extension Modello looks for on template files - by default this is `.php`. Regardless of what you set as the extension, all compiled templates will have the `.php` extension so that they are executed as PHP scripts.

## How do I compile a template?
Modello calls this "baking". The syntax for baking a template is simple, too!

```php
/**
 * File Directory
 * templates/
 *     cached/
 *     example.php
*/

echo $modello->bake('example', [
    'foo' => 'bar'
]);
```

When telling Modello what template to bake, ensure you're not adding the extension - only use the name of the template. Modello will use whatever extension it has set when it looks for your file. You can use dot notation as well, so 'foo.bar' is as valid as 'foo/bar'.

The second argument in the `bake()` function is your values array - these will be extracted into the resulting template.

When a template is compiled for the first time - or if Modello detects the original template file has changed - it will generate a new compiled version of the template and store it in the cached directory. The name of the file is an `md5()` hash of the fully qualified path and name of your template file.

## What syntax can I use in the template?
Modello uses template syntax very similar to Laravel Blade. Here's the directives it currently supports:

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
```

As long as the data you provide the directives is valid PHP, it will work!

For the include directive, it will look for the template you specify relative to the template directory you gave to Modello. If it cannot find the template you specify, it will return "path/to/template.php could not be found", which will show up in your HTML.

## What if I just need to parse?
Modello still has the classic parsing functionality from before - in the new static method `simple()`.

```php
// Again, require() or 'use' Modello depending on your environment

// You can simply pass a string to the first parameter...
echo Modello::simple('Hello, {{ name }}!', ['name' => 'Jerry']);

// Or pass a fully qualified path!
echo Modello::simple('path/to/file', ['foo' => 'bar']);
```

Of course, you wont have access to any of the directives or other features of a compiled template.

## License
Modello is completely free, open source software. It's covered under the classic MIT license, and you can read the details in the [license.md](license.md).