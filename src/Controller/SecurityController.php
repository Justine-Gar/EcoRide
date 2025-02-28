<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SecurityController extends AbstractController
{
    public function __construct(private Security $security) // Injection du service Security
    {}

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse(['message' => 'Cette route est protégée par l\'authentificateur.']);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode sera interceptée par le firewall
        throw new \LogicException('Cette méthode ne devrait jamais être appelée.');
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(): JsonResponse
    {
        return new JsonResponse(['message' => 'Cette route est protégée par l\'authenticateur']);
    }
}
