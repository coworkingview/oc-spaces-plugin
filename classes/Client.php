<?php namespace CoworkingView\Spaces\Classes;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Read-only client for the public CoworkingView API (https://api.coworkingview.com/v1).
 * No API key. Responses are cached so a busy page never becomes one API call
 * per visitor.
 */
class Client
{
    const BASE_URL = 'https://api.coworkingview.com';

    const LOCALES = ['en', 'de', 'es'];

    const PRICING_OPTION_TYPES = [
        'private_office', 'exclusive_bereich', 'fix_desk', 'flex_desk', 'day_pass',
        'meeting_room', 'virtual_office', 'event_space', 'membership',
    ];

    /** The localized path segment of a space's page on coworkingview.com. */
    const SPACE_SEGMENTS = ['en' => 'workspace', 'de' => 'buero', 'es' => 'oficina'];

    /**
     * @var callable|null fn(string $url): array — replaces the HTTP call in tests.
     */
    public static $fetcher = null;

    public static function isSlug($value): bool
    {
        return is_string($value) && preg_match('/^[a-z0-9-]{1,200}$/', $value) === 1;
    }

    public static function locale($value): string
    {
        return in_array($value, self::LOCALES, true) ? $value : 'en';
    }

    /**
     * Spaces in a city. Invalid optional filters are dropped, an invalid city throws.
     */
    public static function spacesUrl(string $city, string $type = '', int $limit = 5, string $locale = 'en'): string
    {
        if (!self::isSlug($city)) {
            throw new Exception('Set the city to a slug such as "berlin".');
        }
        $query = [
            'locale' => self::locale($locale),
            'city' => $city,
            'pageSize' => max(1, min(50, $limit)),
        ];
        if (in_array($type, self::PRICING_OPTION_TYPES, true)) {
            $query['pricingOptionType'] = $type;
        }
        return self::BASE_URL.'/v1/properties?'.http_build_query($query);
    }

    public static function ratesUrl(string $city, string $currency = '', string $locale = 'en'): string
    {
        if (!self::isSlug($city)) {
            throw new Exception('Set the city to a slug such as "madrid".');
        }
        $query = ['locale' => self::locale($locale), 'city' => $city];
        $currency = strtoupper(trim($currency));
        if (preg_match('/^[A-Z]{3}$/', $currency)) {
            $query['currency'] = $currency;
        }
        return self::BASE_URL.'/v1/market/rates?'.http_build_query($query);
    }

    public static function spaceUrl(string $slug, string $locale = 'en'): string
    {
        $locale = self::locale($locale);
        return 'https://coworkingview.com/'.$locale.'/'.self::SPACE_SEGMENTS[$locale].'/'.rawurlencode($slug);
    }

    /**
     * Format an API money object: "EUR 290", "GBP 12.50".
     */
    public static function money($money): string
    {
        if (!is_array($money) || !isset($money['amount'])) {
            return '';
        }
        $amount = (float) $money['amount'];
        $decimals = floor($amount) == $amount ? 0 : 2;
        return trim(($money['currency'] ?? '').' '.number_format($amount, $decimals));
    }

    /**
     * The list of spaces, each with a ready-to-use `url` and `priceLabel`.
     */
    public static function spaces(string $city, string $type, int $limit, string $locale, int $cacheMinutes): array
    {
        $body = self::get(self::spacesUrl($city, $type, $limit, $locale), $cacheMinutes);
        $periods = ['hour' => '/hour', 'day' => '/day', 'week' => '/week', 'month' => '/month', 'year' => '/year'];
        $spaces = [];
        foreach ((array) ($body['data'] ?? []) as $space) {
            if (empty($space['slug']) || empty($space['title'])) {
                continue;
            }
            $price = self::money($space['fromPrice'] ?? null);
            $space['url'] = self::spaceUrl($space['slug'], $locale);
            $space['priceLabel'] = $price === '' ? '' : 'from '.$price.($periods[$space['fromBillingPeriod'] ?? ''] ?? '');
            $spaces[] = $space;
        }
        return $spaces;
    }

    /**
     * One city's published figures (monthly medians per workspace type), or null.
     */
    public static function rates(string $city, string $currency, string $locale, int $cacheMinutes): ?array
    {
        $body = self::get(self::ratesUrl($city, $currency, $locale), $cacheMinutes);
        $cities = (array) ($body['data']['cities'] ?? []);
        return $cities[0] ?? null;
    }

    /**
     * GET and decode, cached for $cacheMinutes in October's cache.
     */
    public static function get(string $url, int $cacheMinutes): array
    {
        if (self::$fetcher) {
            return (self::$fetcher)($url);
        }
        return Cache::remember('coworkingview:'.md5($url), max(1, $cacheMinutes) * 60, function () use ($url) {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withUserAgent('coworkingview-octobercms/1.0 (+https://coworkingview.com)')
                ->get($url);
            if (!$response->successful()) {
                throw new Exception('CoworkingView API answered '.$response->status());
            }
            return (array) $response->json();
        });
    }
}
