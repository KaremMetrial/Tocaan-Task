<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Base class for domain-level API exceptions.
 *
 * Throw this (or a subclass) anywhere in the service layer to short-circuit
 * with a clean JSON error envelope and an explicit HTTP status code.
 *
 * Example:
 *   throw new ApiException('Order cannot be deleted; it has payments.', 409);
 */
class ApiException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $errors  Optional field-level / contextual errors.
     */
    public function __construct(
        string $message = 'Something went wrong.',
        protected int $status = Response::HTTP_BAD_REQUEST,
        protected ?array $errors = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * Render the exception into the standard API error envelope.
     */
    public function render(): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if ($this->errors !== null) {
            $payload['errors'] = $this->errors;
        }

        return response()->json($payload, $this->status);
    }
}
