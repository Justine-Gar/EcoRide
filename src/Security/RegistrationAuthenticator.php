<?php

namespace App\Security;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\RoleRepository;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationAuthenticator extends AbstractAuthenticator
{
  public function __construct(
    private EntityManagerInterface $entityManager,
    private UserPasswordHasherInterface $passwordHasher,
    private RoleRepository $roleRepository
  )
  {}
  
  public function supports(Request $request): ?bool
  {
    return $request->isMethod('POST') && $request->getPathInfo() === '/register';
  }

  public function authenticate(Request $request): Passport
  {
    return new Passport(
      new UserBadge('not-used'),
      new PasswordCredentials('not-used')
    );
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
  {
    throw new \LogicException('Cette méthode ne devrait jamais etre appelée');
  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
  {
    // Récupérer les données envoyées depuis le formulaire
    // Nous gérons deux formats possibles: JSON ou form-data
    $formData = json_decode($request->getContent(), true) ?? $request->request->all();

    // Vérification que toutes les données requises sont présentes
    if (
      !isset($formData['firstname']) || !isset($formData['name']) || !isset($formData['email']) ||
      !isset($formData['phone_number']) || !isset($formData['password']) || !isset($formData['confirm_password']) ||
      !isset($formData['terms'])
    ) {
      return new JsonResponse(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
    }

    // Vérification que les mots de passe correspondent
    if ($formData['password'] !== $formData['confirm_password']) {
      return new JsonResponse(['error' => 'Les mots de passe ne correspondent pas'], Response::HTTP_BAD_REQUEST);
    }

    // Vérification que les conditions sont acceptées
    if (!$formData['terms']) {
      return new JsonResponse(['error' => 'Vous devez accepter les conditions d\'utilisation'], Response::HTTP_BAD_REQUEST);
    }

    // Vérification du rôle sélectionné
    if (!isset($formData['role']) || !in_array($formData['role'], ['Passager', 'Conducteur'])) {
      return new JsonResponse(['error' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
    }

    // Vérification si l'utilisateur existe déjà avec cet email
    $existingUser = $this->entityManager->getRepository(User::class)->findOneByEmail($formData['email']);
    if ($existingUser) {
      return new JsonResponse(['error' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
    }

    // Création du nouvel utilisateur
    $user = new User();
    $user->setFirstname($formData['firstname']);
    $user->setName($formData['name']);
    $user->setEmail($formData['email']);
    $user->setPhoneNumber($formData['phone_number']);
    $user->setCredits(0); // Par défaut, l'utilisateur commence avec 0 crédit

    // Hachage du mot de passe pour sécuriser le stockage
    $hashedPassword = $this->passwordHasher->hashPassword($user, $formData['password']);
    $user->setPassword($hashedPassword);

    // Attribution du rôle sélectionné par l'utilisateur
    $selectedRole = $formData['role'];
    $role = $this->roleRepository->findByName($selectedRole);
    if (!$role) {
      // Si le rôle n'existe pas dans la base de données, nous le créons
      $role = new Role();
      $role->setNameRole($selectedRole);
      $this->entityManager->persist($role); // Sauvegarde le nouveau rôle
    }
    $user->addRole($role); // Ajoute le rôle à l'utilisateur

    // Persistance de l'utilisateur en base de données
    $this->entityManager->persist($user); // Prépare l'insertion de l'utilisateur
    $this->entityManager->flush();        // Exécute toutes les requêtes SQL en attente

    // Retourne une réponse JSON indiquant le succès de l'inscription
    return new JsonResponse([
      'success' => true,
      'message' => 'Inscription réussie, vous pouvez maintenant vous connecter'
    ], Response::HTTP_CREATED); // Code 201 Created
  }
  
}

