<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

/**
 * Centralizes API exception rendering so every error — framework or domain —
 * returns the same JSON envelope used by the ApiResponse trait:
 *
 *   { "success": false, "message": "...", "errors"?: { ... } }
 *
 * Wired in bootstrap/app.php via Handler::register($exceptions).
 */
class Handler
{
    public static function register(Exceptions $exceptions): void
    {
        // Only take over rendering for API requests; web routes keep Laravel defaults.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null; // let the default handler deal with non-API requests
            }

            return self::toJson($e);
        });
    }

    /**
     * Map a throwable to a standardized JSON error response.
     */
    private static function toJson(Throwable $e): JsonResponse
    {
        // Domain exceptions render themselves.
        if ($e instanceof ApiException) {
            return $e->render();
        }

        // 422 — validation.
        if ($e instanceof ValidationException) {
            return self::payload('The given data was invalid.', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        }

        // 401 — unauthenticated.
        if ($e instanceof AuthenticationException) {
            return self::payload('Unauthenticated.', Response::HTTP_UNAUTHORIZED);
        }

        // 403 — forbidden.
        if ($e instanceof AuthorizationException) {
            return self::payload($e->getMessage() ?: 'This action is unauthorized.', Response::HTTP_FORBIDDEN);
        }

        // 404 — model / route not found.
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());

            return self::payload("{$model} not found.", Response::HTTP_NOT_FOUND);
        }

        if ($e instanceof NotFoundHttpException || $e instanceof RouteNotFoundException) {
            return self::payload('The requested resource was not found.', Response::HTTP_NOT_FOUND);
        }

        // Any other HTTP exception keeps its own status code.
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return self::payload($e->getMessage() ?: self::defaultMessage($status), $status);
        }

        // Fallback — 500. Hide internals unless debugging.
        $debug = (bool) config('app.debug');

        return self::payload(
            $debug ? $e->getMessage() : 'Server error. Please try again later.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $debug ? ['exception' => class_basename($e)] : null,
        );
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    private static function payload(string $message, int $status, ?array $errors = null): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    private static function defaultMessage(int $status): string
    {
        return Response::$statusTexts[$status] ?? 'Error';
    }
}
