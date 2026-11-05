<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentByIdDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\GetAppointmentQrUseCase;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * GET /api/v1/appointments/{id}/qr (spec 024): the QR code of an appointment as SVG, holding only
 * its id. It is a pass to the clinic, so no browser or proxy may keep it. The content security
 * policy is the global one of SecurityHeaders, which would replace any other (A2).
 */
final class GetAppointmentQrController
{
    public function __construct(
        private GetAppointmentQrUseCase $useCase,
    ) {}

    public function __invoke(Request $request): Response|JsonResponse
    {
        try {
            $svg = $this->useCase->execute(
                GetAppointmentByIdDTO::create(
                    appointmentId: (string) $request->route('id'),
                )
            );

            if ($svg === null) {
                return response()->json([
                    'error' => 'Appointment not found.',
                ], 404);
            }

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'private, no-store',
                'Content-Disposition' => 'inline; filename="cita.svg"',
            ]);
        } catch (AppointmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (ValueObjectsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (InvalidArgumentException) {
            return response()->json(['error' => 'Invalid appointment id.'], 400);
        } catch (AppointmentAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
