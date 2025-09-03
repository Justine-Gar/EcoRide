<?php

namespace App\Service;

use App\Document\CarpoolAnalytics;
use App\Entity\Carpool;
use Doctrine\ODM\MongoDB\DocumentManager;

class CarpoolAnalyticsService
{
  private DocumentManager $documentManager;

  public function __construct(DocumentManager $documentManager)
  {
    $this->documentManager = $documentManager;
  }

  /**
   * Teste la connexion MongoDB
   */
  public function testConnection(): bool
  {
    try {
      error_log('TEST: Tentative de connexion MongoDB...');
      error_log('URL: ' . $_ENV['MONGODB_URL'] ?? 'URL non définie');
        
      $result = $this->documentManager->getClient()
          ->selectDatabase('ecoride_analytics')
          ->command(['ping' => 1]);
        
      error_log('MongoDB ping success: ' . json_encode($result->toArray()));
      return true;
    } catch (\Exception $e) {
      error_log('MongoDB connection failed: ' . $e->getMessage());
      error_log('Stack trace: ' . $e->getTraceAsString());
      return false;
    }
  }

  /**
   * Enregistre la création d'un covoiturage
   */
  public function logCarpoolCreated(Carpool $carpool): void
  {
    try {
      if (!$this->testConnection()) {
        return;
      }

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
        ->setCarpoolDate($carpool->getDateStart());

      $this->documentManager->persist($analytics);
      $this->documentManager->flush();
    } catch (\Exception $e) {
      error_log('Erreur lors de la création d\'analytics pour le covoiturage ' . $carpool->getIdCarpool() . ': ' . $e->getMessage());
    }
  }


  /**
   * Mise a jour sur le status d'un covoiturage
   */
  public function logCarpoolStatusUpdate(Carpool $carpool, string $action): void
  {
    try {
      if (!$this->testConnection()) {
        return;
      }

      $repository = $this->documentManager->getRepository(CarpoolAnalytics::class);
      $analytics = $repository->findOneBy(['carpoolId' => $carpool->getIdCarpool()]);

      if ($analytics) {
        $analytics->setAction($action)
          ->setStatus($carpool->getStatut())
          ->setPassengerCount($carpool->getPassengers()->count());
      } else {
        $analytics = new CarpoolAnalytics();
        $analytics->setCarpoolId($carpool->getIdCarpool())
          ->setUserId($carpool->getUser()->getIdUser())
          ->setAction($action)
          ->setStatus($carpool->getStatut())
          ->setCredits($carpool->getCredits())
          ->setCommissionCredits(4)
          ->setPassengerCount($carpool->getPassengers()->count())
          ->setDepartLocation($carpool->getLocationStart())
          ->setArrivalLocation($carpool->getLocationReach())
          ->setCarpoolDate($carpool->getDateStart());

        $this->documentManager->persist($analytics);
      }

      $this->documentManager->flush();
    } catch (\Exception $e) {
      error_log('Erreur lors de la mise à jour d\'analytics pour le covoiturage ' . $carpool->getIdCarpool() . ' (action: ' . $action . '): ' . $e->getMessage());
    }
  }


  /**
   * Compte les analytics
   */
  public function countAnalytics(): int
  {
    try {
      if (!$this->testConnection()) {
        return 0;
      }
      
      $collection = $this->documentManager->getDocumentCollection(CarpoolAnalytics::class);
      return $collection->countDocuments([]);
    } catch (\Exception $e) {
      error_log('Erreur lors du comptage des analytics: ' . $e->getMessage());
      return 0;
    }
  }

  /**
   * Récupération des stats des covoiturage par mois
   */
  public function getCarpoolsData(): array
  {
    try {
      if (!$this->testConnection()) {
        throw new \Exception('MongoDB non disponible');
      }

      $collection = $this->documentManager->getDocumentCollection(CarpoolAnalytics::class);
      
      // Covoiturages par mois
      $pipeline = [
        // Étape 1 : Filtrage
        [
          '$match' => [
            'action' => 'created'
          ]
        ],
        // Étape 2 : Groupement par année/mois
        [
          '$group' => [
            '_id' => [
              'year' => ['$year' => '$carpoolDate'],
              'month' => ['$month' => '$carpoolDate']
            ],
            'count' => ['$sum' => 1]
          ]
        ],
        // Étape 3 : Tri
        [
          '$sort' => [
            '_id.year' => 1,
            '_id.month' => 1
          ]
        ]
      ];

      $results = $collection->aggregate($pipeline)->toArray();

      // Statistiques par statut
      $statusPipeline = [
        [
          '$group' => [
            '_id' => '$status',
            'count' => ['$sum' => 1]
          ]
        ]
      ];

      $statusResults = $collection->aggregate($statusPipeline)->toArray();
      $statusCounts = [
        'attente' => 0,
        'actif' => 0,
        'terminé' => 0,
        'annulé' => 0
      ];

      foreach ($statusResults as $result) {
        $status = $result['_id'];
        if (isset($statusCounts[$status])) {
          $statusCounts[$status] = $result['count'];
        }
      }

      // Formatage des données pour le graphique
      $labels = [];
      $data = [];
      
      $moisNoms = [
        1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr', 
        5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
      ];

      foreach ($results as $result) {
        $moisNom = $moisNoms[$result['_id']['month']];
        $annee = $result['_id']['year'];
        $label = $moisNom . ' ' . $annee;
        
        $labels[] = $label;
        $data[] = $result['count'];
      }

      $totalCarpools = array_sum($data);

      return [
        'labels' => $labels,
        'datasets' => [
          [
            'label' => 'Covoiturages créés par mois',
            'data' => $data,
            'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
            'borderColor' => 'rgba(54, 162, 235, 1)',
            'borderWidth' => 1
          ]
        ],
        'stats' => [
          'total' => $totalCarpools,
          'average' => count($data) > 0 ? round($totalCarpools / count($data), 1) : 0,
          'byStatus' => $statusCounts
        ]
      ];
    } catch (\Exception $e) {
      throw new \Exception('Erreur lors de la récupération des données de covoiturages : ' . $e->getMessage());
    }
  }


  /**
   * Récupération des stats des crédits par mois
   */
  public function getCreditsData(): array
  {
    try {
      if (!$this->testConnection()) {
        throw new \Exception('MongoDB non disponible');
      }

      $collection = $this->documentManager->getDocumentCollection(CarpoolAnalytics::class);

      $pipeline = [
        [
          '$match' => [
            'action' => 'created'
          ]
        ],
        [
          '$group' => [
            '_id' => [
              'year' => ['$year' => '$carpoolDate'],
              'month' => ['$month' => '$carpoolDate']
            ],
            'totalCredits' => ['$sum' => '$commissionCredits']
          ]
        ],
        [
          '$sort' => [
            '_id.year' => 1,
            '_id.month' => 1
          ]
        ]
      ];

      $results = $collection->aggregate($pipeline)->toArray();

      $labels = [];
      $data = [];
      
      $moisNoms = [
        1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr', 
        5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
      ];
      
      foreach ($results as $result) {
        $moisNom = $moisNoms[$result['_id']['month']];
        $annee = $result['_id']['year'];
        $label = $moisNom . ' ' . $annee;
        
        $labels[] = $label;
        $data[] = $result['totalCredits'];
      }

      $totalCredits = array_sum($data);

      return [
        'labels' => $labels,
        'datasets' => [
          [
            'label' => 'Crédits de commission par mois',
            'data' => $data,
            'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
            'borderColor' => 'rgba(75, 192, 192, 1)',
            'borderWidth' => 1
          ]
        ],
        'stats' => [
          'total' => $totalCredits,
          'average' => count($data) > 0 ? round($totalCredits / count($data), 1) : 0
        ]
      ];
    } catch (\Exception $e) {
      throw new \Exception('Erreur lors de la récupération des données de crédits : ' . $e->getMessage());
    }
  }

  public function getDocumentManager(): DocumentManager
  {
    return $this->documentManager;
  }
  /**
   * Migre les données existantes vers MongoDB (à exécuter une seule fois)
   */
  public function migrateExistingData(array $carpools): int
  {

    $migrated = 0;
    $errors = [];
    error_log('MIGRATION START: ' . count($carpools) . ' covoiturages à migrer');

    try {
      foreach ($carpools as $carpool) {
        try {
        // Vérifier si déjà migré
          $repository = $this->documentManager->getRepository(CarpoolAnalytics::class);
          $existing = $repository->findOneBy(['carpoolId' => $carpool->getIdCarpool()]);
          if ($existing) {
              error_log('MIGRATION SKIP: Covoiturage ' . $carpool->getIdCarpool() . ' déjà migré');
              continue;
          }
          error_log('MIGRATION PROCESS: Covoiturage ' . $carpool->getIdCarpool());

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
            ->setCreatedAt($carpool->getDateStart()); // Utiliser la date du covoiturage comme date de création

          $this->documentManager->persist($analytics);
          error_log('MIGRATION PERSIST: Covoiturage ' . $carpool->getIdCarpool() . ' persisté');
          
          $migrated++;
        } catch (\Exception $e) {
          $error = 'Erreur migration covoiturage ' . $carpool->getIdCarpool() . ': ' . $e->getMessage();
          error_log('MIGRATION ERROR: ' . $error);
          $errors[] = $error;
        }
      }

      // FLUSH EN UNE SEULE FOIS
      if ($migrated > 0) {
        error_log('MIGRATION FLUSH: Tentative de flush de ' . $migrated . ' éléments');
        $this->documentManager->flush();
        error_log('MIGRATION SUCCESS: Flush réussi pour ' . $migrated . ' éléments');
      } else {
        error_log('MIGRATION WARNING: Aucun élément à flusher');
      }

    } catch (\Exception $e) {
      error_log('MIGRATION FATAL ERROR: ' . $e->getMessage());
      throw new \Exception('Erreur Migration Analytics: ' . $e->getMessage());
    }

    if (!empty($errors)) {
      error_log('MIGRATION ERRORS: ' . implode(', ', $errors));
    }

    error_log('MIGRATION END: ' . $migrated . ' covoiturages migrés avec succès');
    return $migrated;
  }
}