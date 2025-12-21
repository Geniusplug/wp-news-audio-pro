<?php
/**
 * Security Scanner Class
 * 
 * Nulled script detection and file integrity monitoring
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

defined('ABSPATH') or die('Direct access not allowed');

/**
 * WNAP_Security_Scanner class
 * 
 * Military-grade security: nulled detection, file integrity, anti-bypass
 * 
 * @since 1.0.0
 */
class WNAP_Security_Scanner {
    
    /**
     * Nulled script patterns to detect
     * 
     * @var array
     */
    private $nulled_patterns = array(
        'gplvault.com',
        'gplvault',
        'wpnull.org',
        'wpnull',
        'NULLED BY',
        'nulled by',
        'envato_return_true',
        'envato bypass',
        'license bypass',
        'return true; // bypass',
        'function __return_true',
        '// Nulled',
        '// NULLED',
        'GPL Vault',
        'WP Null',
        'nulled-scripts',
    );
    
    /**
     * Critical files to monitor
     * 
     * @var array
     */
    private $critical_files = array();
    
    /**
     * File integrity data
     * 
     * @var array
     */
    private $integrity_data = array();
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->critical_files = array(
            WNAP_PLUGIN_DIR . 'includes/class-license-manager.php',
            WNAP_PLUGIN_DIR . 'includes/class-license-guard.php',
            WNAP_PLUGIN_DIR . 'wp-news-audio-pro.php',
            WNAP_PLUGIN_DIR . 'includes/class-security-scanner.php',
        );
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        // Schedule security scans
        add_action('init', array($this, 'schedule_security_scan'));
        add_action('wnap_security_scan', array($this, 'run_security_scan'));
        
        // Run scan on plugin load (lightweight check)
        add_action('plugins_loaded', array($this, 'quick_security_check'), 1);
        
        // Admin notice for security issues
        add_action('admin_notices', array($this, 'show_security_notices'));
    }
    
    /**
     * Schedule security scan
     * 
     * @since 1.0.0
     */
    public function schedule_security_scan() {
        if (!wp_next_scheduled('wnap_security_scan')) {
            wp_schedule_event(time(), 'daily', 'wnap_security_scan');
        }
    }
    
    /**
     * Run quick security check on plugin load
     * 
     * @since 1.0.0
     */
    public function quick_security_check() {
        // Check if already flagged
        $security_status = get_option('wnap_security_status', 'clean');
        
        if ($security_status === 'compromised') {
            $this->deactivate_plugin();
            return;
        }
        
        // Quick check for common bypass patterns in license manager
        $license_file = WNAP_PLUGIN_DIR . 'includes/class-license-manager.php';
        if (file_exists($license_file)) {
            $content = file_get_contents($license_file);
            
            // Check for obvious bypass patterns
            if (strpos($content, 'return true;') !== false && strpos($content, 'is_license_valid') !== false) {
                $pattern_check = preg_match('/function\s+is_license_valid.*?\{.*?return\s+true\s*;.*?\}/s', $content);
                if ($pattern_check) {
                    $this->flag_security_issue('bypass_detected', 'License bypass detected in license manager');
                }
            }
        }
    }
    
    /**
     * Run comprehensive security scan
     * 
     * @since 1.0.0
     */
    public function run_security_scan() {
        $issues = array();
        
        // 1. Nulled script detection
        $nulled_check = $this->scan_for_nulled_patterns();
        if (!empty($nulled_check)) {
            $issues['nulled_patterns'] = $nulled_check;
        }
        
        // 2. File integrity check
        $integrity_check = $this->check_file_integrity();
        if (!empty($integrity_check)) {
            $issues['file_integrity'] = $integrity_check;
        }
        
        // 3. GPL auto-activation blocker
        $bypass_check = $this->check_for_bypass_attempts();
        if (!empty($bypass_check)) {
            $issues['bypass_attempts'] = $bypass_check;
        }
        
        // Store scan results
        update_option('wnap_last_security_scan', time());
        
        if (!empty($issues)) {
            update_option('wnap_security_issues', $issues);
            update_option('wnap_security_status', 'compromised');
            
            // Send alert email
            $this->send_security_alert($issues);
            
            // Deactivate plugin
            $this->deactivate_plugin();
        } else {
            update_option('wnap_security_status', 'clean');
            delete_option('wnap_security_issues');
        }
        
        return $issues;
    }
    
    /**
     * Scan for nulled script patterns
     * 
     * @return array Found patterns
     * @since 1.0.0
     */
    private function scan_for_nulled_patterns() {
        $found = array();
        
        // Scan all PHP files in plugin directory
        $files = $this->get_php_files(WNAP_PLUGIN_DIR);
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            foreach ($this->nulled_patterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $found[] = array(
                        'file' => str_replace(WNAP_PLUGIN_DIR, '', $file),
                        'pattern' => $pattern,
                    );
                }
            }
        }
        
        return $found;
    }
    
    /**
     * Check file integrity using SHA-256 checksums
     * 
     * @return array Modified files
     * @since 1.0.0
     */
    private function check_file_integrity() {
        $modified = array();
        
        // Get stored checksums
        $stored_checksums = get_option('wnap_file_checksums', array());
        
        // If no stored checksums, create them (first run)
        if (empty($stored_checksums)) {
            $this->create_file_checksums();
            return array();
        }
        
        // Check each critical file
        foreach ($this->critical_files as $file) {
            if (!file_exists($file)) {
                $modified[] = array(
                    'file' => str_replace(WNAP_PLUGIN_DIR, '', $file),
                    'issue' => 'file_missing',
                );
                continue;
            }
            
            $current_hash = hash_file('sha256', $file);
            $file_key = str_replace(WNAP_PLUGIN_DIR, '', $file);
            
            if (isset($stored_checksums[$file_key])) {
                if ($current_hash !== $stored_checksums[$file_key]) {
                    $modified[] = array(
                        'file' => $file_key,
                        'issue' => 'checksum_mismatch',
                    );
                }
            }
        }
        
        return $modified;
    }
    
    /**
     * Create file checksums for critical files
     * 
     * @since 1.0.0
     */
    public function create_file_checksums() {
        $checksums = array();
        
        foreach ($this->critical_files as $file) {
            if (file_exists($file)) {
                $file_key = str_replace(WNAP_PLUGIN_DIR, '', $file);
                $checksums[$file_key] = hash_file('sha256', $file);
            }
        }
        
        update_option('wnap_file_checksums', $checksums);
        return $checksums;
    }
    
    /**
     * Check for bypass attempts
     * 
     * @return array Bypass attempts found
     * @since 1.0.0
     */
    private function check_for_bypass_attempts() {
        $bypasses = array();
        
        // Check for filter hijacking
        global $wp_filter;
        
        // Check if license validation is being filtered
        if (isset($wp_filter['wnap_is_license_valid'])) {
            foreach ($wp_filter['wnap_is_license_valid']->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    // Check if callback is __return_true
                    if (is_string($callback['function']) && $callback['function'] === '__return_true') {
                        $bypasses[] = array(
                            'type' => 'filter_hijacking',
                            'filter' => 'wnap_is_license_valid',
                            'callback' => '__return_true',
                        );
                    }
                }
            }
        }
        
        // Check for database manipulation
        global $wpdb;
        $license_option = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            'wnap_license'
        ));
        
        if ($license_option) {
            // Check if it contains suspicious patterns
            if (stripos($license_option, 'bypass') !== false || 
                stripos($license_option, 'nulled') !== false ||
                stripos($license_option, 'cracked') !== false) {
                $bypasses[] = array(
                    'type' => 'database_manipulation',
                    'option' => 'wnap_license',
                );
            }
        }
        
        return $bypasses;
    }
    
    /**
     * Get all PHP files in directory recursively
     * 
     * @param string $dir Directory path
     * @return array PHP files
     * @since 1.0.0
     */
    private function get_php_files($dir) {
        $files = array();
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Flag security issue
     * 
     * @param string $type Issue type
     * @param string $message Issue message
     * @since 1.0.0
     */
    private function flag_security_issue($type, $message) {
        $issues = get_option('wnap_security_issues', array());
        $issues[$type] = $message;
        
        update_option('wnap_security_issues', $issues);
        update_option('wnap_security_status', 'compromised');
        
        // Send alert
        $this->send_security_alert(array($type => $message));
        
        // Deactivate
        $this->deactivate_plugin();
    }
    
    /**
     * Send security alert email
     * 
     * @param array $issues Security issues
     * @since 1.0.0
     */
    private function send_security_alert($issues) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $site_url = get_site_url();
        
        $subject = sprintf('[%s] Security Alert - WP News Audio Pro', $site_name);
        
        $message = "Security Alert: WP News Audio Pro\n\n";
        $message .= "Site: {$site_name} ({$site_url})\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
        $message .= "Issues Detected:\n\n";
        
        foreach ($issues as $type => $details) {
            $message .= "Type: {$type}\n";
            if (is_array($details)) {
                $message .= print_r($details, true) . "\n";
            } else {
                $message .= "{$details}\n";
            }
            $message .= "\n";
        }
        
        $message .= "Action Taken: Plugin has been deactivated for security.\n\n";
        $message .= "Please contact support:\n";
        $message .= "Email: info.geniusplugtechnology@gmail.com\n";
        $message .= "WhatsApp: +880 1761 487193\n";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Deactivate plugin due to security issue
     * 
     * @since 1.0.0
     */
    private function deactivate_plugin() {
        // Mark as deactivated
        update_option('wnap_security_deactivated', true);
        
        // Remove license
        delete_option('wnap_license');
        
        // Don't actually deactivate the plugin file to avoid breaking the site
        // Just prevent all functionality through the guard
    }
    
    /**
     * Show security notices in admin
     * 
     * @since 1.0.0
     */
    public function show_security_notices() {
        $security_status = get_option('wnap_security_status', 'clean');
        
        if ($security_status === 'compromised') {
            $issues = get_option('wnap_security_issues', array());
            ?>
            <div class="notice notice-error">
                <h3>⚠️ <?php esc_html_e('WP News Audio Pro - Security Alert', 'wp-news-audio-pro'); ?></h3>
                <p>
                    <strong><?php esc_html_e('Security issues detected. Plugin has been deactivated.', 'wp-news-audio-pro'); ?></strong>
                </p>
                <p><?php esc_html_e('Issues found:', 'wp-news-audio-pro'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ($issues as $type => $details) : ?>
                        <li>
                            <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?>:</strong>
                            <?php 
                            if (is_array($details)) {
                                echo esc_html(count($details) . ' issue(s) detected');
                            } else {
                                echo esc_html($details);
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <strong><?php esc_html_e('Support:', 'wp-news-audio-pro'); ?></strong><br>
                    <?php esc_html_e('Email:', 'wp-news-audio-pro'); ?> 
                    <a href="mailto:info.geniusplugtechnology@gmail.com">info.geniusplugtechnology@gmail.com</a><br>
                    <?php esc_html_e('WhatsApp:', 'wp-news-audio-pro'); ?> 
                    <a href="https://wa.me/8801761487193" target="_blank">+880 1761 487193</a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Reset security status (for authorized fixes)
     * 
     * @since 1.0.0
     */
    public function reset_security_status() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        delete_option('wnap_security_status');
        delete_option('wnap_security_issues');
        delete_option('wnap_security_deactivated');
        
        // Recreate file checksums
        $this->create_file_checksums();
        
        return true;
    }
}
