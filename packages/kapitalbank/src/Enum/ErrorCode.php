<?php

declare(strict_types=1);

namespace Integrify\Kapitalbank\Enum;

/**
 * Bankın 400-dən böyük cavablarında qaytardığı `errorCode` dəyərləri.
 *
 * `RequestRejected::code()` bunu qaytarır; tanınmayan kod üçün `null`, xam dəyər isə
 * `RequestRejected::$errorCode`-da qalır.
 *
 * İzahlar bankın sənədlərindən olduğu kimi (ingiliscə) saxlanılıb.
 */
enum ErrorCode: string
{
    /** Invalid amount */
    case InvalidAmount = 'InvalidAmt';

    /** Invalid request */
    case InvalidRequest = 'InvalidRequest';

    /** Invalid transaction */
    case InvalidTransaction = 'InvalidTran';

    /** Invalid transaction linkage */
    case InvalidTransactionLinkage = 'InvalidTranLink';

    /** Transaction prohibited */
    case TransactionProhibited = 'TranProhibited';

    case ActionAppException = 'ActionAppException';

    /** Can't choose settlement account */
    case CantChooseSettlementAccount = 'CantChooseSettleAcct';

    /** Pmo interface not found */
    case PmoInterfaceNotFound = 'PmoIfaceNotFound';

    /** Transaction declined by PMO: {reason} */
    case PmoDecline = 'PmoDecline';

    /** Can't reach PMO */
    case PmoUnreachable = 'PmoUnreachable';

    /** Invalid certificate */
    case InvalidCertificate = 'InvalidCert';

    /** Invalid login or password */
    case InvalidLogin = 'InvalidLogin';

    /** Invalid order state */
    case InvalidOrderState = 'InvalidOrderState';

    /** Invalid user session */
    case InvalidUserSession = 'InvalidUserSession';

    /** Need change password */
    case NeedChangePassword = 'NeedChangePwd';

    /** Operation prohibited */
    case OperationProhibited = 'OperationProhibited';

    /** Password try limit exceeded */
    case PasswordTryLimitExceeded = 'PwdTryLimitExceeded';

    /** User session expired */
    case UserSessionExpired = 'UserSessionExpired';

    /** Declined by CoF Provider: {Reason} */
    case CofProviderDecline = 'CofpDecline';

    /** Can't reach CoF Provider */
    case CofProviderUnreachable = 'CofpUnreachable';

    /** Invalid token */
    case InvalidToken = 'InvalidToken';

    /** Invalid authentication status */
    case InvalidAuthStatus = 'InvalidAutStatus';

    /** Consumer not found */
    case ConsumerNotFound = 'ConsumerNotFound';

    /** Invalid consumer */
    case InvalidConsumer = 'InvalidConsumer';

    /** Invalid secret code */
    case InvalidSecret = 'InvalidSecret';

    /** Secret try limit has been exceeded */
    case SecretTryLimitExceeded = 'SecretTryLimit';

    /** Service error */
    case ServiceError = 'ServiceError';
}
