<?php

namespace App\Controller;

use App\Entity\FoodVoteNote;
use App\Exception\InvalidVoteIdentifierException;
use App\Exception\VotingNotAllowedException;
use App\Service\NearplaceService;
use App\Service\VenueService;
use App\Service\VoteService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VoteController extends JsonAlertController
{
    private $venueService;
    private $voteService;
    private $nearplaceService;
    private $validator;

    public function __construct(VenueService $venueService, VoteService $voteService, NearplaceService $nearplaceService, ValidatorInterface $validator)
    {
        $this->venueService = $venueService;
        $this->voteService = $voteService;
        $this->nearplaceService = $nearplaceService;
        $this->validator = $validator;
    }

    /**
     * @param Request $request
     * @return string
     * @throws InvalidVoteIdentifierException
     */
    private function getIdentifier(Request $request): string
    {
        $identifier = $request->get('identifier');

        if ($identifier
            && !in_array($identifier, VoteService::VOTES_VALID_SPECIAL, true)
            && !$this->venueService->isValidVenue($identifier)
            && !$this->nearplaceService->isValidVenue($identifier)) {
            throw new InvalidVoteIdentifierException("vote identifier {$identifier} is not valid");
        }

        return (string)$identifier;
    }

    /**
     * @Route("/vote/all", name="vote_all")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws VotingNotAllowedException
     */
    public function all(Request $request)
    {
        $ip = $request->getClientIp();
        $this->voteService->assertVotingDisplayAllowed($ip);
        return $this->json([
            'voting_over' => time() >= getenv('VOTING_OVER_TIME'),
            'html' => $this->voteService->summaryHtml($ip, $this->voteService->getAllVoteData($request->get('timestamp'))),
        ]);
    }

    /**
     * @Route("/vote/delete", name="vote_delete")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws VotingNotAllowedException
     */
    public function delete(Request $request)
    {
        $ip = $request->getClientIp();
        $this->voteService->assertVotingAllowed($ip);
        $this->voteService->voteDelete($request->get('timestamp'), $ip);

        return $this->all($request);
    }

    /**
     * @Route("/vote/delete_part", name="vote_delete_part")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws VotingNotAllowedException
     * @throws InvalidVoteIdentifierException
     */
    public function delete_part(Request $request)
    {
        $ip = $request->getClientIp();
        $this->voteService->assertVotingAllowed($ip);
        $this->voteService->voteDelete($request->get('timestamp'), $ip, $this->getIdentifier($request));

        return $this->all($request);
    }

    /**
     * @Route("/vote/up", name="vote_up")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws InvalidVoteIdentifierException
     * @throws VotingNotAllowedException
     */
    public function up(Request $request)
    {
        $ip = $request->getClientIp();

        $this->voteService->assertVotingAllowed($ip);
        $errors = $this->voteService->voteHelper(
            $request->get('timestamp'),
            $ip,
            $this->getIdentifier($request),
            'up'
        );

        return ($errors && count($errors)) ? $this->jsonAlertConstraintViolation($errors)
            : $this->all($request);
    }

    /**
     * @Route("/vote/down", name="vote_down")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws InvalidVoteIdentifierException
     * @throws VotingNotAllowedException
     */
    public function down(Request $request)
    {
        $ip = $request->getClientIp();

        $this->voteService->assertVotingAllowed($ip);
        $errors = $this->voteService->voteHelper(
            $request->get('timestamp'),
            $ip,
            $this->getIdentifier($request),
            'down'
        );

        return ($errors && count($errors)) ? $this->jsonAlertConstraintViolation($errors)
            : $this->all($request);
    }

    /**
     * @Route("/vote/special", name="vote_special")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws InvalidVoteIdentifierException
     * @throws VotingNotAllowedException
     */
    public function special(Request $request)
    {
        $ip = $request->getClientIp();

        $this->voteService->assertVotingAllowed($ip);
        $errors = $this->voteService->voteHelper(
            $request->get('timestamp'),
            $ip,
            'special',
            $this->getIdentifier($request)
        );

        return ($errors && count($errors)) ? $this->jsonAlertConstraintViolation($errors)
            : $this->all($request);
    }

    /**
     * @Route("/vote/set_note", name="vote_set_note")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws VotingNotAllowedException
     */
    public function set_note(Request $request)
    {
        $ip = $request->getClientIp();
        $note = trim($request->get('note'));

        $this->voteService->assertVotingAllowed($ip);

        $foodVoteNote = (new FoodVoteNote())
            ->setNote($note);
        $errors = $this->validator->validate($foodVoteNote);
        if ($errors && count($errors)) {
            return $this->jsonAlertConstraintViolation($errors);
        }

        $errors = $this->voteService->voteHelper(
            $request->get('timestamp'),
            $ip,
            'special',
            $foodVoteNote->getNote()
        );

        return ($errors && count($errors)) ? $this->jsonAlertConstraintViolation($errors)
            : $this->all($request);
    }
}
