<div class="step-content">
    <h2><?php echo $translations['admin_account']; ?></h2>
    
    <div id="admin-result" class="alert d-none"></div>
    
    <form id="admin-form" method="post" action="ajax/save_admin.php">
        <div class="mb-3">
            <label for="project_url" class="form-label"><?php echo $translations['project_url']; ?></label>
            <input type="url" class="form-control" id="project_url" name="project_url" required 
                value="<?php echo isset($_SESSION['admin_config']['project_url']) ? $_SESSION['admin_config']['project_url'] : 'http://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])); ?>">
        </div>
        
        <div class="mb-3">
            <label for="admin_email" class="form-label"><?php echo $translations['admin_email']; ?></label>
            <input type="email" class="form-control" id="admin_email" name="admin_email" required 
                value="<?php echo isset($_SESSION['admin_config']['email']) ? $_SESSION['admin_config']['email'] : ''; ?>">
        </div>
        
        <div class="mb-3">
            <label for="admin_password" class="form-label"><?php echo $translations['admin_password']; ?></label>
            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
        </div>
        
        <div class="mb-3">
            <label for="confirm_password" class="form-label"><?php echo $translations['confirm_password']; ?></label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        
        <div class="row mt-4">
            <div class="col-12 mb-3">
                <button type="button" id="save-admin" class="btn btn-primary">
                    <?php echo $translations['save']; ?>
                </button>
            </div>
            
            <div class="col-12">
                <div class="d-flex justify-content-between">
                    <a href="?step=3" class="btn btn-secondary"><?php echo $translations['return']; ?></a>
                    <a href="?step=5" id="next-step" class="btn btn-success <?php echo isset($_SESSION['admin_saved']) && $_SESSION['admin_saved'] ? '' : 'disabled'; ?>">
                        <?php echo $translations['next']; ?>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Vérifier si l'administrateur a déjà été sauvegardé
    <?php if (!isset($_SESSION['admin_saved']) || !$_SESSION['admin_saved']): ?>
    $('#next-step').addClass('disabled');
    <?php endif; ?>

    $('#save-admin').on('click', function() {
        const adminConfig = {
            project_url: $('#project_url').val(),
            email: $('#admin_email').val(),
            password: $('#admin_password').val(),
            confirm_password: $('#confirm_password').val()
        };
        
        // Valider les champs
        if (!adminConfig.project_url || !adminConfig.email || !adminConfig.password || !adminConfig.confirm_password) {
            $('#admin-result').removeClass('d-none alert-success alert-danger')
                .addClass('alert-danger')
                .html('<?php echo $translations['error']; ?>: <?php echo $translations['all_fields_required']; ?>');
            return;
        }
        
        // Valider la correspondance des mots de passe
        if (adminConfig.password !== adminConfig.confirm_password) {
            $('#admin-result').removeClass('d-none alert-success alert-danger')
                .addClass('alert-danger')
                .html('<?php echo $translations['error']; ?>: <?php echo $translations['passwords_not_match']; ?>');
            return;
        }
        
        // Afficher le chargement
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo $translations['saving']; ?>...');
        
        $.ajax({
            url: 'ajax/save_admin.php',
            type: 'POST',
            data: adminConfig,
            dataType: 'json',
            success: function(response) {
                $('#admin-result').removeClass('d-none alert-success alert-danger');
                
                if (response.status) {
                    $('#admin-result').addClass('alert-success')
                        .html('<?php echo $translations['admin_saved']; ?>');
                    
                    $('#next-step').removeClass('disabled');
                } else {
                    $('#admin-result').addClass('alert-danger')
                        .html('<?php echo $translations['error']; ?>: ' + (response.message || '<?php echo $translations['unknown_error']; ?>'));
                    
                    $('#next-step').addClass('disabled');
                }
            },
            error: function() {
                $('#admin-result').removeClass('d-none alert-success')
                    .addClass('alert-danger')
                    .html('<?php echo $translations['error']; ?>: <?php echo $translations['save_failed']; ?>');
            },
            complete: function() {
                $('#save-admin').prop('disabled', false).html('<?php echo $translations['save']; ?>');
            }
        });
    });
});
</script>
