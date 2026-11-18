<?php declare(strict_types=1);

use MauticPlugin\MauticTOTPBundle\Controller\AuthController;

use MauticPlugin\MauticTOTPBundle\Integration\TOTPIntegration;

use MauticPlugin\MauticTOTPBundle\EventListener\UserAccountSubscriber;
use MauticPlugin\MauticTOTPBundle\EventListener\UserSubscriber;

$classes = [
    "mautic.integration.totp" => TOTPIntegration::class,
    "otp_auto.controller"     => AuthController::class
];

return [
    "name"        => "Time-based One-Time Password (TOTP)",
    "description" => "Two-Factor authentication for Mautic.",
    "version"     => "1.2.0",
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

                "arguments" => [
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
                    "mautic.lead.field.fields_with_unique_identifier",
                    "mautic.helper.user",
                    "twig"
                ]
            ]
        ]
    ],

    "parameters" => [

    ]
];
