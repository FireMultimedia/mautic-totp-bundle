<?php declare(strict_types=1);

use MauticPlugin\MauticTOTPBundle\EventListener\UserAccountSubscriber;
use MauticPlugin\MauticTOTPBundle\EventListener\UserSubscriber;

use MauticPlugin\MauticTOTPBundle\Controller\v6\AuthController   as AuthControllerv6;
use MauticPlugin\MauticTOTPBundle\Controller\v5\AuthController   as AuthControllerv5;
use MauticPlugin\MauticTOTPBundle\Integration\v6\TOTPIntegration as TOTPIntegrationv6;
use MauticPlugin\MauticTOTPBundle\Integration\v5\TOTPIntegration as TOTPIntegrationv5;

use Mautic\CoreBundle\Helper\AppVersion;

// assume that Mautic developers use sane versioning
$mauticVersion = str_replace(".", "", explode("-", (new AppVersion())->getVersion())[0]);

$mauticVersion = str_split((string)$mauticVersion);

switch(true) {
    case $mauticVersion[0] >= 6:
        $classes = [
            "mautic.integration.totp" => TOTPIntegrationv6::class,
            "otp_auto.controller"     => AuthControllerv6::class
        ];

        $defaultIntegrationArguments = [
            "event_dispatcher",
            "mautic.helper.cache_storage",
            "doctrine.orm.entity_manager",
            "request_stack",
            "router",
            "translator",
            "monolog.logger.mautic",
            "mautic.helper.encryption",
            "mautic.lead.model.lead",
            "mautic.lead.model.company",
            "mautic.helper.paths",
            "mautic.core.model.notification",
            "mautic.lead.model.field",
            "mautic.plugin.model.integration_entity",
            "mautic.lead.model.dnc",
            "mautic.lead.field.fields_with_unique_identifier"
        ];
        break;
    case $mauticVersion[0] >= 5:
        $classes = [
            "mautic.integration.totp" => TOTPIntegrationv5::class,
            "otp_auto.controller"     => AuthControllerv5::class
        ];

        $defaultIntegrationArguments = [
            "event_dispatcher",
            "mautic.helper.cache_storage",
            "doctrine.orm.entity_manager",
            "session",
            "request_stack",
            "router",
            "translator",
            "monolog.logger.mautic",
            "mautic.helper.encryption",
            "mautic.lead.model.lead",
            "mautic.lead.model.company",
            "mautic.helper.paths",
            "mautic.core.model.notification",
            "mautic.lead.model.field",
            "mautic.plugin.model.integration_entity",
            "mautic.lead.model.dnc",
            "mautic.lead.field.fields_with_unique_identifier"
        ];
        break;
    default:
        die("Plugin is not compatible with your Mautic version. Please remove MauticTOTPBundle");
}

return [
    "name"        => "Time-based One-Time Password (TOTP)",
    "description" => "Two-Factor authentication for Mautic.",
    "version"     => "1.0.0",
    "author"      => "FireMultimedia B.V.",

    "routes" => [
        "main" => [
            "otp_auth" => [
                "path"       => "/otp",
                "controller" => "{$classes["otp_auto.controller"]}::authAction"
            ]
        ]
    ],

    "services" => [
        "events" => [
            "mautic.totp.event_listener.user_account_subscriber" => [
                "class" => UserAccountSubscriber::class,

                "arguments" => [
                    "router"
                ]
            ],

            "mautic.totp.event_listener.user_subscriber" => [
                "class" => UserSubscriber::class,

                "arguments" => [
                    "router",
                    "mautic.security",
                    "mautic.helper.integration"
                ]
            ]
        ],

        "models" => [

        ],

        "others" => [

        ],

        "integrations" => [
            "mautic.integration.totp" => [
                "class" => $classes["mautic.integration.totp"],

                "arguments" => array_merge($defaultIntegrationArguments, [
                    "mautic.helper.user",
                    "twig"
                ])
            ]
        ]
    ],

    "parameters" => [

    ]
];
