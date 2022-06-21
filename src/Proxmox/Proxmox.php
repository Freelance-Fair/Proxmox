<?php

namespace Proxmox;

class Proxmox
{
	private $endpoint;
	private $token = '';

	public function __construct($endpoint)
	{
		$this->setEndpoint($endpoint);
	}

	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	public function setEndpoint(string $endpoint): void
	{
		$this->endpoint = $endpoint;
	}

	public function getToken(): string
	{
		return $this->token;
	}

	public function setToken(string $token): void
	{
		$this->token = $token;
	}

	public function getFreeId()
	{
		return $this->call('GET', '/cluster/nextid');
	}

	public function listNodes()
	{
		return $this->call('GET', '/nodes');
	}

	public function listVms($node)
	{
		return $this->call('GET', '/nodes/' . $node . '/qemu');
	}

	public function clone($node, $vmId, $newId, $config = [])
	{
		$config = \array_merge([
			'newid' => $newId,
			'name'  => '',
		], $config);

		return $this->call('POST', '/nodes/' . $node . '/qemu/' . $vmId . '/clone', $config);
	}

	public function destroy($node, $vmId)
	{
		return $this->call('DELETE', '/nodes/' . $node . '/qemu/' . $vmId);
	}

	public function getStatus($node, $vmId)
	{
		return $this->call('GET', '/nodes/' . $node . '/qemu/' . $vmId . '/status/current');
	}

	/**
	 * Start the VM
	 *
	 * @return mixed
	 */
	public function start($node, $vmId)
	{
		return $this->call('POST', '/nodes/' . $node . '/qemu/' . $vmId . '/status/start');
	}

	/**
	 * Send ACPI shutdown signal to the VM
	 *
	 * @return mixed
	 */
	public function shutdown($node, $vmId)
	{
		return $this->call('POST', '/nodes/' . $node . '/qemu/' . $vmId . '/status/shutdown');
	}

	/**
	 * Hard stop the VM; use shutdown for clean shutdown
	 *
	 * @return mixed
	 */
	public function stop($node, $vmId)
	{
		return $this->call('POST', '/nodes/' . $node . '/qemu/' . $vmId . '/status/stop');
	}

	public function getNetworkInfo($node, $vmId)
	{
		return $this->call('GET', '/nodes/' . $node . '/qemu/' . $vmId . '/agent/network-get-interfaces');
	}

	public function getAgentInfo($node, $vmId)
	{
		return $this->call('GET', '/nodes/' . $node . '/qemu/' . $vmId . '/agent/info');
	}

	public function agentExec($node, $vmId, $command)
	{
		$returnValue = $this->call('POST', '/nodes/' . $node . '/qemu/' . $vmId . '/agent/exec', ['command' => $command]);

		return $returnValue->pid ?? null;
	}

	public function agentExecStatus($node, $vmId, $pid)
	{
		return $this->call('GET', '/nodes/' . $node . '/qemu/' . $vmId . '/agent/exec-status', ['pid' => $pid]);
	}

	public function agentCopy($node, $vmId, $source, $target)
	{
		return $this->call('POST', '/nodes/' . $node . '/qemu/' . $vmId . '/agent/file-write', ['file' => $target, 'content' => \file_get_contents($source)]);
	}

	public function getConfig($node, $vmId)
	{
		return $this->call('GET', '/nodes/' . $node . '/qemu/' . $vmId . '/config');
	}

	public function call($method, $path, $arguments = [])
	{
		$url = $this->endpoint . $path;

		$opts = [
			'http' => [
				'method' => $method,
				'header' => '',
			],
			'ssl'  => [
				'verify_peer'      => false,
				'verify_peer_name' => false,
			],
		];

		if ($this->getToken()) {
			$opts['http']['header'] .= 'Authorization: PVEAPIToken=' . $this->getToken() . "\r\n";
		}

		switch ($method) {
			case 'GET':
				$vars = [];
				foreach ($arguments as $name => $value) {
					$vars[] = $name . '=' . $value;
				}

				if (\count($vars) > 0) {
					$url .= '?' . \implode('&', $vars);
				}
				break;
			case 'POST':
				$postData = \http_build_query($arguments);

				$opts['http']['header'] .= 'Content-type: application/x-www-form-urlencoded' . "\r\n";
				$opts['http']['content'] = $postData;
				break;
		}

		$rawContent = @\file_get_contents($url, false, \stream_context_create($opts));
		$content    = \json_decode($rawContent);

		return $content->data ?? null;
	}
}
