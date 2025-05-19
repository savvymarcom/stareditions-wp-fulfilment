<?php

namespace SavvyWebFulfilment\Admin;

class SettingsPage
{
    private SavvyPluginConfig $savvyPluginConfig;
    private string $brandName;
    private string $brandLogo;

    public function __construct(SavvyPluginConfig $savvyPluginConfig)
    {
        $this->savvyPluginConfig = $savvyPluginConfig;
        $this->brandName = $this->savvyPluginConfig->getSavvyBrandName();
        $this->brandLogo = $this->savvyPluginConfig->getSavvyBrandLogo();

        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addSettingsPage()
    {

        add_options_page(
            $this->brandName . ' Fulfilment Settings',
            $this->brandName . ' Fulfilment',
            'manage_options',
            'savvy-web-fulfilment',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings()
    {
        register_setting('savvy_web_fulfilment_group', 'savvy_web_access_token', [
            'type' => 'string',
            'sanitize_callback' => fn($value) => $this->validateRequiredField($value, 'savvy_web_access_token'),
            'default' => '',
            'show_in_rest' => false,
        ]);

        register_setting('savvy_web_fulfilment_group', 'savvy_web_notification_email', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default' => '',
            'show_in_rest' => false,
        ]);

        register_setting('savvy_web_fulfilment_group', 'savvy_web_client_code', [
            'type' => 'string',
            'sanitize_callback' => fn($value) => $this->validateRequiredField($value, 'savvy_web_client_code'),
            'default' => '',
            'show_in_rest' => false,
        ]);
        
        register_setting('savvy_web_fulfilment_group', 'savvy_web_store_identifier', [
            'type' => 'string',
            //'sanitize_callback' => [$this, 'handleStoreIdentifierSanitization'],
            'sanitize_callback' => fn($value) => $this->validateRequiredField($value, 'savvy_web_store_identifier'),
            'default' => '',
            'show_in_rest' => false,
        ]);


        add_settings_section(
            'savvy_web_main_section',
            'Fulfilment API Settings',
            null,
            'savvy-web-fulfilment'
        );

        add_settings_field(
            'savvy_web_access_token',
            'Access Token',
            [$this, 'renderAccessTokenField'],
            'savvy-web-fulfilment',
            'savvy_web_main_section'
        );
        
        add_settings_field(
            'savvy_web_notification_email',
            'Notification Email Address',
            [$this, 'renderNotificationEmailField'],
            'savvy-web-fulfilment',
            'savvy_web_main_section'
        );
        
        add_settings_field(
            'savvy_web_client_code',
            'Client Code',
            [$this, 'renderClientCodeField'],
            'savvy-web-fulfilment',
            'savvy_web_main_section'
        );
        
        add_settings_field(
            'savvy_web_store_identifier',
            'Store Identifier',
            [$this, 'renderStoreIdentifierField'],
            'savvy-web-fulfilment',
            'savvy_web_main_section'
        );

        add_action('update_option_savvy_web_access_token', [$this, 'maybeRegisterWithSavvyWeb'], 10, 2);
        add_action('update_option_savvy_web_client_code', [$this, 'maybeRegisterWithSavvyWeb'], 10, 2);
        add_action('update_option_savvy_web_store_identifier', [$this, 'maybeRegisterWithSavvyWeb'], 10, 2);
        
    }

    public function validateRequiredField($value, $optionName)
    {
        if (empty($value)) {
            add_settings_error(
                $optionName,
                $optionName . '_error',
                ucfirst(str_replace('_', ' ', $optionName)) . ' is required.',
                'error'
            );
        }

        return sanitize_text_field($value);
    }

    public function renderSettingsPage()
    {
        $activeTab = $_GET['tab'] ?? 'setup';

        if(!empty($this->brandLogo)){
            $header = '<img src="' . $this->brandLogo . '" alt="' . $this->brandName . ' Logo" style="max-width: 200px; height: auto;">';
        }else{
            $header = '<h1>' . $this->brandName . ' Fulfilment</h1>';
        }

        ?>
            <div class="wrap">
                <?php echo $header; ?>
                <?php echo $this->renderIntro(); ?>
                <hr/>
                <?php echo $this->renderTabs($activeTab); ?>
            </div>
        <?php
    }

    private function renderIntro(): void
    {
        echo "<div style='margin-bottom: 24px;'>
                <h2>About " . esc_html($this->brandName) . " Fulfilment</h2>
                <p>This plugin connects your WooCommerce store to the " . esc_html($this->brandName) . " Fulfilment system, allowing orders to be automatically sent for fulfilment and updated when dispatched. Once configured, any product assigned to a valid fulfilment provider will be automatically processed when the order is marked as paid.</p>
                <p>To get started:</p>
                <ol>
                    <li><strong>Enter your Access Token</strong> below to secure communication.</li>
                    <li><strong>(Optional)</strong>: Add a Notification Email Address to receive fulfilment status updates. If left blank, your site's admin email will be used.</li>
                    <li>The plugin adds a new <strong>Fulfilment Provider</strong> option to the product edit screen. Make sure each product you want fulfilled is assigned to the correct provider.</li>
                </ol>
                <div style='margin: 1em 0; padding: 12px 15px; background-color: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;'>
                    <strong>Note:</strong> If you're using <strong>variable products</strong>, the fulfilment provider must be set individually for <strong>each variation</strong>.
                </div>
                <p>If unsure which fulfilment provider to choose, please contact your " . esc_html($this->brandName) . " account manager.</p>
                <p>When an order is fulfilled, the plugin will update the order status, tracking information, and log the activity. You can view any issues under the Logs tab.</p>
            </div>";
    }

    public function renderTabs($activeTab)
    {
        ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=savvy-web-fulfilment&tab=setup" class="nav-tab <?php echo $activeTab === 'setup' ? 'nav-tab-active' : ''; ?>">Setup</a>
                <a href="?page=savvy-web-fulfilment&tab=logs" class="nav-tab <?php echo $activeTab === 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
            </h2>

        <?php
            if ($activeTab === 'setup') {
                $this->renderSetupTab();
            } elseif ($activeTab === 'logs') {
                $this->renderLogsTab();
            }

    }

    public function renderNotificationEmailField()
    {
        $value = esc_attr(get_option('savvy_web_notification_email', ''));
        echo "<input type='email' name='savvy_web_notification_email' value='{$value}' class='regular-text'>";
        echo "<p class='description'>Optional. This email will receive order fulfilment notifications. If left blank, the default WordPress admin email will be used.</p>";
    }

    public function renderAccessTokenField()
    {
        $value = esc_attr(get_option('savvy_web_access_token', ''));
        echo "<input type='password' name='savvy_web_access_token' value='{$value}' class='regular-text' autocomplete='new-password'>";
        echo "<p class='description'>Enter your {$this->brandName} Access Token. This will be used for API requests.</p>";
    }

    public function renderClientCodeField()
    {
        $value = esc_attr(get_option('savvy_web_client_code', ''));
        echo "<input type='text' name='savvy_web_client_code' value='{$value}' class='regular-text'>";
        echo "<p class='description'>This is your assigned Client Code, used to identify your organisation.</p>";
    }

    public function renderStoreIdentifierField()
    {
        $value = esc_attr(get_option('savvy_web_store_identifier', ''));
        echo "<input type='text' name='savvy_web_store_identifier' value='{$value}' class='regular-text'>";
        echo "<p class='description'>This is your unique Store Identifier (e.g. site name or location).</p>";
    }

    public function renderSetupTab()
    {
        ?>
        <form method="post" action="options.php">
            <?php
                settings_fields('savvy_web_fulfilment_group');
                do_settings_sections('savvy-web-fulfilment');
                submit_button();
            ?>
        </form>
        <?php
    }

    public function handleStoreIdentifierSanitization($value)
    {
        $sanitized = sanitize_text_field($value);

        // Fetch values from the $_POST (already submitted)
        $accessToken = isset($_POST['savvy_web_access_token']) ? sanitize_text_field($_POST['savvy_web_access_token']) : '';
        $clientCode = isset($_POST['savvy_web_client_code']) ? sanitize_text_field($_POST['savvy_web_client_code']) : '';
        $storeId = $sanitized;

        // Only run registration logic if all fields are present
        if ($accessToken && $clientCode && $storeId) {
            try {
                // DO NOT call update_option() here
                $apiService = new \SavvyWebFulfilment\Service\SavvyApiService();
                $apiService->registerSite();

                add_action('admin_notices', function () {
                    echo '<div class="notice notice-success is-dismissible"><p>Successfully registered with SavvyWeb.</p></div>';
                });

            } catch (\Throwable $e) {
                add_action('admin_notices', function () use ($e) {
                    echo '<div class="notice notice-error"><p><strong>SavvyWeb Registration Failed:</strong> ' . esc_html($e->getMessage()) . '</p></div>';
                });
            }
        }

        return $sanitized;
    }

    public function renderLogsTab()
    {
        $logService = new \SavvyWebFulfilment\Logs\LogService();

        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $perPage = 10;

        // Clear logs on demand
        if (isset($_POST['clear_logs']) && check_admin_referer('clear_savvy_logs')) {
            $logService->clearLogs();
            echo '<div class="notice notice-success"><p>Logs deleted successfully.</p></div>';
        }

        $result = $logService->getPaginatedErrors($page, $perPage, $search);
        $logs = $result['logs'];
        $total = $result['total'];
        $totalPages = ceil($total / $perPage);

        ?>
        <div>
            <h3>Error logs</h3>
        </div>
        <div style="margin:18px 0;">
            <form method="get">
                <input type="hidden" name="page" value="savvy-web-fulfilment">
                <input type="hidden" name="tab" value="logs">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search logs...">
                <button type="submit" class="button">Search</button>
            </form>
        </div>

        <div style="margin:18px 0;">
            <form method="post">
                <?php wp_nonce_field('clear_savvy_logs'); ?>
                <button type="submit" name="clear_logs" class="button button-secondary">Delete Logs</button>
            </form>
        </div>


        <?php if (!$logs): ?>
            <p>No error logs from the last 7 days.</p>
        <?php else: ?>
            <p><em>Showing error logs from the last 7 days only.</em></p>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order Ref</th>
                        <th>Method</th>
                        <th>Endpoint</th>
                        <th>Status</th>
                        <th>Code</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log->created_at); ?></td>
                            <td><?php echo esc_html($log->order_ref ?? '—'); ?></td>
                            <td><?php echo esc_html($log->request_method); ?></td>
                            <td><?php echo esc_html($log->endpoint); ?></td>
                            <td><?php echo esc_html($log->status); ?></td>
                            <td><?php echo esc_html($log->response_code); ?></td>
                            <td><?php echo esc_html($log->message); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="tablenav tablenav-bottom">
                    <div class="tablenav-pages" style="float:right;">
                        <?php
                        $base = admin_url('options-general.php?page=savvy-web-fulfilment&tab=logs');
                        if ($search) {
                            $base .= '&s=' . urlencode($search);
                        }

                        for ($i = 1; $i <= $totalPages; $i++) {
                            $link = $base . '&paged=' . $i;
                            echo '<a class="page-numbers ' . ($page === $i ? 'current' : '') . '" href="' . esc_url($link) . '">' . $i . '</a> ';
                        }
                        ?>
                    </div>
                    <div style="clear:both;"></div>
                </div>
            <?php endif; ?>
        <?php endif;
    }

    public function maybeRegisterWithSavvyWeb($oldValue, $newValue)
    {
        $accessToken = get_option('savvy_web_access_token');
        $clientCode = get_option('savvy_web_client_code');
        $storeId = get_option('savvy_web_store_identifier');

        if (empty($accessToken) || empty($clientCode) || empty($storeId)) {
            return;
        }

        try {
            $apiService = new \SavvyWebFulfilment\Service\SavvyApiService();
            $apiService->registerSite();

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>Successfully registered with SavvyWeb.</p></div>';
            });
        } catch (\Throwable $e) {
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p><strong>SavvyWeb Registration Failed:</strong> ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }


}
