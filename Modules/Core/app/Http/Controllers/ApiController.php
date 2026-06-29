<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Core\Traits\ApiResponse;

/**
 * Base controller for all API controllers across modules.
 *
 * Provides the standardized response helpers via the ApiResponse trait
 * and authorization support via AuthorizesRequests, so individual
 * controllers stay thin and consistent.
 */
abstract class ApiController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
}
