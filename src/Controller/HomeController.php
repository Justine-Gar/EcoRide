<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'title' => 'Bienvenue sur EcoRide',
            'depart' => '',
            'arrivee' => '',
            'date' => '',
            'selectedCarpool' => null
        ]);
    }
}
