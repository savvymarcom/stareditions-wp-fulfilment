<?php

namespace SavvyWebFulfilment\Admin;

class ProductMetaFields
{

    private SavvyPluginConfig $savvyPluginConfig;

    public function __construct(SavvyPluginConfig $savvyPluginConfig)
    {

        $this->savvyPluginConfig = $savvyPluginConfig;

        // Simple product
        add_action('woocommerce_product_options_general_product_data', [$this, 'addFulfilmentProviderDropdown']);
        add_action('woocommerce_process_product_meta', [$this, 'saveFulfilmentProviderDropdown']);

        // Variable product
        add_action('woocommerce_product_after_variable_attributes', [$this, 'addVariationFields'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'saveVariationFields'], 10, 2);

    }

    public function addFulfilmentTab($tabs)
    {
        $tabs['savvy_fulfilment'] = [
            'label'    => 'Fulfilment',
            'target'   => 'savvy_fulfilment_data',
            'class'    => ['show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external'],
            'priority' => 80,
        ];
        return $tabs;
    }

    public function addFulfilmentProviderDropdown()
    {
        global $post;

        $value = get_post_meta($post->ID, '_fulfilment_provider', true) ?: 'manual';

        $options = $this->savvyPluginConfig->getSavvyFulfilmentProviderOptions();
        
        woocommerce_wp_select([
            'id'          => '_fulfilment_provider',
            'label'       => 'Fulfilment Provider',
            'description' => 'Choose how this product should be fulfilled.',
            'desc_tip'    => true,
            'value'       => $value,
            'options'     => $options, 
        ]);
    }

    public function saveFulfilmentProviderDropdown($post_id)
    {
        if (isset($_POST['_fulfilment_provider'])) {
            update_post_meta(
                $post_id,
                '_fulfilment_provider',
                sanitize_text_field($_POST['_fulfilment_provider'])
            );
        }
    }

    public function addVariationFields($loop, $variation_data, $variation)
    {
        $value = get_post_meta($variation->ID, '_fulfilment_provider', true) ?: 'manual';
        $options = $this->savvyPluginConfig->getSavvyFulfilmentProviderOptions();

        woocommerce_wp_select([
            'id' => "_fulfilment_provider_{$loop}",
            'name' => "variation_meta[_fulfilment_provider][{$loop}]",
            'label' => 'Fulfilment Provider',
            'desc_tip' => true,
            'description' => 'Choose fulfilment provider for this variation.',
            'value' => $value,
            'options' => $options,
        ]);
    }

    public function saveVariationFields($variation_id, $i)
    {
        if (isset($_POST['variation_meta']['_fulfilment_provider'][$i])) {
            update_post_meta(
                $variation_id,
                '_fulfilment_provider',
                sanitize_text_field($_POST['variation_meta']['_fulfilment_provider'][$i])
            );
        }
    }



}
