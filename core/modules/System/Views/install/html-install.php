<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta content="IE=edge" http-equiv="X-UA-Compatible">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Soosyze | <?php echo $steps[ $step_active ][ 'title' ]; ?></title>
        <link rel="stylesheet" href="<?php echo $style_soosyze; ?>">
        <link rel="stylesheet" href="<?php echo $style_install; ?>">
    </head>
    <body class="dark">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="logo-name">
                        <img class="header_logo" src="<?php echo $base_path; ?>logo.svg" alt="logo">
                        <h1>Soosyze</h1>
                    </div>

                    <ul class="nav nav-pills">
                    <?php $i = 0; ?>
                    <?php foreach ($steps as $key => $step): ?>
                        <li class="<?php echo $key === $step_active ? 'step-active' : ''; ?>">
                            <?php ++$i; ?>
                            <?php if ($steps[$step_active]['weight'] > $step['weight']): ?>
                            <a href="<?php echo $router->getRoute('install.step', [ ':id' => $key ]); ?>">
                                <span style="color: #16ab39">✔</span> <?php echo t($step[ 'title' ]); ?>
                            </a>
                            <?php else: ?>
                            <span class="step-inactive">
                                <span class="step-number"><?php echo $i; ?>.</span> <?php echo t($step[ 'title' ]); ?>
                            </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-md-6">
                    <div class="wrapper-main">
                        <?php if (!empty($section[ 'messages' ])): ?>
                            <?php echo $section[ 'messages' ]; ?>
                        <?php endif; ?>
                        <?php echo $section[ 'page' ]; ?>
                    </div>
                    <p>
                        Power by <strong><a class="footer" href="https://soosyze.com">Soosyze CMS</a></strong>
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>