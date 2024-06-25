<?php

declare(strict_types=1);

namespace Paysera\Payment\Validator;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Contract\RepositoryInterface;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;
use Paysera\DataValidator\Validator\Rules\EntityExists;
use Paysera\DataValidator\Validator\Rules\Required;
use Paysera\Payment\Repository\MessageRepository;
use Paysera\Payment\Repository\OrderStatusRepository;

class PluginSettingsValidator extends AbstractValidator
{
    protected AbstractValidator $validator;

    protected MessageRepository $messageRepository;

    protected array $formTabs = [
        'account_tab' => [
            'payment_paysera_project',
            'payment_paysera_sign',
        ],
        'order_status_tab' => [
            'payment_paysera_new_order_status_id',
            'payment_paysera_paid_status_id',
            'payment_paysera_pending_status_id',
        ],
    ];

    /**
     * @throws IncorrectValidationRuleStructure
     */
    public function __construct(RepositoryInterface $orderStatusRepository, MessageRepository $messageRepository)
    {
        parent::__construct();

        $this->messageRepository = $messageRepository;

        $this->addRule(new Required());
        $this->addRule(new EntityExists($orderStatusRepository));

        $this->setAttributeMessage('payment_paysera_project', $messageRepository->get('error_project'));
        $this->setAttributeMessage('payment_paysera_sign', $messageRepository->get('error_sign'));
        $this->setRuleMessage('entity-exists', $messageRepository->get('error_order_status_must_exist'));
    }

    public function getProcessedErrors(): array
    {
        if (!$this->hasErrors()) {
            return [];
        }

        $processedErrors = [
            'warning' => $this->messageRepository->get('form_has_errors'),
        ];
        foreach (parent::getProcessedErrors() as $field => $fieldErrors) {
            $processedErrors[$this->determineFormTab($field)] = true;

            $processedErrors[$field] = join('<br>', $fieldErrors);
        }

        return $processedErrors;
    }

    protected function determineFormTab($field): string
    {
        foreach ($this->formTabs as $tab => $fields) {
            if (in_array($field, $fields)) {
                return $tab;
            }
        }

        return '';
    }
}
