<?php

namespace WoowUp\Cleansers\Tld;

class TldCorrectionResult
{
    /** @var bool */
    public $wasCorrected;
    /** @var bool */
    public $isIrrecoverable;
    /** @var string|null */
    public $correctedDomain;

    private function __construct($wasCorrected, $isIrrecoverable, $correctedDomain)
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
