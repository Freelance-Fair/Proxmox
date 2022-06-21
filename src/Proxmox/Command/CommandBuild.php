<?php

namespace Proxmox\Command;

use Proxmox\Controller\CommandController;

class CommandBuild extends AbstractCommand
{
	public function getName(): string
	{
		return 'build';
	}

	public function getArguments(): array
	{
		return ['vm' => null, 'shuffle' => null];
	}

	public function getRequiredArguments(): array
	{
		return ['vm'];
	}

	public function run(CommandController $commandController): void
	{
		try {
			$vmId = $this->getProxmoxController()->build(
				$commandController->getSetting('build.template'),
				$commandController->getSetting('vm'),
				(int) ($commandController->getArgument('shuffle') ?? 0)
			);

			$commandController->echo('VM ID: ' . $vmId);
		} catch (\Exception $e) {
			throw new \Exception('Error on build: ' . $e->getMessage());
		}
	}
}
