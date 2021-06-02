<?php if ($previous || $next): ?>

<nav class="nav-flex post-nav">
    <?php if ($previous): ?>

    <div class="nav-flex-left">
        <a class="post-nav__previous" href="<?php echo $previous[ 'link' ]; ?>">
            <span class="post-nav__arrow"><i class="fa fa-arrow-left" aria-hidden=""></i></span>
            <span class="post-nav__label">
            <?php if (strpos($display, 'meta') !== false): ?>

                <span class="post-nav-meta"><?php echo t($previous_text, [ ':node_type_name' => $node_type_name ]); ?></span>
            <?php endif; ?>
            <?php if (strpos($display, 'title') !== false): ?>

                <span class="post-nav-title"><?php echo t($previous[ 'title' ]); ?></span>
            <?php endif; ?>

            </span>
        </a>
    </div>
    <?php endif; ?>
    <?php if ($next): ?>

    <div class="nav-flex-right">
        <a class="post-nav__next" href="<?php echo $next[ 'link' ]; ?>">
            <span class="post-nav__label">
            <?php if (strpos($display, 'meta') !== false): ?>

                <span class="post-nav-meta"><?php echo t($next_text, [ ':node_type_name' => $node_type_name ]); ?></span>
            <?php endif; ?>
            <?php if (strpos($display, 'title') !== false): ?>

                <span class="post-nav-title"><?php echo t($next[ 'title' ]); ?></span>
            <?php endif; ?>

            </span>
            <span class="post-nav__arrow"><i class="fa fa-arrow-right" aria-hidden=""></i></span>
        </a>
    </div>
    <?php endif; ?>

</nav>
<?php endif; ?>