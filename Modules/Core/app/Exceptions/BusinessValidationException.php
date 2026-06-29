<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Raised when a request is well-formed but violates a domain business rule
 * (a state conflict), as opposed to a syntactic/field validation failure.
 *
 * Examples:
 *   - deleting an order that already has payments
 *   - paying for an order that is not in the "confirmed" state
 *
 * Defaults to 409 Conflict — the request conflicts with the current state of
 * the resource. Subclass this (like UnsupportedGatewayException subclasses
 * ApiException) for specific, reusable rules, or throw it directly with a
 * custom message/status.
 *
 * Rendered by the Core exception Handler via the inherited ApiException::render().
 */
class BusinessValidationException extends ApiException
{
    /**
     * @param  array<string, mixed>|null  $errors  Optional contextual details.
     */
    public function __construct(
        string $message = 'This action violates a business rule.',
        int $status = Response::HTTP_CONFLICT,
        ?array $errors = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $errors, $previous);
    }
}
