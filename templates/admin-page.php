<?php
/**
 * Template da página de administração do plugin Tripz PayTour
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Processa formulário se enviado
if (isset($_POST['submit'])) {
    check_admin_referer('tripz_paytour_settings');
    
    update_option('tripz_paytour_app_key', sanitize_text_field($_POST['tripz_paytour_app_key']));
    update_option('tripz_paytour_app_secret', sanitize_text_field($_POST['tripz_paytour_app_secret']));
    update_option('tripz_paytour_api_url', esc_url_raw($_POST['tripz_paytour_api_url']));
    update_option('tripz_paytour_sync_interval', sanitize_text_field($_POST['tripz_paytour_sync_interval']));
    
    // Limpa tokens salvos para forçar nova autenticação
    delete_option('tripz_paytour_access_token');
    delete_option('tripz_paytour_refresh_token');
    delete_option('tripz_paytour_token_expires_at');
    
    echo '<div class="notice notice-success"><p>Configurações salvas com sucesso! Os tokens de acesso foram limpos para forçar nova autenticação.</p></div>';
}

// Obtém configurações atuais
$app_key = get_option('tripz_paytour_app_key', '');
$app_secret = get_option('tripz_paytour_app_secret', '');
$api_url = get_option('tripz_paytour_api_url', 'https://api.paytour.com.br/v2');
$sync_interval = get_option('tripz_paytour_sync_interval', 'daily');

// Verifica status do token
$access_token = get_option('tripz_paytour_access_token', '');
$token_expires_at = get_option('tripz_paytour_token_expires_at', 0);
$token_valid = !empty($access_token) && $token_expires_at > time();

// Estatísticas
global $wpdb;
$trips_count = wp_count_posts('togo_trip')->publish;
$synced_trips = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = 'paytour_id' 
    AND meta_value != ''
");
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="tripz-admin-container">
        <div class="tripz-main-content">
            <div class="card">
                <h2>Configurações da API PayTour</h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('tripz_paytour_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="tripz_paytour_app_key">App Key</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="tripz_paytour_app_key" 
                                       name="tripz_paytour_app_key" 
                                       value="<?php echo esc_attr($app_key); ?>" 
                                       class="regular-text" />
                                <p class="description">
                                    Chave do aplicativo PayTour (app_key). Obtida no painel administrativo da PayTour.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="tripz_paytour_app_secret">App Secret</label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="tripz_paytour_app_secret" 
                                       name="tripz_paytour_app_secret" 
                                       value="<?php echo esc_attr($app_secret); ?>" 
                                       class="regular-text" />
                                <p class="description">
                                    Chave secreta do aplicativo PayTour (app_secret). Mantenha em segurança.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="tripz_paytour_api_url">URL da API</label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="tripz_paytour_api_url" 
                                       name="tripz_paytour_api_url" 
                                       value="<?php echo esc_attr($api_url); ?>" 
                                       class="regular-text" />
                                <p class="description">
                                    URL base da API PayTour. Normalmente não precisa ser alterada.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="tripz_paytour_sync_interval">Intervalo de Sincronização</label>
                            </th>
                            <td>
                                <select id="tripz_paytour_sync_interval" name="tripz_paytour_sync_interval">
                                    <option value="hourly" <?php selected($sync_interval, 'hourly'); ?>>A cada hora</option>
                                    <option value="twicedaily" <?php selected($sync_interval, 'twicedaily'); ?>>Duas vezes por dia</option>
                                    <option value="daily" <?php selected($sync_interval, 'daily'); ?>>Diariamente</option>
                                    <option value="weekly" <?php selected($sync_interval, 'weekly'); ?>>Semanalmente</option>
                                </select>
                                <p class="description">
                                    Frequência da sincronização automática de trips da PayTour.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Salvar Configurações'); ?>
                </form>
            </div>
            
            <div class="card">
                <h2>Sincronização Manual</h2>
                <p>Use os botões abaixo para sincronizar dados manualmente com a PayTour.</p>
                
                <div class="tripz-sync-buttons">
                    <button type="button" id="sync-trips" class="button button-primary">
                        Sincronizar Trips
                    </button>
                    <button type="button" id="test-connection" class="button">
                        Testar Conexão
                    </button>
                </div>
                
                <div id="sync-results" class="tripz-sync-results" style="display: none;">
                    <div class="sync-message"></div>
                </div>
            </div>
        </div>
        
        <div class="tripz-sidebar">
            <div class="card">
                <h3>Estatísticas</h3>
                <div class="tripz-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html($trips_count); ?></span>
                        <span class="stat-label">Total de Trips</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html($synced_trips); ?></span>
                        <span class="stat-label">Trips Sincronizados</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html($trips_count - $synced_trips); ?></span>
                        <span class="stat-label">Trips Locais</span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3>Status da Conexão</h3>
                <div id="connection-status" class="connection-status">
                    <?php if (empty($app_key) || empty($app_secret)): ?>
                        <span class="status-indicator status-error"></span>
                        <span>Credenciais não configuradas</span>
                    <?php else: ?>
                        <span class="status-indicator status-unknown"></span>
                        <span>Clique em "Testar Conexão" para verificar</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h3>Logs Recentes</h3>
                <div class="tripz-logs">
                    <?php
                    $logs = $wpdb->get_results("
                        SELECT * FROM {$wpdb->prefix}tripz_sync_logs 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ");
                    
                    if ($logs): ?>
                        <ul class="log-list">
                            <?php foreach ($logs as $log): ?>
                                <li class="log-item log-<?php echo esc_attr($log->status); ?>">
                                    <div class="log-message"><?php echo esc_html($log->message); ?></div>
                                    <div class="log-time"><?php echo esc_html($log->created_at); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Nenhum log encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h3>Documentação</h3>
                <ul class="tripz-docs">
                    <li><a href="#" target="_blank">Guia de Configuração</a></li>
                    <li><a href="#" target="_blank">API PayTour</a></li>
                    <li><a href="#" target="_blank">Shortcodes Disponíveis</a></li>
                    <li><a href="#" target="_blank">Troubleshooting</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.tripz-admin-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.tripz-main-content {
    flex: 2;
}

.tripz-sidebar {
    flex: 1;
}

.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2, .card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #23282d;
}

.tripz-sync-buttons {
    margin-bottom: 15px;
}

.tripz-sync-buttons .button {
    margin-right: 10px;
}

.tripz-sync-results {
    padding: 10px;
    border-radius: 4px;
    margin-top: 15px;
}

.tripz-sync-results.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.tripz-sync-results.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.tripz-stats {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

.connection-status {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.status-indicator.status-success {
    background: #46b450;
}

.status-indicator.status-error {
    background: #dc3232;
}

.status-indicator.status-unknown {
    background: #ffb900;
}

.log-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.log-item {
    padding: 8px;
    margin-bottom: 5px;
    border-radius: 4px;
    border-left: 4px solid #ddd;
}

.log-item.log-success {
    background: #f0f8f0;
    border-left-color: #46b450;
}

.log-item.log-error {
    background: #fdf2f2;
    border-left-color: #dc3232;
}

.log-message {
    font-size: 13px;
    margin-bottom: 3px;
}

.log-time {
    font-size: 11px;
    color: #666;
}

.tripz-docs {
    list-style: none;
    margin: 0;
    padding: 0;
}

.tripz-docs li {
    margin-bottom: 8px;
}

.tripz-docs a {
    text-decoration: none;
    color: #0073aa;
}

.tripz-docs a:hover {
    text-decoration: underline;
}

@media (max-width: 782px) {
    .tripz-admin-container {
        flex-direction: column;
    }
    
    .tripz-stats {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .stat-item {
        flex: 1;
        min-width: 120px;
    }
}
</style>


            
            <?php if (!empty($app_key) && !empty($app_secret)): ?>
            <div class="card">
                <h3>Informações de Autenticação</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Status do Token:</th>
                        <td>
                            <?php if ($token_valid): ?>
                                <span class="status-indicator status-success"></span>
                                <span style="color: #46b450;">Token válido até <?php echo date('d/m/Y H:i:s', $token_expires_at); ?></span>
                            <?php else: ?>
                                <span class="status-indicator status-warning"></span>
                                <span style="color: #ffb900;">Token expirado ou não existe</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Método de Autenticação:</th>
                        <td>
                            <code>app_key:app_secret</code> → Base64 → Bearer Token
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Endpoint de Login:</th>
                        <td>
                            <code><?php echo esc_html($api_url); ?>/lojas/login?grant_type=application</code>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h3>Configuração PayTour</h3>
                <div class="paytour-config-info">
                    <h4>Como obter as credenciais:</h4>
                    <ol>
                        <li>Acesse o painel administrativo da PayTour</li>
                        <li>Vá para a seção "Desenvolvedor" ou "Integrações"</li>
                        <li>Gere ou visualize suas credenciais de aplicativo</li>
                        <li>Copie o <strong>app_key</strong> e <strong>app_secret</strong></li>
                    </ol>
                    
                    <h4>Fluxo de Autenticação:</h4>
                    <ol>
                        <li>Concatena <code>app_key:app_secret</code></li>
                        <li>Codifica em Base64</li>
                        <li>Envia como <code>Authorization: Basic [base64]</code></li>
                        <li>Recebe <code>access_token</code> e <code>refresh_token</code></li>
                        <li>Usa <code>Bearer [access_token]</code> nas chamadas</li>
                    </ol>
                </div>
            </div>



.status-indicator.status-warning {
    background: #ffb900;
}

.paytour-config-info h4 {
    margin-top: 15px;
    margin-bottom: 8px;
    color: #23282d;
}

.paytour-config-info ol {
    margin-left: 20px;
}

.paytour-config-info li {
    margin-bottom: 5px;
    font-size: 13px;
}

.paytour-config-info code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 12px;
}

