<?php

namespace MauticPlugin\MauticTOTPBundle\Integration\v6;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use MauticPlugin\MauticTOTPBundle\Helper\AuthenticatorHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TOTPIntegration extends AbstractIntegration {

    public const INTEGRATION_NAME = "TOTP";

    protected $user;

    protected $status_field;

    protected $secret_field;

    protected $authHelper;

    protected $secret;

    public function __construct(
        protected EventDispatcherInterface   $dispatcher,
                  CacheStorageHelper         $cacheStorageHelper,
        protected EntityManager              $em,
        protected RequestStack               $requestStack,
        protected RouterInterface            $router,
        protected TranslatorInterface        $translator,
        protected LoggerInterface            $logger,
        protected EncryptionHelper           $encryptionHelper,
        protected LeadModel                  $leadModel,
        protected CompanyModel               $companyModel,
        protected PathsHelper                $pathsHelper,
        protected NotificationModel          $notificationModel,
        protected FieldModel                 $fieldModel,
        protected IntegrationEntityModel     $integrationEntityModel,
        protected DoNotContact               $doNotContact,
        protected FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier,

        private readonly UserHelper  $userHelper,
        private readonly Environment $twig
    ) {
        parent::__construct(
            $dispatcher,
            $cacheStorageHelper,
            $em,
            $requestStack,
            $router,
            $translator,
            $logger,
            $encryptionHelper,
            $leadModel,
            $companyModel,
            $pathsHelper,
            $notificationModel,
            $fieldModel,
            $integrationEntityModel,
            $doNotContact,
            $fieldsWithUniqueIdentifier
        );

        $this->user = $this->userHelper->getUser();

        $id = $this->user->getId();

        $this->status_field = "status_$id";
        $this->secret_field = "secret_$id";

        $this->authHelper = new AuthenticatorHelper();
    }

    /** {@inheritDoc} */
    public function getName(): string {
        return self::INTEGRATION_NAME;
    }

    /** {@inheritDoc} */
    public function getDisplayName(): string {
        return "Time-based one-time password";
    }

    /** {@inheritDoc} */
    public function getAuthenticationType() {
        return "none";
    }

    /** {@inheritDoc} */
    public function getRequiredKeyFields() {
        return [];
    }

    public function isActive(): bool {
        return isset($this->getKeys()[$this->status_field]) && (bool) $this->getKeys()[$this->status_field];
    }

    public function getSecret(): string {
        $this->secret = $this->getKeys()[$this->secret_field] ?? $this->authHelper->generateSecret();

        return $this->secret;
    }

    /** {@inheritdoc} */
    public function getFormNotes($section) {
        if($section !== "custom")
            return parent::getFormNotes($section);

        $hostname = preg_replace(
            "/http[s]?:\/\/|\/s\/dashboard/i",
            "",
            $this->router->generate("mautic_dashboard_index", [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $options = new QROptions();

        $options->version         = 7;
        $options->outputBase64    = false;
        $options->svgAddXmlHeader = false;

        return [
            "custom"     => true,
            "template"   => "@MauticTOTP/Integration/qrcode.html.twig",
            "parameters" => [
                "otp_code" => $this->secret,

                "otp_qr" => (new QRCode($options))->render(sprintf(
                    "otpauth://totp/%s:%s?secret=%s&issuer=%s",
                    $hostname,
                    $this->user->getUsername(),
                    $this->secret,
                    $hostname
                ))
            ]
        ];
    }

    /** {@inheritdoc} */
    public function appendToForm(&$builder, $data, $formArea): void {
        if($formArea !== "keys")
            return;

        $builder->add($this->status_field, YesNoButtonGroupType::class, [
            "label" => "mautic.integration.totp.settings.scanned",
            "data"  => $this->isActive()
        ])->add($this->secret_field, HiddenType::class, [
            "data" => $this->getSecret()
        ]);
    }

}
