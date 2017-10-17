<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <title><?= isset($this->steps[$this->step]) ? $this->steps[$this->step] : $this->layout()->title ?> :: Subrion CMS Web Installer</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author" content="Intelliants LLC">

        <!--[if lt IE 9]>
            <script src="<?= URL_ASSETS ?>templates/js/shiv.js"></script>
            <script src="<?= URL_ASSETS ?>templates/js/respond.min.js"></script>
        <![endif]-->

        <link rel="stylesheet" href="<?= URL_ASSETS ?>templates/css/bootstrap.css">

        <link rel="apple-touch-icon-precomposed" sizes="144x144" href="<?= URL_ASSETS ?>templates/img/ico/apple-touch-icon-144-precomposed.png">
        <link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?= URL_ASSETS ?>templates/img/ico/apple-touch-icon-114-precomposed.png">
        <link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?= URL_ASSETS ?>templates/img/ico/apple-touch-icon-72-precomposed.png">
        <link rel="apple-touch-icon-precomposed" href="<?= URL_ASSETS ?>templates/img/ico/apple-touch-icon-57-precomposed.png">
        <link rel="shortcut icon" href="<?= URL_ASSETS ?>templates/img/ico/favicon.ico">

        <script src="<?= URL_ASSETS ?>templates/js/jquery.js"></script>
        <script src="<?= URL_ASSETS ?>templates/js/bootstrap.min.js"></script>
    </head>

    <body>
        <div class="overall-wrapper">
            <div class="panels-wrapper">
                <section id="panel-left">
                    <a class="brand" href="<?= URL_INSTALL ?>">
                        <img src="<?= URL_ASSETS ?>templates/img/logo.png" alt="Subrion CMS &middot; <?= IA_VERSION ?>">
                    </a>
                    <ul class="nav-main">
                        <?php if (in_array('install', $this->modules)): ?>
                            <li<?php if ($this->module == 'install'): ?> class="active"<?php endif ?>><a href="<?= URL_INSTALL ?>install/"><i class="i-box-add"></i>Installation wizard</a></li>
                        <?php endif ?>
                        <?php if (in_array('upgrade', $this->modules)): ?>
                            <li<?php if ($this->module == 'upgrade'): ?> class="active"<?php endif ?>><a href="<?= URL_INSTALL ?>upgrade/"><i class="i-loop"></i>Upgrade wizard</a></li>
                        <?php endif ?>
                    </ul>
                    <div class="system-info">
                        <div class="social-links">
                            <a href="https://twitter.com/IntelliantsLLC" target="_blank"><i class="i-twitter-2"></i></a>
                            <a href="https://github.com/intelliants/subrion" target="_blank"><i class="i-github"></i></a>
                        </div>
                        <a href="https://subrion.org/" title="Open Source CMS">Subrion CMS</a>
                        <br>
                        <span class="version">v <?= IA_VERSION ?></span>
                    </div>
                </section>

                <section id="panel-center">
                    <ul id="nav-sub-something" class="nav-sub active">
                        <li class="heading">Steps</li>
<?php
    $current = array_search($this->step, array_keys($this->steps));

    $i = 0;
    foreach ($this->steps as $key => $value)
    {
        $i++;
        if ($key == $this->step)
        {
            echo "<li class=\"active\"><span>{$i}. {$value}</span></li>";
        }
        elseif ($current >= $i)
        {
            echo '<li class="done"><a href="' . URL_INSTALL . $this->module . '/' . $key . '/"><i class="i-checkmark"></i> ' . $value . '</a></li>';
        }
        else
        {
            echo "<li><span>{$i}. {$value} </span></li>";
        }
    }
?>
                    </ul>
                </section>

                <section id="panel-content">
                    <div class="navbar navbar-static-top navbar-inverse">
                        <ul class="nav navbar-nav navbar-left">
                            <li>
                                <a href="https://subrion.org/" title="Contact us if you have any questions." target="_blank">
                                    <i class="i-envelop"></i>
                                    <span> Contacts</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://subrion.org/desk/" title="Submit a ticket and get a fast reply." target="_blank">
                                    <i class="i-support"></i>
                                    <span> Helpdesk</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://subrion.org/forums/" title="Ask questions in our user forums." target="_blank">
                                    <i class="i-bubbles-2"></i>
                                    <span> User Forums</span>
                                </a>
                            </li>
                        </ul>
                        <p class="navbar-text pull-right">Copyright &copy; <?= date('Y') ?> <a href="https://intelliants.com/" title="Software Development Company">Intelliants LLC</a></p>
                    </div>

                    <div class="content-wrapper">
                        <div class="block">
                            <?php if (isset($this->steps[$this->step])): ?>
                            <div class="block-heading">
                                <h3><?= $this->steps[$this->step] ?></h3>
                            </div>
                            <?php endif ?>
                            <div class="block-content">
                                <?php if ($this->message): ?>
                                    <div class="min-height">
                                        <div class="alert alert-error"><?= $this->message ?></div>
                                    </div>
                                <?php endif ?>

                                <?= $this->layout()->content ?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </body>
</html>