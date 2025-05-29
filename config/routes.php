<?php
declare(strict_types=1);

use Slim\App;
use ClassCRUD\Controllers\HomeController;
use ClassCRUD\Controllers\ClassController;
use ClassCRUD\Controllers\AuthController;
use ClassCRUD\Controllers\ApiController;
use ClassCRUD\Middleware\AuthMiddleware;

return function (App $app) {
    // Home routes
    $app->get('/', [HomeController::class, 'index'])->setName('home');
    $app->get('/dashboard', [HomeController::class, 'dashboard'])->setName('dashboard');

    // Authentication routes
    $app->get('/login', [AuthController::class, 'showLogin'])->setName('login');
    $app->post('/login', [AuthController::class, 'login']);
    $app->post('/logout', [AuthController::class, 'logout'])->setName('logout');
    $app->get('/register', [AuthController::class, 'showRegister'])->setName('register');
    $app->post('/register', [AuthController::class, 'register']);

    // Class management routes (protected)
    $app->group('/classes', function ($group) {
        // Web interface routes
        $group->get('', [ClassController::class, 'index'])->setName('classes.index');
        $group->get('/create', [ClassController::class, 'create'])->setName('classes.create');
        $group->post('/create', [ClassController::class, 'store'])->setName('classes.store');
        $group->get('/{id:[0-9]+}', [ClassController::class, 'show'])->setName('classes.show');
        $group->get('/{id:[0-9]+}/edit', [ClassController::class, 'edit'])->setName('classes.edit');
        $group->post('/{id:[0-9]+}/edit', [ClassController::class, 'update'])->setName('classes.update');
        $group->post('/{id:[0-9]+}/delete', [ClassController::class, 'delete'])->setName('classes.delete');
        
        // File upload routes
        $group->post('/{id:[0-9]+}/files', [ClassController::class, 'uploadFile'])->setName('classes.upload');
        $group->get('/files/{fileId:[0-9]+}/download', [ClassController::class, 'downloadFile'])->setName('classes.download');
        
        // Export routes
        $group->get('/export/csv', [ClassController::class, 'exportCsv'])->setName('classes.export.csv');
        $group->get('/export/pdf', [ClassController::class, 'exportPdf'])->setName('classes.export.pdf');
        $group->get('/export/excel', [ClassController::class, 'exportExcel'])->setName('classes.export.excel');
    })->add(AuthMiddleware::class);

    // API routes (protected)
    $app->group('/api', function ($group) {
        // Class API endpoints
        $group->get('/classes', [ApiController::class, 'getClasses']);
        $group->get('/classes/{id:[0-9]+}', [ApiController::class, 'getClass']);
        $group->post('/classes', [ApiController::class, 'createClass']);
        $group->put('/classes/{id:[0-9]+}', [ApiController::class, 'updateClass']);
        $group->delete('/classes/{id:[0-9]+}', [ApiController::class, 'deleteClass']);
        
        // Utility API endpoints
        $group->get('/clients', [ApiController::class, 'getClients']);
        $group->get('/agents', [ApiController::class, 'getAgents']);
        $group->get('/supervisors', [ApiController::class, 'getSupervisors']);
        $group->get('/learners', [ApiController::class, 'getLearners']);
        $group->get('/class-types', [ApiController::class, 'getClassTypes']);
        $group->get('/class-subjects/{type}', [ApiController::class, 'getClassSubjects']);
    })->add(AuthMiddleware::class);
};
