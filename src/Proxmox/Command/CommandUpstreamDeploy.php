<?php

namespace Proxmox\Command;

use Proxmox\Controller\CommandController;

class CommandUpstreamDeploy extends AbstractCommand
{
	public function getName(): string
	{
		return 'deploy-upstream';
	}

	public function getArguments(): array
	{
		return ['server' => 'web2', 'upstream' => '/tmp/upstream.conf', 'mapping' => '/tmp/mapping.conf'];
	}

	public function getRequiredArguments(): array
	{
		return ['server', 'upstream', 'mapping'];
	}

	public function run(CommandController $commandController): void
	{
		$server   = $commandController->getArgument('server');
		$upstream = $commandController->getArgument('upstream');
		$mapping  = $commandController->getArgument('mapping');

		$upstreamVariable = $commandController->getSetting('build.upstream');

		try {
			$this->getProxmoxController()->fileCopy($server, $upstream, '/etc/nginx/upstream/' . $upstreamVariable . '.conf');
			$this->getProxmoxController()->fileCopy($server, $mapping, '/etc/nginx/mapping/' . $upstreamVariable . '.conf');
			$this->getProxmoxController()->execute($server, '/etc/init.d/nginx reload');
		} catch (\Exception $e) {
			throw new \Exception('Error on deploying upstream: ' . $e->getMessage());
		}
	}
}
