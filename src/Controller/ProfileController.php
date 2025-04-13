<?php

namespace App\Controller;

// Imports nécessaires pour le contrôleur
use App\Form\UserProfileType;         // Type de formulaire pour le profil
use App\Form\CarType;                  ///Type forme pour voiture
use App\Entity\Car;
use App\Repository\CarRepository;                  
use App\Repository\UserRepository;     // Repository pour les opérations sur les utilisateurs
use App\Repository\ReviewRepository;
use App\Repository\CarpoolRepository;
use App\Repository\RoleRepository;
use App\Service\FileUploader;           // Recupere les images uploader
use Doctrine\ORM\EntityManagerInterface;
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
        private FileUploader $fileUploader,
        private EntityManagerInterface $entityManager,
        private CarRepository $carRepository,
        private ReviewRepository $reviewRepository,
        private CarpoolRepository $carpoolRepository
    ) {}

    // Route pour afficher le profil utilisateur
    // Accessible uniquement aux utilisateurs connectés (ROLE_USER)
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(CarpoolRepository $carpoolRepository, RoleRepository $roleRepository): Response
    {
        // Récupère
        $user = $this->userRepository->getUser($this->getUser());
        $usersCars = $this->carRepository->findByUser($user);
        // Récupérer les rôles directement depuis la base de données
        $conducteurRole = $roleRepository->findByName('Conducteur');
        $passagerRole = $roleRepository->findByName('Passager');

        // Récupérer les covoiturages "actif" et "terminé" de l'utilisateur
        $activeCarpoolsAsDriver = $carpoolRepository->findActiveCarpoolsAsDriver($user);
        $completedCarpoolsAsDriver = $carpoolRepository->findCompletedCarpoolsAsDriver($user);
            
        // Récupérer les covoiturages où l'utilisateur est passager
        $activeCarpoolsAsPassenger = $carpoolRepository->findActiveCarpoolsAsPassenger($user);
        $completedCarpoolsAsPassenger = $carpoolRepository->findCompletedCarpoolsAsPassenger($user);

        //creation formulaire user
        $form = $this->createForm(UserProfileType::class, $user, [
            'action' => $this->generateUrl('app_profile_edit'),
            'method' => 'POST'
        ]);

        //creation formulaire voiture
        $car = new Car();
        $carForm = $this->createForm(CarType::class, $car, [
            'action' => $this->generateUrl('app_profile_add_car'),
            'method' => 'POST'
        ]);

        // Rend la vue avec les données de l'utilisateur
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'cars' => $usersCars,
            'carForm' => $carForm->createView(),
            'activeCarpoolsAsDriver' => $activeCarpoolsAsDriver,
            'completedCarpoolsAsDriver' => $completedCarpoolsAsDriver,
            'activeCarpoolsAsPassenger' => $activeCarpoolsAsPassenger,
            'completedCarpoolsAsPassenger' => $completedCarpoolsAsPassenger,
            'conducteurRoleId' => $conducteurRole ? $conducteurRole->getIdRole() : 3,
            'passagerRoleId' => $passagerRole ? $passagerRole->getIdRole() : 4
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

    //Route pour ajouter une voiture sur le profil utilisateur
    #[Route('/profile/add-car', name: 'app_profile_add_car')]
    #[IsGranted('ROLE_USER')]
    public function addCar(Request $request): Response
    {
        $car = new Car();
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $car->setUser($this->getUser());
            $this->carRepository->save($car, true);

            $this->addFlash('succes', 'Véhicule ajouté avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->redirectToRoute('app_profile');
    }

    //Route pour supprimer une voiture sur profil user
    #[Route('/profile/car/delete/{id}', name: 'app_profile_delete_car')]
    #[IsGranted('ROLE_USER')]
    public function deleteCar(int $id, CarRepository $carRepository, EntityManagerInterface $entityManager): Response
    {
        $car = $carRepository->find($id);

        if (!$car) {
            $this->addFlash('error', 'Véhicule non trouvé.');
            return $this->redirectToRoute('app_profile'); // Redirection vers la page de profil
        }

        $entityManager->remove($car);
        $entityManager->flush();

        $this->addFlash('success', 'Véhicule supprimé avec succès.');

        return $this->redirectToRoute('app_profile'); // Change si nécessaire
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