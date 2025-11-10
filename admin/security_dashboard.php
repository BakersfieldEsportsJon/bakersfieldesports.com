<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/AdminLogger.php';
require_once __DIR__ . '/includes/auth.php';

// Initialize admin logger and monitoring
AdminLogger::init(defined('DEBUG_MODE') ? DEBUG_MODE : false);
require_once __DIR__ . '/../includes/security/monitoring.php';
$monitor = new \Security\SecurityMonitor();

// Verify user is logged in and session is valid
$sessionManager = $GLOBALS['sessionManager'];
if (!$sessionManager) {
    error_log('Session manager not initialized');
    header('Location: /admin/login.php');
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        throw new \Security\Exceptions\SessionNotStartedException('User not logged in');
    }
    
    // Track dashboard access
    $monitor->trackSessionActivity(
        session_id(),
        'dashboard_access',
        'admin/security_dashboard.php',
        true
    );
    
    $_SESSION['last_activity'] = time();
} catch (\Exception $e) {
    error_log('Session validation failed: ' . $e->getMessage());
    if ($sessionManager) {
        $sessionManager->destroy();
    } else {
        session_unset();
        session_destroy();
    }
    header('Location: /admin/login.php');
    exit;
}

// Verify CSRF token on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        AdminLogger::logError('security', 'Invalid CSRF token on security dashboard', [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ]);
        die('Invalid CSRF token');
    }
}

// Log dashboard access
AdminLogger::logLoginAttempt($_SESSION['username'], true, 'Accessed security dashboard');

// Template configuration
$page_title = 'Security Dashboard';
$base_path = '../';
$extra_css = ['../css/styles.css'];
$extra_head_content = '<script>
        // Load Chart.js with fallback
        function loadScript(src, fallback) {
            var script = document.createElement('script');
            script.src = src;
            script.onerror = function() {
                console.log('CDN failed, using local fallback');
                var fallbackScript = document.createElement('script');
                fallbackScript.src = fallback;
                document.head.appendChild(fallbackScript);
            };
            document.head.appendChild(script);
        }
    </script>
    <script>
        loadScript(
            'https://cdn.jsdelivr.net/npm/chart.js',
            'security/assets/js/chart.min.js'
        );
    </script>
    <style>
        .monitoring-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2em;
            margin: 2em 0;
        }

        .monitoring-card {
            background-color: var(--dark-bg);
            padding: 1.5em;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .monitoring-card h3 {
            color: var(--primary-color);
            margin-bottom: 1em;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .stat-value {
            font-size: 2em;
            color: var(--light-color);
            margin: 0.5em 0;
        }

        .stat-label {
            color: #888;
            font-size: 0.9em;
        }

        .location-map {
            width: 100%;
            height: 300px;
            background: var(--darker-bg);
            border-radius: 5px;
            margin-top: 1em;
        }

        .alert-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .alert-item {
            padding: 1em;
            border-radius: 5px;
            margin-bottom: 0.5em;
            background: var(--darker-bg);
            border-left: 3px solid var(--primary-color);
        }

        .alert-item.high {
            border-left-color: #ff4444;
        }

        .alert-item.medium {
            border-left-color: #ffbb33;
        }

        .alert-item.low {
            border-left-color: #00C851;
        }

        .alert-time {
            font-size: 0.8em;
            color: #888;
        }
    </style>';

require_once __DIR__ . '/includes/admin_head.php';
require_once __DIR__ . '/includes/admin_nav.php';
?>
    <div class="dashboard-container">
        <h1>Security Monitoring Dashboard</h1>

        <div class="monitoring-grid">
            <!-- Active Sessions Card -->
            <div class="monitoring-card">
                <h3>Active Sessions</h3>
                <div class="chart-container">
                    <canvas id="sessionsChart"></canvas>
                </div>
            </div>

            <!-- Location Activity Card -->
            <div class="monitoring-card">
                <h3>Location Activity</h3>
                <div class="chart-container">
                    <canvas id="locationChart"></canvas>
                </div>
            </div>

            <!-- Security Alerts Card -->
            <div class="monitoring-card">
                <h3>Recent Security Alerts</h3>
                <ul class="alert-list">
                    <li class="alert-item high">
                        <div class="alert-time">2 minutes ago</div>
                        Multiple failed login attempts detected
                    </li>
                    <li class="alert-item medium">
                        <div class="alert-time">15 minutes ago</div>
                        Suspicious location change detected
                    </li>
                    <li class="alert-item low">
                        <div class="alert-time">1 hour ago</div>
                        New admin session started
                    </li>
                </ul>
            </div>

            <!-- Session Statistics Card -->
            <div class="monitoring-card">
                <h3>Session Statistics</h3>
                <div class="stat-group">
                    <div class="stat-value" id="activeSessionCount">--</div>
                    <div class="stat-label">Active Sessions</div>
                </div>
                <div class="stat-group">
                    <div class="stat-value" id="adminSessionCount">--</div>
                    <div class="stat-label">Admin Sessions</div>
                </div>
                <div class="stat-group">
                    <div class="stat-value" id="averageSessionDuration">--</div>
                    <div class="stat-label">Average Session Duration</div>
                </div>
            </div>
        </div>
    </div>
<?php
$extra_footer_content = '<script>
        // Theme colors from CSS variables
        const colors = {
            background: getComputedStyle(document.documentElement).getPropertyValue('--darker-bg').trim(),
            text: getComputedStyle(document.documentElement).getPropertyValue('--light-color').trim(),
            primary: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim(),
            hover: getComputedStyle(document.documentElement).getPropertyValue('--hover-color').trim()
        };

        // Chart.js global defaults
        Chart.defaults.color = colors.text;
        Chart.defaults.borderColor = colors.background;

        // Sessions Chart
        const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
        new Chart(sessionsCtx, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                datasets: [{
                    label: 'Active Sessions',
                    data: [4, 6, 8, 15, 12, 10],
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: colors.background
                        }
                    },
                    x: {
                        grid: {
                            color: colors.background
                        }
                    }
                }
            }
        });

        // Location Chart
        const locationCtx = document.getElementById('locationChart').getContext('2d');
        new Chart(locationCtx, {
            type: 'doughnut',
            data: {
                labels: ['Local', 'National', 'International'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: [
                        colors.primary,
                        colors.hover,
                        colors.background
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Fetch and update dashboard data
        async function updateDashboard() {
            try {
                const response = await fetch('includes/session_stats.php');
                if (!response.ok) throw new Error('Failed to fetch stats');
                const data = await response.json();

                // Update statistics
                document.getElementById('activeSessionCount').textContent = data.active_sessions;
                document.getElementById('adminSessionCount').textContent = data.admin_sessions;
                document.getElementById('averageSessionDuration').textContent = 
                    data.avg_duration < 60 ? `${data.avg_duration}m` : `${Math.round(data.avg_duration/60)}h`;

                // Update session history chart
                if (data.session_history && data.session_history.length > 0) {
                    sessionsChart.data.labels = data.session_history.map(h => h.hour);
                    sessionsChart.data.datasets[0].data = data.session_history.map(h => h.count);
                    sessionsChart.update();
                }

                // Update location chart
                if (data.location_stats) {
                    locationChart.data.datasets[0].data = [
                        data.location_stats.local,
                        data.location_stats.national,
                        data.location_stats.international
                    ];
                    locationChart.update();
                }

                // Update alerts
                const alertList = document.querySelector('.alert-list');
                if (data.recent_alerts && data.recent_alerts.length > 0) {
                    alertList.innerHTML = data.recent_alerts.map(alert => {
                        const timeAgo = getTimeAgo(new Date(alert.created_at));
                        return `
                            <li class="alert-item ${alert.severity}">
                                <div class="alert-time">${timeAgo}</div>
                                ${alert.event_type.replace(/_/g, ' ')}
                            </li>
                        `;
                    }).join('');
                }

            } catch (error) {
                console.error('Error updating dashboard:', error);
            }
        }

        // Helper function to format time ago
        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            
            let interval = Math.floor(seconds / 31536000);
            if (interval > 1) return interval + ' years ago';
            
            interval = Math.floor(seconds / 2592000);
            if (interval > 1) return interval + ' months ago';
            
            interval = Math.floor(seconds / 86400);
            if (interval > 1) return interval + ' days ago';
            
            interval = Math.floor(seconds / 3600);
            if (interval > 1) return interval + ' hours ago';
            
            interval = Math.floor(seconds / 60);
            if (interval > 1) return interval + ' minutes ago';
            
            return 'just now';
        }

        // Initial update and set refresh interval
        updateDashboard();
        setInterval(updateDashboard, 60000); // Refresh every minute
    </script>';

require_once __DIR__ . '/includes/admin_footer.php';
