<?php
namespace Security\Exceptions;

class SessionLocationException extends SessionSecurityException
{
    private $oldLocation;
    private $newLocation;

    public function __construct(string $message, array $oldLocation = null, array $newLocation = null)
    {
        parent::__construct($message);
        $this->oldLocation = $oldLocation;
        $this->newLocation = $newLocation;
    }

    public function getOldLocation(): ?array
    {
        return $this->oldLocation;
    }

    public function getNewLocation(): ?array
    {
        return $this->newLocation;
    }

    public function getLocationContext(): array
    {
        return [
            'old_location' => $this->oldLocation,
            'new_location' => $this->newLocation,
            'distance' => $this->calculateDistance()
        ];
    }

    private function calculateDistance(): ?float
    {
        if (!$this->oldLocation || !$this->newLocation) {
            return null;
        }

        $geoService = new \Security\GeoLocationService(require __DIR__ . '/../geoip_config.php');
        return $geoService->calculateDistance($this->oldLocation, $this->newLocation);
    }
}
