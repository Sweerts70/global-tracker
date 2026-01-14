<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\TrackPoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class TrackMapController extends AbstractController
{
    private const ALLOWED_SENDERS = [
        '+41798494718',
        'xxxxx',
    ];

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

    #[Route('/sms/inbound', name: 'telnyx_sms_inbound', methods: ['POST'])]
    public function smsInbound(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $raw = $request->getContent();

        // Log access
        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/sms_inbound_access.log',
            sprintf("[%s] %s %s\n", date('c'), $request->getClientIp(), $raw),
            FILE_APPEND
        );

        // Log raw Telnyx payload (debug)
        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/telnyx_inbound.log',
            $raw . PHP_EOL,
            FILE_APPEND
        );

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            // Bad JSON - still return 200 to avoid retries
            return new JsonResponse(['status' => 'ignored']);
        }

        // Only process inbound SMS events
        if (($payload['data']['event_type'] ?? '') !== 'message.received') {
            return new JsonResponse(['status' => 'ignored']);
        }

        $fromRaw = $payload['data']['payload']['from']['phone_number'] ?? '';
        $from    = $this->normalizePhone($fromRaw);

        // Whitelist
        $allowed = array_map([$this, 'normalizePhone'], self::ALLOWED_SENDERS);
        if (!in_array($from, $allowed, true)) {
            // Silently ignore unapproved senders (HTTP 200)
            return new JsonResponse(['status' => 'rejected']);
        }

        $text = (string)($payload['data']['payload']['text'] ?? '');

        // Extract coordinates
        $coords = $this->parseIridiumLatLon($text);
        if ($coords === null) {
            // No coords found; ignore (or you could store message-only points if you want)
            return new JsonResponse(['status' => 'no_coords']);
        }

        // Format to match DECIMAL(9,6) storage (string)
        $latStr = number_format($coords['lat'], 6, '.', '');
        $lonStr = number_format($coords['lon'], 6, '.', '');

        // Create TrackPoint
        $tp = new TrackPoint();
        $tp->setAddedOn(new \DateTime('now', new \DateTimeZone('UTC')));
        $tp->setLat($latStr);
        $tp->setLon($lonStr);

        // Set message/source based on sender
        if ($from === '+41798494718') {
            $tp->setMessage('Sent by phone');
            $tp->setSource('Phone');
        } else {
            $tp->setMessage('Sent by Iridium 9575 Extreme');
            $tp->setSource('Iridium');
        }

        $em->persist($tp);
        $em->flush();

        return new JsonResponse([
            'status' => 'ok',
            'from'   => $from,
            'lat'    => $latStr,
            'lon'    => $lonStr,
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

    private function normalizePhone(string $number): string
    {
        // keep leading +, strip everything else
        return preg_replace('/(?!^\+)[^\d]/', '', trim($number));
    }

    private function parseIridiumLatLon(string $text): ?array
    {
        // Primary: supports integers and decimals
        if (preg_match('/Lat([+-]\d+(?:\.\d+)?)\s+Lon([+-]\d+(?:\.\d+)?)/i', $text, $m)) {
            return ['lat' => (float) $m[1], 'lon' => (float) $m[2]];
        }

        // Fallback: from the URL
        if (preg_match('/[?&]lat=([+-]?\d+(?:\.\d+)?).*?[?&]lon=([+-]?\d+(?:\.\d+)?)/i', $text, $m)) {
            return ['lat' => (float) $m[1], 'lon' => (float) $m[2]];
        }

        return null;
    }
}
