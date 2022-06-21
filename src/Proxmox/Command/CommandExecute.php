<?php

namespace Proxmox\Command;

use Proxmox\Controller\CommandController;

class CommandExecute extends AbstractCommand
{
	public function getName(): string
	{
		return 'execute';
	}

	public function getArguments(): array
	{
		return ['vm' => null, 'command' => null, 'nowait' => null];
	}

	public function getRequiredArguments(): array
	{
		return ['vm', 'command'];
	}

	public function run(CommandController $commandController): void
	{
		$wait = true;
		if ($commandController->hasArgument('nowait')) {
			$wait = false;
		}

		$command = $commandController->getArgument('command');
		if ($command === '') {
			return;
		}

		try {
			$returnValue = $this->getProxmoxController()->execute($commandController->getSetting('vm'), $command, $wait);

			if ($returnValue !== null) {
				$commandController->echo($returnValue);
			}
		} catch (\Exception $e) {
			throw new \Exception('Error on command: ' . $command . ' code: ' . $e->getCode() . ' message: ' . $e->getMessage());
		}
	}
}
