<?php

namespace LNDHub\Contracts;

interface LNDHubClient
{
    public function isConnectionValid(): bool;

    public function getInfo(): array;

    public function addInvoice($invoice): array;

    public function getInvoice($checkingId): array;

    public function isInvoicePaid($checkingId): bool;
}
