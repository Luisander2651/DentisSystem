<?php

use App\Modules\Appointments\Infrastructure\Http\Controllers\CreateAppointmentController;
use App\Modules\Appointments\Infrastructure\Http\Controllers\GetAllApointmentsByStatusAndDateController;
use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Infrastructure\Http\Controllers\LoginController;
use App\Modules\Auth\Infrastructure\Http\Controllers\RegisterController;
use App\Modules\Auth\Infrastructure\Http\Controllers\LogoutController;
use App\Modules\Users\Infrastructure\Http\Controllers\RegisterUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\UpdateUserController;
use App\Modules\Users\Infrastructure\Http\Controllers\GetUsersByRoleAndStatusController;
use App\Modules\Users\Infrastructure\Http\Controllers\DeleteUserByIdController;
use App\Modules\Patients\Infrastructure\Http\Controllers\CreatePatientController;
use App\Modules\Patients\Infrastructure\Http\Controllers\GetPatientsByStatusController;
use App\Modules\Patients\Infrastructure\Http\Controllers\UpdatePatientController;
use App\Modules\Patients\Infrastructure\Http\Controllers\DeletePatientByIdController;
use App\Modules\Patients\Infrastructure\Http\Controllers\GetPatientByIdController;
use App\Modules\Patients\Infrastructure\Http\Controllers\Addresses\CreateAddressController;
use App\Modules\Patients\Infrastructure\Http\Controllers\Addresses\UpdateAddressController;
use App\Modules\Patients\Infrastructure\Http\Controllers\Addresses\DeleteAddressByPatientIdController;
use App\Modules\Patients\Infrastructure\Http\Controllers\ContactInfo\CreateContactInfoController;
use App\Modules\Patients\Infrastructure\Http\Controllers\ContactInfo\UpdateContactInfoController;
use App\Modules\Patients\Infrastructure\Http\Controllers\ContactInfo\DeleteContactInfoByPatientIdController;
use App\Modules\Patients\Infrastructure\Http\Controllers\MedicalData\CreateMedicalDataController;
use App\Modules\Patients\Infrastructure\Http\Controllers\MedicalData\UpdateMedicalDataController;
use App\Modules\Patients\Infrastructure\Http\Controllers\MedicalData\DeleteMedicalDataByPatientIdController;
use App\Modules\Patients\Infrastructure\Http\Controllers\PatientRecord\GetPatientRecordByPatientIdController;
use App\Modules\Appointments\Infrastructure\Http\Controllers\GetAppointmentByIdController;
use App\Modules\Appointments\Infrastructure\Http\Controllers\DeleteAppointmentController;
use App\Modules\Appointments\Infrastructure\Http\Controllers\UpdateAppointmentController;
use App\Modules\Appointments\Infrastructure\Http\Controllers\GetAppointmentsByPatientIdController;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Controllers\SaveCertificationController;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Controllers\UpdateCertificationController;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Controllers\DeleteCertificationController;
use App\Modules\ContentManagement\Modules\Certificaciones\Infrastructure\HTTP\Controllers\GetCertificationsController;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Controllers\SaveGalleryImageController;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Controllers\UpdateGalleryImageController;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Controllers\DeleteGalleryImageController;
use App\Modules\ContentManagement\Modules\Galeria\Infrastructure\HTTP\Controllers\GetGalleryImagesController;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Controllers\SavePromotionController;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Controllers\UpdatePromotionController;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Controllers\DeletePromotionController;
use App\Modules\ContentManagement\Modules\Promociones\Infrastructure\HTTP\Controllers\GetPromotionsController;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers\SaveTestimonialController;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers\UpdateTestimonialController;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers\DeleteTestimonialController;
use App\Modules\ContentManagement\Modules\Testimonios\Infrastructure\HTTP\Controllers\GetTestimonialsController;

Route::middleware(['throttle:api', 'sanctum.cookie'])->group(function () {

    Route::prefix('v1')->group(function (): void {

        Route::prefix('auth')->group(function (): void {
            Route::post('/login', LoginController::class);
            Route::post('/register', RegisterController::class);
            Route::middleware('auth:sanctum')->group(function (): void {
                Route::post('/logout', LogoutController::class);
            });
        });

        Route::middleware(['auth:sanctum', 'only.admin'])->group(function (): void {
            Route::prefix('users')->group(function (): void {
                Route::post('/', RegisterUserController::class);
                Route::put('/{id}', UpdateUserController::class);
                Route::get('/', GetUsersByRoleAndStatusController::class);
                Route::delete('/{id}', DeleteUserByIdController::class);
            });

            Route::prefix('patients')->group(function (): void {
                Route::post('/', CreatePatientController::class);
                Route::delete('/{id}', DeletePatientByIdController::class);
            });

            Route::prefix('certifications')->group(function (): void {
                Route::post('/', SaveCertificationController::class);
                Route::put('/{id}', UpdateCertificationController::class);
                Route::delete('/{id}', DeleteCertificationController::class);
                Route::get('/', GetCertificationsController::class);
            });

            Route::prefix('gallery-images')->group(function (): void {
                Route::post('/', SaveGalleryImageController::class);
                Route::put('/{id}', UpdateGalleryImageController::class);
                Route::delete('/{id}', DeleteGalleryImageController::class);
                Route::get('/', GetGalleryImagesController::class);
            });

            Route::prefix('promotions')->group(function (): void {
                Route::post('/', SavePromotionController::class);
                Route::put('/{id}', UpdatePromotionController::class);
                Route::delete('/{id}', DeletePromotionController::class);
                Route::get('/', GetPromotionsController::class);
            });

            Route::prefix('testimonials')->group(function (): void {
                Route::post('/', SaveTestimonialController::class);
                Route::put('/{id}', UpdateTestimonialController::class);
                Route::delete('/{id}', DeleteTestimonialController::class);
                Route::get('/', GetTestimonialsController::class);
            });
        });
        
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::prefix('patients')->group(function (): void {
                Route::get('/', GetPatientsByStatusController::class);
                Route::put('/{id}', UpdatePatientController::class);
                Route::get('/{id}', GetPatientByIdController::class);

                Route::post('/{patientId}/address', CreateAddressController::class);
                Route::put('/{patientId}/address', UpdateAddressController::class);
                Route::delete('/{patientId}/address', DeleteAddressByPatientIdController::class);

                Route::post('/{patientId}/contact-info', CreateContactInfoController::class);
                Route::put('/{patientId}/contact-info', UpdateContactInfoController::class);
                Route::delete('/{patientId}/contact-info', DeleteContactInfoByPatientIdController::class);

                Route::post('/{patientId}/medical-data', CreateMedicalDataController::class);
                Route::put('/{patientId}/medical-data', UpdateMedicalDataController::class);
                Route::delete('/{patientId}/medical-data', DeleteMedicalDataByPatientIdController::class);

                Route::get('/{patientId}/record', GetPatientRecordByPatientIdController::class);
            });

            Route::prefix('appointments')->group(function (): void {
                Route::get('/', GetAllApointmentsByStatusAndDateController::class);
                Route::post('/', CreateAppointmentController::class);
                Route::get('/patient/{patientId}', GetAppointmentsByPatientIdController::class);
                Route::get('/{id}', GetAppointmentByIdController::class);
                Route::put('/{id}', UpdateAppointmentController::class);
                Route::delete('/{id}', DeleteAppointmentController::class);
            });
        });
    });
});