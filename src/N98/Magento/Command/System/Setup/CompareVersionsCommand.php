<?php

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompareVersionsCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:setup:compare-versions')
            ->setAliases(array('system:setup:compare-versions'))
            ->setDescription('Compare module version with core_resource table.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $modules = \Mage::getConfig()->getNode('modules');
            $resourceModel = \Mage::getResourceSingleton('core/resource');
            $setups = \Mage::getConfig()->getNode('global/resources')->children();
            $table = new \Zend_Text_Table(array('columnWidths' => array(40, 10, 10, 10, 6)));
            $errorCounter = 0;
            foreach ($setups as $setupName => $setup) {
                $moduleName = (string) $setup->setup->module;
                $moduleVersion = (string) $modules->{$moduleName}->version;
                $dbVersion = (string) $resourceModel->getDbVersion($setupName);
                $dataVersion = (string) $resourceModel->getDataVersion($setupName);
                $ok = $dbVersion == $moduleVersion && $dataVersion == $moduleVersion;
                if (!$ok) {
                    $errorCounter++;
                }
                $table->appendRow(
                    array(
                        'Setup'        => $setupName,
                        'Version'      => $moduleVersion,
                        'DB-Version'   => $dbVersion,
                        'Data-Version' => $dataVersion,
                        'Status'       => $ok ? 'OK' : 'Error'
                    )
                );
            }

            if (count($table) > 0) {
                $output->writeln('<error>' . $errorCounter . ' error' . ($errorCounter > 1 ? 's' : '') . ' was found!</error>');
            } else {
                $output->writeln('<info>No setup problems was found.</info>');
            }

            $output->write($table->render());
        }
    }
}