<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="logo-name">
            <img alt="logo" src="logo.png">
            <h1>Installation</h1>
        </div>
        <?php if ($form->form_errors()): ?>
            <?php foreach ($form->form_errors() as $error): ?>

                <div class="alert alert-danger">
                    <p><?php echo $error ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($form->form_success()): ?>
            <?php foreach ($form->form_success() as $success): ?>

                <div class="alert alert-success">
                    <p><?php echo $success ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="cadre">
            <fieldset>
                <legend><span class="glyphicon glyphicon-user" aria-hidden="true"></span> Utilisateur</legend>
                <?php echo $form->renderForm(); ?>
            </fieldset>
        </div>
    </div>
</div>