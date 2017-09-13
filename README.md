# Data fixtures

Intro
-----
Symfony allows to load data using data fixtures. But these fixtures are run each time when `doctrine:fixtures:load` command is executed.

To avoid loading the same fixture several time, **okvpn:fixture:data:load** command was created. This command guarantees that each data fixture will be loaded only once.

This command supports two types of migration files: `main` data fixtures and `demo` data fixtures. During an installation, user can select to load or not demo data.

Fixtures order can be changed with standard Doctrine ordering or dependency functionality. More information about fixture ordering can be found in [doctrine data fixtures manual][1].

Installation
------------
Install using composer:

```
composer require okvpn/fixture-bundle
```

And this bundle to your AppKernel:
```php
<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Okvpn\Bundle\FixtureBundle\OkvpnFixtureBundle(),
            //...
        );
    }
    
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
```

Configure directories for put data fixtures and table name in `config.yml`
```yml
okvpn_fixture:
    table: okvpn_fixture_data
    path_main: Migrations/Data/ORM
    path_demo: Migrations/Data/Demo/ORM
```

Base Example  
------- 

Create file `src/Akuma/PassBundle/Migrations/Data/ORM/TestFixture.php`
```php

<?php 
// src/Akuma/PassBundle/Migrations/Data/ORM/MeteoFixture.php

namespace Akuma\PassBundle\Fixture\Data;

use Akuma\PassBundle\Entity\Item;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class MeteoFixture extends AbstractFixture 
{
    public function load(ObjectManager $manager)
    {
        $item = new Item();
        $item->setHumidity(58.00)
            ->setTemp(10.8)
            ->setPressure(1019.23)
            ->setTimestamp(new \DateTime());

        $manager->persist($item);
        $manager->flush();
    }
}

```

And run command `okvpn:fixture:data:load` to load it.

Versioned fixtures
------------------

There are fixtures which need to be executed time after time. An example is a fixture which uploads countries data. Usually, if you add new countries list, you need to create new data fixture which will upload this data. To avoid this you can use versioned data fixtures.

To make fixture versioned, this fixture must implement [VersionedFixtureInterface](./Fixture/VersionedFixtureInterface.php) and `getVersion` method which returns a version of fixture data.

Example:

``` php

<?php

namespace Acme\DemoBundle\Migrations\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Okvpn\Bundle\FixtureBundle\Fixture\VersionedFixtureInterface;

class LoadSomeDataFixture extends AbstractFixture implements VersionedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // Here we can use fixture data code which will be run time after time
    }
}
```

In this example, if the fixture was not loaded yet, it will be loaded and version 1.0 will be saved as current loaded version of this fixture.

To have possibility to load this fixture again, the fixture must return a version greater then 1.0, for example 1.0.1 or 1.1. A version number must be an PHP-standardized version number string. More info about PHP-standardized version number string can be found in [PHP manual][1].

If a fixture need to know the last loaded version, it must implement [LoadedFixtureVersionAwareInterface](./Fixture/LoadedFixtureVersionAwareInterface.php) and `setLoadedVersion` method:

``` php
<?php

namespace Acme\DemoBundle\Migrations\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Okvpn\Bundle\FixtureBundle\Fixture\VersionedFixtureInterface;
use Okvpn\Bundle\FixtureBundle\Fixture\RequestVersionFixtureInterface;

class LoadSomeDataFixture extends AbstractFixture implements VersionedFixtureInterface, LoadedFixtureVersionAwareInterface
{
    /**
     * @var $currendDBVersion string
     */
    protected $currendDBVersion = null;

    /**
     * {@inheritdoc}
     */
    public function setLoadedVersion($version = null)
    {
        $this->currendDBVersion = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.0';
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // Here we can check last loaded version and load data data difference between last
        // uploaded version and current version
    }
}
```
[1]: https://github.com/doctrine/data-fixtures#fixture-ordering

## Licence

MIT License
