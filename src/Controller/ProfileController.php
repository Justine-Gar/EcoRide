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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;


class ProfileController extends AbstractController
{
    private $requestStack;
    private $security;

    // Injection de dépendance 
    public function __construct(
        private UserRepository $userRepository,
        private FileUploader $fileUploader,
        private CarRepository $carRepository,
        private ReviewRepository $reviewRepository,
        private CarpoolRepository $carpoolRepository,
        RequestStack $requestStack,
        Security $security
    ) {
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    // Route pour afficher le profil utilisateur
    // Accessible uniquement aux utilisateurs connectés (ROLE_USER)
    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(CarpoolRepository $carpoolRepository, RoleRepository $roleRepository): Response
    {
        // Récupère
        $user = $this->userRepository->getUser($this->getUser());
        $usersCars = $this->carRepository->findByUser($user);

        // Récupérer les covoiturages en tant que conducteur
        
        // Récupérer les covoiturage en tant que passager

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
            'carForm' => $carForm->createView()
        ]);
    }

    //Route pour changer le role actif d'un utilisateur
    // Accessible uniquement aux utilisateur connectés
    #[Route('/profile/switch-role/{roleName}', name: 'app_profile_switch_role', methods: 'GET')]
    #[IsGranted('ROLE_USER')]
    public function switchRole(string $roleName, RoleRepository $roleRepository, TokenStorageInterface $tokenStorage, RequestStack $requestStack): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('Utilisateur non connecté');
            }
            
            $roleRepository->setUserMainRole($user, $roleName);

            //Symfony ne garde pas le nouveau token dans la session, obliger de créer un nouveau est de le forcer a etre stocker a nouveau
            // Rafraîchit le token de sécurité
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);
            // Forcer le stokage session
            $request = $requestStack->getCurrentRequest();
            $request->getSession()->set('_security_main', serialize($token));

            return new JsonResponse(['success' => true, 'role' => $roleName]);
        }
        catch (\Exception $e) {
            error_log("Erreur lors du changement de rôle: " . $e->getMessage());

            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    
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
    // Accessible uniquement aux utilisateurs connectés
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
    // Accessible uniquement aux utilisateurs connectés
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

    //Route pour la création de covoiturage
    // Accessible uniquement aux utilisateurs connectés
    #[Route('/profile/new-carpool', name: 'app_profile_new_carpool', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function newCarpool(Request $request, CarpoolRepository $carpoolRepository): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non Connecté'
            ], 403);
        }
        
        $carId = $request->request->get('car_id');
        if (!$carId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Veuillez sélectionner un véhicule'
            ], 400);
        }
        // Récupérer la voiture
        $car = $this->carRepository->find($carId);
        if (!$car) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Véhicule introuvable'
            ], 404);
        }
        // Vérifier que la voiture appartient à l'utilisateur
        if ($car->getUser() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce véhicule ne vous appartient pas'
            ], 403);
        }



        try {

             // Créer une date de départ
            $dateStart = $request->request->get('date_start');
            $hourStart = $request->request->get('hour_start');
            $dateTime = new \DateTime("$dateStart $hourStart");
            
            // Calculer l'heure d'arrivée (2h après le départ)
            $dateTimeArrivee = clone $dateTime;
            $dateTimeArrivee->modify('+2 hours');

            $data = [
                'date_start' => $dateTime->format('Y-m-d'),
                'hour_start' => $dateTime->format('H:i:s'),
                'location_start' => $request->request->get('location_start'),
                'date_reach' => $dateTimeArrivee->format('Y-m-d'),
                'hour_reach' => $dateTimeArrivee->format('H:i:s'),
                'location_reach' => $request->request->get('location_reach'),
                'nbr_places' => $request->request->get('nbr_places'),
                'credits' => $request->request->get('credits'),
                'lat_start' => $request->request->get('lat_start'),
                'lng_start' => $request->request->get('lng_start'),
                'lat_reach' => $request->request->get('lat_reach'),
                'lng_reach' => $request->request->get('lng_reach'),
                'car_id' => $carId
            ];



            $carpool = $carpoolRepository->createCarpool($user, $data);

            $this->addFlash('success', 'Votre covoiturage a été créé avec succès !');

            return new JsonResponse([
                'success' => true,
                'id' => $carpool->getIdCarpool(),
                'redirect' => $this->generateUrl('app_profile')
            ]);

        } catch (\Exception $e) {

            error_log("Erreur lors de la création du covoiturage: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
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