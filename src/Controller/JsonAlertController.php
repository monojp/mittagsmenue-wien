<?php

namespace App\Controller;

use /** @noinspection PhpDeprecationInspection */
    Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/** @noinspection PhpDeprecationInspection */

class JsonAlertController extends Controller
{
    use JsonMessageTrait;

    /**
     * @param string $message
     * @param int|null $width
     * @param string|null $break
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function jsonAlert(string $message, ?int $width = null, ?string $break = null): \Symfony\Component\HttpFoundation\JsonResponse
    {
        return $this->json($this->jsAlertMessage($message, $width, $break));
    }

    /**
     * @param ConstraintViolationListInterface $constraintViolationList
     * @param int|null $width
     * @param string|null $break
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function jsonAlertConstraintViolation(ConstraintViolationListInterface $constraintViolationList, ?int $width = null, ?string $break = null): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $errors = [];
        /** @var  ConstraintViolation $constraintViolation */
        foreach ($constraintViolationList as $constraintViolation) {
            $errors[] = $constraintViolation->getMessage();
        }

        return $this->jsonAlert('Validation error: ' . implode($break, $errors), $width, $break);
    }
}