<?php

namespace Proxmox\Controller;

use Proxmox\Command\CommandInterface;

class CommandController
{
	private $arguments = [];
	private $settings  = [];
	private $commands  = [];

	public function __construct()
	{
		$this->obtainArguments();
	}

	public function setDefaultArguments($arguments)
	{
		foreach ($arguments as $argument => $value) {
			if ($value !== null && !$this->hasArgument($argument)) {
				$this->arguments[$argument] = $value;
			}
		}
	}

	/**
	 * @return array<string,string>
	 */
	public function getArguments(): array
	{
		return $this->arguments;
	}

	public function hasArgument(string $name): bool
	{
		return array_key_exists($name, $this->arguments);
	}

	public function hasArguments(array $names): bool
	{
		foreach ($names as $name) {
			if (!$this->hasArgument($name)) {
				return false;
			}
		}

		return true;
	}

	public function getArgument(string $name, ?string $default = null): ?string
	{
		return $this->arguments[$name] ?? $default;
	}

	public function getSettings(): array
	{
		return $this->settings;
	}

	public function setSettings(array $settings): void
	{
		$this->settings = $settings;
	}

	/**
	 * @return mixed
	 */
	public function getSetting(string $name, $default = null)
	{
		$parts = explode('.', $name);

		$value = $this->settings;
		foreach ($parts as $part) {
			$value = $value[$part] ?? null;
		}

		return $value ?? $default;
	}

	public function setSetting(string $name, $value): void
	{
		$this->settings[$name] = $value;
	}

	public function addSettings(array $settings): void
	{
		foreach ($settings as $name => $value) {
			$this->setSetting($name, $value);
		}
	}

	public function addCommand(CommandInterface $command): void
	{
		$this->commands[$command->getName()] = $command;
	}

	public function runCommand(?string $command): void
	{
		if (!array_key_exists($command, $this->commands)) {
			echo 'available commands: ' . implode(',', array_keys($this->commands)) . "\n";

			return;
		}

		/** @var CommandInterface $commandObject */
		$commandObject = $this->commands[$command];

		$this->setDefaultArguments($commandObject->getArguments());

		if (!$this->hasArguments($commandObject->getRequiredArguments())) {
			echo 'missing arguments for ' . $command . '. Required: ' . implode(',', $commandObject->getRequiredArguments()) . "\n";

			return;
		}

		try {
			$commandObject->run($this);
		} catch (\Exception $e) {
			$this->echo($e->getMessage());
			exit(1);
		}
	}

	public function echo($param): void
	{
		// TODO: silent mode or w/e
		echo $param . "\n";
	}

	private function obtainArguments(): void
	{
		$args = $_SERVER['argv'];
		\array_shift($args);

		$this->arguments = [];
		foreach ($args as $arg) {
			[$name, $value] = \explode('=', $arg);

			if (\strpos($name, '--') === 0) {
				$this->arguments[\substr($name, 2)] = \trim($value, '"');
			}
		}
	}
}
