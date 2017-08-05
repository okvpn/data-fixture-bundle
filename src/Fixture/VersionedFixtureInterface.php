<?php

namespace Okvpn\Bundle\FixtureBundle\Fixture;

interface VersionedFixtureInterface
{
    /**
     * Return current fixture version
     *
     * @return string
     */
    public function getVersion();
}
