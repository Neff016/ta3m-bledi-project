<?php
return [
    'GET ateliers' => [WorkshopController::class, 'index'],
    'GET login' => [AuthController::class, 'loginForm'],
    'POST login' => [AuthController::class, 'login'],
    'GET register' => [AuthController::class, 'registerForm'],
    'POST register' => [AuthController::class, 'register'],
    'GET logout' => [AuthController::class, 'logout'],
    'GET mes-inscriptions' => [RegistrationController::class, 'mine'],
    'POST inscription' => [RegistrationController::class, 'store'],
    'POST annuler-inscription' => [RegistrationController::class, 'cancel'],
    'GET admin' => [AdminController::class, 'dashboard'],
    'GET admin-ateliers' => [AdminController::class, 'workshops'],
    'POST admin-atelier-save' => [AdminController::class, 'saveWorkshop'],
    'POST admin-atelier-delete' => [AdminController::class, 'deleteWorkshop'],
    'GET admin-inscriptions' => [AdminController::class, 'registrations'],
    'POST admin-inscription-delete' => [AdminController::class, 'deleteRegistration'],
];
