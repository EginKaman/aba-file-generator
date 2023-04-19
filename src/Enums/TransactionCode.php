<?php

declare(strict_types=1);

namespace EginKaman\AbaFileGenerator\Enums;

enum TransactionCode: string
{
    case ExternallyInitiatedDebit = '13';
    case ExternallyInitiatedCredit = '50';
    case AustralianGovernmentSecurityInterest = '51';
    case FamilyAllowance = '52';
    case PayrollPayment = '53';
    case PensionPayment = '54';
    case Allotment = '55';
    case Dividend = '56';
    case DebentureOrNoteInterest = '57';
}
