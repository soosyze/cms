<?php

/* Démarrage de la session. */
session_start();

/* Définit par défaut la timezone. polyfills */
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

/* Home for Linux */
$home = isset($_SERVER[ 'HOME' ])
    ? rtrim($_SERVER[ 'HOME' ], '/')
    : null;
/* Home for Windows */
if (empty($home) && isset($_SERVER[ 'HOMEDRIVE' ], $_SERVER[ 'HOMEPATH' ])) {
    $home = rtrim($_SERVER[ 'HOMEDRIVE' ] . $_SERVER[ 'HOMEPATH' ], '\\/');
}

/* Construit une requête dédié à PHP CLI. */
$uri = new Soosyze\Components\Http\Uri('http', $home, '/', 80, '');
$req = new Soosyze\Components\Http\ServerRequest(
    'GET',
    $uri,
    [],
    null,
    '1.1',
    $_SERVER,
    [],
    []
);

$app = Core::getInstance($req);

$app->setSettings([
    'root'                => ROOT,
    'config'              => 'app/config',
    /* Chemin des fichiers. */
    'files'               => 'app/files',
    /* Chemin des fichiers public. */
    'files_public'        => 'app/files/public',
    /* Chemin des modules du core. */
    'modules'             => 'core/modules',
    /* Chemin des modules contributeur. */
    'modules_contributed' => 'app/modules',
    /* Chemins des thèmes par ordre de priorité d'appel. */
    'themes_path'         => [ 'app/themes', 'core/themes' ]
]);

$app->init();
