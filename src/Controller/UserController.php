<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends JsonAlertController
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/user/config/set", name="user_config_set")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function userConfigSet(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $errors = $this->userService->setUserConfig(
            $request->getClientIp(),
            $request->get('name'),
            $request->get('email'),
            filter_var($request->get('vote_reminder'), FILTER_VALIDATE_BOOLEAN),
            filter_var($request->get('voted_mail_only'), FILTER_VALIDATE_BOOLEAN),
            filter_var($request->get('vote_always_show'), FILTER_VALIDATE_BOOLEAN)
        );

        return ($errors && count($errors)) ? $this->jsonAlertConstraintViolation($errors)
            : $this->json(true);
    }
}