<?php
/**
 * Plugin Name: Tripz - Integração PayTour com Togo Framework
 * Plugin URI: https://maremar.tur.br
 * Description: Plugin de integração entre PayTour API e Togo Framework para sincronização de viagens e reservas
 * Version: 1.3.0
 * Author: Maremar Turismo
 * Author URI: https://maremar.tur.br
 * Text Domain: tripz-paytour
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Previne acesso direto
if (!defined("ABSPATH")) {
    exit;
}

// Define constantes do plugin
define("TRIPZ_PAYTOUR_VERSION", "1.3.0");
define("TRIPZ_PAYTOUR_PLUGIN_FILE", __FILE__);
define("TRIPZ_PAYTOUR_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("TRIPZ_PAYTOUR_PLUGIN_URL", plugin_dir_url(__FILE__));
define("TRIPZ_PAYTOUR_PLUGIN_BASENAME", plugin_basename(__FILE__));

/**
 * Classe principal do plugin
 */
class Tripz_PayTour_Integration {
    
    /**
     * Instância única do plugin
     */
    private static $instance = null;
    
    /**
     * Configurações da API PayTour
     */
    private $api_url = "https://api.paytour.com.br/v2";
    private $app_key;
    private $app_secret;
    private $access_token;
    private $refresh_token;
    private $token_expires_at;
    
    /**
     * Construtor
     */
    private function __construct() {
        $this->app_key = get_option("tripz_paytour_app_key", "");
        $this->app_secret = get_option("tripz_paytour_app_secret", "");
        $this->access_token = get_option("tripz_paytour_access_token", "");
        $this->refresh_token = get_option("tripz_paytour_refresh_token", "");
        $this->token_expires_at = get_option("tripz_paytour_token_expires_at", 0);
        
        $this->init_hooks();
    }
    
    /**
     * Retorna instância única do plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializa hooks do WordPress
     */
    private function init_hooks() {
        // Hooks de ativação/desativação
        register_activation_hook(__FILE__, array($this, "activate"));
        register_deactivation_hook(__FILE__, array($this, "deactivate"));
        
        // Hooks de inicialização
        add_action("init", array($this, "init"));
        add_action("admin_init", array($this, "admin_init"));
        add_action("admin_menu", array($this, "admin_menu"));
        
        // Hooks AJAX
        add_action("wp_ajax_tripz_check_availability", array($this, "ajax_check_availability"));
        add_action("wp_ajax_nopriv_tripz_check_availability", array($this, "ajax_check_availability"));
        add_action("wp_ajax_tripz_sync_trips", array($this, "ajax_sync_trips"));
        add_action("wp_ajax_tripz_test_connection", array($this, "ajax_test_connection"));
        
        // Hooks do Togo Framework
        add_action("togo_framework_booking_processed", array($this, "process_booking_to_paytour"), 10, 2);
        add_action("togo_framework_booking_status_changed", array($this, "update_booking_status"), 10, 3);
        
        // Shortcodes
        add_shortcode("tripz_trip_availability", array($this, "shortcode_trip_availability"));
        
        // Scripts e estilos
        add_action("wp_enqueue_scripts", array($this, "enqueue_scripts"));
        add_action("admin_enqueue_scripts", array($this, "admin_enqueue_scripts"));
        
        // Cron jobs
        add_action("tripz_daily_sync", array($this, "daily_sync_trips"));
        
        // Meta boxes
        add_action("add_meta_boxes", array($this, "add_meta_boxes"));
        add_action("save_post", array($this, "save_meta_boxes"));
    }
    
    /**
     * Ativação do plugin
     */
    public function activate() {
        // Verifica se o Togo Framework está ativo
        if (!class_exists("Togo_Framework\\Setup")) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die("Este plugin requer o Togo Framework para funcionar.");
        }
        
        // Agenda sincronização diária
        if (!wp_next_scheduled("tripz_daily_sync")) {
            wp_schedule_event(time(), "daily", "tripz_daily_sync");
        }
        
        // Cria tabelas personalizadas se necessário
        $this->create_tables();
        
        // Define versão do plugin
        update_option("tripz_paytour_version", TRIPZ_PAYTOUR_VERSION);
    }
    
    /**
     * Desativação do plugin
     */
    public function deactivate() {
        // Remove cron jobs
        wp_clear_scheduled_hook("tripz_daily_sync");
    }
    
    /**
     * Inicialização do plugin
     */
    public function init() {
        // Carrega textdomain
        load_plugin_textdomain("tripz-paytour", false, dirname(plugin_basename(__FILE__)) . "/languages");
    }
    
    /**
     * Inicialização do admin
     */
    public function admin_init() {
        // Registra configurações
        register_setting("tripz_paytour_settings", "tripz_paytour_app_key");
        register_setting("tripz_paytour_settings", "tripz_paytour_app_secret");
        register_setting("tripz_paytour_settings", "tripz_paytour_api_url");
        register_setting("tripz_paytour_settings", "tripz_paytour_sync_interval");
    }
    
    /**
     * Adiciona menu no admin
     */
    public function admin_menu() {
        add_options_page(
            "Tripz PayTour",
            "Tripz PayTour",
            "manage_options",
            "tripz-paytour",
            array($this, "admin_page")
        );
    }
    
    /**
     * Página de configurações do admin
     */
    public function admin_page() {
        include TRIPZ_PAYTOUR_PLUGIN_DIR . "templates/admin-page.php";
    }
    
    /**
     * Enfileira scripts do frontend
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            "tripz-paytour-frontend",
            TRIPZ_PAYTOUR_PLUGIN_URL . "assets/js/frontend.js",
            array("jquery"),
            TRIPZ_PAYTOUR_VERSION,
            true
        );
        
        wp_localize_script("tripz-paytour-frontend", "tripz_ajax", array(
            "ajax_url" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("tripz_nonce"),
            "loading_text" => __("Verificando disponibilidade...", "tripz-paytour"),
            "error_text" => __("Erro ao verificar disponibilidade", "tripz-paytour")
        ));
        
        wp_enqueue_style(
            "tripz-paytour-frontend",
            TRIPZ_PAYTOUR_PLUGIN_URL . "assets/css/frontend.css",
            array(),
            TRIPZ_PAYTOUR_VERSION
        );
    }
    
    /**
     * Enfileira scripts do admin
     */
    public function admin_enqueue_scripts($hook) {
        if ("settings_page_tripz-paytour" !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            "tripz-paytour-admin",
            TRIPZ_PAYTOUR_PLUGIN_URL . "assets/js/admin.js",
            array("jquery"),
            TRIPZ_PAYTOUR_VERSION,
            true
        );
        
        wp_localize_script("tripz-paytour-admin", "tripz_admin_ajax", array(
            "ajax_url" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("tripz_admin_nonce")
        ));
    }
    
    /**
     * Autentica com a API PayTour usando app_key e app_secret
     */
    private function authenticate() {
        if (empty($this->app_key) || empty($this->app_secret)) {
            return false;
        }
        
        // Verifica se o token ainda é válido
        if ($this->access_token && $this->token_expires_at > time()) {
            return true;
        }
        
        // Tenta renovar o token se temos refresh_token
        if ($this->refresh_token && $this->token_expires_at > 0) {
            if ($this->refresh_access_token()) {
                return true;
            }
        }
        
        // Faz login com credenciais de aplicativo
        return $this->login_with_app_credentials();
    }
    
    /**
     * Faz login com credenciais de aplicativo
     */
    private function login_with_app_credentials() {
        // Concatena app_key e app_secret separados por dois pontos
        $credentials = $this->app_key . ":" . $this->app_secret;
        
        // Codifica em base64
        $encoded_credentials = base64_encode($credentials);
        
        $url = $this->api_url . "/lojas/login?grant_type=application";
        
        $args = array(
            "method" => "POST",
            "headers" => array(
                "Authorization" => "Basic " . $encoded_credentials,
                "Content-Type" => "application/json",
                "Accept" => "application/json"
            ),
            "timeout" => 30
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log_error("Authentication Error", $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error("JSON Decode Error", "Invalid JSON response during authentication");
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            $error_message = isset($data["message"]) ? $data["message"] : "Authentication failed";
            $this->log_error("Authentication Failed", "Status: {$status_code}, Message: {$error_message}");
            return false;
        }
        
        if (!isset($data["access_token"])) {
            $this->log_error("Authentication Error", "No access token in response");
            return false;
        }
        
        // Salva tokens
        $this->access_token = $data["access_token"];
        $this->refresh_token = isset($data["refresh_token"]) ? $data["refresh_token"] : "";
        $this->token_expires_at = time() + (isset($data["expires_in"]) ? intval($data["expires_in"]) : 3600);
        
        // Persiste no banco de dados
        update_option("tripz_paytour_access_token", $this->access_token);
        update_option("tripz_paytour_refresh_token", $this->refresh_token);
        update_option("tripz_paytour_token_expires_at", $this->token_expires_at);
        
        return true;
    }
    
    /**
     * Renova o access token usando refresh token
     */
    private function refresh_access_token() {
        if (empty($this->refresh_token)) {
            return false;
        }
        
        $url = $this->api_url . "/lojas/login?grant_type=refresh_token";
        
        $args = array(
            "method" => "POST",
            "headers" => array(
                "Authorization" => "Bearer " . $this->refresh_token,
                "Content-Type" => "application/json",
                "Accept" => "application/json"
            ),
            "body" => json_encode(array(
                "refresh_token" => $this->refresh_token
            )),
            "timeout" => 30
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log_error("Token Refresh Error", $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error("JSON Decode Error", "Invalid JSON response during token refresh");
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            // Se refresh falhou, limpa tokens para forçar novo login
            $this->clear_tokens();
            return false;
        }
        
        if (!isset($data["access_token"])) {
            $this->log_error("Token Refresh Error", "No access token in refresh response");
            return false;
        }
        
        // Atualiza tokens
        $this->access_token = $data["access_token"];
        if (isset($data["refresh_token"])) {
            $this->refresh_token = $data["refresh_token"];
        }
        $this->token_expires_at = time() + (isset($data["expires_in"]) ? intval($data["expires_in"]) : 3600);
        
        // Persiste no banco de dados
        update_option("tripz_paytour_access_token", $this->access_token);
        update_option("tripz_paytour_refresh_token", $this->refresh_token);
        update_option("tripz_paytour_token_expires_at", $this->token_expires_at);
        
        return true;
    }
    
    /**
     * Limpa tokens salvos
     */
    private function clear_tokens() {
        $this->access_token = "";
        $this->refresh_token = "";
        $this->token_expires_at = 0;
        
        delete_option("tripz_paytour_access_token");
        delete_option("tripz_paytour_refresh_token");
        delete_option("tripz_paytour_token_expires_at");
    }
    
    /**
     * Faz chamada para API PayTour
     */
    private function api_call($endpoint, $data = array(), $method = "GET") {
        // Autentica antes de fazer a chamada
        if (!$this->authenticate()) {
            return array(
                "success" => false,
                "message" => "Falha na autenticação com PayTour"
            );
        }
        
        $url = $this->api_url . $endpoint;
        
        $args = array(
            "method" => $method,
            "headers" => array(
                "Authorization" => "Bearer " . $this->access_token,
                "Content-Type" => "application/json",
                "Accept" => "application/json"
            ),
            "timeout" => 30
        );
        
        if (!empty($data) && in_array($method, array("POST", "PUT", "PATCH"))) {
            $args["body"] = json_encode($data);
        } elseif (!empty($data) && $method === "GET") {
            $url = add_query_arg($data, $url);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log_error("API Call Error", $response->get_error_message());
            return array(
                "success" => false,
                "message" => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error("JSON Decode Error", "Invalid JSON response from API");
            return array(
                "success" => false,
                "message" => "Resposta inválida da API"
            );
        }
        
        // Se token expirou, tenta renovar e refaz a chamada
        if ($status_code === 401) {
            $this->clear_tokens();
            if ($this->authenticate()) {
                // Atualiza header com novo token
                $args["headers"]["Authorization"] = "Bearer " . $this->access_token;
                $response = wp_remote_request($url, $args);
                
                if (!is_wp_error($response)) {
                    $status_code = wp_remote_retrieve_response_code($response);
                    $body = wp_remote_retrieve_body($response);
                    $decoded = json_decode($body, true);
                }
            }
        }
        
        if ($status_code >= 200 && $status_code < 300) {
            return array(
                "success" => true,
                "data" => $decoded
            );
        } else {
            $error_message = isset($decoded["message"]) ? $decoded["message"] : "Erro na API PayTour";
            $this->log_error("API Error", "Status: {$status_code}, Message: {$error_message}");
            return array(
                "success" => false,
                "message" => $error_message
            );
        }
    }
    
    /**
     * AJAX: Testar conexão
     */
    public function ajax_test_connection() {
        check_ajax_referer("tripz_admin_nonce", "nonce");
        
        if (!current_user_can("manage_options")) {
            wp_send_json_error("Permissão negada");
        }
        
        // Limpa tokens para forçar nova autenticação
        $this->clear_tokens();
        
        // Tenta autenticar
        if ($this->authenticate()) {
            // Faz uma chamada de teste para verificar se a API está funcionando
            $response = $this->api_call("/passeios");
            
            if ($response["success"]) {
                wp_send_json_success("Conexão estabelecida com sucesso!");
            } else {
                wp_send_json_error("Autenticação OK, mas erro na API: " . $response["message"]);
            }
        } else {
            wp_send_json_error("Falha na autenticação. Verifique suas credenciais.");
        }
    }
    
    /**
     * Shortcode para verificação de disponibilidade
     */
    public function shortcode_trip_availability($atts) {
        $atts = shortcode_atts(array(
            "id" => 0,
            "show_price" => true,
            "show_duration" => true,
            "button_text" => __("Verificar Disponibilidade", "tripz-paytour")
        ), $atts);
        
        $trip_id = intval($atts["id"]);
        if (!$trip_id) {
            return "";
        }
        
        // Verifica se é um togo_trip com paytour_id
        $paytour_id = get_post_meta($trip_id, "paytour_id", true);
        if (!$paytour_id) {
            return "";
        }
        
        ob_start();
        include TRIPZ_PAYTOUR_PLUGIN_DIR . "templates/availability-form.php";
        return ob_get_clean();
    }
    
    /**
     * AJAX: Verificar disponibilidade
     */
    public function ajax_check_availability() {
        check_ajax_referer("tripz_nonce", "nonce");
        
        $trip_id = intval($_POST["trip_id"]);
        $checkin = sanitize_text_field($_POST["checkin"]);
        $checkout = sanitize_text_field($_POST["checkout"]);
        $adults = intval($_POST["adults"]);
        $children = intval($_POST["children"]);
        
        $paytour_id = get_post_meta($trip_id, "paytour_id", true);
        if (!$paytour_id) {
            wp_send_json_error("Trip não encontrado na PayTour");
        }
        
        $response = $this->api_call("/passeios/" . $paytour_id, array(
            "data_de" => $checkin,
            "data_ate" => $checkout,
            "minimalResponse" => 0 // Obter todos os detalhes para verificar disponibilidade
        ));

        // Processar a resposta para verificar a disponibilidade real
        if ($response["success"] && isset($response["data"]["_disponibilidade"])) {
            $available_options = array();
            foreach ($response["data"]["_disponibilidade"] as $disponibilidade) {
                // Lógica para verificar se a disponibilidade corresponde aos critérios de adultos/crianças
                // Por simplicidade, vamos apenas retornar as opções disponíveis
                $available_options[] = array(
                    "name" => $disponibilidade["nome"] ?? "Opção",
                    "description" => $disponibilidade["descricao"] ?? "",
                    "price" => $disponibilidade["preco"] ?? 0,
                    "date" => $disponibilidade["data"] ?? ""
                );
            }
            if (!empty($available_options)) {
                wp_send_json_success(array("available" => true, "options" => $available_options));
            } else {
                wp_send_json_error("Nenhuma disponibilidade encontrada para as datas e critérios selecionados.");
            }
        } else if ($response["success"]) {
            wp_send_json_error("Nenhuma informação de disponibilidade encontrada para este passeio.");
        } else {
            wp_send_json_error($response["message"]);
        }
    }
    
    /**
     * AJAX: Sincronizar trips
     */
    public function ajax_sync_trips() {
        check_ajax_referer("tripz_admin_nonce", "nonce");
        
        if (!current_user_can("manage_options")) {
            wp_send_json_error("Permissão negada");
        }
        
        $result = $this->sync_trips_from_paytour();
        
        if ($result["success"]) {
            wp_send_json_success($result["message"]);
        } else {
            wp_send_json_error($result["message"]);
        }
    }
    
    /**
     * Sincroniza trips da PayTour
     */
    public function sync_trips_from_paytour() {
        $response = $this->api_call("/passeios", array("minimalResponse" => 0));
        
        if (!$response["success"]) {
            $this->log_error("Sync Trips API Error", "Response: " . json_encode($response));
            return array(
                "success" => false,
                "message" => "Erro ao conectar com a API PayTour: " . $response["message"]
            );
        }
        
        $trips = $response["data"];
        $synced = 0;
        $errors = 0;
        
        if (is_array($trips)) {
            foreach ($trips as $trip_data) {
                if ($this->sync_single_trip($trip_data)) {
                    $synced++;
                } else {
                    $errors++;
                }
            }
        }
        
        return array(
            "success" => true,
            "message" => sprintf(
                "Sincronização concluída: %d trips sincronizados, %d erros",
                $synced,
                $errors
            )
        );
    }
    
    /**
     * Sincroniza um trip individual
     */
    private function sync_single_trip($trip_data) {
        if (!isset($trip_data["id"])) {
            return false;
        }
        
        // Verifica se já existe um post com este paytour_id
        $existing_posts = get_posts(array(
            "post_type" => "togo_trip",
            "meta_key" => "paytour_id",
            "meta_value" => $trip_data["id"],
            "posts_per_page" => 1
        ));
        
        $post_data = array(
            "post_title" => sanitize_text_field($trip_data["name"] ?? "Trip sem nome"),
            "post_content" => wp_kses_post($trip_data["description"] ?? ""),
            "post_status" => "publish",
            "post_type" => "togo_trip"
        );
        
        if (!empty($existing_posts)) {
            // Atualiza post existente
            $post_data["ID"] = $existing_posts[0]->ID;
            $post_id = wp_update_post($post_data);
        } else {
            // Cria novo post
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Atualiza meta fields
        update_post_meta($post_id, "paytour_id", $trip_data["id"]);
        update_post_meta($post_id, "trip_price", floatval($trip_data["price"] ?? 0));
        update_post_meta($post_id, "trip_duration", sanitize_text_field($trip_data["duration"] ?? ""));
        update_post_meta($post_id, "trip_location", sanitize_text_field($trip_data["location"] ?? ""));
        update_post_meta($post_id, "paytour_data", json_encode($trip_data));
        
        // Sincroniza imagens se disponíveis
        if (!empty($trip_data["images"])) {
            $this->sync_trip_images($post_id, $trip_data["images"]);
        }
        
        return true;
    }
    
    /**
     * Sincroniza imagens do trip
     */
    private function sync_trip_images($post_id, $images) {
        if (empty($images) || !is_array($images)) {
            return;
        }
        
        $featured_set = false;
        
        foreach ($images as $image_url) {
            $attachment_id = $this->upload_image_from_url($image_url, $post_id);
            
            if ($attachment_id && !$featured_set) {
                set_post_thumbnail($post_id, $attachment_id);
                $featured_set = true;
            }
        }
    }
    
    /**
     * Faz upload de imagem a partir de URL
     */
    private function upload_image_from_url($image_url, $post_id) {
        require_once(ABSPATH . "wp-admin/includes/media.php");
        require_once(ABSPATH . "wp-admin/includes/file.php");
        require_once(ABSPATH . "wp-admin/includes/image.php");
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = array(
            "name" => basename($image_url),
            "tmp_name" => $tmp
        );
        
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return false;
        }
        
        return $attachment_id;
    }
    
    /**
     * Processa reserva para PayTour
     */
    public function process_booking_to_paytour($booking_id, $booking_data) {
        $trip_id = get_post_meta($booking_id, "trip_id", true);
        $paytour_id = get_post_meta($trip_id, "paytour_id", true);
        
        if (!$paytour_id) {
            return;
        }
        
        $payload = array(
            "trip_id" => $paytour_id,
            "checkin" => get_post_meta($booking_id, "checkin_date", true),
            "checkout" => get_post_meta($booking_id, "checkout_date", true),
            "adults" => intval(get_post_meta($booking_id, "adults", true)),
            "children" => intval(get_post_meta($booking_id, "children", true)),
            "customer" => array(
                "name" => get_post_meta($booking_id, "client_name", true),
                "email" => get_post_meta($booking_id, "client_email", true),
                "phone" => get_post_meta($booking_id, "client_phone", true)
            ),
            "total_price" => floatval(get_post_meta($booking_id, "total_price", true))
        );
        
        // Aplica filtro para permitir customização
        $payload = apply_filters("tripz_paytour_order_data", $payload, $booking_id);
        
        $response = $this->api_call("/bookings", $payload, "POST");
        
        if ($response["success"]) {
            update_post_meta($booking_id, "paytour_order_id", $response["data"]["order_id"]);
            update_post_meta($booking_id, "booking_status", "confirmed");
        }
    }
    
    /**
     * Atualiza status da reserva
     */
    public function update_booking_status($booking_id, $old_status, $new_status) {
        $paytour_order_id = get_post_meta($booking_id, "paytour_order_id", true);
        
        if (!$paytour_order_id) {
            return;
        }
        
        // Mapeia status do Togo Framework para PayTour
        $status_mapping = apply_filters("tripz_paytour_status_mapping", array(
            "pending" => "pending",
            "confirmed" => "approved",
            "cancelled" => "cancelled",
            "completed" => "approved"
        ));
        
        $paytour_status = $status_mapping[$new_status] ?? $new_status;
        
        $this->api_call("/bookings/" . $paytour_order_id . "/status", array(
            "status" => $paytour_status
        ), "PUT");
    }
    
    /**
     * Sincronização diária automática
     */
    public function daily_sync_trips() {
        $this->sync_trips_from_paytour();
    }
    
    /**
     * Adiciona meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            "tripz_paytour_meta",
            "PayTour Integration",
            array($this, "meta_box_callback"),
            "togo_trip",
            "side",
            "default"
        );
    }
    
    /**
     * Callback do meta box
     */
    public function meta_box_callback($post) {
        wp_nonce_field("tripz_paytour_meta_nonce", "tripz_paytour_meta_nonce");
        
        $paytour_id = get_post_meta($post->ID, "paytour_id", true);
        $trip_price = get_post_meta($post->ID, "trip_price", true);
        $trip_duration = get_post_meta($post->ID, "trip_duration", true);
        $trip_location = get_post_meta($post->ID, "trip_location", true);
        
        include TRIPZ_PAYTOUR_PLUGIN_DIR . "templates/meta-box.php";
    }
    
    /**
     * Salva meta boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST["tripz_paytour_meta_nonce"]) || 
            !wp_verify_nonce($_POST["tripz_paytour_meta_nonce"], "tripz_paytour_meta_nonce")) {
            return;
        }
        
        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can("edit_post", $post_id)) {
            return;
        }
        
        if (isset($_POST["paytour_id"])) {
            update_post_meta($post_id, "paytour_id", sanitize_text_field($_POST["paytour_id"]));
        }
        
        if (isset($_POST["trip_price"])) {
            update_post_meta($post_id, "trip_price", floatval($_POST["trip_price"]));
        }
        
        if (isset($_POST["trip_duration"])) {
            update_post_meta($post_id, "trip_duration", sanitize_text_field($_POST["trip_duration"]));
        }
        
        if (isset($_POST["trip_location"])) {
            update_post_meta($post_id, "trip_location", sanitize_text_field($_POST["trip_location"]));
        }
    }
    
    /**
     * Cria tabelas personalizadas
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabela para logs de sincronização
        $table_name = $wpdb->prefix . "tripz_sync_logs";
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sync_type varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . "wp-admin/includes/upgrade.php");
        dbDelta($sql);
    }
    
    /**
     * Registra erro no log
     */
    private function log_error($type, $message) {
        if (defined("WP_DEBUG") && WP_DEBUG) {
            error_log("Tripz PayTour [{$type}]: {$message}");
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . "tripz_sync_logs";
        
        $wpdb->insert(
            $table_name,
            array(
                "sync_type" => $type,
                "status" => "error",
                "message" => $message,
                "created_at" => current_time("mysql")
            )
        );
    }
}

// Inicializa o plugin
function tripz_paytour_init() {
    return Tripz_PayTour_Integration::get_instance();
}

// Hook para inicializar após todos os plugins carregarem
add_action("plugins_loaded", "tripz_paytour_init");

