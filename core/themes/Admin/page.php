
<header id="header_main">
    <nav class="navbar navbar-grey navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-admin" aria-expanded="false">
                    Menu <i class="fa fa-bars" aria-hidden="true"></i>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navbar-admin">
                <?php echo $section[ 'main_menu' ]; ?>
                <?php echo $section[ 'second_menu' ]; ?>

            </div>
        </div>
    </nav>
    <h1><?php echo $icon; ?> <?php echo $title_main; ?></h1>
    <div class="souligne" ></div>
</header>
<div class="main-wrapper">
    <div class="container">
        <div class="row">
            <?php if (!empty($section[ 'messages' ])): ?>

                <div class="col-md-12">
                    <?php echo $section[ 'messages' ]; ?>

                </div>
            <?php endif; ?>
            <?php if (!empty($section[ 'sidebar' ])): ?>

                <div class="col-md-4">
                    <?php echo $section[ 'sidebar' ]; ?>

                </div>
            <?php endif; ?>

            <?php if (!empty($section[ 'sidebar' ])): ?>
                <?php echo '<div class="col-md-8">'; ?>
            <?php else: ?>
                <?php echo '<div class="col-sm-12">'; ?>
            <?php endif; ?>

            <?php if (!empty($section[ 'content_header' ])): ?>
                <?php echo $section[ 'content_header' ]; ?>
            <?php endif; ?>
            <?php echo $section[ 'content' ]; ?>
            <?php if (!empty($section[ 'content_footer' ])): ?>
                <?php echo $section[ 'content_footer' ]; ?>
            <?php endif; ?>
            <?php echo '</div>'; ?>

        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <footer>             
                <?php if (!empty($section[ 'footer' ])): ?>
                    <hr>   
                    <?php echo $section[ 'footer' ]; ?>

                <?php endif; ?>
            </footer>
        </div>
    </div>
</div>