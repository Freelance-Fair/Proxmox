<?php

namespace Proxmox\Command;

use Proxmox\Controller\CommandController;

class CommandUpstreamGenerate extends AbstractCommand
{
	public function getName(): string
	{
		return 'generate-upstream';
	}

	public function getArguments(): array
	{
		return ['upstream' => '/tmp/upstream.conf', 'mapping' => '/tmp/mapping.conf'];
	}

	public function getRequiredArguments(): array
	{
		return ['upstream', 'mapping'];
	}

	public function run(CommandController $commandController): void
	{
		$upstream = $commandController->getArgument('upstream');
		$mapping  = $commandController->getArgument('mapping');

		$vms = $this->getProxmoxController()->findVms($commandController->getSetting('build.namePrefix'));

		$prefixLength = \strlen($commandController->getSetting('build.namePrefix'));
		$hostTemplate = $commandController->getSetting('build.hostTemplate');

		$hosts     = [];
		$upstreams = [];
		foreach ($vms as $name => $settings) {
			$ip = $settings['ip'];

			if ($ip === null) {
				continue;
			}

			$nameSuffix   = \substr($name, $prefixLength);
			$upstreamName = \str_replace('-', '_', $name);

			$upstreams[] = 'upstream ' . $upstreamName . ' { server ' . $ip . '; }';
			$hosts[]     = \str_replace('{name}', $nameSuffix, $hostTemplate) . ' ' . $upstreamName . ';';
		}

		\file_put_contents($upstream, \implode("\n", $upstreams));
		\file_put_contents($mapping, \implode("\n", $hosts));
	}
}
