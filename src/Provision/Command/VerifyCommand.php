<?php

namespace Aegir\Provision\Command;

use Aegir\Provision\Command;
use Aegir\Provision\Context;
use Aegir\Provision\Context\PlatformContext;
use Aegir\Provision\Context\ServerContext;
use Aegir\Provision\Context\SiteContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VerifyCommand
 *
 * Replacement for drush provision-verify command
 *
 * @package Aegir\Provision\Command
 * @see provision.drush.inc
 * @see drush_provision_verify()
 */
class VerifyCommand extends Command
{

    /**
     * This command needs a context.
     */
    const CONTEXT_REQUIRED = TRUE;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('verify')
          ->setDescription('Verify a Provision Context.')
          ->setHelp(
            'Verify the chosen context: write configuration files, run restart commands, etc. '
          )
          ->setDefinition($this->getCommandDefinition());
    }

    /**
     * Generate the list of options derived from ProvisionContextType classes.
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    protected function getCommandDefinition()
    {
        $inputDefinition = [];
        return new InputDefinition($inputDefinition);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        if (empty($this->context)) {
            throw new \Exception("You must specify a context to verify.");
        }
        
        $this->io->title(strtr("Verify %type: %name", [
            '%name' => $this->context_name,
            '%type' => $this->context->type,
        ]));

        /**
         * The provision-verify command function looks like:
         *
         *
        function drush_provision_verify() {
            provision_backend_invoke(d()->name, 'provision-save');
            d()->command_invoke('verify');
        }
         */

        $this->context->runSteps('verify');

    }
}
