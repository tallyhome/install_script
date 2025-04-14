<div class="step-content">
    <h2><?php echo $translations['database_configuration']; ?></h2>
    
    <div id="db-result" class="alert d-none"></div>
    
    <form id="db-form" method="post" action="ajax/test_database.php">
        <div class="mb-3">
            <label for="db_host" class="form-label"><?php echo $translations['db_host']; ?></label>
            <input type="text" class="form-control" id="db_host" name="db_host" required 
                value="<?php echo isset($_SESSION['db_config']['host']) ? $_SESSION['db_config']['host'] : 'localhost'; ?>">
        </div>
        
        <div class="mb-3">
            <label for="db_port" class="form-label"><?php echo $translations['db_port']; ?></label>
            <input type="text" class="form-control" id="db_port" name="db_port" required 
                value="<?php echo isset($_SESSION['db_config']['port']) ? $_SESSION['db_config']['port'] : '3306'; ?>">
        </div>
        
        <div class="mb-3">
            <label for="db_name" class="form-label"><?php echo $translations['db_name']; ?></label>
            <input type="text" class="form-control" id="db_name" name="db_name" required 
                value="<?php echo isset($_SESSION['db_config']['database']) ? $_SESSION['db_config']['database'] : ''; ?>">
        </div>
        
        <div class="mb-3">
            <label for="db_user" class="form-label"><?php echo $translations['db_username']; ?></label>
            <input type="text" class="form-control" id="db_user" name="db_username" required 
                value="<?php echo isset($_SESSION['db_config']['username']) ? $_SESSION['db_config']['username'] : ''; ?>">
        </div>
        
        <div class="mb-3">
            <label for="db_password" class="form-label"><?php echo $translations['db_password']; ?></label>
            <input type="password" class="form-control" id="db_password" name="db_password" 
                value="<?php echo isset($_SESSION['db_config']['password']) ? $_SESSION['db_config']['password'] : ''; ?>">
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <div>
                <a href="?step=2" class="btn btn-secondary"><?php echo $translations['return']; ?></a>
                <button type="button" id="test-connection" class="btn btn-primary ms-2">
                    <?php echo $translations['test_connection']; ?>
                </button>
            </div>
            <a href="?step=4" id="next-step" class="btn btn-success <?php echo isset($_SESSION['db_tested']) && $_SESSION['db_tested'] ? '' : 'disabled'; ?>">
                <?php echo $translations['next']; ?>
            </a>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Tester la connexion à la base de données
    $('#test-connection').on('click', function() {
        // Récupérer les données du formulaire
        const host = $('#db_host').val();
        const database = $('#db_name').val();
        const username = $('#db_user').val();
        const password = $('#db_password').val();
        
        // Vérifier que les champs obligatoires sont remplis
        if (!host || !database || !username) {
            alert('<?php echo $translations['fill_required_fields']; ?>');
            return;
        }
        
        // Désactiver le bouton pendant le test
        const $button = $(this);
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo $translations['testing']; ?>...');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: 'ajax/test_db.php',
            type: 'POST',
            dataType: 'json',
            data: {
                host: host,
                database: database,
                username: username,
                password: password
            },
            success: function(response) {
                // Réactiver le bouton
                $button.prop('disabled', false).html('<?php echo $translations['test_connection']; ?>');
                
                // Afficher le résultat
                if (response.status) {
                    // Connexion réussie
                    alert('<?php echo $translations['success']; ?>: ' + response.message);
                    $('#next-step').removeClass('disabled');
                } else {
                    // Erreur de connexion
                    alert('<?php echo $translations['error']; ?>: ' + response.message);
                    $('#next-step').addClass('disabled');
                }
            },
            error: function() {
                // Réactiver le bouton
                $button.prop('disabled', false).html('<?php echo $translations['test_connection']; ?>');
                
                // Afficher l'erreur
                alert('<?php echo $translations['error']; ?>: <?php echo $translations['ajax_error']; ?>');
                $('#next-step').addClass('disabled');
            }
        });
    });
});
</script>
