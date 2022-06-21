<?php

namespace Proxmox\Command;

use Proxmox\Controller\CommandController;

class CommandListVMs extends AbstractCommand
{
	public function getName(): string
	{
		return 'listvms';
	}

	public function getArguments(): array
	{
		return [];
	}

	public function getRequiredArguments(): array
	{
		return [];
	}

	public function run(CommandController $commandController): void
	{
		$allVms = $this->getProxmoxController()->findVms($commandController->getSetting('build.namePrefix'));

		foreach ($allVms as $vm) {
			echo $vm['id'] . ': ' . $vm['name'] . "\t" . $vm['ip'] . "\n";
		}
	}
}
