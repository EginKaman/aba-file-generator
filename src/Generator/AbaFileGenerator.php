<?php

declare(strict_types=1);

namespace EginKaman\AbaFileGenerator\Generator;

use DateTimeImmutable;
use Exception;
use EginKaman\AbaFileGenerator\Contracts\Transaction;
use EginKaman\AbaFileGenerator\Enums\TransactionCode;
use EginKaman\AbaFileGenerator\Exceptions\{ValidateDescriptiveRecordException, ValidateDetailRecordException};

class AbaFileGenerator
{
    public const DESCRIPTIVE_TYPE = '0';
    public const DETAIL_TYPE = '1';
    public const BATCH_TYPE = '7';

    /**
     * aba file string.
     */
    private string $abaString = '';

    /**
     * running total of credits in file.
     */
    private int $creditTotal = 0;

    /**
     * running total of debit in file.
     */
    private int $debitTotal = 0;

    private int $numberRecords = 0;

    private string $bsb;

    private string $accountNumber;

    private string $bankName;

    /**
     * The name of the user supplying the aba file. Some banks must match
     * account holder or be specified as "SURNAME Firstname".
     */
    private string $userName;

    /**
     * Appears on recipient's statement as origin of transaction.
     */
    private string $remitter;

    private string $directEntryUserId;

    private string $description;

    /**
     * The date transactions are released to all Financial Institutions.
     *
     * Defaults to today.
     */
    private string|int|DateTimeImmutable $processingDate;

    private bool $includeAccountNumberInDescriptiveRecord = true;

    /**
     * Validates that the BSB is 6 digits with a dash in the middle: 123-456.
     */
    private string $bsbRegex = '/^\d{3}-\d{3}$/';

    public function __construct(
        string $bsb,
        string $accountNumber,
        string $bankName,
        string $userName,
        string $remitter,
        string $directEntryUserId,
        string $description
    ) {
        $this->bsb = $bsb;
        $this->accountNumber = $accountNumber;
        $this->bankName = $bankName;
        $this->userName = $userName;
        $this->remitter = $remitter;
        $this->directEntryUserId = $directEntryUserId;
        $this->description = $description;
        $this->processingDate = time();
    }

    /**
     * Set the processing date.
     */
    public function setProcessingDate(DateTimeImmutable|int|string $date): static
    {
        $this->processingDate = $date;

        return $this;
    }

    /**
     * Set whether to include the remitter's bank account number and BSB in the descriptive record
     * header. Defaults to true for historic reasons. Some banks will require you to change this to
     * false.
     *
     * @return $this
     */
    public function setIncludeAccountNumberInDescriptiveRecord(bool $value): static
    {
        $this->includeAccountNumberInDescriptiveRecord = $value;

        return $this;
    }

    /**
     * @param array<Transaction>|Transaction $transactions
     *
     * @throws Exception
     */
    public function generate(mixed $transactions): string
    {
        if (!is_array($transactions)) {
            $transactions = [$transactions];
        }

        $this->addDescriptiveRecord();

        foreach ($transactions as $transaction) {
            $this->addDetailRecord($transaction);

            if ($transaction->getTransactionCode() === TransactionCode::ExternallyInitiatedDebit->value) {
                $this->debitTotal += $transaction->getAmount();
            } else {
                $this->creditTotal += $transaction->getAmount();
            }
        }

        $this->numberRecords = count($transactions);
        $this->addBatchControlRecord();

        return $this->abaString;
    }

    public function getDescriptiveRecord(): string
    {
        $this->validateDescriptiveRecord();
        // Record Type
        $line = self::DESCRIPTIVE_TYPE;

        if ($this->includeAccountNumberInDescriptiveRecord) {
            // BSB
            $line .= $this->bsb;

            // Account Number
            $line .= str_pad($this->accountNumber, 9, ' ', STR_PAD_LEFT);

            // Reserved - must be a single blank space
            $line .= ' ';
        } else {
            // Reserved - must be 17 blank spaces
            $line .= str_repeat(' ', 17);
        }

        // Sequence Number
        $line .= '01';

        // Bank Name
        $line .= $this->bankName;

        // Reserved - must be seven blank spaces
        $line .= str_repeat(' ', 7);

        // User Name
        $line .= str_pad($this->userName, 26, ' ', STR_PAD_RIGHT);

        // User ID
        $line .= $this->directEntryUserId;

        // File Description
        $line .= str_pad($this->description, 12, ' ', STR_PAD_RIGHT);

        // Processing Date
        $line .= date(
            'dmy',
            is_numeric($this->processingDate) ? $this->processingDate : strtotime($this->processingDate)
        );

        // Reserved - 40 blank spaces
        $line .= str_repeat(' ', 40);

        return $line;
    }

    public function getDetailRecord(Transaction $transaction): string
    {
        $this->validateDetailRecord($transaction);
        // Record Type
        $line = self::DETAIL_TYPE;

        // BSB
        $line .= $transaction->getBsb();

        // Account Number
        $line .= str_pad($transaction->getAccountNumber(), 9, ' ', STR_PAD_LEFT);

        // Indicator
        $line .= $transaction->getIndicator() ?: ' ';

        // Transaction Code
        $line .= $transaction->getTransactionCode();

        // Transaction Amount
        $line .= str_pad($transaction->getAmount(), 10, '0', STR_PAD_LEFT);

        // Account Name
        $line .= str_pad($transaction->getAccountName(), 32, ' ', STR_PAD_RIGHT);

        // Lodgement Reference
        $line .= str_pad($transaction->getReference(), 18, ' ', STR_PAD_RIGHT);

        // Trace BSB - already validated
        $line .= $this->bsb;

        // Trace Account Number - already validated
        $line .= str_pad($this->accountNumber, 9, ' ', STR_PAD_LEFT);

        // Remitter Name - already validated
        $remitter = $transaction->getRemitter() ?: $this->remitter;
        $line .= str_pad($remitter, 16, ' ', STR_PAD_RIGHT);

        // Withholding amount
        $line .= str_pad($transaction->getTaxWithholding(), 8, '0', STR_PAD_LEFT);

        return $line;
    }

    public function getBatchControlRecord(): string
    {
        $line = self::BATCH_TYPE;

        // BSB
        $line .= '999-999';

        // Reserved - must be twelve blank spaces
        $line .= str_repeat(' ', 12);

        // Batch Net Total
        $line .= str_pad((string) abs($this->creditTotal - $this->debitTotal), 10, '0', STR_PAD_LEFT);

        // Batch Credits Total
        $line .= str_pad((string) $this->creditTotal, 10, '0', STR_PAD_LEFT);

        // Batch Debits Total
        $line .= str_pad((string) $this->debitTotal, 10, '0', STR_PAD_LEFT);

        // Reserved - must be 24 blank spaces
        $line .= str_repeat(' ', 24);

        // Number of records
        $line .= str_pad((string) $this->numberRecords, 6, '0', STR_PAD_LEFT);

        // Reserved - must be 40 blank spaces
        $line .= str_repeat(' ', 40);

        return $line;
    }

    /**
     * Create the descriptive record line of the file.
     */
    private function addDescriptiveRecord(): void
    {
        $this->addLine($this->getDescriptiveRecord());
    }

    /**
     * Add a detail record for each transaction.
     */
    private function addDetailRecord(Transaction $transaction): void
    {
        $this->addLine($this->getDetailRecord($transaction));
    }

    private function addBatchControlRecord(): void
    {
        $this->addLine($this->getBatchControlRecord(), false);
    }

    private function addLine(string $line, bool $crlf = true): void
    {
        $this->abaString .= $line . ($crlf ? "\r\n" : '');
    }

    /**
     * Validate the parts of the descriptive record.
     *
     * @throws ValidateDescriptiveRecordException
     */
    private function validateDescriptiveRecord(): void
    {
        if (!preg_match($this->bsbRegex, $this->bsb)) {
            throw new ValidateDescriptiveRecordException(
                'Descriptive record bsb is invalid: ' . $this->bsb . '. Required format is 000-000.'
            );
        }

        if (!preg_match('/^\d{0,9}$/', $this->accountNumber)) {
            throw new ValidateDescriptiveRecordException(
                'Descriptive record account number is invalid: ' . $this->accountNumber . '. Must be up to 9 digits only.'
            );
        }

        if (!preg_match('/^[A-Z]{3}$/', $this->bankName)) {
            throw new ValidateDescriptiveRecordException(
                'Descriptive record bank name is invalid: ' . $this->bankName . '. Must be capital letter abbreviation of length 3.'
            );
        }

        if (!preg_match('/^[A-Za-z\s+]{0,26}$/', $this->userName)) {
            throw new ValidateDescriptiveRecordException(
                'Descriptive record user name is invalid: ' . $this->userName . '. Must be letters only and up to 26 characters long.'
            );
        }

        if (!preg_match('/^\d{6}$/', $this->directEntryUserId)) {
            throw new ValidateDescriptiveRecordException(
                'Descriptive record direct entiry user ID is invalid: ' . $this->directEntryUserId . '. Must be 6 digits long.'
            );
        }

        if (!preg_match('/^[A-Za-z\s]{0,12}$/', $this->description)) {
            throw new ValidateDescriptiveRecordException(
                'Descriptive record description is invalid: ' . $this->description . '. Must be letters only and up to 12 characters long.'
            );
        }
    }

    /**
     * Validate the parts of the transaction.
     *
     * @throws ValidateDetailRecordException
     */
    private function validateDetailRecord(mixed $transaction): void
    {
        if (!$transaction instanceof Transaction) {
            throw new ValidateDetailRecordException('Transactions must implement TransactionInterface.');
        }

        if (!preg_match($this->bsbRegex, $transaction->getBsb())) {
            throw new ValidateDetailRecordException(
                'Detail record bsb is invalid: ' . $transaction->getBsb() . '. Required format is 000-000.'
            );
        }

        if (!preg_match('/^\d{0,9}$/', $transaction->getAccountNumber())) {
            throw new ValidateDetailRecordException(
                'Detail record account number is invalid: ' . $transaction->getAccountNumber() . '. Must be up to 9 digits only.'
            );
        }

        if ($transaction->getIndicator() && !preg_match('/^W|X|Y| /', $transaction->getIndicator())) {
            throw new ValidateDetailRecordException(
                'Detail record transaction indicator is invalid: ' . $transaction->getIndicator() . '. Must be one of W, X, Y or null.'
            );
        }

        if (!preg_match('/^\d{0,10}$/', $transaction->getAmount())) {
            throw new ValidateDetailRecordException(
                'Detail record amount is invalid: ' . $transaction->getAmount() . '. Must be expressed in cents, as an unsigned integer, no longer than 10 digits.'
            );
        }

        if (mb_strlen($transaction->getAccountName()) > 32) {
            throw new ValidateDetailRecordException(
                'Detail record account name is invalid: ' . $transaction->getAccountName() . '. Cannot exceed 32 characters.'
            );
        }

        if (!preg_match('/^[A-Za-z0-9\s+]{0,18}$/', $transaction->getReference())) {
            throw new ValidateDetailRecordException(
                'Detail record reference is invalid: "' . $transaction->getReference() . '". Must be letters or numbers only and up to 18 characters long.'
            );
        }

        if ($transaction->getRemitter() && !preg_match('/^[A-Za-z\s+]{0,16}$/', $transaction->getRemitter())) {
            throw new ValidateDetailRecordException(
                'Detail record remitter is invalid: ' . $transaction->getRemitter() . '. Must be letters only and up to 16 characters long.'
            );
        }

        if (!$this->validateTransactionCode($transaction->getTransactionCode())) {
            throw new ValidateDetailRecordException(
                'Detail record transaction code invalid: ' . $transaction->getTransactionCode() . '. Must be a constant from AbaFileGenerator\Model\TransactionCode.'
            );
        }
    }

    private function validateTransactionCode(string $transactionCode): bool
    {
        return in_array($transactionCode, [
            TransactionCode::ExternallyInitiatedDebit->value,
            TransactionCode::ExternallyInitiatedCredit->value,
            TransactionCode::AustralianGovernmentSecurityInterest->value,
            TransactionCode::FamilyAllowance->value,
            TransactionCode::PayrollPayment->value,
            TransactionCode::PensionPayment->value,
            TransactionCode::Allotment->value,
            TransactionCode::Dividend->value,
            TransactionCode::DebentureOrNoteInterest->value,
        ], true);
    }
}
