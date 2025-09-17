<?php
/**
 * Dashboard do Labz PayTour Super Plugin v3.0
 * 
 * @package LabzPayTour
 * @since 3.0.0
 * @author Paula - Labz Agency
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
      exit;
}

// Obter estatísticas
$total_trips = wp_count_posts('togo_trip')->publish ?? 0;
$total_bookings = wp_count_posts('togo_booking')->publish ?? 0;
$last_sync = get_option('tripz_paytour_last_sync', 'Nunca');
$api_status = get_option('tripz_paytour_api_status', 'disconnected');
?>

<div class="labz-dashboard">
    <!-- Sidebar -->
    <div class="labz-sidebar">
        <div class="labz-profile">
            <div class="profile-avatar">
                <img src="<?php echo plugin_dir_url(__FILE__) . '../assets/images/labz.png'; ?>" alt="Labz Agency" />
            </div>div>
            <h3 class="profile-name">Labz Agency</h3>h3>
            <p class="profile-role">PayTour Integration v3.0</p>p>
        </div>div>

        <nav class="labz-nav">
            <ul>
                              <li class="nav-item active">
                                                    <a href="#dashboard">
                                                                              <i class="fas fa-chart-area"></i>i>
                                                                              <span>Dashboard</span>span>
                                                    </a>a>
                              </li>li>
                            <li class="nav-item">
                                <a href="#sync">
                                                          <i class="fas fa-sync-alt"></i>i>
                                                          <span>Sincronização</span>span>
                                </a>a>
                            </li>li>
                              <li class="nav-item">
                                                    <a href="#trips">
                                                                              <i class="fas fa-map-marked-alt"></i>i>
                                                                              <span>Trips</span>span>
                                                    </a>a>
                              </li>li>
                              <li class="nav-item">
                                                    <a href="#bookings">
                                                                              <i class="fas fa-calendar-check"></i>i>
                                                                              <span>Reservas</span>span>
                                                    </a>a>
                              </li>li>
                              <li class="nav-item">
                                                    <a href="#settings">
                                                                              <i class="fas fa-cog"></i>i>
                                                                              <span>Configurações</span>span>
                                                    </a>a>
                              </li>li>
            </ul>ul>
        </nav>nav>
    </div>div>

      <!-- Main Content -->
      <div class="labz-main">
                <header class="labz-header">
                              <h1>Dashboard</h1>h1>
                              <div class="header-actions">
                                                <button class="btn-sync" onclick="syncPayTour()">
                                                                      <i class="fas fa-sync-alt"></i>i>
                                                                      Sincronizar
                                                </button>button>
                              </div>div>
                </header>header>

                <!-- Stats Cards -->
                <div class="stats-grid">
                              <div class="stat-card revenue">
                                                <div class="stat-content">
                                                                      <h3>Total de Trips</h3>h3>
                                                                      <div class="stat-number"><?php echo number_format($total_trips); ?></div>
                                                                      <div class="stat-change positive">+12% este mês</div>div>
                                                </div>div>
                                                <div class="stat-icon">
                                                                      <i class="fas fa-map-marked-alt"></i>i>
                                                </div>div>
                              </div>div>

                              <div
                                </span>
            </ul>
