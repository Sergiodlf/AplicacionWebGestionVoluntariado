<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class JsonAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        $message = $authException ? $authException->getMessage() : 'Authentication Required';
        
        // Return 401 Unauthorized with a JSON body
        return new JsonResponse([
            'error' => 'Unauthorized',
            'message' => $message
        ], 401);
    }
}
