<?php

declare(strict_types=1);

namespace Encuestas\Web;

use Encuestas\Config;

/**
 * Datos de la petición que dependen de la topología de red. Las cabeceras
 * X-Forwarded-* solo se aceptan cuando la petición llega desde un proxy
 * declarado en TRUSTED_PROXIES (IPs o rangos CIDR separados por comas);
 * de lo contrario cualquier cliente podría falsificar su IP o el esquema.
 */
final class RequestContext
{
    /**
     * @param list<string> $trustedProxies IPs o rangos CIDR de proxies de confianza.
     * @param array<string, mixed> $server Normalmente $_SERVER.
     */
    public function __construct(
        private readonly array $trustedProxies,
        private readonly array $server,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $proxies = array_values(array_filter(array_map('trim', explode(',', Config::get('TRUSTED_PROXIES')))));
        return new self($proxies, $_SERVER);
    }

    /**
     * IP real del cliente. Si la conexión viene de un proxy de confianza se
     * recorre X-Forwarded-For de derecha a izquierda saltando los proxies
     * confiables; la primera IP no confiable es el cliente.
     */
    public function clientIp(): string
    {
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '');
        if ($remote === '' || !$this->isTrustedProxy($remote)) {
            return $remote;
        }

        $forwardedFor = (string) ($this->server['HTTP_X_FORWARDED_FOR'] ?? '');
        $chain = array_values(array_filter(array_map('trim', explode(',', $forwardedFor))));
        for ($i = count($chain) - 1; $i >= 0; $i--) {
            if (!$this->isTrustedProxy($chain[$i])) {
                return $chain[$i];
            }
        }
        // Toda la cadena es de confianza (o venía vacía): se usa el extremo conocido.
        return $chain[0] ?? $remote;
    }

    /** true si la petición original usó HTTPS (directo, o según el proxy de confianza). */
    public function isHttps(): bool
    {
        $https = (string) ($this->server['HTTPS'] ?? '');
        if ($https !== '' && $https !== 'off') {
            return true;
        }
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '');
        return $remote !== ''
            && $this->isTrustedProxy($remote)
            && ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function host(): string
    {
        return (string) ($this->server['HTTP_HOST'] ?? 'localhost');
    }

    /** URL absoluta hacia esta aplicación (para los enlaces enviados por correo). */
    public function absoluteUrl(string $path): string
    {
        return ($this->isHttps() ? 'https' : 'http') . '://' . $this->host() . $path;
    }

    private function isTrustedProxy(string $ip): bool
    {
        foreach ($this->trustedProxies as $trusted) {
            if (str_contains($trusted, '/') ? self::inCidr($ip, $trusted) : $ip === $trusted) {
                return true;
            }
        }
        return false;
    }

    /** Comprueba pertenencia a un rango CIDR (IPv4 e IPv6). */
    private static function inCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $ipBinary = inet_pton($ip);
        $subnetBinary = inet_pton($subnet);
        $prefix = (int) $bits;
        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        $remainderBits = $prefix % 8;
        if ($fullBytes > 0 && substr($ipBinary, 0, $fullBytes) !== substr($subnetBinary, 0, $fullBytes)) {
            return false;
        }
        if ($remainderBits === 0) {
            return true;
        }
        $mask = 0xFF << (8 - $remainderBits) & 0xFF;
        return (ord($ipBinary[$fullBytes]) & $mask) === (ord($subnetBinary[$fullBytes]) & $mask);
    }
}
