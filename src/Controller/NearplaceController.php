<?php

namespace App\Controller;

use App\Service\NearplaceService;
use App\Service\VoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NearplaceController extends AbstractController
{
    /**
     * @Route("/nearbysearch", name="nearbysearch")
     * @param NearplaceService $nearplaceService
     * @param VoteService $voteService
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function nearbysearch(NearplaceService $nearplaceService, VoteService $voteService, Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        // TODO use Twig for GUI-Stuff
        return $this->json(
            $nearplaceService->search(
                $voteService->votingAllowed($request->getClientIp()),
                getenv('LOCATION_FALLBACK_LAT'),
                getenv('LOCATION_FALLBACK_LNG'),
                getenv('LOCATION_DEFAULT_DISTANCE')
            )
        );
    }

}