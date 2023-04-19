<?php

declare(strict_types=1);

namespace EginKaman\AbaFileGenerator\Contracts;

interface Transaction
{
    /**
     * Bank account name for this transaction. Must be 32 characters or less.
     */
    public function getAccountName(): string;

    /**
     * Return the account number as a string. Must be 9 digits or less.
     */
    public function getAccountNumber(): string;

    /**
     * Return the bank's BSB for this account. Format is xxx-xxx.
     */
    public function getBsb(): string;

    /**
     * Return the transaction amount in cents.
     */
    public function getAmount(): string;

    /**
     * Return null for a normal transaction or if withholding tax:
     * "W" – dividend paid to a resident of a country where a double tax agreement is in force.
     * "X" – dividend paid to a resident of any other country.
     * "Y" – interest paid to all non-residents.
     */
    public function getIndicator(): mixed;

    /**
     * Return null for a normal transaction or if withholding tax:
     * "W" – dividend paid to a resident of a country where a double tax agreement is in force.
     * "X" – dividend paid to a resident of any other country.
     * "Y" – interest paid to all non-residents.
     */
    public function getTransactionCode(): string;

    /**
     * Description of transaction to appear on recipients bank statement.
     */
    public function getReference(): string;

    /**
     * Name of originator of entry.
     */
    public function getRemitter(): ?string;

    /**
     * Amount of tax withholding. Return zero if not withholding any amount.
     */
    public function getTaxWithholding(): string;
}
