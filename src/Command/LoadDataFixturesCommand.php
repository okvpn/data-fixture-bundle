<?php

namespace Okvpn\Bundle\FixtureBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Okvpn\Bundle\FixtureBundle\Migration\DataFixturesExecutor;
use Okvpn\Bundle\FixtureBundle\Migration\DataFixturesExecutorInterface;
use Okvpn\Bundle\FixtureBundle\Migration\Loader\DataFixturesLoader;
use Okvpn\Bundle\FixtureBundle\Tools\FixtureDatabaseChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class LoadDataFixturesCommand extends Command
{
    const COMMAND_NAME = 'okvpn:fixtures:data:load';
    const MAIN_FIXTURES_TYPE = DataFixturesExecutorInterface::MAIN_FIXTURES;
    const DEMO_FIXTURES_TYPE = DataFixturesExecutorInterface::DEMO_FIXTURES;

    /** @var Connection */
    protected $connection;

    /** @var ManagerRegistry */
    private $registry;

    /** @var DataFixturesLoader */
    private $dataFixturesLoader;

    /** @var DataFixturesExecutor */
    private $dataFixturesExecutor;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /**
     * @param ManagerRegistry $registry
     * @param DataFixturesLoader $dataFixturesLoader
     * @param DataFixturesExecutor $dataFixturesExecutor
     */
    public function __construct(
        ManagerRegistry $registry,
        DataFixturesLoader $dataFixturesLoader,
        DataFixturesExecutor $dataFixturesExecutor,
        ParameterBagInterface $parameterBag
    ) {
        parent::__construct(self::COMMAND_NAME);

        $this->registry = $registry;
        $this->dataFixturesLoader = $dataFixturesLoader;
        $this->dataFixturesExecutor = $dataFixturesExecutor;
        $this->parameterBag = $parameterBag;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::COMMAND_NAME)
            ->setAliases(['okvpn:fixture:data:load'])
            ->setDescription('Load data fixtures.')
            ->addOption(
                'fixtures-type',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Select fixtures type to be loaded (%s or %s). By default - %s',
                    self::MAIN_FIXTURES_TYPE,
                    self::DEMO_FIXTURES_TYPE,
                    self::MAIN_FIXTURES_TYPE
                ),
                self::MAIN_FIXTURES_TYPE
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs list of fixtures without apply them')
            ->addOption(
                'bundles',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'A list of bundle names to load data from'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'A list of bundle names which fixtures should be skipped'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->connection = $this->registry->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fixtures = null;
        $this->ensureTableExist();
        try {
            $fixtures = $this->getFixtures($input, $output);
        } catch (\RuntimeException $ex) {
            $output->writeln('');
            $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));

            return $ex->getCode() == 0 ? 1 : $ex->getCode();
        }

        if (!empty($fixtures)) {
            if ($input->getOption('dry-run')) {
                $this->outputFixtures($input, $output, $fixtures);
            } else {
                $this->processFixtures($input, $output, $fixtures);
            }
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return array
     * @throws \RuntimeException if loading of data fixtures should be terminated
     */
    protected function getFixtures(InputInterface $input, OutputInterface $output)
    {
        $expectedBundles = $input->getOption('bundles');
        $excludeBundles = $input->getOption('exclude');
        $fixtureRelativePath = $this->getFixtureRelativePath($input);

        $currentBundles = array_map(function (BundleInterface $bundle) {
            return ['name' => $bundle->getName(), 'path' => $bundle->getPath()];
        }, $this->getApplication()->getKernel()->getBundles());

        // Add root_dir to fixtures paths
        $currentBundles[] = ['name' => 'App', 'path' => $this->parameterBag->get('kernel.root_dir')];

        /** @var BundleInterface $bundle */
        foreach ($currentBundles as $bundle) {
            if (!empty($expectedBundles) && !in_array($bundle['name'], $expectedBundles)) {
                continue;
            }
            if (!empty($excludeBundles) && in_array($bundle['name'], $excludeBundles)) {
                continue;
            }
            $path = $bundle['path'] . $fixtureRelativePath;
            if (is_dir($path)) {
                $this->dataFixturesLoader->loadFromDirectory($path);
            }
        }

        return $this->dataFixturesLoader->getFixtures();
    }

    /**
     * Output list of fixtures
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $fixtures
     */
    protected function outputFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'List of "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
            )
        );
        foreach ($fixtures as $fixture) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', get_class($fixture)));
        }
    }

    /**
     * Process fixtures
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $fixtures
     */
    protected function processFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'Loading "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
            )
        );

        $this->dataFixturesExecutor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $this->dataFixturesExecutor->execute($fixtures, $this->getTypeOfFixtures($input));
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getTypeOfFixtures(InputInterface $input)
    {
        return $input->getOption('fixtures-type');
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getFixtureRelativePath(InputInterface $input)
    {
        $fixtureRelativePath = $this->getTypeOfFixtures($input) === self::DEMO_FIXTURES_TYPE
            ? $this->parameterBag->get('okvpn_fixture.path_data_demo')
            : $this->parameterBag->get('okvpn_fixture.path_data_main');

        return str_replace('/', DIRECTORY_SEPARATOR, '/' . $fixtureRelativePath);
    }

    protected function ensureTableExist()
    {
        $table = $this->parameterBag->get('okvpn_fixture.table');
        if (!FixtureDatabaseChecker::tablesExist($this->connection, $table)) {
            FixtureDatabaseChecker::declareTable($this->connection, $table);
        }
    }
}
