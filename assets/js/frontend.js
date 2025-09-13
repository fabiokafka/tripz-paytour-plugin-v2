/**
 * JavaScript do frontend para o plugin Tripz PayTour
 */

(function($) {
    'use strict';
    
    // Inicialização quando o documento estiver pronto
    $(document).ready(function() {
        initAvailabilityForms();
        initDateValidation();
    });
    
    /**
     * Inicializa formulários de verificação de disponibilidade
     */
    function initAvailabilityForms() {
        $('.availability-check-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var container = form.closest('.tripz-availability-form');
            var tripId = container.data('trip-id');
            
            // Validação básica
            if (!validateForm(form)) {
                return;
            }
            
            // Coleta dados do formulário
            var formData = {
                action: 'tripz_check_availability',
                trip_id: tripId,
                checkin: form.find('input[name="checkin"]').val(),
                checkout: form.find('input[name="checkout"]').val(),
                adults: form.find('select[name="adults"]').val(),
                children: form.find('select[name="children"]').val(),
                nonce: tripz_ajax.nonce
            };
            
            // Mostra loading
            showLoading(container);
            
            // Faz requisição AJAX
            $.ajax({
                url: tripz_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    hideLoading(container);
                    
                    if (response.success) {
                        showAvailabilityResults(container, response.data, true);
                    } else {
                        showAvailabilityResults(container, response.data, false);
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading(container);
                    showAvailabilityResults(container, tripz_ajax.error_text, false);
                    console.error('Erro AJAX:', error);
                }
            });
        });
    }
    
    /**
     * Inicializa validação de datas
     */
    function initDateValidation() {
        // Atualiza data mínima do checkout quando checkin muda
        $('input[name="checkin"]').on('change', function() {
            var checkinDate = $(this).val();
            var checkoutInput = $(this).closest('form').find('input[name="checkout"]');
            
            if (checkinDate) {
                var minCheckout = new Date(checkinDate);
                minCheckout.setDate(minCheckout.getDate() + 1);
                
                var minCheckoutStr = minCheckout.toISOString().split('T')[0];
                checkoutInput.attr('min', minCheckoutStr);
                
                // Se checkout é anterior ao novo mínimo, limpa o campo
                if (checkoutInput.val() && checkoutInput.val() <= checkinDate) {
                    checkoutInput.val('');
                }
            }
        });
        
        // Validação em tempo real
        $('input[name="checkout"]').on('change', function() {
            var checkoutDate = $(this).val();
            var checkinDate = $(this).closest('form').find('input[name="checkin"]').val();
            
            if (checkinDate && checkoutDate && checkoutDate <= checkinDate) {
                alert('A data de checkout deve ser posterior à data de checkin.');
                $(this).val('');
            }
        });
    }
    
    /**
     * Valida formulário antes do envio
     */
    function validateForm(form) {
        var checkin = form.find('input[name="checkin"]').val();
        var checkout = form.find('input[name="checkout"]').val();
        var adults = parseInt(form.find('select[name="adults"]').val());
        
        // Verifica se todos os campos obrigatórios estão preenchidos
        if (!checkin || !checkout || !adults) {
            alert('Por favor, preencha todos os campos obrigatórios.');
            return false;
        }
        
        // Verifica se as datas são válidas
        var checkinDate = new Date(checkin);
        var checkoutDate = new Date(checkout);
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (checkinDate < today) {
            alert('A data de checkin não pode ser anterior a hoje.');
            return false;
        }
        
        if (checkoutDate <= checkinDate) {
            alert('A data de checkout deve ser posterior à data de checkin.');
            return false;
        }
        
        // Verifica número de pessoas
        if (adults < 1 || adults > 10) {
            alert('O número de adultos deve estar entre 1 e 10.');
            return false;
        }
        
        return true;
    }
    
    /**
     * Mostra indicador de loading
     */
    function showLoading(container) {
        container.find('.availability-results').hide();
        container.find('.availability-loading').show();
        container.find('.btn-check-availability').prop('disabled', true);
    }
    
    /**
     * Esconde indicador de loading
     */
    function hideLoading(container) {
        container.find('.availability-loading').hide();
        container.find('.btn-check-availability').prop('disabled', false);
    }
    
    /**
     * Mostra resultados da verificação de disponibilidade
     */
    function showAvailabilityResults(container, data, success) {
        var resultsDiv = container.find('.availability-results');
        var resultsContent = resultsDiv.find('.results-content');
        
        // Remove classes anteriores
        resultsDiv.removeClass('success error');
        
        if (success) {
            resultsDiv.addClass('success');
            resultsContent.html(formatSuccessResults(data));
        } else {
            resultsDiv.addClass('error');
            resultsContent.html(formatErrorResults(data));
        }
        
        resultsDiv.show();
        
        // Scroll suave até os resultados
        $('html, body').animate({
            scrollTop: resultsDiv.offset().top - 20
        }, 500);
    }
    
    /**
     * Formata resultados de sucesso
     */
    function formatSuccessResults(data) {
        var html = '<h4>Disponibilidade Confirmada!</h4>';
        
        if (data.options && data.options.length > 0) {
            html += '<div class="availability-details">';
            
            data.options.forEach(function(option) {
                html += '<div class="availability-item">';
                html += '<div class="item-info">';
                html += '<div class="item-name">' + escapeHtml(option.name || 'Opção Padrão') + '</div>';
                if (option.description) {
                    html += '<div class="item-description">' + escapeHtml(option.description) + '</div>';
                }
                html += '</div>';
                html += '<div class="item-details">';
                if (option.price) {
                    html += '<div class="item-price">R$ ' + formatPrice(option.price) + '</div>';
                }
                html += '<div class="item-status available">Disponível</div>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            // Botão de reserva
            html += '<button type="button" class="book-now-button" onclick="initiateBooking()">Reservar Agora</button>';
        } else {
            html += '<p>Trip disponível para as datas selecionadas.</p>';
            if (data.price) {
                html += '<p><strong>Preço:</strong> R$ ' + formatPrice(data.price) + '</p>';
            }
            html += '<button type="button" class="book-now-button" onclick="initiateBooking()">Reservar Agora</button>';
        }
        
        return html;
    }
    
    /**
     * Formata resultados de erro
     */
    function formatErrorResults(data) {
        var message = typeof data === 'string' ? data : 'Não foi possível verificar a disponibilidade.';
        
        var html = '<h4>Ops! Algo deu errado</h4>';
        html += '<p>' + escapeHtml(message) + '</p>';
        html += '<p>Tente novamente com datas diferentes ou entre em contato conosco.</p>';
        
        return html;
    }
    
    /**
     * Formata preço para exibição
     */
    function formatPrice(price) {
        return parseFloat(price).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    /**
     * Escapa HTML para prevenir XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Inicia processo de reserva
     */
    window.initiateBooking = function() {
        // Esta função pode ser customizada para integrar com o sistema de reservas
        // Por padrão, redireciona para uma página de contato ou formulário de reserva
        
        var contactUrl = tripz_ajax.contact_url || '#contact';
        var bookingUrl = tripz_ajax.booking_url || contactUrl;
        
        if (bookingUrl === '#contact') {
            // Se não há URL específica, mostra modal de contato ou rola para seção de contato
            var contactSection = $('#contact, .contact-section, .contact-form');
            if (contactSection.length) {
                $('html, body').animate({
                    scrollTop: contactSection.offset().top - 20
                }, 500);
            } else {
                alert('Entre em contato conosco para finalizar sua reserva!');
            }
        } else {
            window.location.href = bookingUrl;
        }
    };
    
    /**
     * Utilitários para integração com outros plugins
     */
    window.TripzPayTour = {
        // Permite que outros scripts acessem funcionalidades do plugin
        checkAvailability: function(tripId, params) {
            return $.ajax({
                url: tripz_ajax.ajax_url,
                type: 'POST',
                data: $.extend({
                    action: 'tripz_check_availability',
                    trip_id: tripId,
                    nonce: tripz_ajax.nonce
                }, params)
            });
        },
        
        formatPrice: formatPrice,
        escapeHtml: escapeHtml
    };
    
})(jQuery);

