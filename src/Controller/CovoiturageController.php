<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CovoiturageController extends AbstractController
{
  #[Route('/covoiturage', name: 'app_covoiturage')]
  public function index(Request $request): Response
  {
    return $this->render('covoiturage/index.html.twig');
  }
}