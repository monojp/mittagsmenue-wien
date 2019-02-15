<?php

namespace App\Service;

use App\Nearplace\NearplaceVenues;

class NearplaceService
{
    private $geoService;

    public function __construct(GeoService $geoService)
    {
        $this->geoService = $geoService;
    }

    /**
     * TODO: Get rid of GUI stuff
     * @param bool $votingAllowed
     * @param string $lat
     * @param string $lng
     * @param int $radius
     * @return array
     */
    public function search(bool $votingAllowed, string $lat, string $lng, int $radius): array
    {
        $lat = sprintf('%F', $lat);
        $lng = sprintf('%F', $lng);

        // add custom venues that are in reach
        $response = [];
        foreach (NearplaceVenues::VENUES as $name => $data) {
            $lat_venue = sprintf('%F', $data['lat']);
            $lng_venue = sprintf('%F', $data['lng']);
            if ($this->geoService->distance($lat_venue, $lng_venue, $lat, $lng, false) <= $radius) {
                $maps_href = "https://www.openstreetmap.org/directions?engine=graphhopper_foot&route=$lat,$lng;$lat_venue,$lng_venue";
                $name_escaped = htmlspecialchars($name, ENT_QUOTES);
                $name_escaped = str_replace("'", '', $name_escaped);

                $actions = "<a href='${maps_href}' class='no_decoration lat_lng_link' target='_blank'>
				<div data-enhanced='true' class='ui-link ui-btn ui-icon-location ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' title='OpenStreetMap Route'>OpenStreetMap Route</div>
			</a>";
                if ($votingAllowed) {
                    $actions .= "<div data-enhanced='true' class='ui-link ui-btn ui-icon-plus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_up(\"{$name_escaped}\")' title='Vote Up'>Vote up</div>"
                        . "<div data-enhanced='true' class='ui-link ui-btn ui-icon-minus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_down(\"{$name_escaped}\")' title='Vote Down'>Vote Down</div>";
                }

                $response[] = [
                    'name' => $name,
                    "<a href='{$data['website']}' title='Homepage' target='_blank'>{$name_escaped}</a>",
                    round($this->geoService->distance($lat, $lng, $lat_venue, $lng_venue, false) * 1000),
                    $actions,
                ];
            }
        }

        return $response;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isValidVenue(string $name): bool
    {
        return array_key_exists($name, NearplaceVenues::VENUES);
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function venueWebsite(string $name): ?string
    {
        if (!$this->isValidVenue($name)) {
            return null;
        }

        return NearplaceVenues::VENUES[$name]['website'];
    }
}