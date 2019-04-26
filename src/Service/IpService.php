<?php

namespace App\Service;

class IpService
{
    private const LOCALHOSTS = [
        '127.0.0.1',
        '::1',
        '[127.0.0.1]',
        '[::1]',
    ];

    /**
     * @param string $ip
     * @param string $range
     * @return bool
     */
    public function cidrMatch(string $ip, string $range): bool
    {
        [$subnet, $bits] = explode('/', $range);
        if ($bits === null) {
            $bits = 32;
        }
        $ipInt = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask; // nb: in case the supplied subnet wasn't correctly aligned
        return ($ipInt & $mask) === $subnet;
    }

    /**
     * @param string $ip
     * @return bool
     */
    public function isInternIp(string $ip): bool
    {
        return in_array($ip, self::LOCALHOSTS, true)
            || $this->cidrMatch($ip, getenv('ALLOW_VOTING_IP_NET'));
    }

}