<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

$internalConfig = array(
    'basePath' => dirname(dirname(__FILE__)),
    'aliases' => array(
        'core' => realpath(__DIR__ . '/../../assets/packages'),
        'fonts' => realpath(__DIR__ . '/../../assets/fonts'),
    ),
    'components' => array(
        'assetManager' => array(
            'excludeFiles' => array("config.xml", "node_modules", "src"),
            'class' => 'application.core.LSYii_AssetManager',
            'basePath' => realpath(__DIR__ . '/../../assets'), // Ensure this path is correct
            'baseUrl' => '/assets', // Ensure this URL is correct
        ),
    ),
);
