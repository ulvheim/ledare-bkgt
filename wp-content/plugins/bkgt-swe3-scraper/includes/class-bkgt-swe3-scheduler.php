<?php
/**
 * SWE3 scraper scheduler class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_SWE3_Scheduler {

    /**
     * Cron hook name
     */
    const CRON_HOOK = 'bkgt_swe3_daily_scrape';

    /**
     * Default scrape time (02:00)
     */
    const DEFAULT_SCRAPE_HOUR = 2;
    const DEFAULT_SCRAPE_MINUTE = 0;

    /**
     * Schedule the daily scrape
     */
    public function schedule_daily_scrape() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            $this->schedule_next_run();
            $this->log('info', 'Scheduled daily SWE3 scraping');
        }
    }

    /**
     * Unschedule the daily scrape
     */
    public function unschedule_daily_scrape() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            $this->log('info', 'Unscheduled daily SWE3 scraping');
        }
    }

    /**
     * Schedule the next run
     */
    private function schedule_next_run() {
        $next_run = $this->get_next_run_time();
        wp_schedule_event($next_run, 'daily', self::CRON_HOOK);
    }

    /**
     * Get the next run time
     */
    private function get_next_run_time() {
        $hour = get_option('bkgt_swe3_scrape_hour', self::DEFAULT_SCRAPE_HOUR);
        $minute = get_option('bkgt_swe3_scrape_minute', self::DEFAULT_SCRAPE_MINUTE);

        $now = current_time('timestamp');
        $next_run = strtotime(date('Y-m-d', $now) . sprintf(' %02d:%02d:00', $hour, $minute));

        // If the time has already passed today, schedule for tomorrow
        if ($next_run <= $now) {
            $next_run = strtotime('+1 day', $next_run);
        }

        return $next_run;
    }

    /**
     * Reschedule the next run (useful for changing times)
     */
    public function reschedule_next_run() {
        $this->unschedule_daily_scrape();
        $this->schedule_daily_scrape();
    }

    /**
     * Execute the scheduled scrape
     */
    public function execute_scheduled_scrape() {
        $this->log('info', 'Starting scheduled SWE3 scraping');

        // Check if scraping is enabled
        if (get_option('bkgt_swe3_scrape_enabled', 'yes') !== 'yes') {
            $this->log('info', 'SWE3 scraping is disabled, skipping');
            return;
        }

        // Execute the scrape
        $scraper = bkgt_swe3_scraper()->scraper;
        $result = $scraper->execute_scrape();

        if ($result) {
            $this->log('info', 'Scheduled SWE3 scraping completed successfully');
            update_option('bkgt_swe3_last_successful_scrape', current_time('mysql'));
        } else {
            $this->log('error', 'Scheduled SWE3 scraping failed');
            $this->handle_scrape_failure();
        }

        // Reschedule for next run
        $this->schedule_next_run();
    }

    /**
     * Handle scrape failure
     */
    private function handle_scrape_failure() {
        $failure_count = get_option('bkgt_swe3_failure_count', 0) + 1;
        update_option('bkgt_swe3_failure_count', $failure_count);

        // Send notification after 3 consecutive failures
        if ($failure_count >= 3) {
            $this->send_failure_notification($failure_count);
        }

        // Reset failure count on successful scrape
        update_option('bkgt_swe3_last_failure', current_time('mysql'));
    }

    /**
     * Reset failure count (called on successful scrape)
     */
    public function reset_failure_count() {
        update_option('bkgt_swe3_failure_count', 0);
    }

    /**
     * Send failure notification
     */
    private function send_failure_notification($failure_count) {
        $admin_email = get_option('admin_email');
        $site_name = get_option('blogname');

        $subject = sprintf('[%s] SWE3 Scraper - Multiple Failures Detected', $site_name);

        $message = sprintf(
            "The SWE3 document scraper has failed %d times in a row.\n\n" .
            "Last failure: %s\n" .
            "Last successful scrape: %s\n\n" .
            "Please check the scraper logs and ensure SWE3 website is accessible.\n\n" .
            "You can manually trigger a scrape from the admin dashboard.\n\n" .
            "Best regards,\n" .
            "%s System",
            $failure_count,
            get_option('bkgt_swe3_last_failure', 'Never'),
            get_option('bkgt_swe3_last_successful_scrape', 'Never'),
            $site_name
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Manually trigger a scrape
     */
    public function trigger_manual_scrape() {
        $this->log('info', 'Manual SWE3 scraping triggered by user');

        $scraper = bkgt_swe3_scraper()->scraper;
        $result = $scraper->execute_scrape();

        if ($result) {
            $this->reset_failure_count();
            $this->log('info', 'Manual SWE3 scraping completed successfully');
            return array('success' => true, 'message' => 'Scraping completed successfully');
        } else {
            $this->log('error', 'Manual SWE3 scraping failed');
            return array('success' => false, 'message' => 'Scraping failed - check logs for details');
        }
    }

    /**
     * Get scheduler status
     */
    public function get_scheduler_status() {
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        $last_scrape = get_option('bkgt_swe3_last_scrape', 'never');
        $last_successful = get_option('bkgt_swe3_last_successful_scrape', 'never');
        $failure_count = get_option('bkgt_swe3_failure_count', 0);
        $enabled = get_option('bkgt_swe3_scrape_enabled', 'yes');

        return array(
            'enabled' => $enabled === 'yes',
            'next_run' => $next_run ? date('Y-m-d H:i:s', $next_run) : null,
            'last_scrape' => $last_scrape,
            'last_successful_scrape' => $last_successful,
            'failure_count' => $failure_count,
            'scrape_hour' => get_option('bkgt_swe3_scrape_hour', self::DEFAULT_SCRAPE_HOUR),
            'scrape_minute' => get_option('bkgt_swe3_scrape_minute', self::DEFAULT_SCRAPE_MINUTE),
        );
    }

    /**
     * Update scrape schedule
     */
    public function update_schedule($hour, $minute) {
        // Validate input
        $hour = intval($hour);
        $minute = intval($minute);

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return array('success' => false, 'message' => 'Invalid time specified');
        }

        // Update options
        update_option('bkgt_swe3_scrape_hour', $hour);
        update_option('bkgt_swe3_scrape_minute', $minute);

        // Reschedule
        $this->reschedule_next_run();

        $this->log('info', sprintf('Updated scrape schedule to %02d:%02d', $hour, $minute));

        return array('success' => true, 'message' => sprintf('Schedule updated to %02d:%02d daily', $hour, $minute));
    }

    /**
     * Enable or disable scraping
     */
    public function set_scraping_enabled($enabled) {
        $enabled_value = $enabled ? 'yes' : 'no';
        update_option('bkgt_swe3_scrape_enabled', $enabled_value);

        if ($enabled) {
            $this->schedule_daily_scrape();
            $message = 'SWE3 scraping enabled';
        } else {
            $this->unschedule_daily_scrape();
            $message = 'SWE3 scraping disabled';
        }

        $this->log('info', $message);

        return array('success' => true, 'message' => $message);
    }

    /**
     * Initialize cron hooks
     */
    public function init_hooks() {
        add_action(self::CRON_HOOK, array($this, 'execute_scheduled_scrape'));
    }

    /**
     * Get available schedule options
     */
    public function get_schedule_options() {
        return array(
            'hours' => range(0, 23),
            'minutes' => array(0, 15, 30, 45), // Common intervals
        );
    }

    /**
     * Clean up old scheduled events (useful for debugging)
     */
    public function cleanup_scheduled_events() {
        $crons = _get_cron_array();

        if (empty($crons)) {
            return;
        }

        $removed = 0;
        foreach ($crons as $timestamp => $cron) {
            if (isset($cron[self::CRON_HOOK])) {
                foreach ($cron[self::CRON_HOOK] as $key => $data) {
                    wp_unschedule_event($timestamp, self::CRON_HOOK, $data['args']);
                    $removed++;
                }
            }
        }

        if ($removed > 0) {
            $this->log('info', sprintf('Cleaned up %d old scheduled events', $removed));
        }
    }

    /**
     * Log message
     */
    private function log($level, $message) {
        if (method_exists(bkgt_swe3_scraper()->scraper, 'log')) {
            bkgt_swe3_scraper()->scraper->log($level, '[Scheduler] ' . $message);
        } else {
            error_log(sprintf('[BKGT SWE3 Scheduler] [%s] %s', strtoupper($level), $message));
        }
    }
}