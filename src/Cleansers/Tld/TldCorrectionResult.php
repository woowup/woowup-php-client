<?php

namespace WoowUp\Cleansers\Tld;

class TldCorrectionResult
{
    public bool $wasCorrected;
    public bool $isIrrecoverable;
    public ?string $correctedDomain;

    private function __construct(bool $wasCorrected, bool $isIrrecoverable, ?string $correctedDomain)
    {
        $this->wasCorrected     = $wasCorrected;
        $this->isIrrecoverable  = $isIrrecoverable;
        $this->correctedDomain  = $correctedDomain;
    }

    public static function unchanged(string $domain): self
    {
        return new self(false, false, $domain);
    }

    public static function corrected(string $domain): self
    {
        return new self(true, false, $domain);
    }

    public static function irrecoverable(): self
    {
        return new self(false, true, null);
    }
}
