<?php

namespace Proxmox\Command;

use Proxmox\Controller\CommandController;

class CommandCopy extends AbstractCommand
{
	public function getName(): string
	{
		return 'copy';
	}

	public function getArguments(): array
	{
		return ['vm' => null, 'source' => null, 'target' => null];
	}

	public function getRequiredArguments(): array
	{
		return ['vm', 'source', 'target'];
	}

	public function run(CommandController $commandController): void
	{
		$this->getProxmoxController()->fileCopy($commandController->getSetting('vm'), $commandController->getArgument('source'), $commandController->getArgument('target'));
	}
}
