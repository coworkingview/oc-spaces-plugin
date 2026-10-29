<p align="center">
  <img src="https://coworkingview.com/brand/coworkingview-wordmark.svg" alt="CoworkingView" width="320">
</p>

# CoworkingView Spaces for October CMS

Show coworking spaces, private offices and what workspace costs in a city on any October CMS page,
with the prices operators publish. Data comes from the [CoworkingView](https://coworkingview.com)
catalogue through its public API: **no API key, no account**.

- **Coworking Spaces** (`coworkingSpaces`): a list of spaces in a city, filtered by workspace type,
  each with its district and entry price, linked to its page with the full offer.
- **Coworking Market Rates** (`coworkingRates`): the median monthly price of a private office,
  fixed desk or flex desk in a city, with how many spaces each figure comes from. These are the
  figures of the [Coworking Price Index](https://coworkingview.com/en/tools/price-index).

Documentation: [coworkingview.com/en/mcp](https://coworkingview.com/en/mcp) (API, OpenAPI, MCP server
and the other integrations).

## Install

```bash
composer require coworkingview/spaces-plugin
```

Or from the backend: **Settings → System → Updates & Plugins → Install Plugins**, search for
"CoworkingView".

## Use

Add a component to a page or layout and render it:

```twig
title = "Coworking in Berlin"
url = "/coworking-berlin"

[coworkingSpaces]
city = "berlin"
type = "private_office"
limit = 5
==
{% component 'coworkingSpaces' %}
```

```twig
[coworkingRates]
city = "madrid"
showCredit = 1
==
{% component 'coworkingRates' %}
```

### Properties

| Component | Property | Default | Notes |
| --- | --- | --- | --- |
| both | `city` | `berlin` / `madrid` | City slug, as in [coworkingview.com/en/coworking/berlin](https://coworkingview.com/en/coworking/berlin) |
| both | `locale` | `en` | `en`, `de` or `es`: names, descriptions and links |
| both | `cacheMinutes` | `720` | How long a response is kept in October's cache |
| coworkingSpaces | `type` | any | `private_office`, `fix_desk`, `flex_desk`, `day_pass`, `meeting_room`, `virtual_office`, `event_space`, `membership`, `exclusive_bereich` |
| coworkingSpaces | `limit` | `5` | 1 to 50 |
| coworkingRates | `currency` | the city's | ISO code such as `USD` to convert the figures |
| coworkingRates | `showCredit` | off | Adds "Data: CoworkingView Price Index (CC BY 4.0)" |

Both components render nothing for visitors when the data is unavailable; a signed-in backend user
sees why. Override the markup by copying `components/spaces/default.htm` or
`components/rates/default.htm` into your theme's `partials/coworkingSpaces/` or `partials/coworkingRates/`.

Cities include [Berlin](https://coworkingview.com/en/coworking/berlin),
[Munich](https://coworkingview.com/en/coworking/munich), [Madrid](https://coworkingview.com/en/coworking/madrid),
[Barcelona](https://coworkingview.com/en/coworking/barcelona), [London](https://coworkingview.com/en/coworking/london),
[Amsterdam](https://coworkingview.com/en/coworking/amsterdam) and more than 70 others.

## Reading the numbers

Market rates are medians over the spaces that publish a price; each row shows how many spaces it
comes from. Prices are what operators publish: each space links to its page with the current offer.
The market figures are licensed CC BY 4.0, so turn on `showCredit` when you republish them.

## Privacy

Your server calls `api.coworkingview.com` when the cache is empty, sending the component's
parameters. Visitors' browsers never contact CoworkingView and no cookies are set.

## Development

```bash
php tests/run.php
```

## License

MIT © [CoworkingView](https://coworkingview.com)
