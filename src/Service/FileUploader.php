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
        try {
            // Log des informations du fichier
            error_log('Début upload fichier');
            error_log('Fichier reçu : ' . $file->getClientOriginalName());
            error_log('Type MIME : ' . $file->getMimeType());
            error_log('Taille : ' . $file->getSize());

            // Récupérer et sécuriser le nom du fichier
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            error_log('Nom sécurisé : ' . $fileName);
            error_log('Dossier cible : ' . $this->getTargetDirectory());

            // Vérifier si le dossier existe
            if (!file_exists($this->getTargetDirectory())) {
                error_log('Création du dossier cible');
                mkdir($this->getTargetDirectory(), 0777, true);
            }

            // Déplacer le fichier
            $file->move($this->getTargetDirectory(), $fileName);
            error_log('Fichier déplacé avec succès');

            return $fileName;

        } catch (\Exception $e) {
            error_log('Erreur dans FileUploader: ' . $e->getMessage());
            throw $e;
        }
    }



  // Getter pour récupérer le dossier cible
  public function getTargetDirectory(): string
  {
    return $this->targetDirectory;
  }
}