<?php

namespace Proxmox\Controller;

use Proxmox\Proxmox;

class ProxmoxController
{
	private $ipPool = [];

	private $defaultNode;
	private $network;

	/**
	 * @var Proxmox
	 */
	private $proxmox;

	public function __construct($node, $network)
	{
		$this->defaultNode = $node;
		$this->network     = $network;
	}

	/**
	 * @return Proxmox
	 */
	public function getProxmox()
	{
		return $this->proxmox;
	}

	/**
	 * @param Proxmox $proxmox
	 */
	public function setProxmox($proxmox): void
	{
		$this->proxmox = $proxmox;
	}

	/**
	 * @return mixed
	 */
	public function getDefaultNode()
	{
		return $this->defaultNode;
	}

	/**
	 * @param mixed $defaultNode
	 */
	public function setDefaultNode($defaultNode): void
	{
		$this->defaultNode = $defaultNode;
	}

	public function getNetwork(): string
	{
		return $this->network;
	}

	public function setNetwork(string $network): void
	{
		$this->network = $network;
	}

	public function addToIpPool($ip): void
	{
		if (\is_array($ip)) {
			foreach ($ip as $subIp) {
				$this->addToIpPool($subIp);
			}
		} else {
			$this->ipPool[] = $ip;
		}
	}

	public function findVms($prefix = '', $allNodes = true)
	{
		$checkNodes = [];
		if ($allNodes) {
			$nodes = $this->getProxmox()->listNodes();
			foreach ($nodes as $node) {
				$checkNodes[] = $node->node;
			}
		} else {
			$checkNodes[] = $this->getDefaultNode();
		}

		$foundVms = [];
		foreach ($checkNodes as $checkNode) {
			$vms = $this->getProxmox()->listVms($checkNode);

			foreach ($vms as $vm) {
				if (\strpos($vm->name, $prefix) !== 0) {
					continue;
				}

				$foundVms[$vm->name] = ['node' => $checkNode, 'id' => $vm->vmid, 'name' => $vm->name, 'ip' => $this->getIp($vm->vmid)];
			}
		}

		return $foundVms;
	}

	/**
	 * @throws \Exception
	 */
	public function build($templateName, $name, $shuffle = 0)
	{
		// check if the given name is available
		$vms = $this->findVms($name);
		if (isset($vms[$name])) {
			// we already have this VM.
			// - do we need to check the status?
			// - when to rebuild?

			// return the ID
			return $vms[$name]['id'];
		}

		// find the template
		$templates = $this->findVms($templateName);
		if (!isset($templates[$templateName])) {
			throw new \Exception('Template ' . $templateName . ' not found');
		}

		$templateId = $templates[$templateName]['id'];

		// get an ip
		$availableIps = $this->findAvailableIps();
		if (\count($availableIps) === 0) {
			throw new \Exception('No more available IPs');
		}

		$assignIp = $availableIps[$shuffle] ?? $availableIps[0];

		$newId       = $this->getProxmox()->getFreeId();
		$returnValue = $this->getProxmox()->clone($this->getDefaultNode(), $templateId, $newId, ['name' => $name]);

		// first, wait for clone to be ready. This means not locked and stopped
		$this->waitForStatus($newId, 'stopped');

		// now we have to start the VM, do this by given a start command, then loop-wait until started (or timeout)
		$this->getProxmox()->start($this->getDefaultNode(), $newId);

		$this->waitForStatus($newId, 'running');

		// yes it should be started! But the agent needs to be up and running too. Give this a few seconds
		$done = false;
		while (!$done) {
			$returnValue = $this->getProxmox()->getAgentInfo($this->getDefaultNode(), $newId);

			if ($returnValue !== null) {
				$done = true;
			} else {
				\sleep(1);
			}
		}

		// now we can change the IP and hostname
		$this->getProxmox()->agentCopy($this->getDefaultNode(), $newId, __DIR__ . '/../../scripts/setup-network.sh', '/root/setup-network.sh');
		$this->getProxmox()->agentExec($this->getDefaultNode(), $newId, 'chmod +x /root/setup-network.sh');
		$this->getProxmox()->agentExec($this->getDefaultNode(), $newId, '/root/setup-network.sh ' . $assignIp . ' ' . $name);

		// now it should be done
		return $newId;
	}

	public function execute($name, $command, $waitForExit = true)
	{
		$vms = $this->findVms($name);

		if (!isset($vms[$name])) {
			throw new \Exception('VM not found');
		}

		$vmId = $vms[$name]['id'];

		$pid = $this->getProxmox()->agentExec($this->getDefaultNode(), $vmId, $command);

		$returnValue = null;
		if ($waitForExit) {
			$returnValue = '';

			$done = false;
			while (!$done) {
				$status = $this->getProxmox()->agentExecStatus($this->getDefaultNode(), $vmId, $pid);

				if (isset($status->{'out-data'})) {
					$returnValue = $status->{'out-data'};
				}

				if ($status === null) {
					$done = true;
				} elseif ($status->exited === 1) {
					if ($status->exitcode !== 0) {
						throw new \Exception($status->{'err-data'});
					}

					$done = true;
				} else {
					\usleep(250000);
				}
			}
		}

		return $returnValue;
	}

	public function fileCopy($name, $source, $target): void
	{
		$vms = $this->findVms($name);

		if (!isset($vms[$name])) {
			throw new \Exception('VM not found');
		}

		$this->_fileCopy($vms[$name]['id'], $source, $target, $vms[$name]['node']);
	}

	private function _fileCopy($vmId, $source, $target, $node = null): void
	{
		if (\is_dir($source)) {
			$files = \array_diff(\scandir($source), ['.', '..']);

			foreach ($files as $file) {
				$fullSource = $source . '/' . $file;
				$fullTarget = $target . '/' . $file;
				if (\is_dir($fullSource)) {
					$this->getProxmox()->agentExec($node ?? $this->getDefaultNode(), $vmId, 'mkdir -p ' . $fullTarget);
				}

				$this->_fileCopy($vmId, $fullSource, $fullTarget);
			}
		} else {
			$this->getProxmox()->agentCopy($node ?? $this->getDefaultNode(), $vmId, $source, $target);
		}
	}

	public function destroy($name): void
	{
		$vms = $this->findVms($name);

		if (!isset($vms[$name])) {
			throw new \Exception('VM not found');
		}

		$vmId = $vms[$name]['id'];

		$this->getProxmox()->stop($this->getDefaultNode(), $vmId);

		$this->waitForStatus($vmId, 'stopped');

		// it is stopped, now DESTROOOYYY!
		$this->getProxmox()->destroy($this->getDefaultNode(), $vmId);

		$done = false;
		while (!$done) {
			$vms = $this->findVms($name);

			// if we cannot find the VM anymore, it is good!
			if (!isset($vms[$name])) {
				$done = true;
			} else {
				\sleep(1);
			}
		}
	}

	public function waitForStatus($vmId, $checkState, $timeout = 60): void
	{
		$started = \time();

		$done = false;
		while (!$done) {
			$status = $this->getProxmox()->getStatus($this->getDefaultNode(), $vmId);

			if (isset($status->locked)) {
				\sleep(1);
			} elseif ($status->status === $checkState) {
				$done = true;
			} else {
				\sleep(1);
			}

			if (\time() - $started > $timeout) {
				throw new \Exception('Starting VM timeout');
			}
		}
	}

	public function findAvailableIps()
	{
		$ips = [];
		$vms = $this->getProxmox()->listVms($this->getDefaultNode());
		foreach ($vms as $vm) {
			$ips[] = $this->getIp($vm->vmid);
		}

		return \array_values(\array_diff($this->ipPool, $ips));
	}

	public function getIp($vmid, $node = null)
	{
		$network = $this->getProxmox()->getNetworkInfo($node ?? $this->getDefaultNode(), $vmid);

		if ($network === null) {
			return null;
		}

		foreach ($network->result as $result) {
			if ($result->name === 'lo') {
				continue;
			}

			foreach ($result->{'ip-addresses'} as $address) {
				if ($address->{'ip-address-type'} === 'ipv4') {
					return $address->{'ip-address'};
				}
			}
		}

		return null;
	}
}
