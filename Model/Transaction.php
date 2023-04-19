<?php

declare(strict_types=1);

namespace EginKaman\AbaFileGenerator\Model;

use EginKaman\AbaFileGenerator\Contracts\Transaction as TransactionContract;

/**
 * Basic class implementing TransactionInterface. If this is too simple, extend
 * and override. If it breaks your inheritance chain, simply use your own class
 * and implement the TransactionInterface there.
 */
class Transaction implements TransactionContract
{
    private string $accountName;
    private string $accountNumber;
    private string $bsb;
    private int $amount;
    private mixed $indicator;
    private string $transactionCode;
    private string $reference;
    private ?string $remitter;
    private int $taxWithholding;

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function setAccountName(string $accountName): static
    {
        $this->accountName = $accountName;

        return $this;
    }

    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): static
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    public function getBsb(): string
    {
        return $this->bsb;
    }

    public function setBsb(string $bsb): static
    {
        $this->bsb = $bsb;

        return $this;
    }

    public function getAmount(): string
    {
        return (string) $this->amount;
    }

    /**
     * @param int $amount the transaction amount in cents
     *
     * @return $this
     */
    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getIndicator(): mixed
    {
        return $this->indicator ?: null;
    }

    public function setIndicator(mixed $indicator): static
    {
        $this->indicator = $indicator;

        return $this;
    }

    public function getTransactionCode(): string
    {
        return $this->transactionCode;
    }

    public function setTransactionCode(string $transactionCode): static
    {
        $this->transactionCode = $transactionCode;

        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getRemitter(): ?string
    {
        return $this->remitter ?: null;
    }

    public function setRemitter(?string $remitter): static
    {
        $this->remitter = $remitter;

        return $this;
    }

    public function getTaxWithholding(): string
    {
        return (string) $this->taxWithholding ?: '0';
    }

    public function setTaxWithholding(int $taxWithholding): static
    {
        $this->taxWithholding = $taxWithholding;

        return $this;
    }
}
