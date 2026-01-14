<?php

namespace Webkul\Project\Services\Locks;

use Exception;
use Webkul\Project\Models\EntityLock;

/**
 * Exception thrown when attempting to modify a locked entity.
 */
class LockViolationException extends Exception
{
    protected EntityLock $lock;
    protected string $entityType;
    protected ?int $entityId;
    protected ?string $fieldName;

    public function __construct(
        EntityLock $lock,
        string $entityType,
        ?int $entityId = null,
        ?string $fieldName = null
    ) {
        $this->lock = $lock;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->fieldName = $fieldName;

        $message = $this->buildMessage();
        parent::__construct($message);
    }

    protected function buildMessage(): string
    {
        $parts = ["Cannot modify {$this->entityType}"];

        if ($this->entityId) {
            $parts[0] .= " #{$this->entityId}";
        }

        if ($this->fieldName) {
            $parts[0] .= " field '{$this->fieldName}'";
        }

        $parts[] = "Locked by gate '{$this->lock->locked_by_gate}'";
        $parts[] = "Lock level: {$this->lock->lock_level}";
        $parts[] = "Locked at: {$this->lock->locked_at->format('M j, Y g:i A')}";
        $parts[] = "To modify, create a Change Order";

        return implode('. ', $parts);
    }

    public function getLock(): EntityLock
    {
        return $this->lock;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    /**
     * Get a user-friendly error message.
     */
    public function getUserMessage(): string
    {
        $entity = $this->entityId 
            ? "{$this->entityType} #{$this->entityId}"
            : $this->entityType;

        $field = $this->fieldName ? " ({$this->fieldName})" : '';

        return "This {$entity}{$field} is locked and cannot be modified directly. " .
            "Please create a Change Order to request changes.";
    }
}
