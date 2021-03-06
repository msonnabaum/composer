<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Command;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Justin Rainbow <justin.rainbow@gmail.com>
 */
class DependsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('depends')
            ->setDescription('Where is a package used?')
            ->setDefinition(array(
                new InputArgument('package', InputArgument::REQUIRED, 'the package to inspect')
            ))
            ->setHelp(<<<EOT
The depends command displays detailed information about where a
package is referenced.
<info>php composer.phar depends composer/composer</info>

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $references = $this->getReferences($input, $output, $composer);

        $this->printReferences($input, $output, $references);
    }

    /**
     * finds a list of packages which depend on another package
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Composer $composer
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getReferences(InputInterface $input, OutputInterface $output, Composer $composer)
    {
        $needle = $input->getArgument('package');

        $references = array();

        // check if we have a local installation so we can grab the right package/version
        $repos = array_merge(
            array($composer->getRepositoryManager()->getLocalRepository()),
            $composer->getRepositoryManager()->getRepositories()
        );
        foreach ($repos as $repository) {
            foreach ($repository->getPackages() as $package) {
                foreach (array('requires', 'recommends', 'suggests') as $type) {
                    foreach ($package->{'get'.$type}() as $link) {
                        if ($link->getTarget() === $needle) {
                            $references[] = array($type, $package, $link);
                        }
                    }
                }
            }
        }

        return $references;
    }

    private function printReferences(InputInterface $input, OutputInterface $output, array $references)
    {
        foreach ($references as $ref) {
            $output->writeln($ref[1]->getPrettyName() . ' ' . $ref[1]->getPrettyVersion() . ' <info>' . $ref[0] . '</info> ' . $ref[2]->getPrettyConstraint());
        }
    }
}