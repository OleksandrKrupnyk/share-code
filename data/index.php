<html lang="en">
    <head>
    <title>
        Hello page 3
    </title>
    </head>
    <body>
        <h1>Hello world3!</h1>
        <div>
            <ul>
                <?php for($i=1;$i<=10;$i++):?>
                <li> <?=random_int(10,20)?> This is a test page. It can be a script on php language.</li>
                <?php endfor;?>
            </ul>
        </div>
        <?php phpinfo(); ?>
</body>
</html>