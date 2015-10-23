<?php

namespace Phlib\Console\Helper;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;

/**
 * Class ConfigurationHelper
 * @package Phlib\Console\Helper
 */
class ConfigurationHelper extends AbstractHelper implements InputAwareInterface
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var mixed
     */
    protected $config = null;

    /**
     * @param Application $application
     * @param array $options
     */
    public static function initHelper(Application $application, array $options = [])
    {
        $defaults = [
            'name'         => 'config',
            'abbreviation' => 'c',
            'description'  => 'Path to the configuration file.',
            'filename'     => 'config.php'
        ];
        $options = $options + $defaults;

        $application
            ->getDefinition()
            ->addOption(new InputOption(
                $options['name'],
                $options['abbreviation'],
                InputOption::VALUE_REQUIRED,
                $options['description']
            ));
        $application->getHelperSet()
            ->set(new ConfigurationHelper($options['name'], $options['filename']));
    }

    /**
     * @param string $name
     * @param string $filename
     */
    public function __construct($name = 'config', $filename = 'config.php')
    {
        $this->name = $name;
        $this->filename = $filename;
    }

    /**
     * Sets the Console Input.
     *
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'configuration';
    }

    /**
     * @return mixed|false
     */
    public function fetch()
    {
        if (is_null($this->config)) {
            $this->config = $this->loadConfiguration();
        }
        return $this->config;
    }

    /**
     * @return mixed|false
     */
    protected function loadConfiguration()
    {
        $path = $this->input->getOption($this->name);
        if ($path !== null) {
            return $this->loadFromSpecificFile($path);
        }

        return $this->loadFromDetectedFile();
    }

    /**
     * @return mixed|false
     */
    protected function loadFromDetectedFile()
    {
        $filePath = $this->detectFile();
        if ($filePath === false || !is_file($filePath) || !is_readable($filePath)) {
            return false;
        }
        return include_once $filePath;
    }

    /**
     * @param string $filePath
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function loadFromSpecificFile($filePath)
    {
        if (is_dir($filePath)) {
            $filePath = $filePath . DIRECTORY_SEPARATOR . $this->filename;
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("Specified configuration '$filePath' is not accessible.");
        }

        return include_once $filePath;
    }

    /**
     * @return string|false
     */
    protected function detectFile()
    {
        $directories = [getcwd(), getcwd() . DIRECTORY_SEPARATOR . 'config'];
        $configFile = null;
        foreach ($directories as $directory) {
            $configFile = $directory . DIRECTORY_SEPARATOR . $this->filename;
            if (file_exists($configFile)) {
                return $configFile;
            }
        }
        return false;
    }
}
