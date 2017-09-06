<?php

namespace Okvpn\Bundle\FixtureBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Okvpn\Bundle\FixtureBundle\Entity\DataFixture;

class ConfigurationResolver
{
    private $table;

    /**
     * @param string $table
     */
    public function __construct($table = null)
    {
        $this->table = $table;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();
        if (null !== $this->table && $metadata->getName() === DataFixture::class) {
            $table = $metadata->table;
            $table['name'] = $this->table;
            $metadata->setPrimaryTable($table);
        }
    }
}
