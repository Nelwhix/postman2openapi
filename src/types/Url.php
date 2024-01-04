<?php declare(strict_types=1);

namespace types;

class Url
{
    private ?string $raw;
    public ?array $path;
    private ?string $query;
    public ?string $protocol;
    public ?string $host;
    public ?string $port;
    public ?bool $valid;
    private ?array $pathVars;

    public function __construct(?string $fixedUrl = null) {
        $this->raw = null;
        $this->path = null;
        $this->query = null;
        $this->protocol = null;
        $this->host = null;
        $this->port = null;
        $this->valid = null;
        $this->pathVars = null;

        if ($fixedUrl !== null) {
            $parsedUrl = parse_url($fixedUrl);
            if (!$parsedUrl) {
                return;
            }
            if (isset($parsedUrl['path'])) {
                $this->path = $parsedUrl['path'];
            }
            if (isset($parsedUrl['query'])) {
                $this->query = $parsedUrl['query'];
            }
            if (isset($parsedUrl['scheme'])) {
                $this->protocol = $parsedUrl['scheme'];
            }
            if (isset($parsedUrl['host'])) {
                $this->host = $parsedUrl['host'];
            }
            if (isset($parsedUrl['port'])) {
                $this->port = $parsedUrl['port'];
            }
            $this->valid = true;
        }
    }

    public function setValid(bool $valid): void {
        $this->valid = $valid;
    }

    public function setRawUrl(string $rawUrl): void {
        $this->raw = $rawUrl;
    }

    public function setPathVars(?array $pathVars): void {
        if ($pathVars === null) {
            $this->pathVars = [];
            return;
        }

        $this->pathVars = array_reduce($pathVars, function ($carry, $item) {
            $carry[$item['key']] = [
              'value' => $item['value'],
              'description' => $item['description'],
              'type' =>  gettype($item['value'])
            ];
        }, []);
    }
}