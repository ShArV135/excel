<?php

namespace AppBundle\Service\Report;

use Symfony\Component\HttpFoundation\Request;

class SaleExportConfig
{
    public const MODE_MANAGER = 'MODE_MANAGER';
    public const MODE_GENERAL_MANAGER = 'MODE_GENERAL_MANAGER';

    private $mode;
    private $marginCol = false;
    private $debtCol = false;

    public static function fromRequest(Request $request): self
    {
        $self = new self();

        if ($request->get('margin_col')) {
            $self->setMarginCol(true);
        }

        if ($request->get('debt_col')) {
            $self->setDebtCol(true);
        }

        return $self;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function isManagerMode(): bool
    {
        return $this->mode === self::MODE_MANAGER;
    }

    public function isGeneralMode(): bool
    {
        return $this->mode === self::MODE_GENERAL_MANAGER;
    }

    public function isMarginCol(): bool
    {
        return $this->marginCol;
    }

    public function setMarginCol(bool $marginCol): void
    {
        $this->marginCol = $marginCol;
    }

    public function isDebtCol(): bool
    {
        return $this->debtCol;
    }

    public function setDebtCol(bool $debtCol): void
    {
        $this->debtCol = $debtCol;
    }
}
