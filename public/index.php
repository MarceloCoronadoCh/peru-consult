<?php
/**
 * Peru Consult API
 * Simple REST API for DNI and RUC queries
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use Peru\Jne\{Dni, DniFactory};
use Peru\Sunat\{Ruc, RucFactory};
use Peru\Http\ContextClient;

// Helper function to send JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Helper function to send error response
function errorResponse($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

// Parse the URI
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($scriptName, '', $requestUri);
$path = trim(parse_url($path, PHP_URL_PATH), '/');
$segments = explode('/', $path);

// Routes
try {
    // Health check
    if ($path === '' || $path === 'health') {
        jsonResponse([
            'success' => true,
            'message' => 'Peru Consult API is running',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /dni/{number}' => 'Consulta DNI',
                'GET /ruc/{number}' => 'Consulta RUC',
                'GET /health' => 'Health check'
            ]
        ]);
    }

    // DNI Query - GET /dni/{number}
    if ($segments[0] === 'dni' && isset($segments[1])) {
        $dniNumber = $segments[1];

        // Validate DNI format (8 digits)
        if (!preg_match('/^\d{8}$/', $dniNumber)) {
            errorResponse('DNI debe tener 8 dígitos', 400);
        }

        $factory = new DniFactory(new ContextClient());
        $dni = $factory->create();

        $person = $dni->get($dniNumber);

        if (!$person) {
            errorResponse('No se encontró información para el DNI proporcionado', 404);
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'dni' => $person->dni,
                'nombres' => $person->nombres,
                'apellidoPaterno' => $person->apellidoPaterno,
                'apellidoMaterno' => $person->apellidoMaterno,
                'codVerifica' => $person->codVerifica ?? null
            ]
        ]);
    }

    // RUC Query - GET /ruc/{number}
    if ($segments[0] === 'ruc' && isset($segments[1])) {
        $rucNumber = $segments[1];

        // Validate RUC format (11 digits)
        if (!preg_match('/^\d{11}$/', $rucNumber)) {
            errorResponse('RUC debe tener 11 dígitos', 400);
        }

        $factory = new RucFactory(new ContextClient());
        $ruc = $factory->create();

        $company = $ruc->get($rucNumber);

        if (!$company) {
            errorResponse('No se encontró información para el RUC proporcionado', 404);
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'ruc' => $company->ruc,
                'razonSocial' => $company->razonSocial,
                'nombreComercial' => $company->nombreComercial ?? null,
                'telefonos' => $company->telefonos ?? [],
                'tipo' => $company->tipo ?? null,
                'estado' => $company->estado ?? null,
                'condicion' => $company->condicion ?? null,
                'direccion' => $company->direccion ?? null,
                'departamento' => $company->departamento ?? null,
                'provincia' => $company->provincia ?? null,
                'distrito' => $company->distrito ?? null,
                'fechaInscripcion' => $company->fechaInscripcion ?? null,
                'sistemaEmision' => $company->sistemaEmision ?? null,
                'actividadExterior' => $company->actividadExterior ?? null,
                'sistemaContabilidad' => $company->sistemaContabilidad ?? null,
                'comercioExterior' => $company->comercioExterior ?? null,
                'emisionElectronica' => $company->emisionElectronica ?? null,
                'fechaEmisorFe' => $company->fechaEmisorFe ?? null,
                'cpe' => $company->cpe ?? null,
                'fechaPle' => $company->fechaPle ?? null,
                'padrones' => $company->padrones ?? null,
                'fechaBaja' => $company->fechaBaja ?? null,
                'profesion' => $company->profesion ?? null
            ]
        ]);
    }

    // Route not found
    errorResponse('Endpoint no encontrado. Consulta /health para ver los endpoints disponibles.', 404);

} catch (Exception $e) {
    errorResponse('Error interno del servidor: ' . $e->getMessage(), 500);
}
