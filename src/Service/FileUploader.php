<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
  public function __construct(
    private string $targetDirectory,
    private SluggerInterface $slugger
  ){}

  public function upload(UploadedFile $file): string
  {
    //Genere un nom de fichier unique
    //Exemple : "Ma photo été.jpg
    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //$originalFilename = "Ma photo été"

    //le slugger va transformer ce nom en version sécurisée
    $safeFilename = $this->slugger->slug($originalFilename);
    //$safeFilename = "ma-photo-ete"

    //rajoute un identifient unique 
    $fileName = $safeFilename.'_'.uniqid().'.'.$file->guessExtension();
    //Final = "ma-photo-ete-64f3d21b8c3e9.jpg"

    try {
      // Déplace le fichier de manière sécurisée
      $file->move($this->getTargetDirectory(), $fileName);
    } catch (FileException $e) {
        throw new \Exception('Une erreur est survenue lors de l\'upload du fichier');
    }

    return $fileName;
  }

  public function getTargetDirectory(): string
  {
    return $this->targetDirectory;
  }
}