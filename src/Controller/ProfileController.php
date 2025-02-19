<?php

namespace App\Controller;

// Imports nécessaires pour le contrôleur
use App\Form\UserProfileType;         // Type de formulaire pour le profil
use App\Repository\UserRepository;     // Repository pour les opérations sur les utilisateurs
use App\Service\FileUploader;           // Recupere les images uploader
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    // Injection de dépendance du UserRepository via le constructeur
    public function __construct(
        private UserRepository $userRepository,
        private FileUploader $fileUploader
    ) {}

    // Route pour afficher le profil utilisateur
    // Accessible uniquement aux utilisateurs connectés (ROLE_USER)
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        // Récupère l'utilisateur connecté 
        $user = $this->userRepository->getUser($this->getUser());
        //creation formulaire
        $form = $this->createForm(UserProfileType::class, $user, [
            'action' => $this->generateUrl('app_profile_edit'),
            'method' => 'POST'
        ]);
        // Rend la vue avec les données de l'utilisateur
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    // Route pour modifier le profil utilisateur
    // Accessible uniquement aux utilisateurs connectés
    #[Route('/profile/edit', name: 'app_profile_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request): Response
    {
        try {
            $user = $this->userRepository->getUser($this->getUser());
            $form = $this->createForm(UserProfileType::class, $user);
            $form->handleRequest($request);
    
            // Si le formulaire est soumis mais invalide, log les erreurs
            if ($form->isSubmitted()) {
                // Récupérer le fichier ici pour qu'il soit disponible dans tout le scope
                $profilePictureFile = $form->get('profilePicture')->getData();
                
                if ($form->isValid()) {
                    if ($profilePictureFile) {
                        try {
                            $fileName = $this->fileUploader->upload($profilePictureFile);
                            $user->setProfilPicture($fileName);
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                            return $this->redirectToRoute('app_profile');
                        }
                    }
                    
                    $this->userRepository->save($user, true);
                    $this->addFlash('success', 'Profil mis à jour avec succès !');
                    
                } else {
                    // Récupérer toutes les erreurs du formulaire
                    foreach ($form->getErrors(true) as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                }
            }
    
            return $this->redirectToRoute('app_profile');
    
        } catch (\Exception $e) {
            
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            return $this->redirectToRoute('app_profile');
        }
    }

    // Route pour l'interface administrateur
    // Accessible uniquement aux administrateurs
    #[Route('/admin', name: 'app_admin')]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function admin(): Response
    {
        // Récupère l'administrateur connecté
        $user = $this->userRepository->getUser($this->getUser());
        
        // Rend la vue admin
        return $this->render('profile/admin.html.twig', [
            'user' => $user
        ]);
    }

    // Route pour l'interface staff
    // Accessible uniquement aux membres du staff
    #[Route('/staff', name: 'app_staff')]
    #[IsGranted('ROLE_STAFF')]
    public function staff(): Response
    {
        // Récupère le membre du staff connecté
        $user = $this->userRepository->getUser($this->getUser());
        
        // Rend la vue staff
        return $this->render('profile/staff.html.twig', [
            'user' => $user
        ]);
    }
}