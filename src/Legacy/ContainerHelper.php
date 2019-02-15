<?php

namespace App\Legacy;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Helperclass for easy static access to a PSR Service Container
 * @package App\Legacy
 */
class ContainerHelper implements ContainerInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var self $instance */
    private static $instance;

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @see ContainerInterface::get()
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if (!$this->container instanceof ContainerInterface) {
            throw new RuntimeException('Container has not been set yet or is invalid');
        }

        return $this->container->get($id);
    }

    /**
     * @see ContainerInterface::has()
     * @param string $id
     * @return bool
     */
    public function has($id): bool
    {
        if (!$this->container instanceof ContainerInterface) {
            throw new RuntimeException('Container has not been set yet or is invalud');
        }

        return $this->container->has($id);
    }
}