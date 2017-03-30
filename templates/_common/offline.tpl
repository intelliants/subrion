<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <title><?= $page['title'] ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author" content="Intelliants LLC">

        <link rel="stylesheet" type="text/css" href="<?= $page['baseurl'] ?>js/bootstrap/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container" style="padding-top: 100px;">
            <h1><?= $page['title'] ?></h1>

            <div class="well">
                <?= $page['content'] ?>
            </div>

            <div id="copyright">
                <p>Copyright &copy; <?= date('Y') ?> <?= $page['title'] ?></p>
            </div>
        </div>
    </body>
</html>