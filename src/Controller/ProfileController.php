<?php
// src/Controller/ProfileController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        // Récupère l'utilisateur connecté
        $user = $this->getUser();
        
        return $this->render('profile/index.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/admin', name: 'app_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        $user = $this->getUser();
        
        return $this->render('profile/admin.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/staff', name: 'app_staff')]
    #[IsGranted('ROLE_STAFF')]
    public function staff(): Response
    {
        $user = $this->getUser();
        
        return $this->render('profile/staff.html.twig', [
            'user' => $user
        ]);
    }
}