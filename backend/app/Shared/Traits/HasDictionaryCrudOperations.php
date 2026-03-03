<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use RuntimeException;

/**
 * Trait for services that follow the dictionary CRUD pattern:
 * findById, findByIdOrFail, update(id, data), deleteById.
 */
trait HasDictionaryCrudOperations
{
    /**
     * Find entity by ID. Must be implemented by the using class.
     */
    abstract public function findById(string $id): ?object;

    /**
     * Delete the given entity. Must be implemented by the using class.
     */
    abstract protected function deleteEntity(object $entity): void;

    /**
     * Message for "entity not found" exception. Override in the using class for a specific entity name.
     */
    protected function entityNotFoundMessage(): string
    {
        return "Entity not found";
    }

    /**
     * Find entity by ID or throw.
     *
     * @return object Entity instance (type depends on the service)
     */
    protected function findByIdOrFail(string $id): object
    {
        $entity = $this->findById($id);

        if ($entity === null) {
            throw new RuntimeException($this->entityNotFoundMessage());
        }

        return $entity;
    }

    /**
     * Delete entity by ID. Uses findByIdOrFail and deleteEntity.
     */
    public function deleteById(string $id): void
    {
        $entity = $this->findByIdOrFail($id);
        $this->deleteEntity($entity);
    }
}
