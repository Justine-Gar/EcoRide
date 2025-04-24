<?php

namespace App\Controller;

use App\Entity\PreferenceType;
use App\Entity\UserPreference;
use App\Repository\PreferenceTypeRepository;
use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;

//Route pour gerer les preference user ou systeme
#[Route('/profile/preferences')]
#[isGranted('ROLE_USER')]
class UserPreferenceController extends AbstractController
{
    #[Route('/add', name: 'app_user_preference_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager, PreferenceTypeRepository $preferenceTypeRepo): Response
    {
        $user = $this->getUser();

        $preferenceName = $request->request->get('preference_name');
        $preferenceValue = $request->request->get('preference_value');

        if (!$preferenceName || !$preferenceValue) {
            $this->addFlash('error', 'Le nom et la valeur de la préférence sont requis.');
            return $this->redirectToRoute('app_profile');
        }

        $preferenceType = new PreferenceType();
        $preferenceType->setName($preferenceName);
        $preferenceType->setSystem(false);
        $preferenceType->setUser($user);

        $entityManager->persist($preferenceType);

        $userPreference = new UserPreference();
        $userPreference->setUser($user);
        $userPreference->setPreferenceType($preferenceType);
        $userPreference->setChooseValue($preferenceValue);

        $entityManager->persist($userPreference);
        $entityManager->flush();

        $this->addFlash('success', 'Votre préférence a été ajoutée avec succès.');
        return $this->redirectToRoute('app_profile');
    }


    #[Route('/toggle-system', name: 'app_user_preference_toggle', methods: ['POST'])]
    public function toggleSystem(Request $request, EntityManagerInterface $entityManager, PreferenceTypeRepository $preferenceTypeRepo, UserPreferenceRepository $userPreferenceRepo): Response
    {
        $user = $this->getUser();

        $preferenceIds = $request->request->all('system_preferences');
        if (!is_array($preferenceIds)) {
            $preferenceIds = [];
        }

        $systemPreferences = $preferenceTypeRepo->findSystemPreferences();

        foreach ($systemPreferences as $preference) {
            $preferenceId = $preference->getIdPreferenceType();

            $existingPref = $userPreferenceRepo->findOneBy([
                'user' => $user,
                'preferenceType' => $preference
            ]);

            if (in_array((string)$preferenceId, $preferenceIds)) {
                if (!$existingPref) {
                    $userPreference = new UserPreference();
                    $userPreference->setUser($user);
                    $userPreference->setPreferenceType($preference);
                    $userPreference->setChooseValue('oui');

                    $entityManager->persist($userPreference);
                }
            } else {
                if ($existingPref) {
                    $entityManager->remove($existingPref);
                }
            }
        }

        $entityManager->flush();

        $this->addFlash('success', 'Vos préférences ont été mises à jour avec succès.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/delete/{id}', name: 'app_user_preference_delete', methods: ['POST'])]
    public function delete(UserPreference $userPreference, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Verifie que la préférence appartient à l'user
        if ($userPreference->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette préférence.');
        }

        $preferenceType = $userPreference->getPreferenceType();
        if (!$preferenceType->isSystem()) {
            $entityManager->remove($preferenceType);
        }

        $entityManager->remove($userPreference);
        $entityManager->flush();

        $this->addFlash('success', 'La préférence a été supprimée avec succès.');
        return $this->redirectToRoute('app_profile');
    }
}
