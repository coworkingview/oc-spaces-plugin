<?php namespace CoworkingView\Spaces\Components;

use Cms\Classes\ComponentBase;
use CoworkingView\Spaces\Classes\Client;
use Exception;

/**
 * What coworking costs in a city: the monthly median per workspace type, with
 * the number of spaces behind each figure (CoworkingView Price Index, CC BY 4.0).
 */
class Rates extends ComponentBase
{
    /**
     * @var array Rows of ['label', 'median', 'spaces'].
     */
    public $rows = [];

    /**
     * @var string The city's display name.
     */
    public $cityName = '';

    /**
     * @var string|null Why nothing is shown.
     */
    public $error;

    /**
     * @var bool Whether to render the error: only for a signed-in backend user.
     */
    public $showError = false;

    const LABELS = [
        'private_office' => 'Private office',
        'exclusive_bereich' => 'Exclusive area',
        'fix_desk' => 'Fixed desk',
        'flex_desk' => 'Flex desk',
        'day_pass' => 'Day pass',
        'meeting_room' => 'Meeting room',
        'virtual_office' => 'Virtual office',
        'event_space' => 'Event space',
        'membership' => 'Membership',
    ];

    public function componentDetails()
    {
        return [
            'name' => 'Coworking Market Rates',
            'description' => 'A table of median monthly prices per workspace type in a city.',
        ];
    }

    public function defineProperties()
    {
        return [
            'city' => [
                'title' => 'City',
                'type' => 'string',
                'default' => 'madrid',
                'validationPattern' => '^[a-z0-9-]+$',
                'validationMessage' => 'Use a city slug such as berlin or madrid.',
            ],
            'currency' => [
                'title' => 'Currency',
                'description' => 'ISO code such as USD to convert the figures. Empty keeps the city\'s own currency.',
                'type' => 'string',
                'default' => '',
            ],
            'locale' => [
                'title' => 'Language',
                'type' => 'dropdown',
                'default' => 'en',
                'options' => ['en' => 'English', 'de' => 'Deutsch', 'es' => 'Español'],
            ],
            'cacheMinutes' => [
                'title' => 'Cache (minutes)',
                'type' => 'string',
                'default' => '720',
                'validationPattern' => '^[0-9]+$',
            ],
            'showCredit' => [
                'title' => 'Show source credit',
                'description' => 'The figures are CC BY 4.0, which asks for a credit and a link when republished.',
                'type' => 'checkbox',
                'default' => false,
            ],
        ];
    }

    public function onRun()
    {
        try {
            $city = Client::rates(
                (string) $this->property('city'),
                (string) $this->property('currency'),
                (string) $this->property('locale'),
                (int) $this->property('cacheMinutes')
            );
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->showError = class_exists(\Backend\Facades\BackendAuth::class)
                && (bool) \Backend\Facades\BackendAuth::getUser();
            return;
        }
        $this->cityName = trim((string) ($city['city']['name'] ?? ''));
        $this->rows = self::rowsFrom($city);
    }

    /**
     * The published figures as display rows. Public so it can be tested alone.
     */
    public static function rowsFrom(?array $city): array
    {
        $rows = [];
        foreach ((array) ($city['figures'] ?? []) as $figure) {
            if (empty($figure['optionType']) || empty($figure['median'])) {
                continue;
            }
            $rows[] = [
                'label' => self::LABELS[$figure['optionType']] ?? $figure['optionType'],
                'median' => Client::money($figure['median']),
                'spaces' => (int) ($figure['propertyCount'] ?? 0),
            ];
        }
        return $rows;
    }
}
