<!DOCTYPE html>
<html lang="en-US">
    <head>
        <title>{{ $title }}</title>
    </head>

    <body>
        <h1>{{ $h1 }}</h1>
        <main>
            <p>
                {{ $content }}
            </p>

            @if($foo)
                <p>Foo is true!</p>
            @else
                <p>Foo is not true!</p>
            @endif
        </main>
    </body>
</html>