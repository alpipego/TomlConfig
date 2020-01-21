<?php
declare(strict_types = 1);

namespace Alpipego\TomlConfig;

use Noodlehaus\AbstractConfig;
use Yosymfony\Toml\Toml;

class TomlConfig extends AbstractConfig
{
	protected $default;
	protected $env;

	public function __construct(array $paths)
	{
		$this->default = $this->interpolate(Toml::parseFile(array_shift($paths)));
		parent::__construct(array_reduce($paths, function (array $data, string $path) {
			return array_replace_recursive($data, $this->interpolate(Toml::parseFile($path)));
		}, $this->default));
		$this->setenv('', $this->all());
	}

	public function writeEnv(string $path)
	{
		if ( ! is_dir($path)) {
			$path = dirname($path);
		}
		$path = rtrim($path, '/') . '/';
		$env  = array_filter($this->env);
		file_put_contents($path . '.env', implode("\n", array_map(function ($key, $value) {
			if (is_string($value)) {
				return sprintf('%s="%s"', $key, $value);
			}

			return sprintf('%s=%s', $key, $value);
		}, array_keys($env), $env)));
	}

	protected function getDefaults()
	{
		return $this->default;
	}

	protected function setenv(string $itemKey, array $value)
	{
		$envKey = '';
		if ( ! empty($itemKey)) {
			$envKey = '_' . $itemKey;
		}
		array_walk($value, function ($value, $key) use ($envKey) {
			$envKey .= '_' . $key;
			if ( ! is_scalar($value)) {
				return $this->setenv($envKey, $value ?? []);
			}

			$key = strtoupper(ltrim($envKey, '_'));
			if ( ! getenv($key)) {
				putenv($key . '=' . $value);
			}
			if ( ! isset($_ENV[$key])) {
				$_ENV[$key] = $value;
			}
			$this->env[$key] = $value;
		});
	}

	protected function interpolate(array $data) : array
	{
		$levels    = [];
		$compound  = [];
		$recursion = function (string $key, $values) use (&$recursion, &$levels, &$data, &$compound) {
			if ( ! is_array($values)) {
				array_pop($levels);
				$arr =& $data;
				foreach ($levels as $level) {
					if ( ! array_key_exists($level, $arr)) {
						$levels = [array_pop($levels)];
						continue;
					}
					$arr =& $data[$level];
				}

				$compoundKey = array_reduce($levels, function ($carry, $level) {
						return ($carry ?? '') . $level . '.';
					}) . $key;
				$compound[]  = $values;

				if (preg_match('/\${([A-Za-z0-9.]+)}/', (string)$values, $vars)) {
					$value = $vars[0];
					if (array_key_exists($vars[1], $arr)) {
						$value = $arr[$vars[1]];
					} elseif (array_key_exists($vars[1], $data)) {
						$value = $data[$vars[1]];
					} elseif (array_key_exists($vars[1], $compound)) {
						$value = $compound[$vars[1]];
					}
					$arr[$key] = $compound[$compoundKey] = str_replace($vars[0], $value, $values);
				}

				if ( ! array_key_exists($key, (array)$arr)) {
					$levels = [];
				}

				return;
			}

			foreach ($values as $key => $array) {
				$levels[]     = $key;
				$output[$key] = [];
				$recursion((string)$key, $array);
			}
		};

		$recursion(array_keys($data)[0], $data);

		return $data;
	}
}
