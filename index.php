<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Network Monitoring Dashboard</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- HERO / LANDING -->
    <section class="hero">
        <!-- Decorative background shapes to stop the page feeling flat -->
        <div class="hero-blur hero-blur-one"></div>
        <div class="hero-blur hero-blur-two"></div>
        <div class="hero-grid-overlay"></div>

        <nav class="navbar">
            <div class="nav-shell">
                <div class="logo">SNM<span>Dashboard</span></div>

                <div class="nav-links">
                    <a href="index.php" class="active-link">Home</a>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="devices.php">Devices</a>
                    <a href="logs.php">Logs</a>
                    <a href="login.php" class="nav-button">Admin Login</a>
                </div>
            </div>
        </nav>

        <div class="hero-content">
            <div class="hero-layout">
                
                <!-- LEFT SIDE: real copy, CTA, trust points -->
                <div class="hero-copy">
                    <div class="hero-tag">Independent Network Engineering Project</div>

                    <h1>
                        Monitor your <span>network devices</span><br>
                        securely and in real time
                    </h1>

                    <h2>Private monitoring built for approved infrastructure</h2>

                    <p>
                        A secure, database-driven monitoring platform designed to track trusted devices,
                        record status history, and present network health through a clean, controlled web interface.
                    </p>

                    <div class="hero-buttons">
                        <a href="login.php" class="btn-primary">Open Admin Panel</a>
                        <a href="dashboard.php" class="btn-secondary">View Dashboard</a>
                    </div>

                    <!-- Small trust / feature bullets under CTA -->
                    <div class="hero-points">
                        <div class="hero-point">
                            <span class="point-icon"></span>
                            <span>Approved devices only</span>
                        </div>
                        <div class="hero-point">
                            <span class="point-icon"></span>
                            <span>Status history logging</span>
                        </div>
                        <div class="hero-point">
                            <span class="point-icon"></span>
                            <span>Protected admin access</span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: dashboard preview -->
                <div class="hero-visual">
                    <div class="preview-panel">
                        <div class="preview-top">
                            <div class="dot"></div>
                            <div class="dot"></div>
                            <div class="dot"></div>
                        </div>

                        <div class="preview-grid">
                            <div class="card overview-card">
                                <h3>System Overview</h3>
                                <div class="big-number">12 Devices</div>
                                <p class="card-text">
                                    Approved hosts monitored through a controlled backend collector.
                                </p>

                                <div class="mini-metrics">
                                    <div>
                                        <span class="mini-label">Healthy</span>
                                        <strong>9</strong>
                                    </div>
                                    <div>
                                        <span class="mini-label">Offline</span>
                                        <strong>3</strong>
                                    </div>
                                </div>

                                <div class="latency-bar">
                                    <div class="latency-fill"></div>
                                </div>
                            </div>

                            <div class="card">
                                <h3>Live Status</h3>
                                <div class="status-row">
                                    <span>Core Router</span>
                                    <span class="badge online">Online</span>
                                </div>
                                <div class="status-row">
                                    <span>Web Server</span>
                                    <span class="badge online">Online</span>
                                </div>
                                <div class="status-row">
                                    <span>Test Host</span>
                                    <span class="badge offline">Offline</span>
                                </div>
                            </div>

                            <div class="card">
                                <h3>Latest Checks</h3>
                                <div class="status-row">
                                    <span>Latency</span>
                                    <strong>21 ms</strong>
                                </div>
                                <div class="status-row">
                                    <span>Last Scan</span>
                                    <strong>14:32</strong>
                                </div>
                                <div class="status-row">
                                    <span>Collector</span>
                                    <strong class="collector-live">Active</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Floating quick card for more visual life -->
                        <div class="floating-card">
                            <p>Uptime</p>
                            <strong>99.98%</strong>
                            <span>Last 30 days</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- QUICK STATS STRIP -->
    <section class="stats-strip">
        <div class="section-shell stats-grid">
            <div class="stat-box">
                <span class="stat-label">Approved Devices</span>
                <strong>12</strong>
                <p>Only trusted devices are tracked by the platform.</p>
            </div>

            <div class="stat-box">
                <span class="stat-label">Current Uptime</span>
                <strong>99.98%</strong>
                <p>Consistent visibility across your monitored environment.</p>
            </div>

            <div class="stat-box">
                <span class="stat-label">Average Latency</span>
                <strong>21 ms</strong>
                <p>Quick performance snapshots for recent checks.</p>
            </div>

            <div class="stat-box">
                <span class="stat-label">Logged Events</span>
                <strong>1,204</strong>
                <p>Historical activity available for troubleshooting.</p>
            </div>
        </div>
    </section>

    <!-- FEATURE SECTION -->
    <section class="features">
        <div class="section-shell">
            <div class="section-title">
                <h2>Built for a clean, safe monitoring workflow</h2>
                <p>
                    The platform separates the dashboard from the backend collector so monitoring actions
                    stay controlled, approved, and secure by design.
                </p>
            </div>

            <div class="feature-grid">
                <div class="feature-box">
                    <div class="feature-icon">🔐</div>
                    <h3>Protected Admin Access</h3>
                    <p>
                        Secure login, session-based authentication, and protected routes ensure only authorised
                        administrators can access monitoring data and device controls.
                    </p>
                </div>

                <div class="feature-box">
                    <div class="feature-icon">🗄️</div>
                    <h3>Database-Driven Results</h3>
                    <p>
                        The dashboard reads stored monitoring results from the database rather than launching
                        direct scans from the browser.
                    </p>
                </div>

                <div class="feature-box">
                    <div class="feature-icon">📈</div>
                    <h3>Status History Tracking</h3>
                    <p>
                        Each approved device can store timestamps, latency values, and recent online or offline
                        history for quick review and troubleshooting.
                    </p>
                </div>

                <div class="feature-box">
                    <div class="feature-icon">🛡️</div>
                    <h3>Safe Monitoring Design</h3>
                    <p>
                        The backend collector only checks devices already approved and stored in the database,
                        helping prevent unsafe arbitrary scanning behaviour.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- PRODUCT SHOWCASE SECTION -->
    <section class="showcase">
        <div class="section-shell showcase-grid">
            <div class="showcase-copy">
                <div class="section-kicker">Dashboard Preview</div>
                <h2>A cleaner way to read network health</h2>
                <p>
                    Instead of exposing raw backend behaviour in the browser, the platform presents stored
                    monitoring results through focused cards, health indicators, and easy-to-review status history.
                </p>

                <ul class="showcase-list">
                    <li>Review approved devices at a glance</li>
                    <li>Track online and offline state changes</li>
                    <li>Read recent checks without clutter</li>
                    <li>Keep admin actions separated and protected</li>
                </ul>
            </div>

            <div class="showcase-panel">
                <div class="showcase-window">
                    <div class="showcase-header">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <div class="showcase-body">
                        <div class="showcase-sidebar">
                            <div class="side-pill active-pill"></div>
                            <div class="side-pill"></div>
                            <div class="side-pill"></div>
                            <div class="side-pill"></div>
                        </div>

                        <div class="showcase-main">
                            <div class="showcase-top-cards">
                                <div class="mini-card">
                                    <p>Online</p>
                                    <strong>9</strong>
                                </div>
                                <div class="mini-card">
                                    <p>Offline</p>
                                    <strong>3</strong>
                                </div>
                                <div class="mini-card">
                                    <p>Latency</p>
                                    <strong>21 ms</strong>
                                </div>
                            </div>

                            <div class="showcase-chart"></div>

                            <div class="showcase-rows">
                                <div class="showcase-row">
                                    <span>Core Router</span>
                                    <span class="badge online">Online</span>
                                </div>
                                <div class="showcase-row">
                                    <span>Proxy Node</span>
                                    <span class="badge online">Online</span>
                                </div>
                                <div class="showcase-row">
                                    <span>Lab Device</span>
                                    <span class="badge offline">Offline</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FINAL CTA -->
    <section class="cta-band">
        <div class="section-shell cta-band-inner">
            <div>
                <div class="section-kicker">Get Started</div>
                <h2>Explore the dashboard or open the admin panel</h2>
                <p>
                    Review the current monitoring layout, or sign in to manage protected functionality.
                </p>
            </div>

            <div class="cta-band-buttons">
                <a href="dashboard.php" class="btn-secondary">View Dashboard</a>
                <a href="login.php" class="btn-primary">Admin Login</a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="section-shell footer-shell">
            <div class="footer-brand">
                <div class="logo footer-logo">SNM<span>Dashboard</span></div>
                <p>Secure, portfolio-focused network monitoring interface.</p>
            </div>

            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="devices.php">Devices</a>
                <a href="logs.php">Logs</a>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> Secure Network Monitoring Dashboard — Personal Portfolio Project
        </div>
    </footer>

</body>
</html>