/**
 * JavaScript do admin para o plugin Tripz PayTour
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initSyncButtons();
        initConnectionTest();
        initFormValidation();
    });
    
    /**
     * Inicializa botões de sincronização
     */
    function initSyncButtons() {
        $('#sync-trips').on('click', function() {
            var button = $(this);
            var originalText = button.text();
            
            button.prop('disabled', true).text('Sincronizando...');
            showSyncResults('', 'loading');
            
            $.ajax({
                url: tripz_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tripz_sync_trips',
                    nonce: tripz_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showSyncResults(response.data, 'success');
                        // Atualiza estatísticas
                        updateStats();
                    } else {
                        showSyncResults(response.data || 'Erro na sincronização', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showSyncResults('Erro na comunicação com o servidor: ' + error, 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    /**
     * Inicializa teste de conexão
     */
    function initConnectionTest() {
        $('#test-connection').on('click', function() {
            var button = $(this);
            var originalText = button.text();
            
            button.prop('disabled', true).text('Testando...');
            updateConnectionStatus('testing', 'Testando conexão...');
            
            $.ajax({
                url: tripz_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'tripz_test_connection',
                    nonce: tripz_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateConnectionStatus('success', 'Conexão estabelecida com sucesso');
                        showSyncResults('Conexão com PayTour estabelecida com sucesso!', 'success');
                    } else {
                        updateConnectionStatus('error', response.data || 'Falha na conexão');
                        showSyncResults('Erro na conexão: ' + (response.data || 'Verifique suas credenciais'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    updateConnectionStatus('error', 'Erro na comunicação');
                    showSyncResults('Erro na comunicação com o servidor: ' + error, 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    /**
     * Inicializa validação de formulário
     */
    function initFormValidation() {
        // Validação da chave API
        $('#tripz_paytour_api_key').on('blur', function() {
            var apiKey = $(this).val().trim();
            
            if (apiKey && apiKey.length < 10) {
                showFieldError($(this), 'A chave da API parece muito curta');
            } else {
                hideFieldError($(this));
            }
        });
        
        // Validação da URL da API
        $('#tripz_paytour_api_url').on('blur', function() {
            var apiUrl = $(this).val().trim();
            
            if (apiUrl && !isValidUrl(apiUrl)) {
                showFieldError($(this), 'URL inválida');
            } else {
                hideFieldError($(this));
            }
        });
        
        // Validação antes do envio
        $('form').on('submit', function(e) {
            var hasErrors = false;
            
            // Verifica chave API
            var apiKey = $('#tripz_paytour_api_key').val().trim();
            if (!apiKey) {
                showFieldError($('#tripz_paytour_api_key'), 'Chave da API é obrigatória');
                hasErrors = true;
            }
            
            // Verifica URL da API
            var apiUrl = $('#tripz_paytour_api_url').val().trim();
            if (!apiUrl || !isValidUrl(apiUrl)) {
                showFieldError($('#tripz_paytour_api_url'), 'URL da API é obrigatória e deve ser válida');
                hasErrors = true;
            }
            
            if (hasErrors) {
                e.preventDefault();
                showSyncResults('Por favor, corrija os erros no formulário antes de salvar.', 'error');
            }
        });
    }
    
    /**
     * Mostra resultados da sincronização
     */
    function showSyncResults(message, type) {
        var resultsDiv = $('#sync-results');
        var messageDiv = resultsDiv.find('.sync-message');
        
        // Remove classes anteriores
        resultsDiv.removeClass('success error loading');
        
        if (type === 'loading') {
            resultsDiv.addClass('loading');
            messageDiv.html('<div class="sync-spinner"></div> Sincronizando dados...');
        } else {
            resultsDiv.addClass(type);
            messageDiv.text(message);
        }
        
        resultsDiv.show();
        
        // Auto-hide após 5 segundos para mensagens de sucesso
        if (type === 'success') {
            setTimeout(function() {
                resultsDiv.fadeOut();
            }, 5000);
        }
    }
    
    /**
     * Atualiza status da conexão
     */
    function updateConnectionStatus(status, message) {
        var statusDiv = $('#connection-status');
        var indicator = statusDiv.find('.status-indicator');
        var text = statusDiv.find('span:last-child');
        
        // Remove classes anteriores
        indicator.removeClass('status-success status-error status-unknown status-testing');
        
        // Adiciona nova classe
        indicator.addClass('status-' + status);
        
        // Atualiza texto
        text.text(message);
    }
    
    /**
     * Atualiza estatísticas
     */
    function updateStats() {
        $.ajax({
            url: tripz_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tripz_get_stats',
                nonce: tripz_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data;
                    
                    // Atualiza números nas estatísticas
                    $('.stat-item').each(function() {
                        var label = $(this).find('.stat-label').text().toLowerCase();
                        var numberSpan = $(this).find('.stat-number');
                        
                        if (label.includes('total')) {
                            numberSpan.text(stats.total_trips || 0);
                        } else if (label.includes('sincronizados')) {
                            numberSpan.text(stats.synced_trips || 0);
                        } else if (label.includes('locais')) {
                            numberSpan.text(stats.local_trips || 0);
                        }
                    });
                }
            }
        });
    }
    
    /**
     * Mostra erro em campo específico
     */
    function showFieldError(field, message) {
        hideFieldError(field);
        
        var errorDiv = $('<div class="field-error">' + message + '</div>');
        field.after(errorDiv);
        field.addClass('error');
    }
    
    /**
     * Esconde erro de campo específico
     */
    function hideFieldError(field) {
        field.removeClass('error');
        field.next('.field-error').remove();
    }
    
    /**
     * Valida se uma string é uma URL válida
     */
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    /**
     * Adiciona estilos CSS dinamicamente
     */
    function addAdminStyles() {
        var styles = `
            <style>
                .sync-spinner {
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    border: 2px solid #f3f3f3;
                    border-top: 2px solid #0073aa;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-right: 8px;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .tripz-sync-results.loading {
                    background: #e7f3ff;
                    border: 1px solid #b3d9ff;
                    color: #0066cc;
                }
                
                .status-indicator.status-testing {
                    background: #0073aa;
                    animation: pulse 1.5s ease-in-out infinite alternate;
                }
                
                @keyframes pulse {
                    from { opacity: 1; }
                    to { opacity: 0.5; }
                }
                
                .form-table input.error,
                .form-table select.error {
                    border-color: #dc3232;
                    box-shadow: 0 0 2px rgba(220, 50, 50, 0.3);
                }
                
                .field-error {
                    color: #dc3232;
                    font-size: 12px;
                    margin-top: 5px;
                    font-style: italic;
                }
                
                .tripz-admin-container .card {
                    transition: box-shadow 0.3s ease;
                }
                
                .tripz-admin-container .card:hover {
                    box-shadow: 0 2px 8px rgba(0,0,0,.1);
                }
                
                .sync-message {
                    display: flex;
                    align-items: center;
                }
            </style>
        `;
        
        $('head').append(styles);
    }
    
    // Adiciona estilos quando a página carrega
    addAdminStyles();
    
    /**
     * Funcionalidades avançadas para debug
     */
    if (window.location.search.includes('debug=1')) {
        // Adiciona botões de debug
        var debugButtons = `
            <div class="card" style="border-color: #ffb900;">
                <h3>🔧 Debug Tools</h3>
                <button type="button" id="clear-cache" class="button">Limpar Cache</button>
                <button type="button" id="export-logs" class="button">Exportar Logs</button>
                <button type="button" id="reset-plugin" class="button button-secondary">Reset Plugin</button>
            </div>
        `;
        
        $('.tripz-sidebar').append(debugButtons);
        
        // Funcionalidades de debug
        $('#clear-cache').on('click', function() {
            if (confirm('Tem certeza que deseja limpar o cache?')) {
                $.post(tripz_admin_ajax.ajax_url, {
                    action: 'tripz_clear_cache',
                    nonce: tripz_admin_ajax.nonce
                }, function(response) {
                    alert(response.success ? 'Cache limpo!' : 'Erro ao limpar cache');
                });
            }
        });
        
        $('#export-logs').on('click', function() {
            window.open(tripz_admin_ajax.ajax_url + '?action=tripz_export_logs&nonce=' + tripz_admin_ajax.nonce);
        });
        
        $('#reset-plugin').on('click', function() {
            if (confirm('ATENÇÃO: Isso irá resetar todas as configurações do plugin. Continuar?')) {
                $.post(tripz_admin_ajax.ajax_url, {
                    action: 'tripz_reset_plugin',
                    nonce: tripz_admin_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert('Plugin resetado! A página será recarregada.');
                        location.reload();
                    } else {
                        alert('Erro ao resetar plugin');
                    }
                });
            }
        });
    }
    
})(jQuery);

