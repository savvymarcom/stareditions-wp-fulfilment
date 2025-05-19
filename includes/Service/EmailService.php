<?php

namespace SavvyWebFulfilment\Service;

use SavvyWebFulfilment\Admin\SavvyPluginConfig;
use WC_Order;

class EmailService
{

    private SavvyPluginConfig $savvyPluginConfig;
    private array $headers;
    private string $adminEmail;
    private string $pluginName;
    private string $brandName;

    public function __construct()
    {
        $this->savvyPluginConfig = new SavvyPluginConfig();
        $this->init();
    }

    private function init()
    {
        $this->adminEmail = get_option('savvy_web_notification_email') ?: get_option('admin_email');

        $site_url = site_url();
        $site_url = str_replace(['http://', 'https://'], '', $site_url);
        $domain = explode('/', $site_url)[0];
        $fromEmail = 'admin@' . $domain;

        $this->pluginName = $this->savvyPluginConfig->getSavvyPluginName();
        $this->brandName = $this->savvyPluginConfig->getSavvyBrandName();

        $this->headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: '. $this->pluginName  . ' <'. $fromEmail .'>',
            'Reply-To: '. $this->pluginName  . ' <'. $fromEmail .'>',
            'X-Mailer: WooCommerce + '. $this->pluginName,
            'MIME-Version: 1.0'
        ];
    }

    public function sendFulfilmentErrorEmail(WC_Order $order, string $error): void
    {
        
        $orderId = $order->get_id();

        $subject = 'WooCommerce Order #' . $orderId . ' - Fulfilment Error';
        $link = admin_url("post.php?post={$orderId}&action=edit");
        
        $emailTitle = "WooCommerce Order #{$orderId} - Fulfilment Error";
        $emailHeading = "Fulfilment Error for Order #{$orderId}";

        $emailBody = "<p style='margin: 0 0 16px; font-size: 14px;'>The following order has been marked as fulfilled by {$this->brandName}:</p>
                        <p style='margin: 0 0 16px; font-size: 14px;'>An error occurred while trying to send WooCommerce Order #{$order->get_id()} to {$this->brandName} for fulfilment.</p>
                        <p style='margin: 0 0 16px; font-size: 14px;'><strong>Error Message:</strong><br />{$error}</p>
                        <p style='margin: 0 0 16px; font-size: 14px;'><strong>What to do next:</strong></p>
                        <ol>
                            <li>
                                <strong>Check the order and its items</strong>
                                <ul>
                                    <li>Ensure all required fields are completed (e.g. shipping address, customer name, etc.)</li>
                                    <li>Make sure all products being fulfilled by {$this->brandName} have valid SKUs and configuration</li>
                                    <li>If you identify and fix the issue, return to the order screen and click the <strong>'Resend to Fulfilment'</strong> button.</li>
                                </ul>
                            </li>
                            <li>
                                <strong>If the error is not something you can fix</strong>
                                <ul>
                                    <li>The issue may be with the {$this->brandName} fulfilment system or your account setup.</li>
                                    <li>In this case, please contact your {$this->brandName} account manager for assistance.</li>
                                </ul>
                            </li>
                        </ol>
                        <p style='margin-bottom: 20px;'>
                            <a href='{$link}' style='color: #ffffff; background-color: #462a7b; text-decoration: none; padding: 10px 20px; border-radius: 5px; display: inline-block; font-size: 14px;'>
                            View Order in Admin
                            </a>
                        </p>";

        $emailContent = $this->emailTemplate($emailTitle, $emailHeading, $emailBody);
 
        $success = wp_mail($this->adminEmail, $subject, $emailContent, $this->headers);

        if (!$success) {
            error_log('[SavvyWebPlugin] ❌ wp_mail failed to send.');
        }
    }


    public function sendFulfilmentStatusUpdateEmail($orderId, $status, $tracking, $carrier): void
    {

        $subject = "WooCommerce #{$orderId} marked as Fulfilled";
        $link = admin_url("post.php?post={$orderId}&action=edit");

        $emailTitle = "WooCommerce Order #{$orderId} marked as Fulfilled";
        $emailHeading = "Order #{$orderId} Fulfilled by {$this->brandName}";

        $emailBody = "<p style='margin: 0 0 16px; font-size: 14px;'>The following order has been marked as fulfilled by {$this->brandName}:</p>
                      <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr style='background-color: #f9f9f9;'>
                          <td style='padding: 12px; font-weight: bold; border: 1px solid #e5e5e5;'>Order ID:</td>
                          <td style='padding: 12px; border: 1px solid #e5e5e5;'>#{$orderId}</td>
                        </tr>
                        <tr>
                          <td style='padding: 12px; font-weight: bold; border: 1px solid #e5e5e5;'>Status:</td>
                          <td style='padding: 12px; border: 1px solid #e5e5e5;'>{$status}</td>
                        </tr>
                        <tr style='background-color: #f9f9f9;'>
                          <td style='padding: 12px; font-weight: bold; border: 1px solid #e5e5e5;'>Tracking Number:</td>
                          <td style='padding: 12px; border: 1px solid #e5e5e5;'>{$tracking}</td>
                        </tr>
                        <tr>
                          <td style='padding: 12px; font-weight: bold; border: 1px solid #e5e5e5;'>Carrier:</td>
                          <td style='padding: 12px; border: 1px solid #e5e5e5;'>{$carrier}</td>
                        </tr>
                      </table>
                      <p style='margin-bottom: 20px;'>
                        <a href='{$link}' style='color: #ffffff; background-color: #462a7b; text-decoration: none; padding: 10px 20px; border-radius: 5px; display: inline-block; font-size: 14px;'>
                          View Order in Admin
                        </a>
                      </p>";

        $emailContent = $this->emailTemplate($emailTitle, $emailHeading, $emailBody);
        
        $success = WC()->mailer()->send($this->adminEmail, $subject, $emailContent, $this->headers);        

        if (!$success) {
            error_log('[SavvyWebPlugin] ❌ wp_mail failed to send.');
        }

    }

    private function emailTemplate($title, $heading, $body) {
        return <<<EOD
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title>{$title}</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            <style type="text/css">
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #f7f7f7;
                    font-family: Arial, sans-serif;
                    font-size: 14px;
                    color: #333333;
                }
                table {
                    border-collapse: collapse;
                }
                td {
                    padding: 0;
                }
                #wrapper {
                    max-width: 600px;
                    margin: 0 auto;
                }
                #template_header {
                    background-color: #462a7b;
                    color: #ffffff;
                    padding: 20px;
                    text-align: left;
                    border-radius: 3px 3px 0 0;
                }
                #template_header h1 {
                    color: #ffffff;
                    font-size: 24px;
                    font-weight: bold;
                    margin: 0;
                }
                #template_body {
                    background-color: #ffffff;
                }
                #body_content {
                    padding: 20px;
                }
                #template_footer {
                    background-color: #462a7b;
                    color: #ffffff;
                    padding: 10px;
                    text-align: center;
                    border-radius: 0 0 3px 3px;
                }
                #template_footer p {
                    margin: 0;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <table id="outer_wrapper" align="center" width="100%" style="background-color: #f7f7f7;">
                <tr>
                    <td>
                        <div id="wrapper">
                            <table id="inner_wrapper" width="100%">
                                <tr>
                                    <td id="template_header">
                                        <h1>{$heading}</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td id="template_body">
                                        <div id="body_content">
                                            {$body}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td id="template_footer">
                                        <p>&copy; {$this->pluginName}</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        EOD;
    }

}