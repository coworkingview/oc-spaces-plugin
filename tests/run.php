<?php
/**
 * Dependency-free tests: `php tests/run.php`. Stubs the one October class the
 * components extend, so the client and the row mapping run without a CMS.
 */

namespace Cms\Classes {
    if (!class_exists(ComponentBase::class)) {
        class ComponentBase
        {
            public $props = [];
            public function property($name) { return $this->props[$name] ?? null; }
        }
    }
}

namespace {
    require __DIR__.'/../classes/Client.php';
    require __DIR__.'/../components/Spaces.php';
    require __DIR__.'/../components/Rates.php';

    use CoworkingView\Spaces\Classes\Client;
    use CoworkingView\Spaces\Components\Rates;
    use CoworkingView\Spaces\Components\Spaces;

    $failures = 0;
    $count = 0;
    function check($label, $ok) {
        global $failures, $count;
        $count++;
        if (!$ok) { $failures++; echo "FAIL $label\n"; }
    }
    function throws(callable $fn) {
        try { $fn(); } catch (Exception $e) { return true; }
        return false;
    }

    // URLs
    check('spaces url', Client::spacesUrl('berlin', 'private_office', 5, 'en')
        === 'https://api.coworkingview.com/v1/properties?locale=en&city=berlin&pageSize=5&pricingOptionType=private_office');
    check('limit clamped to 50', strpos(Client::spacesUrl('berlin', '', 500), 'pageSize=50') !== false);
    check('unknown type dropped', strpos(Client::spacesUrl('berlin', 'desk'), 'pricingOptionType') === false);
    check('unknown locale falls back', strpos(Client::spacesUrl('berlin', '', 5, 'fr'), 'locale=en') !== false);
    check('bad city throws', throws(fn () => Client::spacesUrl('New York')));
    check('rates url', Client::ratesUrl('madrid', 'usd') === 'https://api.coworkingview.com/v1/market/rates?locale=en&city=madrid&currency=USD');
    check('bad currency dropped', strpos(Client::ratesUrl('madrid', 'euro'), 'currency') === false);
    check('space url localized', Client::spaceUrl('a-b', 'es') === 'https://coworkingview.com/es/oficina/a-b'
        && Client::spaceUrl('a-b', 'de') === 'https://coworkingview.com/de/buero/a-b');
    check('money', Client::money(['amount' => 290, 'currency' => 'EUR']) === 'EUR 290'
        && Client::money(['amount' => 12.5, 'currency' => 'GBP']) === 'GBP 12.50'
        && Client::money(null) === '');

    // Spaces mapping, through a fake fetcher
    Client::$fetcher = fn ($url) => ['data' => [
        ['slug' => 'a', 'title' => 'A', 'fromPrice' => ['amount' => 290, 'currency' => 'EUR'], 'fromBillingPeriod' => 'month'],
        ['slug' => 'b', 'title' => 'B'],
        ['title' => 'no slug'],
    ]];
    $spaces = Client::spaces('berlin', '', 5, 'en', 60);
    check('spaces mapped', count($spaces) === 2 && $spaces[0]['url'] === 'https://coworkingview.com/en/workspace/a'
        && $spaces[0]['priceLabel'] === 'from EUR 290/month' && $spaces[1]['priceLabel'] === '');

    // Spaces component
    $c = new Spaces();
    $c->props = ['city' => 'berlin', 'type' => '', 'limit' => '2', 'locale' => 'en', 'cacheMinutes' => '60'];
    $c->onRun();
    check('component lists', count($c->spaces) === 2 && $c->error === null);
    $c->props['city'] = 'Not A Slug';
    $c->onRun();
    check('component error hidden from visitors', $c->spaces === [] && $c->error !== null && $c->showError === false);

    // Rates
    Client::$fetcher = fn ($url) => ['data' => ['cities' => [[
        'city' => ['slug' => 'madrid', 'name' => 'Madrid '],
        'figures' => [
            ['optionType' => 'fix_desk', 'median' => ['amount' => 260, 'currency' => 'EUR'], 'propertyCount' => 7],
            ['optionType' => 'flex_desk'],
        ],
    ]]]];
    $r = new Rates();
    $r->props = ['city' => 'madrid', 'currency' => '', 'locale' => 'en', 'cacheMinutes' => '60'];
    $r->onRun();
    check('rates rows', $r->rows === [['label' => 'Fixed desk', 'median' => 'EUR 260', 'spaces' => 7]] && $r->cityName === 'Madrid');
    check('rates empty city', Rates::rowsFrom(null) === []);

    echo $failures ? "\n$failures of $count failed\n" : "$count passed\n";
    exit($failures ? 1 : 0);
}
