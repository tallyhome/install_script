<div class="step-content">
    <h2><?php echo $translations['license_verification']; ?></h2>
    
    <div class="alert alert-info mb-4">
        <p><strong><?php echo $translations['info']; ?>:</strong> <?php echo $translations['license_info']; ?></p>
        <p><?php echo $translations['license_api_info']; ?>: <code>https://licence.myvcard.fr</code></p>
    </div>
    
    <div id="license-result" class="alert d-none"></div>
    
    <form id="license-form" method="post" action="ajax/verify_license.php">
        <div class="mb-3">
            <label for="license_key" class="form-label"><?php echo $translations['license_key']; ?></label>
            <input type="text" class="form-control" id="license_key" name="license_key" required 
                placeholder="xxxx-xxxx-xxxx-xxxx" 
                value="<?php echo isset($_SESSION['license_key']) ? $_SESSION['license_key'] : ''; ?>">
            <div class="form-text"><?php echo $translations['license_key_info']; ?></div>
        </div>
        
        <div class="d-flex justify-content-between">
            <button type="button" id="verify-license" class="btn btn-primary">
                <?php echo $translations['verify']; ?>
            </button>
            
            <a href="?step=2" id="next-step" class="btn btn-success <?php echo isset($_SESSION['license_verified']) && $_SESSION['license_verified'] ? '' : 'disabled'; ?>">
                <?php echo $translations['next']; ?>
            </a>
        </div>
    </form>
    
    <div id="license-details" class="mt-4 d-none">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><?php echo $translations['license_details']; ?></h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong><?php echo $translations['status']; ?>:</strong> 
                    <span class="badge bg-success"><?php echo $translations['license_valid']; ?></span>
                </div>
                <div class="mb-3">
                    <strong><?php echo $translations['expiry_date']; ?>:</strong> 
                    <span id="expiry-date"></span>
                </div>
                <div class="mb-3" id="secure-code-container">
                    <strong><?php echo $translations['secure_code']; ?>:</strong> 
                    <span id="secure-code"></span>
                </div>
                <div class="mb-3" id="valid-until-container">
                    <strong><?php echo $translations['valid_until']; ?>:</strong> 
                    <span id="valid-until"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Attendre que jQuery soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si jQuery est chargé
    if (typeof jQuery === 'undefined') {
        console.error('jQuery n\'est pas chargé!');
        return;
    }
    
    console.log('Document ready - License verification script loaded');
    
    $('#verify-license').on('click', function() {
        console.log('Verify license button clicked');
        const licenseKey = $('#license_key').val();
        console.log('License key:', licenseKey);
        
        if (!licenseKey) {
            $('#license-result').removeClass('d-none alert-success alert-danger')
                .addClass('alert-danger')
                .html('<?php echo $translations['license_invalid']; ?>');
            return;
        }
        
        // Show loading
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo $translations['verify']; ?>');
        
        // Log AJAX request
        console.log('Sending AJAX request to verify license');
        
        $.ajax({
            url: '/install/ajax/verify_license.php',
            type: 'POST',
            data: { license_key: licenseKey },
            dataType: 'json',
            success: function(response) {
                console.log('AJAX success response:', response);
                $('#license-result').removeClass('d-none alert-success alert-danger');
                $('#license-details').addClass('d-none');
                
                if (response.status) {
                    $('#license-result').addClass('alert-success')
                        .html('<?php echo $translations['license_valid']; ?>');
                    
                    $('#next-step').removeClass('disabled');
                    
                    // Show license details
                    $('#license-details').removeClass('d-none');
                    $('#expiry-date').text(response.expiry_date || '<?php echo $translations['not_available']; ?>');
                    
                    // Show secure code if available
                    if (response.secure_code) {
                        $('#secure-code').text(response.secure_code);
                        $('#secure-code-container').show();
                    } else {
                        $('#secure-code-container').hide();
                    }
                    
                    // Show valid until if available
                    if (response.valid_until) {
                        $('#valid-until').text(response.valid_until);
                        $('#valid-until-container').show();
                    } else {
                        $('#valid-until-container').hide();
                    }
                } else {
                    $('#license-result').addClass('alert-danger')
                        .html(response.message || '<?php echo $translations['license_invalid']; ?>');
                    
                    $('#next-step').addClass('disabled');
                }
            },
            error: function() {
                $('#license-result').removeClass('d-none alert-success')
                    .addClass('alert-danger')
                    .html('<?php echo $translations['error']; ?>: <?php echo $translations['license_invalid']; ?>');
            },
            complete: function() {
                $('#verify-license').prop('disabled', false).html('<?php echo $translations['verify']; ?>');
            }
        });
    });
    
    // If license is already verified, show details
    <?php if (isset($_SESSION['license_verified']) && $_SESSION['license_verified']): ?>
    $('#license-details').removeClass('d-none');
    $('#expiry-date').text('<?php echo $_SESSION['license_expiry'] ?? $translations['not_available']; ?>');
    
    <?php if (isset($_SESSION['license_secure_code'])): ?>
    $('#secure-code').text('<?php echo $_SESSION['license_secure_code']; ?>');
    $('#secure-code-container').show();
    <?php else: ?>
    $('#secure-code-container').hide();
    <?php endif; ?>
    
    <?php if (isset($_SESSION['license_valid_until'])): ?>
    $('#valid-until').text('<?php echo $_SESSION['license_valid_until']; ?>');
    $('#valid-until-container').show();
    <?php else: ?>
    $('#valid-until-container').hide();
    <?php endif; ?>
    <?php endif; ?>
});
</script>
