<?php
/**
 * Template do formulário de verificação de disponibilidade
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

$trip_price = get_post_meta($trip_id, 'trip_price', true);
$trip_duration = get_post_meta($trip_id, 'trip_duration', true);
$trip_location = get_post_meta($trip_id, 'trip_location', true);
?>

<div class="tripz-availability-form" data-trip-id="<?php echo esc_attr($trip_id); ?>">
    <div class="tripz-trip-info">
        <?php if ($atts['show_price'] && $trip_price): ?>
            <div class="trip-price">
                <span class="price-label"><?php _e('A partir de:', 'tripz-paytour'); ?></span>
                <span class="price-value">R$ <?php echo number_format($trip_price, 2, ',', '.'); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($atts['show_duration'] && $trip_duration): ?>
            <div class="trip-duration">
                <span class="duration-label"><?php _e('Duração:', 'tripz-paytour'); ?></span>
                <span class="duration-value"><?php echo esc_html($trip_duration); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($trip_location): ?>
            <div class="trip-location">
                <span class="location-label"><?php _e('Local:', 'tripz-paytour'); ?></span>
                <span class="location-value"><?php echo esc_html($trip_location); ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <form class="availability-check-form">
        <div class="form-row">
            <div class="form-group">
                <label for="checkin-<?php echo $trip_id; ?>"><?php _e('Check-in:', 'tripz-paytour'); ?></label>
                <input type="date" 
                       id="checkin-<?php echo $trip_id; ?>" 
                       name="checkin" 
                       required 
                       min="<?php echo date('Y-m-d'); ?>" />
            </div>
            
            <div class="form-group">
                <label for="checkout-<?php echo $trip_id; ?>"><?php _e('Check-out:', 'tripz-paytour'); ?></label>
                <input type="date" 
                       id="checkout-<?php echo $trip_id; ?>" 
                       name="checkout" 
                       required 
                       min="<?php echo date('Y-m-d'); ?>" />
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="adults-<?php echo $trip_id; ?>"><?php _e('Adultos:', 'tripz-paytour'); ?></label>
                <select id="adults-<?php echo $trip_id; ?>" name="adults" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($i, 2); ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="children-<?php echo $trip_id; ?>"><?php _e('Crianças:', 'tripz-paytour'); ?></label>
                <select id="children-<?php echo $trip_id; ?>" name="children">
                    <?php for ($i = 0; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-check-availability">
                <?php echo esc_html($atts['button_text']); ?>
            </button>
        </div>
    </form>
    
    <div class="availability-results" style="display: none;">
        <div class="results-content"></div>
    </div>
    
    <div class="availability-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <div class="loading-text"><?php _e('Verificando disponibilidade...', 'tripz-paytour'); ?></div>
    </div>
</div>

<style>
.tripz-availability-form {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tripz-trip-info {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e1e5e9;
}

.trip-price, .trip-duration, .trip-location {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.price-label, .duration-label, .location-label {
    font-weight: 500;
    color: #666;
}

.price-value {
    font-size: 18px;
    font-weight: bold;
    color: #2c5aa0;
}

.duration-value, .location-value {
    color: #333;
}

.availability-check-form .form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.availability-check-form .form-group {
    flex: 1;
}

.availability-check-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.availability-check-form input,
.availability-check-form select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.availability-check-form input:focus,
.availability-check-form select:focus {
    outline: none;
    border-color: #2c5aa0;
    box-shadow: 0 0 0 2px rgba(44, 90, 160, 0.1);
}

.form-actions {
    text-align: center;
    margin-top: 20px;
}

.btn-check-availability {
    background: #2c5aa0;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-check-availability:hover {
    background: #1e3f73;
}

.btn-check-availability:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.availability-results {
    margin-top: 20px;
    padding: 15px;
    border-radius: 6px;
}

.availability-results.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.availability-results.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.availability-loading {
    text-align: center;
    padding: 20px;
}

.loading-spinner {
    width: 30px;
    height: 30px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #2c5aa0;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    color: #666;
    font-style: italic;
}

.availability-details {
    margin-top: 15px;
}

.availability-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 4px solid #2c5aa0;
}

.availability-item .item-name {
    font-weight: 500;
}

.availability-item .item-price {
    font-weight: bold;
    color: #2c5aa0;
}

.availability-item .item-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.availability-item .item-status.available {
    background: #d4edda;
    color: #155724;
}

.availability-item .item-status.unavailable {
    background: #f8d7da;
    color: #721c24;
}

.book-now-button {
    width: 100%;
    background: #28a745;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 15px;
    transition: background-color 0.3s ease;
}

.book-now-button:hover {
    background: #218838;
}

/* Responsivo */
@media (max-width: 768px) {
    .availability-check-form .form-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .tripz-availability-form {
        padding: 15px;
    }
    
    .trip-price, .trip-duration, .trip-location {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .availability-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

