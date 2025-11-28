<?php

namespace App\Services\Footer;

use App\Services\Footer\Contracts\ContextProviderInterface;
use Illuminate\Support\Collection;

/**
 * Context Registry
 *
 * Central registry for managing context providers.
 * Allows registration, retrieval, and management of context providers
 * for the global footer system.
 */
class ContextRegistry
{
    /**
     * Registered context providers.
     *
     * @var Collection<string, ContextProviderInterface>
     */
    protected Collection $providers;

    /**
     * Create a new ContextRegistry instance
     *
     */
    public function __construct()
    {
        $this->providers = collect();
    }

    /**
     * Register a single context provider.
     *
     * @param ContextProviderInterface $provider
     * @return self
     */
    /**
     * Register
     *
     * @param ContextProviderInterface $provider
     * @return self
     */
    public function register(ContextProviderInterface $provider): self
    {
        $this->providers->put($provider->getContextType(), $provider);

        return $this;
    }

    /**
     * Register multiple context providers.
     *
     * @param array<ContextProviderInterface> $providers
     * @return self
     */
    /**
     * Register Many
     *
     * @param array $providers
     * @return self
     */
    public function registerMany(array $providers): self
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }

        return $this;
    }

    /**
     * Get a context provider by type.
     *
     * @param string $contextType
     * @return ContextProviderInterface|null
     */
    /**
     * Get
     *
     * @param string $contextType
     * @return ?ContextProviderInterface
     */
    public function get(string $contextType): ?ContextProviderInterface
    {
        return $this->providers->get($contextType);
    }

    /**
     * Check if a context provider exists.
     *
     * @param string $contextType
     * @return bool
     */
    /**
     * Has
     *
     * @param string $contextType
     * @return bool
     */
    public function has(string $contextType): bool
    {
        return $this->providers->has($contextType);
    }

    /**
     * Get all registered context providers.
     *
     * @return Collection<string, ContextProviderInterface>
     */
    /**
     * All
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->providers;
    }

    /**
     * Get all context types.
     *
     * @return array<string>
     */
    public function getContextTypes(): array
    {
        return $this->providers->keys()->toArray();
    }

    /**
     * Remove a context provider.
     *
     * @param string $contextType
     * @return self
     */
    /**
     * Remove
     *
     * @param string $contextType
     * @return self
     */
    public function remove(string $contextType): self
    {
        $this->providers->forget($contextType);

        return $this;
    }

    /**
     * Clear all registered providers.
     *
     * @return self
     */
    /**
     * Clear
     *
     * @return self
     */
    public function clear(): self
    {
        $this->providers = collect();

        return $this;
    }

    /**
     * Get context configuration for JavaScript.
     * Returns an array suitable for passing to Alpine.js component.
     *
     * @return array<string, array{
     *     name: string,
     *     emptyLabel: string,
     *     borderColor: string,
     *     iconPath: string
     * }>
     */
    public function getJavaScriptConfig(): array
    {
        return $this->providers->mapWithKeys(function (ContextProviderInterface $provider) {
            return [
                $provider->getContextType() => [
                    'name' => $provider->getContextName(),
                    'emptyLabel' => $provider->getEmptyLabel(),
                    'borderColor' => $provider->getBorderColor(),
                    'iconPath' => $provider->getIconPath(),
                ],
            ];
        })->toArray();
    }
}
