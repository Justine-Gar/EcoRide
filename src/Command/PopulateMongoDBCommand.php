<?php

namespace App\Command;

use App\Document\CarpoolAnalytics;
use App\Repository\CarpoolRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'app:populate-mongodb',
  description: 'Peuple MongoDB avec les données existantes + données de test',
)]
class PopulateMongoDBCommand extends Command
{
  private DocumentManager $documentManager;
  private CarpoolRepository $carpoolRepository;

  public function __construct(DocumentManager $documentManager, CarpoolRepository $carpoolRepository)
  {
    $this->documentManager = $documentManager;
    $this->carpoolRepository = $carpoolRepository;
    parent::__construct();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    try {
      // Test de connexion MongoDB
      $this->documentManager->getClient()->selectDatabase('ecoride_analytics')->command(['ping' => 1]);
      $io->success('✅ Connexion MongoDB réussie');

      $collection = $this->documentManager->getDocumentCollection(CarpoolAnalytics::class);

      // Étape 1: Nettoyer les anciennes données
      $io->section('🧹 Nettoyage des anciennes données');
      $deleteResult = $collection->deleteMany([]);
      $io->info("Supprimé {$deleteResult->getDeletedCount()} anciens documents");

      // Étape 2: Migrer les covoiturages existants
      $io->section('📦 Migration des covoiturages existants');
      $existingCarpools = $this->carpoolRepository->findAll();
      $io->info("Trouvé " . count($existingCarpools) . " covoiturages dans MySQL");

      $migratedCount = 0;
      foreach ($existingCarpools as $carpool) {
        try {
          $analytics = new CarpoolAnalytics();
          $analytics->setCarpoolId($carpool->getIdCarpool())
            ->setUserId($carpool->getUser()->getIdUser())
            ->setAction('created')
            ->setStatus($carpool->getStatut())
            ->setCredits($carpool->getCredits())
            ->setCommissionCredits(4)
            ->setPassengerCount($carpool->getPassengers()->count())
            ->setDepartLocation($carpool->getLocationStart())
            ->setArrivalLocation($carpool->getLocationReach())
            ->setCarpoolDate($carpool->getDateStart())
            ->setCreatedAt($carpool->getDateStart());

          $this->documentManager->persist($analytics);
          $migratedCount++;

          if ($migratedCount % 10 === 0) {
            $this->documentManager->flush();
            $io->text("Migration en cours... $migratedCount covoiturages migrés");
          }
        } catch (\Exception $e) {
          $io->warning("Erreur migration covoiturage {$carpool->getIdCarpool()}: {$e->getMessage()}");
        }
      }

      // Flush final pour les covoiturages existants
      $this->documentManager->flush();
      $io->success("✅ $migratedCount covoiturages migrés depuis MySQL");

      // Étape 3: Ajouter des données de test pour avoir des graphiques intéressants
      $io->section('🎲 Ajout de données de test');

      $currentYear = (int)date('Y');
      $testCarpoolId = 90000; // ID élevé pour éviter les conflits

      // Données de test pour les 6 derniers mois
      $testData = [
        ['month' => 1, 'carpools' => 8, 'year' => $currentYear],
        ['month' => 2, 'carpools' => 12, 'year' => $currentYear],
        ['month' => 3, 'carpools' => 15, 'year' => $currentYear],
        ['month' => 4, 'carpools' => 20, 'year' => $currentYear],
        ['month' => 5, 'carpools' => 25, 'year' => $currentYear],
        ['month' => 6, 'carpools' => 18, 'year' => $currentYear],
      ];

      $testCount = 0;
      foreach ($testData as $monthData) {
        for ($i = 0; $i < $monthData['carpools']; $i++) {
          $randomDay = rand(1, 28);
          $carpoolDate = new \DateTime("{$monthData['year']}-{$monthData['month']}-$randomDay");

          $analytics = new CarpoolAnalytics();
          $analytics->setCarpoolId($testCarpoolId++)
            ->setUserId(1) // User admin
            ->setAction('created')
            ->setStatus(['attente', 'actif', 'terminé', 'annulé'][rand(0, 3)])
            ->setCredits(rand(5, 20))
            ->setCommissionCredits(4)
            ->setPassengerCount(rand(1, 4))
            ->setDepartLocation('Paris Test ' . $i)
            ->setArrivalLocation('Lyon Test ' . $i)
            ->setCarpoolDate($carpoolDate)
            ->setCreatedAt($carpoolDate);

          $this->documentManager->persist($analytics);
          $testCount++;

          if ($testCount % 20 === 0) {
            $this->documentManager->flush();
            $io->text("Génération de données de test... $testCount créés");
          }
        }
      }

      // Flush final
      $this->documentManager->flush();
      $io->success("✅ $testCount données de test ajoutées");

      // Étape 4: Vérification finale
      $io->section('🔍 Vérification finale');
      $totalCount = $collection->countDocuments([]);
      $createdCount = $collection->countDocuments(['action' => 'created']);

      $io->info("Total documents MongoDB: $totalCount");
      $io->info("Documents avec action 'created': $createdCount");

      // Test d'agrégation
      $pipeline = [
        ['$match' => ['action' => 'created']],
        ['$group' => [
          '_id' => [
            'year' => ['$year' => '$carpoolDate'],
            'month' => ['$month' => '$carpoolDate']
          ],
          'count' => ['$sum' => 1]
        ]],
        ['$sort' => ['_id.year' => 1, '_id.month' => 1]]
      ];

      $aggregationResults = $collection->aggregate($pipeline)->toArray();
      $io->info('📊 Données par mois:');
      foreach ($aggregationResults as $result) {
        $year = $result['_id']['year'];
        $month = $result['_id']['month'];
        $count = $result['count'];
        $io->text("  - $month/$year: $count covoiturages");
      }

      if (count($aggregationResults) > 0) {
        $io->success('🎉 MongoDB est maintenant peuplé ! Les graphiques devraient s\'afficher.');
        $io->note('Actualisez la page admin pour voir les données.');
      } else {
        $io->error('❌ Aucune donnée n\'a été générée pour les graphiques.');
      }

      return Command::SUCCESS;
    } catch (\Exception $e) {
      $io->error('❌ Erreur: ' . $e->getMessage());
      $io->text('Stack trace: ' . $e->getTraceAsString());
      return Command::FAILURE;
    }
  }
}
