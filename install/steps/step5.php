<div class="step-content">
    <h2><?php echo $translations['installation_summary']; ?></h2>
    
    <div id="install-result" class="alert d-none"></div>
    
    <div class="installation-progress">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['license_check']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <p><?php echo $translations['license_valid']; ?> <?php echo isset($_SESSION['license_expiry']) ? '- ' . $translations['expiry_date'] . ': ' . $_SESSION['license_expiry'] : ''; ?></p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['project_detection_check']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <?php
                $projectType = '';
                switch($_SESSION['project_type'] ?? 'php') {
                    case 'php':
                        $projectType = $translations['php_project'];
                        break;
                    case 'laravel':
                        $projectType = $translations['laravel_project'];
                        break;
                    case 'react':
                        $projectType = $translations['react_project'];
                        break;
                }
                ?>
                <p><?php echo $projectType; ?></p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['env_creation_check']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <?php if ($_SESSION['project_type'] === 'php'): ?>
                    <p><?php echo $translations['env_not_needed']; ?></p>
                <?php else: ?>
                    <p><?php echo isset($_SESSION['has_env']) && $_SESSION['has_env'] ? $translations['env_file_exists'] : $translations['env_file_missing']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['vendor_check']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar <?php echo ($_SESSION['project_type'] === 'php' || (isset($_SESSION['has_vendor']) && $_SESSION['has_vendor'])) ? 'bg-success' : 'bg-warning'; ?>" role="progressbar" style="width: 100%"></div>
                </div>
                <?php if ($_SESSION['project_type'] === 'php'): ?>
                    <p><?php echo $translations['vendor_not_needed']; ?></p>
                <?php else: ?>
                    <p><?php echo isset($_SESSION['has_vendor']) && $_SESSION['has_vendor'] ? $translations['vendor_exists'] : $translations['vendor_missing'] . ' - ' . $translations['run_composer']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['database_check']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <p><?php echo $translations['connection_success']; ?></p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo $translations['admin_setup_check']; ?></h5>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <p><?php echo $translations['admin_saved']; ?></p>
            </div>
        </div>
    </div>
    
    <div id="installation-progress-bar" class="progress mb-4 d-none">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
    </div>
    
    <div class="d-flex justify-content-between mt-4">
        <a href="?step=4" class="btn btn-secondary"><?php echo $translations['return']; ?></a>
        <div>
            <button type="button" id="start-installation" class="btn btn-success"><?php echo $translations['start_installation']; ?></button>
            <a href="../" id="go-to-login" class="btn btn-primary d-none"><?php echo $translations['go_to_login']; ?></a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#start-installation').on('click', function() {
        // Show progress bar
        $('#installation-progress-bar').removeClass('d-none');
        
        // Disable button
        var installingText = "<?php echo addslashes($translations['installing']); ?>";
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + installingText + '...');
        
        // Clear previous result
        $('#install-result').removeClass('alert-success alert-danger').addClass('d-none');
        
        // Start installation
        $.ajax({
            url: 'ajax/install.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                // Update progress bar
                $('.progress-bar').css('width', '100%');
                
                // Show result
                $('#install-result').removeClass('d-none');
                
                if (response.status) {
                    var completeText = "<?php echo addslashes($translations['installation_complete']); ?>";
                    $('#install-result').addClass('alert-success')
                        .html(completeText);
                    
                    $('#start-installation').addClass('d-none');
                    $('#go-to-login').removeClass('d-none');
                } else {
                    var errorText = "<?php echo addslashes($translations['error']); ?>";
                    $('#install-result').addClass('alert-danger')
                        .html(errorText + ': ' + response.message);
                    
                    var retryText = "<?php echo addslashes($translations['retry']); ?>";
                    $('#start-installation').prop('disabled', false).html(retryText);
                }
            },
            error: function() {
                // Update progress bar
                $('.progress-bar').css('width', '0%');
                
                // Show error
                var errorText = "<?php echo addslashes($translations['error']); ?>";
                var failedText = "<?php echo addslashes($translations['installation_failed']); ?>";
                
                $('#install-result').removeClass('d-none')
                    .addClass('alert-danger')
                    .html(errorText + ': ' + failedText);
                
                var retryText = "<?php echo addslashes($translations['retry']); ?>";
                $('#start-installation').prop('disabled', false).html(retryText);
            }
        });
    });
});
</script>
