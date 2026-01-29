<?php
// src/EventSubscriber/VisitorLogSubscriber.php

namespace App\EventSubscriber;

use App\Entity\VisitorLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

final class VisitorLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $ipHashSecret, // set in services.yaml from env
        // optionally inject a GeoIP service here (see section 4)
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $req = $event->getRequest();

        // Skip assets / profiler / your API endpoint if you want
        $path = $req->getPathInfo();
        if (str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler') || str_starts_with($path, '/assets') || str_starts_with($path, '/api/')) {
            return;
        }

        $ip = $this->getBestClientIp($req);
        $ipTrunc = $this->truncateIp($ip);

        $log = new VisitorLog();
        $log->setMethod($req->getMethod());
        $log->setHost($req->getHost());
        $log->setPath($path);
        $log->setRoute($req->attributes->get('_route'));
        $log->setQueryString($req->getQueryString());
        $log->setUserAgent($req->headers->get('user-agent'));
        $log->setReferer($req->headers->get('referer'));
        $log->setAcceptLanguage($req->headers->get('accept-language'));
        $log->setDnt($this->parseDnt($req));
        $log->setIpTruncated($ipTrunc);
        $log->setIpHash($ip ? hash_hmac('sha256', $ip, $this->ipHashSecret) : null);

        // Optional: geo enrichment (see section 4)
        // $this->geoEnrich($log, $ip);

        $this->em->persist($log);
        $this->em->flush();
    }

    private function parseDnt(Request $req): ?bool
    {
        $dnt = $req->headers->get('dnt');
        if ($dnt === null) return null;
        return $dnt === '1';
    }

    /**
     * If you’re behind a reverse proxy (Cloudflare/Nginx), configure Symfony Trusted Proxies
     * and then getClientIp() becomes reliable.
     */
    private function getBestClientIp(Request $req): ?string
    {
        return $req->getClientIp();
    }

    private function truncateIp(?string $ip): ?string
    {
        if (!$ip) return null;

        // IPv4: zero last octet: a.b.c.0
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return sprintf('%s.%s.%s.0', $parts[0], $parts[1], $parts[2]);
        }

        // IPv6: keep first 3 hextets, zero rest (very coarse)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $hextets = explode(':', $ip);
            $hextets = array_pad($hextets, 8, '0');
            return strtolower(sprintf('%s:%s:%s::', $hextets[0], $hextets[1], $hextets[2]));
        }

        return null;
    }
}
