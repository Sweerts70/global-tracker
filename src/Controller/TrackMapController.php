<?php

namespace App\Controller;

use App\Entity\TrackPoint;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrackMapController extends AbstractController
{
    #[Route('/', name: 'track_map', methods: ['GET'])]
    public function map(): Response
    {
        return $this->render('track/map.html.twig');
    }

    #[Route('/api/trackpoints', name: 'api_trackpoints', methods: ['GET'])]
    public function points(EntityManagerInterface $em): JsonResponse
    {
        $points = $em->getRepository(TrackPoint::class)->findBy([], ['addedOn' => 'ASC']);

        $maxGapNm = 1000.0;

        // NEW: if more than 3 days between updates, start a new segment
        $maxGapSeconds = 3 * 24 * 60 * 60; // 3 days

        $segments = [];
        $currentSegment = [];

        $prevLat = null;
        $prevLon = null;
        $prevAddedOn = null; /** @var \DateTimeInterface|null */

        foreach ($points as $p) {
            $lat = (float) $p->getLat();
            $lon = (float) $p->getLon();
            $addedOn = $p->getAddedOn(); // DateTimeInterface

            $startNewSegment = false;

            if ($prevLat !== null && $prevLon !== null && $prevAddedOn !== null) {
                // a) Distance gap
                $gapNm = $this->haversineNm($prevLat, $prevLon, $lat, $lon);
                if ($gapNm > $maxGapNm) {
                    $startNewSegment = true;
                }
                // b) Time gap
                $gapSeconds = $addedOn->getTimestamp() - $prevAddedOn->getTimestamp();
                if ($gapSeconds > $maxGapSeconds) {
                    $startNewSegment = true;
                }

                if ($startNewSegment) {
                    if (count($currentSegment) >= 2) {
                        $segments[] = $currentSegment;
                    }
                    $currentSegment = [];
                }
            }

            $currentSegment[] = [$lat, $lon];

            $prevLat = $lat;
            $prevLon = $lon;
            $prevAddedOn = $addedOn;
        }

        if (count($currentSegment) >= 2) {
            $segments[] = $currentSegment;
        }

        $pointsOut = [];
        foreach ($points as $p) {
            $pointsOut[] = [
                'lat' => (float) $p->getLat(),
                'lon' => (float) $p->getLon(),
                'addedOn' => $p->getAddedOn()->format('Y-m-d H:i') . ' UTC',
                'message' => $p->getMessage(),
            ];
        }

        $latestUtc = null;
        if (!empty($points)) {
            $latestUtc = end($points)->getAddedOn()->format('Y-m-d H:i');
        }

        return $this->json([
            'segments' => $segments,
            'points' => $pointsOut,
            'latestUtc' => $latestUtc,
        ]);
    }

    /**
     * Great-circle distance (Haversine) in nautical miles.
     */
    private function haversineNm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000.0;

        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2 - $lat1);
        $dLambda = deg2rad($lon2 - $lon1);

        $a = sin($dPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($dLambda / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $meters = $earthRadiusMeters * $c;

        return $meters / 1852.0; // meters → nautical miles
    }
}
