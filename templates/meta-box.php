<?php
/**
 * Template do meta box para edição de trips
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tripz-meta-box">
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="paytour_id"><?php _e('ID PayTour:', 'tripz-paytour'); ?></label>
            </th>
            <td>
                <input type="text" 
                       id="paytour_id" 
                       name="paytour_id" 
                       value="<?php echo esc_attr($paytour_id); ?>" 
                       class="regular-text" />
                <p class="description">
                    <?php _e('ID único do trip na plataforma PayTour.', 'tripz-paytour'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="trip_price"><?php _e('Preço:', 'tripz-paytour'); ?></label>
            </th>
            <td>
                <input type="number" 
                       id="trip_price" 
                       name="trip_price" 
                       value="<?php echo esc_attr($trip_price); ?>" 
                       step="0.01" 
                       min="0" 
                       class="regular-text" />
                <p class="description">
                    <?php _e('Preço base do trip em reais (R$).', 'tripz-paytour'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="trip_duration"><?php _e('Duração:', 'tripz-paytour'); ?></label>
            </th>
            <td>
                <input type="text" 
                       id="trip_duration" 
                       name="trip_duration" 
                       value="<?php echo esc_attr($trip_duration); ?>" 
                       class="regular-text" 
                       placeholder="Ex: 3 dias, 2 horas, etc." />
                <p class="description">
                    <?php _e('Duração estimada do trip.', 'tripz-paytour'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="trip_location"><?php _e('Localização:', 'tripz-paytour'); ?></label>
            </th>
            <td>
                <input type="text" 
                       id="trip_location" 
                       name="trip_location" 
                       value="<?php echo esc_attr($trip_location); ?>" 
                       class="regular-text" 
                       placeholder="Ex: Ilhabela, SP" />
                <p class="description">
                    <?php _e('Localização principal do trip.', 'tripz-paytour'); ?>
                </p>
            </td>
        </tr>
    </table>
    
    <?php if ($paytour_id): ?>
        <div class="tripz-sync-info">
            <h4><?php _e('Informações de Sincronização', 'tripz-paytour'); ?></h4>
            
            <div class="sync-status">
                <span class="status-indicator status-success"></span>
                <span><?php _e('Trip sincronizado com PayTour', 'tripz-paytour'); ?></span>
            </div>
            
            <div class="sync-actions">
                <button type="button" 
                        class="button" 
                        id="sync-single-trip" 
                        data-trip-id="<?php echo esc_attr($post->ID); ?>"
                        data-paytour-id="<?php echo esc_attr($paytour_id); ?>">
                    <?php _e('Sincronizar Agora', 'tripz-paytour'); ?>
                </button>
                
                <button type="button" 
                        class="button" 
                        id="view-paytour-data" 
                        data-trip-id="<?php echo esc_attr($post->ID); ?>">
                    <?php _e('Ver Dados PayTour', 'tripz-paytour'); ?>
                </button>
            </div>
            
            <div id="paytour-data-modal" class="tripz-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e('Dados PayTour', 'tripz-paytour'); ?></h3>
                        <button type="button" class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <pre id="paytour-data-content"></pre>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="tripz-sync-info">
            <div class="sync-status">
                <span class="status-indicator status-warning"></span>
                <span><?php _e('Trip não sincronizado com PayTour', 'tripz-paytour'); ?></span>
            </div>
            
            <p class="description">
                <?php _e('Para sincronizar este trip com PayTour, adicione o ID PayTour acima e salve o post.', 'tripz-paytour'); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.tripz-meta-box .form-table th {
    width: 150px;
    padding: 10px 0;
}

.tripz-meta-box .form-table td {
    padding: 10px 0;
}

.tripz-sync-info {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e1e5e9;
}

.tripz-sync-info h4 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.sync-status {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.status-indicator.status-success {
    background: #46b450;
}

.status-indicator.status-warning {
    background: #ffb900;
}

.status-indicator.status-error {
    background: #dc3232;
}

.sync-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.sync-actions .button {
    font-size: 13px;
}

.tripz-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 6px;
    max-width: 600px;
    max-height: 80vh;
    width: 90%;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e1e5e9;
    background: #f8f9fa;
}

.modal-header h3 {
    margin: 0;
    color: #23282d;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

#paytour-data-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    font-size: 12px;
    line-height: 1.4;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin: 0;
}

.tripz-loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Sincronizar trip individual
    $('#sync-single-trip').on('click', function() {
        var button = $(this);
        var tripId = button.data('trip-id');
        var paytourId = button.data('paytour-id');
        
        button.prop('disabled', true).append('<span class="tripz-loading"></span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tripz_sync_single_trip',
                trip_id: tripId,
                paytour_id: paytourId,
                nonce: '<?php echo wp_create_nonce('tripz_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Trip sincronizado com sucesso!');
                } else {
                    alert('Erro ao sincronizar trip: ' + response.data);
                }
            },
            error: function() {
                alert('Erro na comunicação com o servidor.');
            },
            complete: function() {
                button.prop('disabled', false).find('.tripz-loading').remove();
            }
        });
    });
    
    // Ver dados PayTour
    $('#view-paytour-data').on('click', function() {
        var tripId = $(this).data('trip-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tripz_get_paytour_data',
                trip_id: tripId,
                nonce: '<?php echo wp_create_nonce('tripz_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#paytour-data-content').text(JSON.stringify(response.data, null, 2));
                    $('#paytour-data-modal').show();
                } else {
                    alert('Erro ao carregar dados: ' + response.data);
                }
            },
            error: function() {
                alert('Erro na comunicação com o servidor.');
            }
        });
    });
    
    // Fechar modal
    $('.modal-close, .tripz-modal').on('click', function(e) {
        if (e.target === this) {
            $('#paytour-data-modal').hide();
        }
    });
});
</script>

