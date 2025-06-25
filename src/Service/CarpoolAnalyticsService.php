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
   * Enregistre la création d'un covoiturage
   */
  public function logCarpoolCreated(Carpool $carpool): void
  {
    try {
      $analytics = new CarpoolAnalytics();
      $analytics->setCarpoolId($carpool->getIdCarpool())
        ->setUserId($carpool->getUser()->getIdUser())
        ->setAction('created')
        ->setStatus($carpool->getStatut())
        ->setCredits($carpool->getCredits())
        ->setCommissionCredits(4) // Commission fixe de 4 crédits
        ->setPassengerCount($carpool->getPassengers()->count())
        ->setDepartLocation($carpool->getLocationStart())
        ->setArrivalLocation($carpool->getLocationReach())
        ->setCarpoolDate($carpool->getDateStart());

      $this->documentManager->persist($analytics);
      $this->documentManager->flush();

    } catch (\Exception $e)
    {
      error_log('Erreur analytics lors de la création d\'un covoiturage: ' . $e->getMessage());
    }
  }


  /**
   * Mise a jour sur le status d'un covoiturage
   */
  public function logCarpoolStatusUpdate(Carpool $carpool, string $action): void
  {
    try {
      // Récupérer l'analytics existant
      $repository = $this->documentManager->getRepository(CarpoolAnalytics::class);
      $analytics = $repository->findOneBy(['carpoolId' => $carpool->getIdCarpool()]);

      if ($analytics) {
        $analytics->setAction($action)
          ->setStatus($carpool->getStatut())
          ->setPassengerCount($carpool->getPassengers()->count());
      } else {
        // Si pas trouvé, créer un nouveau (cas de fallback)
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
      error_log('Erreur analytics lors de l\'updates d\'un covoiturage: ' . $e->getMessage());
    }
  }


  /**
   * Récupération des stats des covoiturage des 30 derniers jours
   */
  public function getCarpoolsData(): array
  {
    try {
      $collection = $this->documentManager->getDocumentCollection(CarpoolAnalytics::class);

      // Date d'aujourd'hui
      $today = new \DateTime('now');
      $today->setTime(23, 59, 59);

      // Date d'il y a 30 jours
      $thirtyDaysAgo = (new \DateTime('now'))->modify('-29 days');
      $thirtyDaysAgo->setTime(0, 0, 0);

      // Initialisation du tableau avec toutes les dates et 0 covoiturage
      $dates = [];
      $carpoolsPerDay = [];

      for ($i = 0; $i < 30; $i++) {
        $date = clone $thirtyDaysAgo;
        $date->modify("+$i days");
        $dateStr = $date->format('Y-m-d');
        $dates[] = $dateStr;
        $carpoolsPerDay[$dateStr] = 0;
      }

      // Agrégation MongoDB pour compter les covoiturages créés par jour
      $pipeline = [
        [
          '$match' => [
            'action' => 'created',
            'createdAt' => [
              '$gte' => $thirtyDaysAgo,
              '$lte' => $today
            ]
          ]
        ],
        [
          '$group' => [
            '_id' => [
              'year' => ['$year' => '$createdAt'],
              'month' => ['$month' => '$createdAt'],
              'day' => ['$dayOfMonth' => '$createdAt']
            ],
            'count' => ['$sum' => 1]
          ]
        ]
      ];

      $results = $collection->aggregate($pipeline)->toArray();

      // Remplir le tableau avec les vraies données
      foreach ($results as $result) {
        $date = sprintf(
          '%04d-%02d-%02d',
          $result['_id']['year'],
          $result['_id']['month'],
          $result['_id']['day']
        );
        if (isset($carpoolsPerDay[$date])) {
          $carpoolsPerDay[$date] = $result['count'];
        }
      }

      // Statistiques par statut
      $statusPipeline = [
        [
          '$match' => [
            'createdAt' => [
              '$gte' => $thirtyDaysAgo,
              '$lte' => $today
            ]
          ]
        ],
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

      // Conversion pour Chart.js
      $labels = array_map(function ($date) {
        return (new \DateTime($date))->format('d/m');
      }, $dates);

      $data = array_values($carpoolsPerDay);
      $totalCarpools = array_sum($data);
      $averageCarpools = count($data) > 0 ? $totalCarpools / count($data) : 0;

      return [
        'labels' => $labels,
        'datasets' => [
          [
            'label' => 'Covoiturages créés',
            'data' => $data,
            'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
            'borderColor' => 'rgba(54, 162, 235, 1)',
            'borderWidth' => 1
          ]
        ],
        'stats' => [
          'total' => $totalCarpools,
          'average' => round($averageCarpools, 1),
          'byStatus' => $statusCounts
        ]
      ];
    } catch (\Exception $e) {
      throw new \Exception('Erreur lors de la récupération des données de covoiturages : ' . $e->getMessage());
    }
  }


  /**
   * Récupération des stats des crédits des 30 derniers jours
   */
  public function getCreditsData(): array
  {
    try {
      $collection = $this->documentManager->getDocumentCollection(CarpoolAnalytics::class);

      // Date d'aujourd'hui
      $today = new \DateTime('now');
      $today->setTime(23, 59, 59);

      // Date d'il y a 30 jours
      $thirtyDaysAgo = (new \DateTime('now'))->modify('-29 days');
      $thirtyDaysAgo->setTime(0, 0, 0);

      // Initialisation du tableau avec toutes les dates et 0 crédit
      $dates = [];
      $creditsByDay = [];

      for ($i = 0; $i < 30; $i++) {
        $date = clone $thirtyDaysAgo;
        $date->modify("+$i days");
        $dateStr = $date->format('Y-m-d');
        $dates[] = $dateStr;
        $creditsByDay[$dateStr] = 0;
      }

      // Agrégation MongoDB pour calculer les crédits gagnés par jour (commission)
      $pipeline = [
        [
          '$match' => [
            'action' => 'created',
            'createdAt' => [
              '$gte' => $thirtyDaysAgo,
              '$lte' => $today
            ]
          ]
        ],
        [
          '$group' => [
            '_id' => [
              'year' => ['$year' => '$createdAt'],
              'month' => ['$month' => '$createdAt'],
              'day' => ['$dayOfMonth' => '$createdAt']
            ],
            'totalCredits' => ['$sum' => '$commissionCredits']
          ]
        ]
      ];

      $results = $collection->aggregate($pipeline)->toArray();

      // Remplir le tableau avec les vraies données
      foreach ($results as $result) {
        $date = sprintf(
          '%04d-%02d-%02d',
          $result['_id']['year'],
          $result['_id']['month'],
          $result['_id']['day']
        );
        if (isset($creditsByDay[$date])) {
          $creditsByDay[$date] = $result['totalCredits'];
        }
      }

      // Conversion pour Chart.js
      $labels = array_map(function ($date) {
        return (new \DateTime($date))->format('d/m');
      }, $dates);

      $data = array_values($creditsByDay);
      $totalCredits = array_sum($data);
      $averageCredits = count($data) > 0 ? $totalCredits / count($data) : 0;

      return [
        'labels' => $labels,
        'datasets' => [
          [
            'label' => 'Crédits reçus',
            'data' => $data,
            'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
            'borderColor' => 'rgba(75, 192, 192, 1)',
            'borderWidth' => 1
          ]
        ],
        'stats' => [
          'total' => $totalCredits,
          'average' => round($averageCredits, 1)
        ]
      ];
    } catch (\Exception $e) {
      throw new \Exception('Erreur lors de la récupération des données de crédits : ' . $e->getMessage());
    }
  }

  /**
   * Migre les données existantes vers MongoDB (à exécuter une seule fois)
   */
  public function migrateExistingData(array $carpools): int
  {
    $migrated = 0;

    try {
      foreach ($carpools as $carpool) {
        // Vérifier si déjà migré
        $repository = $this->documentManager->getRepository(CarpoolAnalytics::class);
        $existing = $repository->findOneBy(['carpoolId' => $carpool->getIdCarpool()]);

        if (!$existing) {
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
          $migrated++;
        }
      }

      $this->documentManager->flush();
    } catch (\Exception $e) {
      error_log('Erreur Migration Analytics: ' . $e->getMessage());
    }

    return $migrated;
  }
}