<?php

namespace Proxmox\Command;

use Proxmox\Controller\CommandController;

interface CommandInterface
{
	public function getName(): string;

	/**
	 * @return array<string,?string>
	 */
	public function getArguments(): array;

	/**
	 * @return array<string>
	 */
	public function getRequiredArguments(): array;

	/**
	 * @throws \Exception
	 */
	public function run(CommandController $commandController): void;
}
