<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Infrastructure\Http\Controllers;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Http\UnexpectedErrorResponse;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentByIdDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\UseCases\GetPatientAppointmentQrUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * GET /api/v1/portal/appointments/{id}/qr (spec 025, CA7, CA12, CA18): the QR code of an open
 * appointment of the patient, as SVG. Someone else's appointment answers 404 like a missing one;
 * a closed one of their own answers 409. The no-store header comes from the portal group.
 */
final class GetPortalAppointmentQrController
{
    public function __construct(
        private GetPatientAppointmentQrUseCase $useCase,
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
                return response()->json(['error' => 'Appointment not found.'], 404);
            }

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => 'inline; filename="cita.svg"',
            ]);
        } catch (InvalidArgumentException) {
            return response()->json(['error' => 'Invalid appointment id.'], 400);
        } catch (AppointmentAplicationExceptions) {
            // The only one the use case throws: a closed appointment of the patient.
            return response()->json(['error' => 'The QR code is only available for open appointments.'], 409);
        } catch (AuthorizationException $e) {
            // The 403 of the whole API, rendered in bootstrap/app.php.
            throw $e;
        } catch (\Exception $e) {
            return UnexpectedErrorResponse::from($e, self::class);
        }
    }
}
