<!DOCTYPE html>
<html lang="en-US">
    <head>
        <title><?php echo($title); ?></title>
    </head>

    <body>
        <h1><?php echo($h1); ?></h1>
        <main>
            <p>
                <?php echo($content); ?>
            </p>

            <?php if ($foo) { ?>
                <p>Foo is true!</p>
            <?php } else { ?>
                <p>Foo is not true!</p>
            <?php } ?>
        </main>
    </body>
</html>