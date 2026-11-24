<?php

use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

$project = dirname(__DIR__);
$environment = $project.'/.env';

if (file_exists($environment)) {
    fwrite(STDOUT, "Se conservó el archivo de entorno existente.\n");
    exit(0);
}

if (! copy($project.'/.env.example', $environment)) {
    fwrite(STDERR, "No se pudo crear el archivo de entorno.\n");
    exit(1);
}

$command = new Process([PHP_BINARY, 'artisan', 'key:generate'], $project);
$status = $command->run(static function ($type, $message): void {
    fwrite($type === Process::ERR ? STDERR : STDOUT, $message);
});

if ($status !== 0) {
    fwrite(STDERR, "El archivo de entorno quedó creado. Genera APP_KEY antes de iniciar una instalación nueva.\n");
}

exit($status);
