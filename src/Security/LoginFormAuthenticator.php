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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
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

        if (!$email || !$password) {
            throw new AuthenticationException('Email et mot de passe requis');
        }
        // Débogage
        if (!$csrfToken) {
            throw new AuthenticationException('CSRF token manquant');
        }

        return new Passport(
            new UserBadge($email, function($userIdentifier) {
                // CRITIQUE: Récupérer l'utilisateur complet avec son ID
                $user = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $userIdentifier]);
                
                if (!$user) {
                    throw new UserNotFoundException('Utilisateur non trouvé');
                }
                
                // VERIFICATION: L'utilisateur a bien un ID
                if (!$user->getIdUser()) {
                    throw new AuthenticationException('Utilisateur invalide');
                }
                
                return $user;
            }),
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
        $roles = $user->getRoles();

        // Determine redirect path
        $redirectPath = '/profile'; // Default path
        if (in_array('ROLE_ADMINISTRATEUR', $roles)) {
            $redirectPath = '/admin';
        } elseif (in_array('ROLE_STAFF', $roles)) {
            $redirectPath = '/staff';
        }

        // Si c'est une requête AJAX, retourner du JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Connexion réussie ! Vous allez être redirigé...',
                'redirect' => $redirectPath
            ]);
        }

        // Sinon, faire une redirection normale
        return new RedirectResponse($redirectPath);
    }

    /**
     * Appelé en cas d'échec de l'authentification
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        error_log("ERREUR DE CONNEXION: " . get_class($exception) . " - " . $exception->getMessage());
    
        $message = match(get_class($exception)) {
            UserNotFoundException::class => 'Identifiants ou Mots de passe incorrects',
            default => 'Erreur de connexion: ' . $exception->getMessage()
        };

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'error' => $message
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new RedirectResponse('/login?error=1');
    }
    
}