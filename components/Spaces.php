<?php namespace CoworkingView\Spaces\Components;

use Cms\Classes\ComponentBase;
use CoworkingView\Spaces\Classes\Client;
use Exception;

/**
 * Lists coworking spaces in a city, linked to their page on coworkingview.com.
 */
class Spaces extends ComponentBase
{
    /**
     * @var array The spaces to display.
     */
    public $spaces = [];

    /**
     * @var string|null Why nothing is shown.
     */
    public $error;

    /**
     * @var bool Whether to render the error: only for a signed-in backend user.
     */
    public $showError = false;

    public function componentDetails()
    {
        return [
            'name' => 'Coworking Spaces',
            'description' => 'Lists coworking spaces and private offices in a city, with their entry price.',
        ];
    }

    public function defineProperties()
    {
        return [
            'city' => [
                'title' => 'City',
                'description' => 'City slug, as in coworkingview.com/en/coworking/berlin.',
                'type' => 'string',
                'default' => 'berlin',
                'validationPattern' => '^[a-z0-9-]+$',
                'validationMessage' => 'Use a city slug such as berlin or madrid.',
            ],
            'type' => [
                'title' => 'Workspace type',
                'type' => 'dropdown',
                'default' => '',
                'options' => [
                    '' => 'Any',
                    'private_office' => 'Private office',
                    'exclusive_bereich' => 'Exclusive area',
                    'fix_desk' => 'Fixed desk',
                    'flex_desk' => 'Flex desk',
                    'day_pass' => 'Day pass',
                    'meeting_room' => 'Meeting room',
                    'virtual_office' => 'Virtual office',
                    'event_space' => 'Event space',
                    'membership' => 'Membership',
                ],
            ],
            'limit' => [
                'title' => 'Number of spaces',
                'type' => 'string',
                'default' => '5',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Enter a number from 1 to 50.',
            ],
            'locale' => [
                'title' => 'Language',
                'type' => 'dropdown',
                'default' => 'en',
                'options' => ['en' => 'English', 'de' => 'Deutsch', 'es' => 'Español'],
            ],
            'cacheMinutes' => [
                'title' => 'Cache (minutes)',
                'description' => 'How long results are kept before the API is asked again.',
                'type' => 'string',
                'default' => '720',
                'validationPattern' => '^[0-9]+$',
            ],
        ];
    }

    public function onRun()
    {
        try {
            $this->spaces = Client::spaces(
                (string) $this->property('city'),
                (string) $this->property('type'),
                (int) $this->property('limit'),
                (string) $this->property('locale'),
                (int) $this->property('cacheMinutes')
            );
        } catch (Exception $e) {
            $this->spaces = [];
            $this->error = $e->getMessage();
            $this->showError = class_exists(\Backend\Facades\BackendAuth::class)
                && (bool) \Backend\Facades\BackendAuth::getUser();
        }
    }
}
