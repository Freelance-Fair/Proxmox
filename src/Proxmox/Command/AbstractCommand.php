<?php

namespace Proxmox\Command;

use Proxmox\Controller\ProxmoxController;

abstract class AbstractCommand implements CommandInterface
{
	private ProxmoxController $proxmoxController;

	public function __construct(ProxmoxController $proxmoxController)
	{
		$this->setProxmoxController($proxmoxController);
	}

	public function getProxmoxController(): ProxmoxController
	{
		return $this->proxmoxController;
	}

	public function setProxmoxController(ProxmoxController $proxmoxController): void
	{
		$this->proxmoxController = $proxmoxController;
	}
}
