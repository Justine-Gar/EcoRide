<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MentionController extends AbstractController
{
  #[Route('/mention', name: 'app_mention')]
  public function index(Request $request): Response
  {
    return $this->render('mention/index.html.twig');
  }
}