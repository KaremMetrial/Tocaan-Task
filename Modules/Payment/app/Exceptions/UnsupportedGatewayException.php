<?php

declare(strict_types=1);

namespace Modules\Payment\Exceptions;

use Modules\Core\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown by the PaymentGatewayManager when a requested payment method
 * does not map to any registered gateway strategy.
 */
class UnsupportedGatewayException extends ApiException
{
    public function __construct(string $method)
    {
        parent::__construct(
            message: "Unsupported payment gateway: [{$method}].",
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: ['method' => ["The payment method '{$method}' is not supported."]],
        );
    }
}
