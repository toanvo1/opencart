<?php

declare(strict_types=1);

namespace Paysera\Payment\Repository;

class MessageRepository
{
    /**
     * @var \Registry
     */
    protected $registry;

    /**
     * @var Language system/library/language.php
     */
    protected $languageModel;

    /**
     * @param \Registry $registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;

        $registry->get('load')->language('extension/payment/paysera');
        $this->languageModel = $registry->get('language');
    }

    public function get(string $key): string
    {
        return $this->languageModel->get($key);
    }

    public function loadLanguagePack(string $languageKey): void
    {
        $this->registry->get('load')->language($languageKey);
    }
}
