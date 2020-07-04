# Modello - simple templater

**Modello** is a super-simple, super-lightweight templating engine written in PHP. It's purpose is to be a standalone class that can be included in any project and used to quickly and efficiently parse a template.

## How does it work?

Download the `.zip` or clone the project using Git. A Componser-ready package is coming soon. Move the `Modello.php`class file to wherever you want to. In the script you need to use Modello in, `require` it.

There are two ways to start up Modello. We'll cover the main way to get it going.
```
require 'path/to/Modello.php';

$modello = new Modello('templates/');
```
You need to pass the path to your template files, with a trailing slash. This path must be to a directory, and must be readable by the script. To parse a template, you only need to use the `bake()` method. The `bake()` method takes two arguments. First is the path to the template, using dot notation. It will search your template directory for the file, and find the first file with the givenname, ending in `.html`.
```
/**
 * File Structure
 * templates/
 *     components/
 *         header.html
 *     page.html
 */

 echo $modello->bake('page', [
     'header' => $modello->bake('components.header')
 ]);
```

As you can see, the second parameter allows you to fill in placeholders in your templates with data. These take the format of `{{key}}`, and in your values array you only need to assign a value to the same-named key, e.g. `'key' => 'value'`. This format ignores extra whitespace between the brackets, so `{{ key }}` or `{{   key}}` is just as valid. Please not the key name cannot have spaces or non-alphanumeric characters. You can use dashes and underscores.
```
{{ key one }} // Will not work
{{ key-one }} // Will work
{{ key_one }} // Will work
```