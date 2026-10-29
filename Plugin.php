<?php namespace CoworkingView\Spaces;

use System\Classes\PluginBase;

/**
 * Plugin registration for CoworkingView Spaces.
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails
     */
    public function pluginDetails()
    {
        return [
            'name' => 'CoworkingView Spaces',
            'description' => 'Coworking spaces, private offices and market rates for a city, with operator-published prices. No API key.',
            'author' => 'CoworkingView',
            'icon' => 'icon-building',
            'homepage' => 'https://coworkingview.com/en/mcp',
        ];
    }

    /**
     * registerComponents
     */
    public function registerComponents()
    {
        return [
            \CoworkingView\Spaces\Components\Spaces::class => 'coworkingSpaces',
            \CoworkingView\Spaces\Components\Rates::class => 'coworkingRates',
        ];
    }
}
