<?php

namespace SavvyWebFulfilment\Service;

class UpdaterService
{
    private string $pluginFile;

    public function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;

        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkForUpdate']);
        add_filter('plugins_api', [$this, 'showPluginInfo'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'afterInstall'], 10, 3);
    }

    public function checkForUpdate($transient)
    {
        if (!isset($transient->checked)) {
            return $transient;
        }

        $github = $this->fetchReleaseData();
        if (!$github) {
            return $transient;
        }

        $pluginData = get_plugin_data($this->pluginFile);
        $pluginSlug = plugin_basename($this->pluginFile);

        if (version_compare($github['tag_name'], $transient->checked[$pluginSlug], '>')) {
            $transient->response[$pluginSlug] = (object)[
                'slug' => dirname($pluginSlug),
                'new_version' => $github['tag_name'],
                'url' => $pluginData['PluginURI'],
                'package' => $github['zipball_url'],
            ];
        }

        return $transient;
    }

    public function showPluginInfo($result, $action, $args)
    {
        $slug = dirname(plugin_basename($this->pluginFile));
        if ($action !== 'plugin_information' || $args->slug !== $slug) {
            return $result;
        }

        $github = $this->fetchReleaseData();
        if (!$github) {
            return $result;
        }

        $pluginData = get_plugin_data($this->pluginFile);

        return (object)[
            'name' => $pluginData['Name'],
            'slug' => $slug,
            'version' => $github['tag_name'],
            'author' => $pluginData['AuthorName'],
            'author_profile' => $pluginData['AuthorURI'],
            'last_updated' => $github['published_at'],
            'homepage' => $pluginData['PluginURI'],
            'short_description' => $pluginData['Description'],
            'sections' => [
                'Description' => $pluginData['Description'],
                'Updates' => $github['body'] ?? 'See changelog on GitHub.',
            ],
            'download_link' => $github['zipball_url'],
        ];
    }

    public function afterInstall($response, $hook_extra, $result)
    {
        global $wp_filesystem;

        $installDir = plugin_dir_path($this->pluginFile);
        $wp_filesystem->move($result['destination'], $installDir);
        $result['destination'] = $installDir;

        $pluginSlug = plugin_basename($this->pluginFile);
        if (is_plugin_active($pluginSlug)) {
            activate_plugin($pluginSlug);
        }

        return $result;
    }

    private function fetchReleaseData(): ?array
    {
        $username = get_option('savvy_web_updater_user');
        $repo = get_option('savvy_web_updater_repo');

        $url = "https://api.github.com/repos/$username/$repo/releases/latest";

        $response = wp_remote_get($url, [
            'headers' => [
                'User-Agent' => 'SavvyWebPluginUpdater',
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['tag_name']) || empty($data['zipball_url'])) {
            return null;
        }

        return $data;
    }



    public function updateGithubToken(array $payload): void
    {
        if (!empty($payload['wp_github_user'])) {
            update_option('savvy_web_updater_user', sanitize_text_field($payload['wp_github_user']));
        }

        if (!empty($payload['wp_github_repo'])) {
            update_option('savvy_web_updater_repo', sanitize_text_field($payload['wp_github_repo']));
        }

    }

}
