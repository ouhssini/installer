<?php

namespace SoftCortex\Installer\Services;

class LicenseVerificationResult
{
    public function __construct(
        public bool $valid,
        public ?string $itemName = null,
        public ?string $buyerName = null,
        public ?string $purchaseDate = null,
        public ?string $supportedUntil = null,
        public ?string $licenseType = null,
        public ?string $error = null
    ) {}

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getLicenseType(): ?string
    {
        return $this->licenseType;
    }

    public function isRegularLicense(): bool
    {
        return $this->licenseType === 'regular';
    }

    public function isExtendedLicense(): bool
    {
        return $this->licenseType === 'extended';
    }
}
