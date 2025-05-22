<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractJsonApiController extends AbstractController
{
    public const DEFAULT_RESPONSE_CONTENT_TYPE = 'application/json; charset=utf-8';

    protected function getJsonResponseFromArrayData(array $arrayPayload, int $responseCode, string $contentType = self::DEFAULT_RESPONSE_CONTENT_TYPE): JsonResponse
    {
        return new JsonResponse($arrayPayload, $responseCode, ['Content-Type' => $contentType]);
    }

    protected function getJsonResponseFromJsonData(string $jsonPayload, int $responseCode, string $contentType = self::DEFAULT_RESPONSE_CONTENT_TYPE): JsonResponse
    {
        return new JsonResponse($jsonPayload, $responseCode, ['Content-Type' => $contentType], true);
    }

    protected function createNotFoundJsonResponse(string $message): JsonResponse
    {
        return $this->getJsonResponseFromArrayData(['message' => $message], Response::HTTP_NOT_FOUND);
    }

    protected function createValidationFailedResponse(array $errors): JsonResponse
    {
        return $this->getJsonResponseFromArrayData([
            'message' => 'Validation Failed',
            'errors' => $errors
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}