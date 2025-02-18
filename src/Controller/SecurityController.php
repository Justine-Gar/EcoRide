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
    public function login(Request $request): JsonResponse
    {
        try {
            // Récupère les données du formulaire
            $email = $request->request->get('_username');
            $password = $request->request->get('_password');

            // Vérifie si l'utilisateur est déjà connecté en utilisant le nouveau service Security
            $user = $this->security->getUser();

            if ($user) {
                // Si l'utilisateur est connecté, renvoie une réponse succès
                return new JsonResponse([
                    'success' => true,
                    'redirect' => $this->generateUrl('app_home')
                ]);
            }

            // Si l'utilisateur n'est pas connecté, lance une exception
            throw new AuthenticationException('Identifiants invalides');
        } catch (AuthenticationException $e) {
            // En cas d'erreur, renvoie un message d'erreur
            return new JsonResponse([
                'success' => false,
                'message' => 'Identifiants invalides. Veuillez réessayer.'
            ], 401);
        }
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode sera interceptée par le firewall
        throw new \LogicException('Cette méthode ne devrait jamais être appelée.');
    }
}
