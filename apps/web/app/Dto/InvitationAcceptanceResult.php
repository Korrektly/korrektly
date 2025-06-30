<?php

declare(strict_types=1);

namespace App\Dto;

readonly class InvitationAcceptanceResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public string $type = 'info'
    ) {}

    /**
     * Check if the operation was successful
     */
    public function wasSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the operation failed
     */
    public function failed(): bool
    {
        return ! $this->success;
    }

    /**
     * Get the message type for flash messages
     */
    public function getFlashType(): string
    {
        return $this->type;
    }

    /**
     * Get the message content
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
