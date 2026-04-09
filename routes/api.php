<?php

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
                Route::get('/', GetPatientsByStatusController::class);
            });
        });
        
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::prefix('patients')->group(function (): void {
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
        });
    });
    
});