<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;

class LoginFormAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Cette méthode détermine si cet authentificateur doit être utilisé pour la requête
     */
    public function supports(Request $request): ?bool
    {
        // On ne gère que les requêtes POST sur /login
        return $request->isMethod('POST') && $request->getPathInfo() === '/login';
    }

    /**
     * Crée un passport pour l'authentification
     */
    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username');
        $password = $request->request->get('_password');
        $csrfToken = $request->request->get('_csrf_token');

        // Débogage
        if (!$csrfToken) {
            throw new AuthenticationException('CSRF token manquant');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken)
            ]
        );
    }

    /**
     * Appelé en cas de succès de l'authentification
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Récupère l'utilisateur connecté
        $user = $token->getUser();

        // Prépare les données de redirection selon le rôle
        $redirectData = [
            'success' => true,
            'redirect' => $this->getRedirectPath($user)
        ];

        return new JsonResponse($redirectData);
    }

    /**
     * Détermine le chemin de redirection en fonction du rôle de l'utilisateur
     */
    private function getRedirectPath($user): string
    {
        // Récupère les rôles de l'utilisateur
        $roles = $user->getRoles();

        // Définit la redirection selon le rôle le plus élevé
        if (in_array('ROLE_ADMINISTRATEUR', $roles)) {
            return '/admin';
        } elseif (in_array('ROLE_STAFF', $roles)) {
            return '/staff';
        } else {
            return '/profile';  // Redirection par défaut pour ROLE_USER
        }
    }
    /**
     * Appelé en cas d'échec de l'authentification
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'success' => false,
            'message' => 'Identifiants invalides. Veuillez réessayer.'
        ], Response::HTTP_UNAUTHORIZED);
    }
}