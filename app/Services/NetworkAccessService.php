<?php

namespace App\Services;

use InvalidArgumentException;

class NetworkAccessService
{
    public function allowClient(string $mac): bool
    {
        $mac = $this->normalizeMac($mac);
        $safeMac = escapeshellarg($mac);

        exec("sudo -n /usr/local/bin/gofi-allow-client {$safeMac} 2>&1", $output, $code);

        return $code === 0;
    }

    public function denyClient(string $mac): bool
    {
        $mac = $this->normalizeMac($mac);
        $safeMac = escapeshellarg($mac);

        exec("sudo -n /usr/local/bin/gofi-deny-client {$safeMac} 2>&1", $output, $code);

        return $code === 0;
    }

    private function normalizeMac(string $mac): string
    {
        $mac = strtolower(trim($mac));

        if (!preg_match('/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/', $mac)) {
            throw new InvalidArgumentException('Invalid MAC address format.');
        }

        return $mac;
    }

    public function getMacFromIp(string $ip): ?string
    {
        $output = [];
        exec("arp -n " . escapeshellarg($ip), $output);

        foreach ($output as $line) {
            if (preg_match('/([0-9a-f]{2}:){5}[0-9a-f]{2}/i', $line, $matches)) {
                return strtolower($matches[0]);
            }
        }

        return null;
    }
}
