<?php

namespace App\Controller;

use App\Legacy\ContainerHelper;
use App\Service\VenueService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class VenueController extends JsonAlertController
{
    private $venueService;

    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    /**
     * @Route("/venue/{name}/menu", name="venue_menu")
     * @param string $name
     * @param Request $request
     * @param SessionInterface $session
     * @return Response
     */
    public function menu(string $name, Request $request, SessionInterface $session): Response
    {
        global $timestamp;
        $timestamp = $request->get('timestamp');

        // Close session to speed up parallel requests
        if ($session->isStarted()) {
            $session->save();
        }

        // Make service container available for legacy code FIXME: get rid of this
        ContainerHelper::getInstance()->setContainer($this->container);

        $venue = $this->venueService->venueFromName($name);
        if ($venue) {
            return Response::create($venue->getMenuData());
        }

        return $this->jsonAlert("{$name} is not a valid venue class");
    }
}