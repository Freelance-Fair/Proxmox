<?php

namespace Proxmox\Command;

use Proxmox\Controller\CommandController;

class CommandDestroy extends AbstractCommand
{
	public function getName(): string
	{
		return 'destroy';
	}

	public function getArguments(): array
	{
		return ['vm' => null];
	}

	public function getRequiredArguments(): array
	{
		return ['vm'];
	}

	public function run(CommandController $commandController): void
	{
		$vm = $commandController->getSetting('vm');

		try {
			$this->getProxmoxController()->destroy($vm);
		} catch (\Exception $e) {
			throw new \Exception('Error on destroy: ' . $vm . ' code: ' . $e->getCode() . ' message: ' . $e->getMessage());
		}
	}
}
