# Modello - simple templater

**Modello** is a super-simple, super-lightweight templating engine written in PHP. It's purpose is to be a standalone class that can be included in any project and used to quickly and efficiently parse a template.

## Contributing
While I don't intend for this to become a replacement for other templating solutions or anything to really depend on in a project, I felt it would be fun to make a simple little compiler for small projects. The goal is ease of use and compactness, and hopefully my work reflects that.

If you have ideas, or want to contribute some code, feel more than welcome to open an Issue or a Pull Request. Thanks for dropping by!

## How does it work?

Download the `.zip` or clone the project using Git. Move the `Modello.php`class file to wherever you want to. In the script you need to use Modello in, `require` it.

There are two ways to use Modello. We'll cover the main way to get it going.

```php
require 'path/to/Modello.php';

$modello = new Modello('templates/');
```
You need to pass the path to your template files, with a trailing slash. This path must be to a directory, and must be readable by the script. To parse a template, you only need to use the `bake()` method. The `bake()` method takes two arguments. First is the path to the template, using dot notation. It will search your template directory for the file, and find the first file with the given name, ending in `.php`. You can change the extension by passing a second argument to the constructor, for example '.html'.

```php
/**
 * templates/
 *     page.html
 */

 echo $modello->bake('page', [
     'content' => 'Hello, world!'
 ]);
```

As you can see, the second parameter allows you to fill in placeholders in your templates with data. These take the format of `{{$key}}`, and in your values array you only need to assign a value to the same-named key, e.g. `'key' => 'value'`. This format ignores extra whitespace between the brackets, so `{{ $key }}` or `{{   $key}}` is just as valid. Please not the key name cannot have spaces or non-alphanumeric characters. You can use underscores.

```php
{{ $key one }} // Will not work
{{ $key-one }} // Will not work
{{ $key_one }} // Will work
```

Modello also currently supports basic if-else control structures, the syntax for which is:

```php
@if(condition)
    ... content here
@else
    ... other content
@endif
```

It can access any variable you passed in the `$values` parameter of your `bake()`.

If you need to quickly parse a simple string, you can use the static method `simple()`, which can be used like so:

```php
Modello::simple('Hello, {{ name }}!', ['name' => 'Splash']);
```

You can also specify a file in the first parameter (e.g. `$_SERVER['DOCUMENT_ROOT'].'/templates/foo.html'`) and if it is readable it will parse that file using the same formatting.