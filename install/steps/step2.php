<div class="step-content">
    <h2><?php echo $translations['project_detection']; ?></h2>
    
    <div id="detection-result" class="alert alert-info">
        <?php echo $translations['info']; ?>: <?php echo $translations['project_detection']; ?>...
    </div>
    
    <div class="detection-details">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['project_type']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <p id="project-type-result"><?php echo $translations['project_detection']; ?>...</p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['env_file']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <p id="env-file-result"><?php echo $translations['project_detection']; ?>...</p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['vendor_directory']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <p id="vendor-result"><?php echo $translations['project_detection']; ?>...</p>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between mt-4">
        <a href="?step=1" class="btn btn-secondary"><?php echo $translations['return']; ?></a>
        <a href="?step=3" id="next-step" class="btn btn-success disabled"><?php echo $translations['next']; ?></a>
    </div>
</div>

<script>
$(document).ready(function() {
    // Start project detection
    detectProject();
    
    function detectProject() {
        // Afficher l'indicateur de chargement
        $('#detection-result').removeClass('alert-success alert-danger alert-warning')
            .addClass('alert-info')
            .html('<?php echo $translations['info']; ?>: <?php echo $translations['project_detection']; ?>...');
            
        // Réinitialiser les barres de progression
        $('.progress-bar').css('width', '0%');
        $('.progress-bar').removeClass('bg-danger');
        
        // Réinitialiser les résultats
        $('#project-type-result, #env-file-result, #vendor-result').text('<?php echo $translations['project_detection']; ?>...');
        
        // Utiliser une requête AJAX vers le script PHP actuel avec un paramètre d'action
        $.ajax({
            url: 'install.php',
            type: 'GET',
            data: {
                step: 2,
                action: 'detect_project',
                ajax: 1
            },
            dataType: 'json',
            success: function(response) {
                console.log('Project detection response:', response);
                
                updateProjectType(response);
                updateEnvFile(response);
                updateVendorDirectory(response);
                
                if (response.ready_for_next) {
                    $('#next-step').removeClass('disabled');
                    $('#detection-result').removeClass('alert-info alert-danger alert-warning')
                        .addClass('alert-success')
                        .html('<?php echo $translations['success']; ?>: <?php echo $translations['project_detection']; ?> <?php echo $translations['complete']; ?>');
                } else {
                    $('#detection-result').removeClass('alert-info alert-success alert-danger')
                        .addClass('alert-warning')
                        .html('<?php echo $translations['warning']; ?>: <?php echo $translations['project_detection']; ?> <?php echo $translations['complete']; ?> <?php echo $translations['with_warnings']; ?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Project detection error:', error);
                
                // Mettre à jour les barres de progression pour montrer l'échec
                $('.progress-bar').css('width', '100%').addClass('bg-danger');
                
                $('#detection-result').removeClass('alert-info alert-success alert-warning')
                    .addClass('alert-danger')
                    .html('<?php echo $translations['error']; ?>: <?php echo $translations['project_detection']; ?>');
            }
        });
    }
    
    function updateProjectType(data) {
        // Sélectionner directement par ID au lieu de contenu de texte
        const progressBar = $('#project-type-result').closest('.card-body').find('.progress-bar');
        progressBar.css('width', '100%');
        
        let projectTypeText = '';
        switch(data.type) {
            case 'php':
                projectTypeText = '<?php echo $translations['php_project']; ?>';
                break;
            case 'laravel':
                projectTypeText = '<?php echo $translations['laravel_project']; ?>';
                break;
            case 'react':
                projectTypeText = '<?php echo $translations['react_project']; ?>';
                break;
            default:
                projectTypeText = '<?php echo $translations['php_project']; ?>';
        }
        
        $('#project-type-result').text(projectTypeText);
    }
    
    function updateEnvFile(data) {
        // Sélectionner directement par ID au lieu de contenu de texte
        const progressBar = $('#env-file-result').closest('.card-body').find('.progress-bar');
        progressBar.css('width', '100%');
        
        if (data.type === 'php') {
            // Pour les projets PHP simples, indiquer que .env n'est pas nécessaire
            $('#env-file-result').html('<?php echo $translations['env_not_needed']; ?>');
        } else if (data.has_env) {
            // Le fichier .env existe
            $('#env-file-result').html('<?php echo $translations['env_file_exists']; ?>');
        } else if (data.env_created) {
            // Le fichier .env a été créé
            $('#env-file-result').html('<?php echo $translations['creating_env']; ?> - <?php echo $translations['success']; ?>');
        } else {
            // Le fichier .env est manquant et n'a pas pu être créé
            $('#env-file-result').html('<?php echo $translations['env_file_missing']; ?> - <?php echo $translations['creating_env']; ?>...');
        }
    }
    
    function updateVendorDirectory(data) {
        // Sélectionner directement par ID au lieu de contenu de texte
        const progressBar = $('#vendor-result').closest('.card-body').find('.progress-bar');
        progressBar.css('width', '100%');
        
        if (data.type === 'php') {
            // Pour les projets PHP simples, indiquer que vendor n'est pas nécessaire
            $('#vendor-result').html('<?php echo $translations['vendor_not_needed']; ?>');
        } else if (data.has_vendor) {
            // Le répertoire vendor existe
            $('#vendor-result').html('<?php echo $translations['vendor_exists']; ?>');
        } else if (data.vendor_installed) {
            // Le répertoire vendor a été installé
            $('#vendor-result').html('<?php echo $translations['vendor_installed']; ?> - <?php echo $translations['success']; ?>');
        } else {
            // Le répertoire vendor est manquant et n'a pas pu être installé
            $('#vendor-result').html('<?php echo $translations['vendor_missing']; ?> - <?php echo $translations['run_composer']; ?>');
        }
    }
});
</script>
